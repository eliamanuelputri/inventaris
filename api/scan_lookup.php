<?php
/**
 * API Barcode & SKU Lookup & Quick Stock Adjustment
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$pdo = db();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: Cari data barang berdasarkan SKU atau Barcode
if ($method === 'GET') {
    $code = sanitize($_GET['code'] ?? '');

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Kode barcode / SKU tidak boleh kosong.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, u.symbol as unit_symbol,
               l.warehouse_name, l.rack_code
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN units u ON p.unit_id = u.id
        LEFT JOIN locations l ON p.location_id = l.id
        WHERE p.sku = :code OR p.barcode = :code
        LIMIT 1
    ");
    $stmt->execute(['code' => $code]);
    $prod = $stmt->fetch();

    if (!$prod) {
        // Coba cari LIKE jika exact match belum ketemu
        $stmtLike = $pdo->prepare("
            SELECT p.*, c.name as category_name, u.symbol as unit_symbol,
                   l.warehouse_name, l.rack_code
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN units u ON p.unit_id = u.id
            LEFT JOIN locations l ON p.location_id = l.id
            WHERE p.name LIKE :q OR p.sku LIKE :q OR p.barcode LIKE :q
            LIMIT 1
        ");
        $stmtLike->execute(['q' => "%{$code}%"]);
        $prod = $stmtLike->fetch();
    }

    if (!$prod) {
        echo json_encode([
            'success' => false,
            'message' => "Barang dengan kode '{$code}' tidak ditemukan di sistem."
        ]);
        exit;
    }

    // Ambil forecast 30 hari
    $forecast = calculate_product_forecast((int)$prod['id'], (int)$prod['current_stock'], (int)$prod['min_stock']);

    // Cek batch terdekat
    $stmtBatches = $pdo->prepare("
        SELECT * FROM product_batches 
        WHERE product_id = :pid AND current_qty > 0 
        ORDER BY expiry_date ASC LIMIT 3
    ");
    $stmtBatches->execute(['pid' => $prod['id']]);
    $batches = $stmtBatches->fetchAll();

    $batchList = [];
    foreach ($batches as $b) {
        $st = get_expiry_status($b['expiry_date']);
        $batchList[] = [
            'batch_no' => $b['batch_no'],
            'expiry_date' => $b['expiry_date'],
            'expiry_formatted' => format_date($b['expiry_date']),
            'current_qty' => (int)$b['current_qty'],
            'status' => $st['status'],
            'label' => $st['label'],
            'badge_class' => $st['badge_class']
        ];
    }

    $photoUrl = (!empty($prod['image']) && file_exists(UPLOAD_PATH . '/products/' . $prod['image']))
        ? UPLOAD_URL . 'products/' . $prod['image']
        : null;

    echo json_encode([
        'success' => true,
        'product' => [
            'id' => (int)$prod['id'],
            'sku' => $prod['sku'],
            'barcode' => $prod['barcode'],
            'name' => $prod['name'],
            'category_name' => $prod['category_name'] ?? 'Umum',
            'unit_symbol' => $prod['unit_symbol'] ?? 'Unit',
            'rack_code' => $prod['rack_code'] ?? 'Belum Ditata',
            'warehouse_name' => $prod['warehouse_name'] ?? 'Gudang Utama',
            'purchase_price' => (float)$prod['purchase_price'],
            'purchase_price_formatted' => format_rupiah($prod['purchase_price']),
            'selling_price' => (float)$prod['selling_price'],
            'selling_price_formatted' => format_rupiah($prod['selling_price']),
            'current_stock' => (int)$prod['current_stock'],
            'min_stock' => (int)$prod['min_stock'],
            'is_low_stock' => ((int)$prod['current_stock'] <= (int)$prod['min_stock']),
            'photo_url' => $photoUrl,
            'track_expiry' => (bool)$prod['track_expiry']
        ],
        'forecast' => $forecast,
        'batches' => $batchList
    ]);
    exit;
}

// 2. POST: Aksi Cepat Tambah Stok / Kurang Stok Langsung dari Scanner
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $productId = (int)($data['product_id'] ?? 0);
    $actionType = sanitize($data['action_type'] ?? 'in'); // 'in' or 'out'
    $qty = (int)($data['qty'] ?? 1);
    $notes = sanitize($data['notes'] ?? 'Transaksi Cepat via Scanner Barcode');
    $userId = (int)$_SESSION['user']['id'];

    if ($productId <= 0 || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtP = $pdo->prepare("SELECT * FROM products WHERE id = :id FOR UPDATE");
        $stmtP->execute(['id' => $productId]);
        $prod = $stmtP->fetch();

        if (!$prod) throw new Exception("Barang tidak ditemukan.");

        if ($actionType === 'out') {
            if ($prod['current_stock'] < $qty) {
                throw new Exception("Stok tidak mencukupi! Sisa stok saat ini: {$prod['current_stock']} unit.");
            }
            $newStock = $prod['current_stock'] - $qty;
            $trxNo = generate_trx_no('out');

            // Simpan Transaksi Keluar
            $stmtTrx = $pdo->prepare("
                INSERT INTO transactions (transaction_no, type, reason, destination, reference_no, transaction_date, user_id, total_items, total_amount, notes)
                VALUES (:no, 'out', 'pemakaian_internal', 'Scan Barcode Cepat', 'SCAN-QUICK', CURDATE(), :uid, :items, :amt, :notes)
            ");
            $price = ($prod['selling_price'] > 0) ? $prod['selling_price'] : $prod['purchase_price'];
            $stmtTrx->execute([
                'no' => $trxNo,
                'uid' => $userId,
                'items' => $qty,
                'amt' => $qty * $price,
                'notes' => $notes
            ]);
            $trxId = $pdo->lastInsertId();

            $stmtIt = $pdo->prepare("
                INSERT INTO transaction_items (transaction_id, product_id, qty, unit_price, subtotal)
                VALUES (:tid, :pid, :qty, :pr, :sub)
            ");
            $stmtIt->execute(['tid' => $trxId, 'pid' => $productId, 'qty' => $qty, 'pr' => $price, 'sub' => $qty * $price]);

            $stmtUp = $pdo->prepare("UPDATE products SET current_stock = :ns WHERE id = :id");
            $stmtUp->execute(['ns' => $newStock, 'id' => $productId]);

            record_stock_mutation($pdo, $productId, null, $prod['location_id'], 'barang_keluar', $trxNo, 0, $qty, $newStock, $notes, $userId);

            $msg = "Berhasil mengeluarkan {$qty} unit '{$prod['name']}'. Sisa stok: {$newStock} unit.";

        } else { // 'in'
            $newStock = $prod['current_stock'] + $qty;
            $trxNo = generate_trx_no('in');

            $stmtTrx = $pdo->prepare("
                INSERT INTO transactions (transaction_no, type, reason, reference_no, transaction_date, user_id, total_items, total_amount, notes)
                VALUES (:no, 'in', 'pembelian', 'SCAN-RESTOCK', CURDATE(), :uid, :items, :amt, :notes)
            ");
            $price = $prod['purchase_price'];
            $stmtTrx->execute([
                'no' => $trxNo,
                'uid' => $userId,
                'items' => $qty,
                'amt' => $qty * $price,
                'notes' => $notes
            ]);
            $trxId = $pdo->lastInsertId();

            $stmtIt = $pdo->prepare("
                INSERT INTO transaction_items (transaction_id, product_id, qty, unit_price, subtotal)
                VALUES (:tid, :pid, :qty, :pr, :sub)
            ");
            $stmtIt->execute(['tid' => $trxId, 'pid' => $productId, 'qty' => $qty, 'pr' => $price, 'sub' => $qty * $price]);

            $stmtUp = $pdo->prepare("UPDATE products SET current_stock = :ns WHERE id = :id");
            $stmtUp->execute(['ns' => $newStock, 'id' => $productId]);

            record_stock_mutation($pdo, $productId, null, $prod['location_id'], 'barang_masuk', $trxNo, $qty, 0, $newStock, $notes, $userId);

            $msg = "Berhasil menambah {$qty} unit stok masuk untuk '{$prod['name']}'. Stok sekarang: {$newStock} unit.";
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'new_stock' => $newStock
        ]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

