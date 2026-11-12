<?php
require_once __DIR__ . '/functions.php';
$pdo = db();

// Hitung notifikasi stok menipis & hampir kedaluwarsa untuk badge lonceng
$alertLowStockCount = 0;
$alertExpiryCount = 0;

if ($pdo) {
    try {
        $stmtLow = $pdo->query("SELECT COUNT(*) FROM products WHERE current_stock <= min_stock");
        $alertLowStockCount = (int)$stmtLow->fetchColumn();

        $stmtExp = $pdo->query("
            SELECT COUNT(*) 
            FROM product_batches 
            WHERE current_qty > 0 
              AND expiry_date IS NOT NULL 
              AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $alertExpiryCount = (int)$stmtExp->fetchColumn();
    } catch (Exception $e) {
        // Fallback jika belum migrasi
    }
}
$totalAlerts = $alertLowStockCount + $alertExpiryCount;
$user = current_user();
?>
<header class="no-print sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur shadow-sm sm:px-6">
    <!-- Left: Hamburger (Mobile) & Brand Title -->
    <div class="flex items-center gap-3">
        <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 md:hidden focus:outline-none">
            <i data-lucide="menu" class="h-6 w-6"></i>
        </button>

        <a href="<?= BASE_URL ?>" class="flex items-center gap-2.5">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-brand-600 to-crimson-600 text-white shadow-md shadow-brand-500/20">
                <i data-lucide="boxes" class="h-5 w-5"></i>
            </div>
            <div class="hidden sm:block">
                <span class="text-lg font-black tracking-tight text-slate-900">INVENTA<span class="text-brand-600">.</span></span>
                <span class="ml-1 rounded bg-brand-100 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-brand-700 uppercase">PRO</span>
            </div>
        </a>
    </div>

    <!-- Center: Global Quick Search Bar -->
    <div class="hidden md:flex flex-1 max-w-md mx-6">
        <form action="<?= BASE_URL ?>" method="GET" class="w-full relative">
            <input type="hidden" name="page" value="products">
            <div class="relative">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                <input 
                    type="text" 
                    name="q" 
                    value="<?= sanitize($_GET['q'] ?? '') ?>"
                    placeholder="Cari kode barang (SKU), barcode, nama..." 
                    class="w-full rounded-full border border-slate-200 bg-slate-50/70 py-2 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition-all"
                >
            </div>
        </form>
    </div>

    <!-- Right: Actions (Scan Barcode, Notifications, User Profile) -->
    <div class="flex items-center gap-2 sm:gap-3">
        <!-- Quick Barcode Scanner Button (Camera & Hub) -->
        <a 
            href="<?= BASE_URL ?>index.php?page=scanner" 
            class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:border-brand-500 hover:text-brand-600 hover:bg-brand-50/50 transition-colors"
            title="Buka Pemindai Barcode / QR Code"
        >
            <i data-lucide="scan" class="h-4 w-4 text-brand-600"></i>
            <span class="hidden sm:inline">Scan Barcode</span>
        </a>

        <!-- Notification Bell (Alert Stok Kritis & Kedaluwarsa) -->
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button 
                type="button" 
                @click="open = !open" 
                class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none"
            >
                <i data-lucide="bell" class="h-5 w-5"></i>
                <?php if ($totalAlerts > 0): ?>
                    <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-crimson-600 text-[10px] font-bold text-white shadow-sm ring-2 ring-white">
                        <?= $totalAlerts ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Notification Dropdown Menu -->
            <div 
                x-show="open" 
                x-cloak 
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-80 rounded-2xl bg-white p-3 shadow-xl ring-1 ring-slate-900/5 focus:outline-none z-50"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2 px-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Peringatan Inventaris</span>
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700"><?= $totalAlerts ?> Perhatian</span>
                </div>

                <div class="space-y-1.5 max-h-64 overflow-y-auto">
                    <?php if ($alertLowStockCount > 0): ?>
                        <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=low_stock" class="flex items-start gap-2.5 rounded-xl p-2 hover:bg-orange-50 text-slate-700 transition">
                            <div class="rounded-lg bg-orange-100 p-1.5 text-brand-600 mt-0.5">
                                <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                            </div>
                            <div class="text-xs">
                                <p class="font-bold text-slate-900"><?= $alertLowStockCount ?> Barang Menipis</p>
                                <p class="text-slate-500 text-[11px]">Sisa stok berada di bawah batas minimum.</p>
                            </div>
                        </a>
                    <?php endif; ?>

                    <?php if ($alertExpiryCount > 0): ?>
                        <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=expiring" class="flex items-start gap-2.5 rounded-xl p-2 hover:bg-red-50 text-slate-700 transition">
                            <div class="rounded-lg bg-red-100 p-1.5 text-crimson-600 mt-0.5">
                                <i data-lucide="calendar-x" class="h-4 w-4"></i>
                            </div>
                            <div class="text-xs">
                                <p class="font-bold text-slate-900"><?= $alertExpiryCount ?> Batch Mendekati Expired</p>
                                <p class="text-slate-500 text-[11px]">Kedaluwarsa dalam waktu ≤ 30 hari ke depan.</p>
                            </div>
                        </a>
                    <?php endif; ?>

                    <?php if ($totalAlerts === 0): ?>
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <i data-lucide="check-circle" class="mx-auto mb-1 h-6 w-6 text-emerald-500"></i>
                            Semua stok dalam batas aman dan tidak ada batch kedaluwarsa.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="border-t border-slate-100 mt-2 pt-2 text-center">
                    <a href="<?= BASE_URL ?>index.php?page=forecasting" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat Analisis Forecasting Lengkap &rarr;</a>
                </div>
            </div>
        </div>

        <!-- User Dropdown -->
        <?php if ($user): ?>
            <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
                <button 
                    type="button" 
                    @click="userMenuOpen = !userMenuOpen" 
                    class="flex items-center gap-2 rounded-full p-1 text-slate-700 hover:bg-slate-100 focus:outline-none"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-tr from-brand-500 to-crimson-600 font-bold text-white text-xs shadow-sm">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="hidden text-left sm:block">
                        <div class="text-xs font-bold leading-none text-slate-800"><?= sanitize($user['name'] ?? 'Pengguna') ?></div>
                        <div class="text-[10px] uppercase font-semibold tracking-wider text-slate-400 mt-0.5">
                            <?= ($user['role'] ?? '') === 'admin' ? '<span class="text-brand-600">Admin</span>' : '<span class="text-blue-600">Petugas</span>' ?>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                </button>

                <!-- Profile Dropdown -->
                <div 
                    x-show="userMenuOpen" 
                    x-cloak 
                    class="absolute right-0 mt-2 w-52 rounded-2xl bg-white p-2 shadow-xl ring-1 ring-slate-900/5 focus:outline-none z-50 text-xs text-slate-700"
                >
                    <div class="px-3 py-2 border-b border-slate-100 mb-1">
                        <p class="font-bold text-slate-900"><?= sanitize($user['name']) ?></p>
                        <p class="text-slate-400 truncate"><?= sanitize($user['email']) ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>index.php?page=profile" class="flex items-center gap-2 rounded-xl px-3 py-2 hover:bg-slate-100 transition">
                        <i data-lucide="user" class="h-4 w-4 text-slate-400"></i>
                        <span>Profil Saya</span>
                    </a>
                    <?php if (is_admin()): ?>
                        <a href="<?= BASE_URL ?>index.php?page=users" class="flex items-center gap-2 rounded-xl px-3 py-2 hover:bg-slate-100 transition">
                            <i data-lucide="users" class="h-4 w-4 text-slate-400"></i>
                            <span>Kelola Pengguna</span>
                        </a>
                    <?php endif; ?>
                    <div class="border-t border-slate-100 my-1"></div>
                    <a href="<?= BASE_URL ?>actions/auth_action.php?action=logout" class="flex items-center gap-2 rounded-xl px-3 py-2 text-crimson-600 hover:bg-red-50 transition font-medium">
                        <i data-lucide="log-out" class="h-4 w-4"></i>
                        <span>Keluar / Logout</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?= BASE_URL ?>index.php?page=login" class="rounded-xl bg-brand-600 px-3.5 py-1.5 text-xs font-bold text-white shadow hover:bg-brand-700 transition">
                Masuk
            </a>
        <?php endif; ?>
    </div>
</header>

