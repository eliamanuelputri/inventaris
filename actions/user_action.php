<?php
/**
 * User & Role Management Action Controller (Admin Only)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pdo = db();
if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=users');
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role = sanitize($_POST['role'] ?? 'petugas');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '123456');

    if (empty($username) || empty($name) || empty($email)) {
        flash_set('error', 'Username, Nama, dan Email wajib diisi.');
        header('Location: ' . BASE_URL . 'index.php?page=users');
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, name, email, password, role, phone, is_active)
            VALUES (:u, :n, :e, :p, :r, :ph, 1)
        ");
        $stmt->execute([
            'u' => $username,
            'n' => $name,
            'e' => $email,
            'p' => $hash,
            'r' => $role,
            'ph' => $phone
        ]);

        flash_set('success', "Pengguna baru '{$name}' ({$role}) berhasil dibuat.");
        header('Location: ' . BASE_URL . 'index.php?page=users');
        exit;
    } catch (Exception $e) {
        flash_set('error', 'Gagal membuat pengguna: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'index.php?page=users');
        exit;
    }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role = sanitize($_POST['role'] ?? 'petugas');
    $phone = sanitize($_POST['phone'] ?? '');
    $newPassword = (string)($_POST['password'] ?? '');

    if (!empty($newPassword)) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            UPDATE users SET name = :n, email = :e, role = :r, phone = :ph, password = :p WHERE id = :id
        ");
        $stmt->execute(['n' => $name, 'e' => $email, 'r' => $role, 'ph' => $phone, 'p' => $hash, 'id' => $id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users SET name = :n, email = :e, role = :r, phone = :ph WHERE id = :id
        ");
        $stmt->execute(['n' => $name, 'e' => $email, 'r' => $role, 'ph' => $phone, 'id' => $id]);
    }

    flash_set('success', "Data pengguna '{$name}' berhasil diperbarui.");
    header('Location: ' . BASE_URL . 'index.php?page=users');
    exit;
}

if ($action === 'toggle_status') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id === (int)$_SESSION['user']['id']) {
        flash_set('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        header('Location: ' . BASE_URL . 'index.php?page=users');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = :id");
    $stmt->execute(['id' => $id]);

    flash_set('success', 'Status keaktifan pengguna berhasil diubah.');
    header('Location: ' . BASE_URL . 'index.php?page=users');
    exit;
}

header('Location: ' . BASE_URL . 'index.php?page=users');
exit;

