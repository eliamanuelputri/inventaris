<?php
$pageTitle = 'Dashboard Inventaris - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        <?php
        $pdo = db();
        $totalItems = 0;
        $totalValuation = 0;
        $totalLowStock = 0;
        $totalExpiringSoon = 0;
        $recentTransactions = [];
        $lowStockProducts = [];

        if ($pdo) {
            // 1. Total Jenis Barang & Total Valuasi Stok
            $stmtVal = $pdo->query("
                SELECT COUNT(*) as total_items, 
                       COALESCE(SUM(current_stock * purchase_price), 0) as total_valuation 
                FROM products
            ");
            $valData = $stmtVal->fetch();
            $totalItems = (int)($valData['total_items'] ?? 0);
            $totalValuation = (float)($valData['total_valuation'] ?? 0);

            // 2. Total Barang di bawah Stok Minimum
            $stmtLow = $pdo->query("SELECT COUNT(*) FROM products WHERE current_stock <= min_stock");
            $totalLowStock = (int)$stmtLow->fetchColumn();

            // 3. Total Batch Mendekati Expired (<= 30 hari)
            $stmtExp = $pdo->query("
                SELECT COUNT(*) 
                FROM product_batches 
                WHERE current_qty > 0 
                  AND expiry_date IS NOT NULL 
                  AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ");
            $totalExpiringSoon = (int)$stmtExp->fetchColumn();

            // 4. Daftar Barang Menipis untuk Quick Restock Table (Limit 5)
            $stmtLowList = $pdo->query("
                SELECT p.*, c.name as category_name, u.symbol as unit_symbol, l.rack_code, l.warehouse_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN units u ON p.unit_id = u.id
                LEFT JOIN locations l ON p.location_id = l.id
                WHERE p.current_stock <= p.min_stock
                ORDER BY (p.current_stock - p.min_stock) ASC
                LIMIT 5
            ");
            $lowStockProducts = $stmtLowList->fetchAll();

            // 5. Transaksi Terbaru (Limit 6)
            $stmtRecent = $pdo->query("
                SELECT t.*, u.name as user_name, s.name as supplier_name
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN suppliers s ON t.supplier_id = s.id
                ORDER BY t.created_at DESC
                LIMIT 6
            ");
            $recentTransactions = $stmtRecent->fetchAll();

            // 6. Data Tren 7 Hari Terakhir untuk Chart
            $days = [];
            $inboundCounts = [];
            $outboundCounts = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $days[] = date('d M', strtotime($d));

                $qIn = $pdo->prepare("SELECT COALESCE(SUM(total_items), 0) FROM transactions WHERE type = 'in' AND transaction_date = ?");
                $qIn->execute([$d]);
                $inboundCounts[] = (int)$qIn->fetchColumn();

                $qOut = $pdo->prepare("SELECT COALESCE(SUM(total_items), 0) FROM transactions WHERE type = 'out' AND transaction_date = ?");
                $qOut->execute([$d]);
                $outboundCounts[] = (int)$qOut->fetchColumn();
            }

            // 7. Komposisi Kategori untuk Donut Chart
            $stmtCat = $pdo->query("
                SELECT c.name, COUNT(p.id) as item_count, COALESCE(SUM(p.current_stock * p.purchase_price), 0) as cat_val
                FROM categories c
                LEFT JOIN products p ON p.category_id = c.id
                GROUP BY c.id
                ORDER BY cat_val DESC
                LIMIT 5
            ");
            $catLabels = [];
            $catValues = [];
            while ($cRow = $stmtCat->fetch()) {
                $catLabels[] = $cRow['name'];
                $catValues[] = (float)$cRow['cat_val'];
            }
        }
        ?>

        <!-- Welcome Banner & Quick Action Buttons -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Ringkasan Inventaris
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Status stok real-time, perkiraan pergerakan barang, dan indikator penting hari ini.
                </p>
            </div>

            <!-- Quick Action Shortcuts (Sortly/Odoo Speed Bar) -->
            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="<?= BASE_URL ?>index.php?page=inbound_create" 
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition"
                >
                    <i data-lucide="arrow-down-left" class="h-4 w-4"></i>
                    <span>Barang Masuk</span>
                </a>
                <a 
                    href="<?= BASE_URL ?>index.php?page=outbound_create" 
                    class="inline-flex items-center gap-1.5 rounded-xl bg-crimson-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-crimson-700 transition"
                >
                    <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                    <span>Barang Keluar</span>
                </a>
                <a 
                    href="<?= BASE_URL ?>index.php?page=opname_create" 
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <i data-lucide="clipboard-check" class="h-4 w-4 text-brand-600"></i>
                    <span>Stock Opname</span>
                </a>
            </div>
        </div>

        <!-- 4 Key Metric Cards (Sortly Visual Style) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            
            <!-- Card 1: Total Valuasi Stok -->
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Nilai Aset</span>
                    <div class="rounded-xl bg-brand-50 p-2.5 text-brand-600">
                        <i data-lucide="coins" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900"><?= format_rupiah($totalValuation) ?></div>
                    <div class="text-xs text-slate-500 mt-0.5">Dihitung dari harga modal beli</div>
                </div>
            </div>

            <!-- Card 2: Total Jenis SKU -->
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Katalog Barang</span>
                    <div class="rounded-xl bg-blue-50 p-2.5 text-blue-600">
                        <i data-lucide="package-2" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900"><?= number_format($totalItems) ?> <span class="text-xs font-semibold text-slate-400">SKU</span></div>
                    <div class="text-xs text-slate-500 mt-0.5">Terdaftar di berbagai rak & gudang</div>
                </div>
            </div>

            <!-- Card 3: Stok Kritis / Menipis -->
            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=low_stock" class="block rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:border-brand-300 hover:shadow-md transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Stok Menipis</span>
                    <div class="rounded-xl bg-orange-50 p-2.5 text-brand-600 group-hover:scale-110 transition">
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black <?= $totalLowStock > 0 ? 'text-brand-600' : 'text-slate-900' ?>">
                        <?= $totalLowStock ?> <span class="text-xs font-semibold text-slate-400">Item</span>
                    </div>
                    <div class="text-xs text-brand-700 font-medium mt-0.5 flex items-center gap-1">
                        <span>Perlu pemesanan ulang (Restock)</span>
                        <i data-lucide="chevron-right" class="h-3 w-3"></i>
                    </div>
                </div>
            </a>

            <!-- Card 4: Mendekati Kedaluwarsa -->
            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=expiring" class="block rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:border-crimson-300 hover:shadow-md transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Mendekati Kedaluwarsa</span>
                    <div class="rounded-xl bg-red-50 p-2.5 text-crimson-600 group-hover:scale-110 transition">
                        <i data-lucide="calendar-x" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black <?= $totalExpiringSoon > 0 ? 'text-crimson-600' : 'text-slate-900' ?>">
                        <?= $totalExpiringSoon ?> <span class="text-xs font-semibold text-slate-400">Batch</span>
                    </div>
                    <div class="text-xs text-crimson-700 font-medium mt-0.5 flex items-center gap-1">
                        <span>Kedaluwarsa dlm ≤ 30 hari</span>
                        <i data-lucide="chevron-right" class="h-3 w-3"></i>
                    </div>
                </div>
            </a>

        </div>

        <!-- Charts Section (Tren Transaksi & Valuasi Kategori) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Chart Bar / Line: Tren 7 Hari -->
            <div class="lg:col-span-2 rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Aktivitas Keluar-Masuk Barang (7 Hari Terakhir)</h2>
                        <p class="text-xs text-slate-400">Volume item masuk vs pemakaian harian</p>
                    </div>
                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">Unit Transaksi</span>
                </div>
                <div class="h-64">
                    <canvas id="trxChart"></canvas>
                </div>
            </div>

            <!-- Chart Doughnut: Valuasi per Kategori -->
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Distribusi Nilai Aset</h2>
                        <p class="text-xs text-slate-400">Komposisi modal per kategori</p>
                    </div>
                </div>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Two Data Tables: Restock Alert & Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Table 1: Peringatan & Rekomendasi Restock Cerdas -->
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Barang Perlu Segera Restock</h2>
                        <p class="text-xs text-slate-400">Dilengkapi analisis estimasi tanggal habis</p>
                    </div>
                    <a href="<?= BASE_URL ?>index.php?page=forecasting" class="text-xs font-bold text-brand-600 hover:text-brand-700">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    <?php if (empty($lowStockProducts)): ?>
                        <div class="py-8 text-center text-slate-400 text-xs">
                            <i data-lucide="check-circle" class="mx-auto mb-1 h-6 w-6 text-emerald-500"></i>
                            Bagus! Tidak ada barang yang berada di bawah batas minimum.
                        </div>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $p): 
                            $forecast = calculate_product_forecast((int)$p['id'], (int)$p['current_stock'], (int)$p['min_stock']);
                        ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-orange-50/50 border border-orange-100 hover:bg-orange-50 transition">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-lg bg-orange-100 flex items-center justify-center font-bold text-brand-700 text-xs shrink-0">
                                        <?= strtoupper(substr($p['sku'], 0, 3)) ?>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 leading-tight">
                                            <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $p['id'] ?>" class="hover:text-brand-600">
                                                <?= sanitize($p['name']) ?>
                                            </a>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            <span>SKU: <?= sanitize($p['sku']) ?></span> &bull; 
                                            <span>Lokasi: <?= sanitize($p['rack_code'] ?? 'Belum Ditata') ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-black text-crimson-600">
                                        Sisa: <?= $p['current_stock'] ?> <?= sanitize($p['unit_symbol'] ?? '') ?>
                                    </div>
                                    <div class="text-[10px] font-semibold text-slate-500">
                                        Habis dlm: <span class="text-brand-700"><?= $forecast['days_remaining_text'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table 2: 5 Transaksi Terbaru -->
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Transaksi Terbaru</h2>
                        <p class="text-xs text-slate-400">Mutasi barang masuk & keluar terakhir</p>
                    </div>
                    <a href="<?= BASE_URL ?>index.php?page=stock_cards" class="text-xs font-bold text-brand-600 hover:text-brand-700">
                        Mutasi Lengkap &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    <?php if (empty($recentTransactions)): ?>
                        <div class="py-8 text-center text-slate-400 text-xs">
                            Belum ada riwayat transaksi barang.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentTransactions as $tx): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 hover:bg-white transition">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl flex items-center justify-center <?= $tx['type'] === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-crimson-600' ?>">
                                        <i data-lucide="<?= $tx['type'] === 'in' ? 'arrow-down-left' : 'arrow-up-right' ?>" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 leading-tight">
                                            <?= sanitize($tx['transaction_no']) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            <?= format_date($tx['transaction_date']) ?> &bull; 
                                            <span><?= $tx['type'] === 'in' ? ('Supplier: ' . sanitize($tx['supplier_name'] ?? '-')) : ('Tujuan: ' . sanitize($tx['destination'] ?? '-')) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-black <?= $tx['type'] === 'in' ? 'text-emerald-600' : 'text-crimson-600' ?>">
                                        <?= $tx['type'] === 'in' ? '+' : '-' ?><?= number_format($tx['total_items']) ?> item
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        <?= format_rupiah($tx['total_amount']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Transaction Activity Chart
    const ctxTrx = document.getElementById('trxChart');
    if (ctxTrx) {
        new Chart(ctxTrx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($days ?? []) ?>,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: <?= json_encode($inboundCounts ?? []) ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                    },
                    {
                        label: 'Barang Keluar',
                        data: <?= json_encode($outboundCounts ?? []) ?>,
                        backgroundColor: '#ea580c',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11 } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. Category Donut Chart
    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($catLabels ?? []) ?>,
                datasets: [{
                    data: <?= json_encode($catValues ?? []) ?>,
                    backgroundColor: ['#ea580c', '#dc2626', '#f59e0b', '#3b82f6', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                },
                cutout: '70%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

