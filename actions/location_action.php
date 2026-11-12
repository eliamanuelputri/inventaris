<?php
/**
 * Location Action Controller (Gudang & Rak)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=locations');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $warehouse = sanitize($_POST['warehouse_name'] ?? 'Gudang Utama');
    $rack = sanitize($_POST['rack_code'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');

    if (empty($rack)) {
        flash_set('error', 'Kode Rak / Bin wajib diisi.');
        header('Location: ' . BASE_URL . 'index.php?page=locations');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO locations (warehouse_name, rack_code, description)
        VALUES (:w, :r, :d)
    ");
    $stmt->execute(['w' => $warehouse, 'r' => $rack, 'd' => $desc]);

    flash_set('success', "Lokasi rak '{$rack}' berhasil ditambahkan.");
    header('Location: ' . BASE_URL . 'index.php?page=locations');
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $warehouse = sanitize($_POST['warehouse_name'] ?? '');
    $rack = sanitize($_POST['rack_code'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE locations 
        SET warehouse_name = :w, rack_code = :r, description = :d
        WHERE id = :id
    ");
    $stmt->execute(['w' => $warehouse, 'r' => $rack, 'd' => $desc, 'id' => $id]);

    flash_set('success', "Lokasi rak '{$rack}' berhasil diperbarui.");
    header('Location: ' . BASE_URL . 'index.php?page=locations');
    exit;
}

if ($action === 'delete') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM locations WHERE id = :id");
    $stmt->execute(['id' => $id]);

    flash_set('success', 'Lokasi rak berhasil dihapus.');
    header('Location: ' . BASE_URL . 'index.php?page=locations');
    exit;
}

header('Location: ' . BASE_URL . 'index.php?page=locations');
exit;

