<?php
/**
 * Outbound Transaction Action Controller (Barang Keluar / Penjualan / Pemakaian)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=outbound');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $destination = sanitize($_POST['destination'] ?? '');
    $reason = sanitize($_POST['reason'] ?? 'penjualan');
    $refNo = sanitize($_POST['reference_no'] ?? '');
    $trxDate = sanitize($_POST['transaction_date'] ?? date('Y-m-d'));
    $notes = sanitize($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];

    if (empty($items) || !is_array($items)) {
        flash_set('error', 'Silakan masukkan minimal satu barang keluar.');
        header('Location: ' . BASE_URL . 'index.php?page=outbound_create');
        exit;
    }

    $trxNo = generate_trx_no('out');
    $userId = (int)$_SESSION['user']['id'];

    try {
        $pdo->beginTransaction();

        $totalItems = 0;
        $totalAmount = 0;

        // 1. Validasi Ketersediaan Stok Fisik
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['qty'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);

            if ($productId <= 0 || $qty <= 0) continue;

            $stmtCheck = $pdo->prepare("SELECT name, current_stock FROM products WHERE id = :id FOR UPDATE");
            $stmtCheck->execute(['id' => $productId]);
            $prod = $stmtCheck->fetch();

            if (!$prod) {
                throw new Exception("Barang ID #{$productId} tidak ditemukan.");
            }

            if ($prod['current_stock'] < $qty) {
                throw new Exception("Stok untuk '{$prod['name']}' tidak mencukupi! Sisa stok saat ini: {$prod['current_stock']}, diminta keluar: {$qty}.");
            }

            $totalItems += $qty;
            $totalAmount += ($qty * $price);
        }

        // 2. Simpan Header Transaksi Keluar
        $stmtTrx = $pdo->prepare("
            INSERT INTO transactions (transaction_no, type, reason, destination, reference_no, transaction_date, user_id, total_items, total_amount, notes)
            VALUES (:trx_no, 'out', :reason, :dest, :ref_no, :trx_date, :user_id, :tot_items, :tot_amount, :notes)
        ");
        $stmtTrx->execute([
            'trx_no' => $trxNo,
            'reason' => $reason,
            'dest' => $destination,
            'ref_no' => $refNo,
            'trx_date' => $trxDate,
            'user_id' => $userId,
            'tot_items' => $totalItems,
            'tot_amount' => $totalAmount,
            'notes' => $notes
        ]);
        $trxId = (int)$pdo->lastInsertId();

        // 3. Simpan Item & Kurangi Stok
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['qty'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);
            $batchId = !empty($item['batch_id']) ? (int)$item['batch_id'] : null;
            $subtotal = $qty * $price;

            if ($productId <= 0 || $qty <= 0) continue;

            // Jika batch dipilih, kurangi stok pada batch
            if ($batchId) {
                $stmtDecBatch = $pdo->prepare("
                    UPDATE product_batches 
                    SET current_qty = GREATEST(0, current_qty - :qty) 
                    WHERE id = :bid
                ");
                $stmtDecBatch->execute(['qty' => $qty, 'bid' => $batchId]);
            }

            // Simpan Item Transaksi
            $stmtItem = $pdo->prepare("
                INSERT INTO transaction_items (transaction_id, product_id, batch_id, qty, unit_price, subtotal)
                VALUES (:tid, :pid, :bid, :qty, :price, :sub)
            ");
            $stmtItem->execute([
                'tid' => $trxId,
                'pid' => $productId,
                'bid' => $batchId,
                'qty' => $qty,
                'price' => $price,
                'sub' => $subtotal
            ]);

            // Kurangi Stok di Produk
            $stmtProd = $pdo->prepare("
                UPDATE products 
                SET current_stock = current_stock - :qty 
                WHERE id = :pid
            ");
            $stmtProd->execute(['qty' => $qty, 'pid' => $productId]);

            // Ambil sisa stok akhir
            $stmtGetStock = $pdo->prepare("SELECT current_stock, location_id FROM products WHERE id = :pid");
            $stmtGetStock->execute(['pid' => $productId]);
            $prodRow = $stmtGetStock->fetch();
            $newStock = (int)$prodRow['current_stock'];
            $locId = $prodRow['location_id'];

            // Catat Mutasi
            record_stock_mutation(
                $pdo,
                $productId,
                $batchId,
                $locId,
                'barang_keluar',
                $trxNo,
                0,
                $qty,
                $newStock,
                "Barang Keluar: {$reason} (" . ($destination ?: 'Internal') . ")",
                $userId
            );
        }

        $pdo->commit();
        flash_set('success', "Transaksi Barang Keluar #{$trxNo} berhasil dicatat! Stok berkurang otomatis.");
        header('Location: ' . BASE_URL . 'index.php?page=outbound');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        flash_set('error', $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=outbound_create');
        exit;
    }
}

header('Location: ' . BASE_URL . 'index.php?page=outbound');
exit;

