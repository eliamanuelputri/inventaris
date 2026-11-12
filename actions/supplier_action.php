<?php
/**
 * Supplier Action Controller
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=suppliers');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['code'] ?? ('SUP-' . mt_rand(100, 999)));
    $name = sanitize($_POST['name'] ?? '');
    $contact = sanitize($_POST['contact_person'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($name)) {
        flash_set('error', 'Nama pemasok / supplier wajib diisi.');
        header('Location: ' . BASE_URL . 'index.php?page=suppliers');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO suppliers (code, name, contact_person, phone, email, address, notes)
        VALUES (:code, :name, :cp, :phone, :email, :addr, :notes)
    ");
    $stmt->execute([
        'code' => $code,
        'name' => $name,
        'cp' => $contact,
        'phone' => $phone,
        'email' => $email,
        'addr' => $address,
        'notes' => $notes
    ]);

    flash_set('success', "Pemasok '{$name}' berhasil ditambahkan.");
    header('Location: ' . BASE_URL . 'index.php?page=suppliers');
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $contact = sanitize($_POST['contact_person'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE suppliers 
        SET name = :name, contact_person = :cp, phone = :phone, email = :email, address = :addr, notes = :notes
        WHERE id = :id
    ");
    $stmt->execute([
        'name' => $name,
        'cp' => $contact,
        'phone' => $phone,
        'email' => $email,
        'addr' => $address,
        'notes' => $notes,
        'id' => $id
    ]);

    flash_set('success', "Data supplier '{$name}' berhasil diperbarui.");
    header('Location: ' . BASE_URL . 'index.php?page=suppliers');
    exit;
}

if ($action === 'delete') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = :id");
    $stmt->execute(['id' => $id]);

    flash_set('success', 'Supplier berhasil dihapus.');
    header('Location: ' . BASE_URL . 'index.php?page=suppliers');
    exit;
}

header('Location: ' . BASE_URL . 'index.php?page=suppliers');
exit;

