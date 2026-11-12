<?php
/**
 * Authentication Action Controller
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = db();

if (!$pdo) {
    flash_set('error', 'Koneksi database bermasalah.');
    header('Location: ' . BASE_URL . 'index.php?page=login');
    exit;
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = sanitize($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (empty($usernameOrEmail) || empty($password)) {
        flash_set('error', 'Silakan masukkan username/email dan password.');
        header('Location: ' . BASE_URL . 'index.php?page=login');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE (username = :u OR email = :e) AND is_active = 1 
        LIMIT 1
    ");
    $stmt->execute(['u' => $usernameOrEmail, 'e' => $usernameOrEmail]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login berhasil!
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'phone' => $user['phone']
        ];
        flash_set('success', 'Selamat datang kembali, ' . $user['name'] . '!');
        header('Location: ' . BASE_URL . 'index.php?page=dashboard');
        exit;
    } else {
        flash_set('error', 'Username/Email atau password salah, atau akun dinonaktifkan.');
        header('Location: ' . BASE_URL . 'index.php?page=login');
        exit;
    }
}

if ($action === 'logout') {
    unset($_SESSION['user']);
    session_destroy();
    session_start();
    flash_set('success', 'Anda telah berhasil keluar.');
    header('Location: ' . BASE_URL . 'index.php?page=login');
    exit;
}

if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $userId = (int)$_SESSION['user']['id'];
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');

    if (empty($name) || empty($email)) {
        flash_set('error', 'Nama dan Email wajib diisi.');
        header('Location: ' . BASE_URL . 'index.php?page=profile');
        exit;
    }

    if (!empty($newPassword)) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET name = :n, email = :e, phone = :p, password = :pwd WHERE id = :id");
        $stmt->execute(['n' => $name, 'e' => $email, 'p' => $phone, 'pwd' => $hash, 'id' => $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = :n, email = :e, phone = :p WHERE id = :id");
        $stmt->execute(['n' => $name, 'e' => $email, 'p' => $phone, 'id' => $userId]);
    }

    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['phone'] = $phone;

    flash_set('success', 'Profil Anda berhasil diperbarui.');
    header('Location: ' . BASE_URL . 'index.php?page=profile');
    exit;
}

header('Location: ' . BASE_URL);
exit;

