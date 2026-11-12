<?php
/**
 * Category Action Controller (CRUD Kategori Barang)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=categories');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $code = sanitize($_POST['code'] ?? '');
    $color = sanitize($_POST['color'] ?? '#f97316');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name)) {
        flash_set('error', 'Nama kategori wajib diisi.');
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    }

    // Generate kode jika kosong
    if (empty($code)) {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($name, 0, 4)));
        $code = 'CAT-' . ($clean ?: 'GEN') . '-' . mt_rand(100, 999);
    }

    // Cek duplikasi kode
    $stmtCheck = $pdo->prepare("SELECT id FROM categories WHERE code = :code LIMIT 1");
    $stmtCheck->execute(['code' => $code]);
    if ($stmtCheck->fetch()) {
        flash_set('error', "Kode kategori '{$code}' sudah digunakan. Silakan gunakan kode lain.");
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, code, color, description)
            VALUES (:name, :code, :color, :desc)
        ");
        $stmt->execute([
            'name' => $name,
            'code' => $code,
            'color' => $color,
            'desc' => $description
        ]);

        flash_set('success', "Kategori '{$name}' ({$code}) berhasil ditambahkan!");
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    } catch (Exception $e) {
        flash_set('error', 'Gagal menyimpan kategori: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $code = sanitize($_POST['code'] ?? '');
    $color = sanitize($_POST['color'] ?? '#f97316');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name) || $id <= 0) {
        flash_set('error', 'Nama kategori wajib diisi.');
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    }

    // Cek duplikasi kode pada ID lain
    $stmtCheck = $pdo->prepare("SELECT id FROM categories WHERE code = :code AND id != :id LIMIT 1");
    $stmtCheck->execute(['code' => $code, 'id' => $id]);
    if ($stmtCheck->fetch()) {
        flash_set('error', "Kode kategori '{$code}' sudah digunakan oleh kategori lain.");
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET name = :name, code = :code, color = :color, description = :desc
            WHERE id = :id
        ");
        $stmt->execute([
            'name' => $name,
            'code' => $code,
            'color' => $color,
            'desc' => $description,
            'id' => $id
        ]);

        flash_set('success', "Kategori '{$name}' berhasil diperbarui.");
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    } catch (Exception $e) {
        flash_set('error', 'Gagal memperbarui kategori: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    }
}

if ($action === 'delete') {
    require_admin(); // Hanya admin yang boleh menghapus kategori
    $id = (int)($_GET['id'] ?? 0);

    try {
        $stmtName = $pdo->prepare("SELECT name FROM categories WHERE id = :id");
        $stmtName->execute(['id' => $id]);
        $cat = $stmtName->fetch();

        if ($cat) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute(['id' => $id]);
            flash_set('success', "Kategori '{$cat['name']}' berhasil dihapus. Barang di kategori ini kini berstatus belum dikategorikan.");
        } else {
            flash_set('error', 'Kategori tidak ditemukan.');
        }

        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    } catch (Exception $e) {
        flash_set('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=categories');
        exit;
    }
}

header('Location: ' . BASE_URL . 'index.php?page=categories');
exit;

