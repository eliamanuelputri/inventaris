<?php
/**
 * INVENTA PRO - Main Router & Dispatcher
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$dbError = Database::getError();

// Handle Database Setup / Connection screen jika MySQL Laragon belum menyala
if (!$pdo) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Database - <?= APP_NAME ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <script src="https://unpkg.com/lucide@latest"></script>
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    </head>
    <body class="bg-slate-900 text-slate-100 flex min-h-screen items-center justify-center p-4">
        <div class="max-w-md w-full bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-700 text-center">
            <div class="h-16 w-16 bg-gradient-to-tr from-amber-500 to-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-orange-500/30">
                <i data-lucide="database" class="h-8 w-8 text-white"></i>
            </div>
            
            <h1 class="text-2xl font-black tracking-tight text-white mb-2">Menunggu Koneksi MySQL</h1>
            <p class="text-slate-400 text-sm mb-6">
                Aplikasi siap digunakan. Silakan pastikan service MySQL di Laragon sudah dalam keadaan <span class="text-amber-400 font-semibold">Running</span>.
            </p>

            <div class="bg-slate-900/80 rounded-2xl p-4 text-left border border-slate-700/60 mb-6 text-xs font-mono space-y-2">
                <div class="flex justify-between text-slate-400">
                    <span>Host:</span>
                    <span class="text-amber-400"><?= DB_HOST ?>:<?= DB_PORT ?></span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>User:</span>
                    <span class="text-amber-400"><?= DB_USER ?></span>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Database:</span>
                    <span class="text-amber-400"><?= DB_NAME ?> (Auto-create)</span>
                </div>
                <?php if ($dbError): ?>
                    <div class="pt-2 border-t border-slate-800 text-rose-400 break-words">
                        Pesan: <?= htmlspecialchars($dbError) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-3">
                <button onclick="window.location.reload()" class="w-full bg-gradient-to-r from-orange-500 to-rose-600 hover:from-orange-600 hover:to-rose-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-orange-500/25 transition flex items-center justify-center gap-2">
                    <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                    <span>Coba Sambungkan Lagi</span>
                </button>
            </div>

            <p class="text-[11px] text-slate-500 mt-6">
                Tips Laragon: Buka aplikasi Laragon &rarr; Klik tombol <strong>"Start All"</strong>. Database dan tabel otomatis dibuat saat terhubung.
            </p>
        </div>
        <script>lucide.createIcons();</script>
    </body>
    </html>
    <?php
    exit;
}

// Router
$page = $_GET['page'] ?? 'dashboard';

// Autentikasi check
if (!is_logged_in() && $page !== 'login') {
    header('Location: ' . BASE_URL . 'index.php?page=login');
    exit;
}

if (is_logged_in() && $page === 'login') {
    header('Location: ' . BASE_URL . 'index.php?page=dashboard');
    exit;
}

// Route mapping
$routes = [
    'dashboard'       => 'pages/dashboard.php',
    'scanner'         => 'pages/scanner.php',
    'login'           => 'pages/login.php',
    'products'        => 'pages/products/index.php',
    'products_create' => 'pages/products/create.php',
    'products_edit'   => 'pages/products/edit.php',
    'products_detail' => 'pages/products/detail.php',
    'inbound'         => 'pages/transactions/inbound.php',
    'inbound_create'  => 'pages/transactions/inbound_create.php',
    'outbound'        => 'pages/transactions/outbound.php',
    'outbound_create' => 'pages/transactions/outbound_create.php',
    'stock_cards'     => 'pages/transactions/stock_cards.php',
    'forecasting'     => 'pages/forecasting/index.php',
    'opname'          => 'pages/opname/index.php',
    'opname_create'   => 'pages/opname/create.php',
    'opname_detail'   => 'pages/opname/detail.php',
    'reports'         => 'pages/reports/index.php',
    'suppliers'       => 'pages/suppliers/index.php',
    'categories'      => 'pages/categories/index.php',
    'locations'       => 'pages/locations/index.php',
    'users'           => 'pages/users/index.php',
    'profile'         => 'pages/profile.php',
];

if (isset($routes[$page]) && file_exists(__DIR__ . '/' . $routes[$page])) {
    require __DIR__ . '/' . $routes[$page];
} else {
    // 404
    http_response_code(404);
    require __DIR__ . '/includes/header.php';
    echo '<div class="p-8 text-center"><h1 class="text-3xl font-bold text-slate-800">404 - Halaman Tidak Ditemukan</h1><p class="mt-2 text-slate-500">Halaman yang Anda tuju tidak tersedia.</p><a href="' . BASE_URL . '" class="mt-4 inline-block text-brand-600 font-bold">&larr; Kembali ke Dashboard</a></div>';
    require __DIR__ . '/includes/footer.php';
}

