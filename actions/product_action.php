<?php
/**
 * Product Action Controller (CRUD & Photo Upload)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database gagal.');
    header('Location: ' . BASE_URL . 'index.php?page=products');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Helper upload foto
function handle_image_upload(?array $file): ?string {
    if (!$file || empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $fileInfo = pathinfo($file['name']);
    $ext = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($ext, $allowedExts)) {
        return null;
    }

    $targetDir = UPLOAD_PATH . '/products/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $newFileName = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $targetPath = $targetDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $newFileName;
    }

    return null;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = sanitize($_POST['sku'] ?? '');
    $barcode = sanitize($_POST['barcode'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
    $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
    $sellingPrice = (float)($_POST['selling_price'] ?? 0);
    $minStock = (int)($_POST['min_stock'] ?? 5);
    $initialStock = (int)($_POST['initial_stock'] ?? 0);
    $trackExpiry = isset($_POST['track_expiry']) ? 1 : 0;
    $description = sanitize($_POST['description'] ?? '');

    if (empty($sku) || empty($name)) {
        flash_set('error', 'Kode barang (SKU) dan Nama barang wajib diisi.');
        header('Location: ' . BASE_URL . 'index.php?page=products_create');
        exit;
    }

    // Cek duplikasi SKU
    $stmtC = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
    $stmtC->execute(['sku' => $sku]);
    if ($stmtC->fetch()) {
        flash_set('error', "Kode SKU '{$sku}' sudah digunakan oleh barang lain.");
        header('Location: ' . BASE_URL . 'index.php?page=products_create');
        exit;
    }

    $imageName = handle_image_upload($_FILES['image'] ?? null);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO products (sku, barcode, name, category_id, unit_id, location_id, purchase_price, selling_price, min_stock, current_stock, image, track_expiry, description)
            VALUES (:sku, :barcode, :name, :cat_id, :unit_id, :loc_id, :buy_price, :sell_price, :min_stk, :cur_stk, :img, :track_exp, :desc)
        ");
        $stmt->execute([
            'sku' => $sku,
            'barcode' => !empty($barcode) ? $barcode : $sku,
            'name' => $name,
            'cat_id' => $categoryId,
            'unit_id' => $unitId,
            'loc_id' => $locationId,
            'buy_price' => $purchasePrice,
            'sell_price' => $sellingPrice,
            'min_stk' => $minStock,
            'cur_stk' => $initialStock,
            'img' => $imageName,
            'track_exp' => $trackExpiry,
            'desc' => $description
        ]);
        $productId = (int)$pdo->lastInsertId();

        // Jika ada stok awal, catat mutasi saldo awal
        if ($initialStock > 0) {
            record_stock_mutation(
                $pdo,
                $productId,
                null,
                $locationId,
                'saldo_awal',
                'INIT-' . $productId,
                $initialStock,
                0,
                $initialStock,
                'Input saldo awal barang baru'
            );
        }

        $pdo->commit();
        flash_set('success', "Barang '{$name}' berhasil ditambahkan ke inventaris!");
        header('Location: ' . BASE_URL . 'index.php?page=products');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        flash_set('error', 'Gagal menyimpan barang: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=products_create');
        exit;
    }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $sku = sanitize($_POST['sku'] ?? '');
    $barcode = sanitize($_POST['barcode'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
    $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
    $sellingPrice = (float)($_POST['selling_price'] ?? 0);
    $minStock = (int)($_POST['min_stock'] ?? 5);
    $trackExpiry = isset($_POST['track_expiry']) ? 1 : 0;
    $description = sanitize($_POST['description'] ?? '');

    $stmtOld = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmtOld->execute(['id' => $id]);
    $oldProduct = $stmtOld->fetch();

    if (!$oldProduct) {
        flash_set('error', 'Barang tidak ditemukan.');
        header('Location: ' . BASE_URL . 'index.php?page=products');
        exit;
    }

    $imageName = handle_image_upload($_FILES['image'] ?? null);
    if (!$imageName) {
        $imageName = $oldProduct['image'];
    }

    $stmt = $pdo->prepare("
        UPDATE products 
        SET sku = :sku, barcode = :barcode, name = :name, category_id = :cat_id, unit_id = :unit_id,
            location_id = :loc_id, purchase_price = :buy_price, selling_price = :sell_price,
            min_stock = :min_stk, image = :img, track_expiry = :track_exp, description = :desc
        WHERE id = :id
    ");
    $stmt->execute([
        'sku' => $sku,
        'barcode' => $barcode,
        'name' => $name,
        'cat_id' => $categoryId,
        'unit_id' => $unitId,
        'loc_id' => $locationId,
        'buy_price' => $purchasePrice,
        'sell_price' => $sellingPrice,
        'min_stk' => $minStock,
        'img' => $imageName,
        'track_exp' => $trackExpiry,
        'desc' => $description,
        'id' => $id
    ]);

    flash_set('success', "Data barang '{$name}' berhasil diperbarui.");
    header('Location: ' . BASE_URL . 'index.php?page=products_detail&id=' . $id);
    exit;
}

if ($action === 'delete') {
    require_admin(); // Hanya admin yang boleh menghapus barang
    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute(['id' => $id]);

    flash_set('success', 'Barang berhasil dihapus.');
    header('Location: ' . BASE_URL . 'index.php?page=products');
    exit;
}

header('Location: ' . BASE_URL . 'index.php?page=products');
exit;

