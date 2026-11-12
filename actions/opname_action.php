<?php
/**
 * Stock Opname Action Controller (Physical Audit & Variance Reconciliation)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=opname');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $auditDate = sanitize($_POST['audit_date'] ?? date('Y-m-d'));
    $notes = sanitize($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];
    $finalizeNow = isset($_POST['finalize']) && $_POST['finalize'] === '1';

    if (empty($items) || !is_array($items)) {
        flash_set('error', 'Silakan masukkan minimal satu barang dalam sesi opname.');
        header('Location: ' . BASE_URL . 'index.php?page=opname_create');
        exit;
    }

    $opnameNo = generate_opname_no();
    $userId = (int)$_SESSION['user']['id'];
    $status = $finalizeNow ? 'completed' : 'draft';

    try {
        $pdo->beginTransaction();

        $totalItems = 0;
        $totalVarianceQty = 0;
        $totalVarianceValue = 0;

        // Hitung total ringkasan
        foreach ($items as $it) {
            $sysQty = (int)($it['system_qty'] ?? 0);
            $phyQty = (int)($it['physical_qty'] ?? $sysQty);
            $price = (float)($it['unit_price'] ?? 0);
            $diff = $phyQty - $sysQty;

            $totalItems++;
            $totalVarianceQty += $diff;
            $totalVarianceValue += ($diff * $price);
        }

        // 1. Simpan Header Sesi Opname
        $stmtOp = $pdo->prepare("
            INSERT INTO stock_opnames (opname_no, audit_date, user_id, status, total_items, total_variance_qty, total_variance_value, notes, completed_at)
            VALUES (:no, :adate, :uid, :stat, :tot_items, :tot_var_qty, :tot_var_val, :notes, :comp_at)
        ");
        $stmtOp->execute([
            'no' => $opnameNo,
            'adate' => $auditDate,
            'uid' => $userId,
            'stat' => $status,
            'tot_items' => $totalItems,
            'tot_var_qty' => $totalVarianceQty,
            'tot_var_val' => $totalVarianceValue,
            'notes' => $notes,
            'comp_at' => $finalizeNow ? date('Y-m-d H:i:s') : null
        ]);
        $opnameId = (int)$pdo->lastInsertId();

        // 2. Simpan Item Detail
        foreach ($items as $it) {
            $productId = (int)($it['product_id'] ?? 0);
            $locId = !empty($it['location_id']) ? (int)$it['location_id'] : null;
            $sysQty = (int)($it['system_qty'] ?? 0);
            $phyQty = (int)($it['physical_qty'] ?? $sysQty);
            $price = (float)($it['unit_price'] ?? 0);
            $reason = sanitize($it['reason'] ?? 'koreksi_hitung');
            $itemNotes = sanitize($it['notes'] ?? '');
            $varianceQty = $phyQty - $sysQty;
            $varianceValue = $varianceQty * $price;

            if ($productId <= 0) continue;

            $stmtItem = $pdo->prepare("
                INSERT INTO stock_opname_items (opname_id, product_id, location_id, system_qty, physical_qty, variance_qty, unit_price, variance_value, reason, notes)
                VALUES (:oid, :pid, :loc, :sys, :phy, :var_q, :price, :var_v, :reason, :notes)
            ");
            $stmtItem->execute([
                'oid' => $opnameId,
                'pid' => $productId,
                'loc' => $locId,
                'sys' => $sysQty,
                'phy' => $phyQty,
                'var_q' => $varianceQty,
                'price' => $price,
                'var_v' => $varianceValue,
                'reason' => $reason,
                'notes' => $itemNotes
            ]);

            // 3. JIKA LANGSUNG FINALISASI (COMPLETED):
            // Sesuaikan stok sistem agar cocok dengan hitungan fisik!
            if ($finalizeNow && $varianceQty != 0) {
                // Update tabel products
                $stmtUpdateProd = $pdo->prepare("UPDATE products SET current_stock = :phy WHERE id = :pid");
                $stmtUpdateProd->execute(['phy' => $phyQty, 'pid' => $productId]);

                // Catat di Mutasi Stok
                $qtyIn = ($varianceQty > 0) ? $varianceQty : 0;
                $qtyOut = ($varianceQty < 0) ? abs($varianceQty) : 0;

                record_stock_mutation(
                    $pdo,
                    $productId,
                    null,
                    $locId,
                    'opname_penyesuaian',
                    $opnameNo,
                    $qtyIn,
                    $qtyOut,
                    $phyQty,
                    "Penyesuaian Opname #{$opnameNo} ({$reason}): " . ($itemNotes ?: 'Selisih ' . $varianceQty),
                    $userId
                );
            }
        }

        $pdo->commit();

        if ($finalizeNow) {
            flash_set('success', "Stock Opname #{$opnameNo} berhasil diselesaikan! Stok sistem telah otomatis disesuaikan.");
        } else {
            flash_set('info', "Draft Stock Opname #{$opnameNo} berhasil disimpan.");
        }

        header('Location: ' . BASE_URL . 'index.php?page=opname_detail&id=' . $opnameId);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        flash_set('error', 'Gagal memproses opname: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=opname_create');
        exit;
    }
}

if ($action === 'finalize') {
    $opnameId = (int)($_GET['id'] ?? 0);
    $userId = (int)$_SESSION['user']['id'];

    try {
        $pdo->beginTransaction();

        $stmtHeader = $pdo->prepare("SELECT * FROM stock_opnames WHERE id = :id AND status = 'draft'");
        $stmtHeader->execute(['id' => $opnameId]);
        $opname = $stmtHeader->fetch();

        if (!$opname) {
            throw new Exception("Sesi opname tidak ditemukan atau sudah selesai.");
        }

        // Ambil item
        $stmtItems = $pdo->prepare("SELECT * FROM stock_opname_items WHERE opname_id = :oid");
        $stmtItems->execute(['oid' => $opnameId]);
        $items = $stmtItems->fetchAll();

        foreach ($items as $it) {
            $varianceQty = (int)$it['variance_qty'];
            $productId = (int)$it['product_id'];
            $phyQty = (int)$it['physical_qty'];

            if ($varianceQty != 0) {
                // Update stok produk
                $stmtUpdate = $pdo->prepare("UPDATE products SET current_stock = :phy WHERE id = :pid");
                $stmtUpdate->execute(['phy' => $phyQty, 'pid' => $productId]);

                // Catat mutasi penyesuaian
                $qtyIn = ($varianceQty > 0) ? $varianceQty : 0;
                $qtyOut = ($varianceQty < 0) ? abs($varianceQty) : 0;

                record_stock_mutation(
                    $pdo,
                    $productId,
                    null,
                    $it['location_id'],
                    'opname_penyesuaian',
                    $opname['opname_no'],
                    $qtyIn,
                    $qtyOut,
                    $phyQty,
                    "Penyesuaian Opname ({$it['reason']})",
                    $userId
                );
            }
        }

        // Set status completed
        $stmtDone = $pdo->prepare("UPDATE stock_opnames SET status = 'completed', completed_at = NOW() WHERE id = :id");
        $stmtDone->execute(['id' => $opnameId]);

        $pdo->commit();
        flash_set('success', "Sesi Stock Opname #{$opname['opname_no']} resmi disetujui & stok sistem disesuaikan!");
        header('Location: ' . BASE_URL . 'index.php?page=opname_detail&id=' . $opnameId);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        flash_set('error', 'Gagal finalisasi opname: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=opname');
        exit;
    }
}

header('Location: ' . BASE_URL . 'index.php?page=opname');
exit;

