<?php
/**
 * Inbound Transaction Action Controller (Barang Masuk / Pembelian)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=inbound');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $refNo = sanitize($_POST['reference_no'] ?? '');
    $trxDate = sanitize($_POST['transaction_date'] ?? date('Y-m-d'));
    $notes = sanitize($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];

    if (empty($items) || !is_array($items)) {
        flash_set('error', 'Silakan tambahkan minimal satu barang masuk.');
        header('Location: ' . BASE_URL . 'index.php?page=inbound_create');
        exit;
    }

    $trxNo = generate_trx_no('in');
    $userId = (int)$_SESSION['user']['id'];

    try {
        $pdo->beginTransaction();

        $totalItems = 0;
        $totalAmount = 0;

        // Hitung total dulu
        foreach ($items as $item) {
            $qty = (int)($item['qty'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);
            if ($qty > 0) {
                $totalItems += $qty;
                $totalAmount += ($qty * $price);
            }
        }

        // 1. Simpan Header Transaksi
        $stmtTrx = $pdo->prepare("
            INSERT INTO transactions (transaction_no, type, reason, supplier_id, reference_no, transaction_date, user_id, total_items, total_amount, notes)
            VALUES (:trx_no, 'in', 'pembelian', :sup_id, :ref_no, :trx_date, :user_id, :tot_items, :tot_amount, :notes)
        ");
        $stmtTrx->execute([
            'trx_no' => $trxNo,
            'sup_id' => $supplierId,
            'ref_no' => $refNo,
            'trx_date' => $trxDate,
            'user_id' => $userId,
            'tot_items' => $totalItems,
            'tot_amount' => $totalAmount,
            'notes' => $notes
        ]);
        $trxId = (int)$pdo->lastInsertId();

        // 2. Simpan Item & Update Stok Barang
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['qty'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);
            $subtotal = $qty * $price;
            $locationId = !empty($item['location_id']) ? (int)$item['location_id'] : null;
            $batchNo = sanitize($item['batch_no'] ?? '');
            $expiryDate = !empty($item['expiry_date']) ? $item['expiry_date'] : null;

            if ($productId <= 0 || $qty <= 0) continue;

            $batchId = null;
            // Jika ada batch / expiry
            if (!empty($batchNo) || !empty($expiryDate)) {
                $bNo = !empty($batchNo) ? $batchNo : ('LOT-' . date('Ymd') . '-' . mt_rand(100, 999));
                $stmtBatch = $pdo->prepare("
                    INSERT INTO product_batches (product_id, batch_no, expiry_date, initial_qty, current_qty, location_id)
                    VALUES (:pid, :bno, :exp, :iqty, :cqty, :loc)
                ");
                $stmtBatch->execute([
                    'pid' => $productId,
                    'bno' => $bNo,
                    'exp' => $expiryDate,
                    'iqty' => $qty,
                    'cqty' => $qty,
                    'loc' => $locationId
                ]);
                $batchId = (int)$pdo->lastInsertId();
            }

            // Simpan Item
            $stmtItem = $pdo->prepare("
                INSERT INTO transaction_items (transaction_id, product_id, batch_id, location_id, qty, unit_price, subtotal, expiry_date)
                VALUES (:tid, :pid, :bid, :loc, :qty, :price, :sub, :exp)
            ");
            $stmtItem->execute([
                'tid' => $trxId,
                'pid' => $productId,
                'bid' => $batchId,
                'loc' => $locationId,
                'qty' => $qty,
                'price' => $price,
                'sub' => $subtotal,
                'exp' => $expiryDate
            ]);

            // Tambah Stok di Tabel Products & update harga beli terakhir
            $stmtProd = $pdo->prepare("
                UPDATE products 
                SET current_stock = current_stock + :qty,
                    purchase_price = CASE WHEN :new_price > 0 THEN :new_price ELSE purchase_price END
                WHERE id = :pid
            ");
            $stmtProd->execute([
                'qty' => $qty,
                'new_price' => $price,
                'pid' => $productId
            ]);

            // Ambil stok terbaru
            $stmtGetStock = $pdo->prepare("SELECT current_stock FROM products WHERE id = :pid");
            $stmtGetStock->execute(['pid' => $productId]);
            $newStock = (int)$stmtGetStock->fetchColumn();

            // Catat Mutasi
            record_stock_mutation(
                $pdo,
                $productId,
                $batchId,
                $locationId,
                'barang_masuk',
                $trxNo,
                $qty,
                0,
                $newStock,
                "Barang Masuk dari Transaksi #{$trxNo}",
                $userId
            );
        }

        $pdo->commit();
        flash_set('success', "Transaksi Barang Masuk #{$trxNo} berhasil disimpan! Stok bertambah otomatis.");
        header('Location: ' . BASE_URL . 'index.php?page=inbound');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        flash_set('error', 'Gagal memproses barang masuk: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=inbound_create');
        exit;
    }
}

header('Location: ' . BASE_URL . 'index.php?page=inbound');
exit;

