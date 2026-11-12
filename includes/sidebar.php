<?php
require_once __DIR__ . '/functions.php';
$currentPage = $_GET['page'] ?? 'dashboard';
?>

<!-- Desktop Sidebar (Sortly / Odoo Navigation) -->
<aside class="no-print hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 pt-16 border-r border-slate-200/80 bg-white z-20">
    <div class="flex flex-1 flex-col overflow-y-auto px-4 py-5 space-y-6">
        
        <!-- Section: Menu Utama -->
        <div>
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Utama</p>
            <nav class="mt-2 space-y-1">
                <a 
                    href="<?= BASE_URL ?>index.php?page=dashboard" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'dashboard' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="layout-dashboard" class="h-4 w-4 <?= $currentPage === 'dashboard' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Dashboard Ringkasan</span>
                </a>
            </nav>
        </div>

        <!-- Section: Manajemen Stok & Inventaris -->
        <div>
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Inventaris & Stok</p>
            <nav class="mt-2 space-y-1">
                <a 
                    href="<?= BASE_URL ?>index.php?page=scanner" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'scanner' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="scan" class="h-4 w-4 <?= $currentPage === 'scanner' ? 'text-white' : 'text-brand-500 group-hover:text-brand-600' ?>"></i>
                    <span>Pusat Scan Barcode</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=products" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'products' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="package" class="h-4 w-4 <?= $currentPage === 'products' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Katalog Barang & Rak</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=inbound" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'inbound' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="arrow-down-left" class="h-4 w-4 <?= $currentPage === 'inbound' ? 'text-white' : 'text-emerald-500 group-hover:text-emerald-600' ?>"></i>
                    <span>Barang Masuk (Beli)</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=outbound" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'outbound' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="arrow-up-right" class="h-4 w-4 <?= $currentPage === 'outbound' ? 'text-white' : 'text-crimson-500 group-hover:text-crimson-600' ?>"></i>
                    <span>Barang Keluar (Jual/Pakai)</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=stock_cards" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'stock_cards' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="history" class="h-4 w-4 <?= $currentPage === 'stock_cards' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Kartu Mutasi Stok</span>
                </a>
            </nav>
        </div>

        <!-- Section: Fitur Cerdas & Audit -->
        <div>
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Cerdas & Audit</p>
            <nav class="mt-2 space-y-1">
                <a 
                    href="<?= BASE_URL ?>index.php?page=forecasting" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'forecasting' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="trending-down" class="h-4 w-4 <?= $currentPage === 'forecasting' ? 'text-white' : 'text-amber-500 group-hover:text-amber-600' ?>"></i>
                    <span>Perkiraan Habis & Expired</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=opname" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'opname' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="clipboard-check" class="h-4 w-4 <?= $currentPage === 'opname' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Stock Opname (Fisik)</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=reports" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'reports' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 <?= $currentPage === 'reports' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Laporan & Ekspor</span>
                </a>
            </nav>
        </div>

        <!-- Section: Master Data & Konfigurasi -->
        <div>
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Master Data</p>
            <nav class="mt-2 space-y-1">
                <a 
                    href="<?= BASE_URL ?>index.php?page=categories" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'categories' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="folder" class="h-4 w-4 <?= $currentPage === 'categories' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Kategori Barang</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=suppliers" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'suppliers' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="truck" class="h-4 w-4 <?= $currentPage === 'suppliers' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Data Supplier</span>
                </a>

                <a 
                    href="<?= BASE_URL ?>index.php?page=locations" 
                    class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'locations' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                >
                    <i data-lucide="map-pin" class="h-4 w-4 <?= $currentPage === 'locations' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                    <span>Lokasi Gudang & Rak</span>
                </a>

                <?php if (is_admin()): ?>
                    <a 
                        href="<?= BASE_URL ?>index.php?page=users" 
                        class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all <?= $currentPage === 'users' ? 'bg-gradient-to-r from-brand-500 to-crimson-600 text-white shadow-md shadow-brand-500/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>"
                    >
                        <i data-lucide="shield-check" class="h-4 w-4 <?= $currentPage === 'users' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' ?>"></i>
                        <span>Hak Akses & Pengguna</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- PWA Install Prompt Banner in Desktop Sidebar -->
    <div class="p-3 border-t border-slate-100" id="pwa-install-container" style="display: none;">
        <div class="rounded-2xl bg-gradient-to-br from-brand-50 to-crimson-50 p-3 border border-brand-100/60 text-center">
            <i data-lucide="smartphone" class="mx-auto h-5 w-5 text-brand-600 mb-1"></i>
            <p class="text-xs font-bold text-slate-800">Pasang Aplikasi PWA</p>
            <p class="text-[10px] text-slate-500 mb-2">Akses instan seperti aplikasi desktop & HP</p>
            <button id="pwa-install-btn" type="button" class="w-full rounded-xl bg-brand-600 py-1.5 text-xs font-semibold text-white shadow hover:bg-brand-700 transition">
                Install Sekarang
            </button>
        </div>
    </div>
</aside>

<!-- Mobile Slide-out Drawer Menu -->
<div 
    x-show="mobileMenuOpen" 
    x-cloak 
    class="no-print relative z-50 md:hidden" 
    role="dialog" 
    aria-modal="true"
>
    <div 
        x-show="mobileMenuOpen" 
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
        @click="mobileMenuOpen = false"
    ></div>

    <div class="fixed inset-0 flex z-50">
        <div 
            x-show="mobileMenuOpen" 
            x-transition:enter="transition ease-in-out duration-200 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="relative flex w-full max-w-xs flex-1 flex-col bg-white pt-5 pb-4 shadow-xl"
        >
            <div class="flex items-center justify-between px-4 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-brand-600 to-crimson-600 text-white font-bold">
                        <i data-lucide="boxes" class="h-5 w-5"></i>
                    </div>
                    <span class="text-base font-black text-slate-900">INVENTA<span class="text-brand-600">.</span>PRO</span>
                </div>
                <button type="button" @click="mobileMenuOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="mt-4 flex-1 overflow-y-auto px-4 space-y-4 text-xs font-semibold">
                <a href="<?= BASE_URL ?>index.php?page=dashboard" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'dashboard' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i> Dashboard
                </a>
                <a href="<?= BASE_URL ?>index.php?page=scanner" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'scanner' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="scan" class="h-4 w-4 text-brand-600"></i> Scan Barcode Cepat
                </a>
                <a href="<?= BASE_URL ?>index.php?page=products" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'products' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="package" class="h-4 w-4"></i> Data Barang & Rak
                </a>
                <a href="<?= BASE_URL ?>index.php?page=inbound" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'inbound' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="arrow-down-left" class="h-4 w-4 text-emerald-500"></i> Barang Masuk
                </a>
                <a href="<?= BASE_URL ?>index.php?page=outbound" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'outbound' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="arrow-up-right" class="h-4 w-4 text-crimson-500"></i> Barang Keluar
                </a>
                <a href="<?= BASE_URL ?>index.php?page=forecasting" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'forecasting' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="trending-down" class="h-4 w-4 text-amber-500"></i> Perkiraan Habis & Expired
                </a>
                <a href="<?= BASE_URL ?>index.php?page=opname" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'opname' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="clipboard-check" class="h-4 w-4"></i> Stock Opname
                </a>
                <a href="<?= BASE_URL ?>index.php?page=reports" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'reports' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="file-spreadsheet" class="h-4 w-4"></i> Laporan & Ekspor
                </a>
                <a href="<?= BASE_URL ?>index.php?page=categories" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'categories' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="folder" class="h-4 w-4"></i> Kategori Barang
                </a>
                <a href="<?= BASE_URL ?>index.php?page=suppliers" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'suppliers' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="truck" class="h-4 w-4"></i> Supplier
                </a>
                <a href="<?= BASE_URL ?>index.php?page=locations" class="flex items-center gap-3 rounded-xl p-2.5 <?= $currentPage === 'locations' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                    <i data-lucide="map-pin" class="h-4 w-4"></i> Lokasi Rak
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation Bar (PWA Phone App Feel) -->
<nav class="no-print md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-slate-200 z-30 flex items-center justify-around py-1.5 px-2">
    <a href="<?= BASE_URL ?>index.php?page=dashboard" class="flex flex-col items-center p-1 text-[10px] font-semibold <?= $currentPage === 'dashboard' ? 'text-brand-600' : 'text-slate-400' ?>">
        <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
        <span>Home</span>
    </a>
    <a href="<?= BASE_URL ?>index.php?page=products" class="flex flex-col items-center p-1 text-[10px] font-semibold <?= $currentPage === 'products' ? 'text-brand-600' : 'text-slate-400' ?>">
        <i data-lucide="package" class="h-5 w-5"></i>
        <span>Barang</span>
    </a>
    
    <!-- Central Fast Scanner Floating Button -->
    <a 
        href="<?= BASE_URL ?>index.php?page=scanner" 
        class="flex flex-col items-center -mt-5 bg-gradient-to-tr from-brand-500 to-crimson-600 text-white p-3 rounded-full shadow-lg shadow-brand-500/30 hover:scale-105 transition"
        title="Scan Barcode"
    >
        <i data-lucide="scan" class="h-6 w-6"></i>
    </a>

    <a href="<?= BASE_URL ?>index.php?page=forecasting" class="flex flex-col items-center p-1 text-[10px] font-semibold <?= $currentPage === 'forecasting' ? 'text-brand-600' : 'text-slate-400' ?>">
        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
        <span>Peringatan</span>
    </a>
    <a href="<?= BASE_URL ?>index.php?page=opname" class="flex flex-col items-center p-1 text-[10px] font-semibold <?= $currentPage === 'opname' ? 'text-brand-600' : 'text-slate-400' ?>">
        <i data-lucide="clipboard-check" class="h-5 w-5"></i>
        <span>Opname</span>
    </a>
</nav>

