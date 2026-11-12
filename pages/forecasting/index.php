<?php
$pageTitle = 'Peringatan & Perkiraan Habis Stok - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$filter = sanitize($_GET['filter'] ?? 'all'); // all, low_stock, fast_burn, expiring

// 1. Ambil semua produk dan hitung forecast-nya
$products = $pdo ? $pdo->query("
    SELECT p.*, c.name as category_name, u.symbol as unit_symbol, l.rack_code, l.warehouse_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN locations l ON p.location_id = l.id
    ORDER BY p.name ASC
")->fetchAll() : [];

$forecastList = [];
$countLowStock = 0;
$countFastBurn = 0;

foreach ($products as $p) {
    $f = calculate_product_forecast((int)$p['id'], (int)$p['current_stock'], (int)$p['min_stock']);
    $item = array_merge($p, [
        'forecast' => $f,
        'is_low' => ($p['current_stock'] <= $p['min_stock'])
    ]);

    if ($item['is_low']) $countLowStock++;
    if ($f['days_remaining'] <= 14 && $f['burn_rate'] > 0) $countFastBurn++;

    // Terapkan filter tampilan
    if ($filter === 'low_stock' && !$item['is_low']) continue;
    if ($filter === 'fast_burn' && ($f['days_remaining'] > 14 || $f['burn_rate'] <= 0)) continue;

    $forecastList[] = $item;
}

// 2. Ambil data batch untuk Expiry Tracking
$stmtExp = $pdo ? $pdo->query("
    SELECT pb.*, p.sku, p.name as product_name, u.symbol as unit_symbol, l.rack_code, l.warehouse_name
    FROM product_batches pb
    JOIN products p ON pb.product_id = p.id
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN locations l ON pb.location_id = l.id
    WHERE pb.current_qty > 0 AND pb.expiry_date IS NOT NULL
    ORDER BY pb.expiry_date ASC
") : null;
$batches = $stmtExp ? $stmtExp->fetchAll() : [];

$countExpiringSoon = 0;
foreach ($batches as $b) {
    $st = get_expiry_status($b['expiry_date']);
    if ($st['status'] === 'critical' || $st['status'] === 'expired') {
        $countExpiringSoon++;
    }
}
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Peringatan & Perkiraan Habis (Forecasting)
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Prediksi kehabisan barang berbasis <strong>Moving Burn Rate 30 Hari</strong> dan alarm kedaluwarsa batch.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    onclick="exportTableToExcel('forecast-table', 'Analisis_Perkiraan_Habis_Stok')" 
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i>
                    <span>Export Excel Analisis</span>
                </button>
            </div>
        </div>

        <!-- 3 Key Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            
            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=low_stock" class="p-5 rounded-2xl bg-white border <?= $filter === 'low_stock' ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-100' ?> shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Di Bawah Stok Minimum</span>
                    <div class="rounded-xl bg-orange-100 p-2 text-brand-600">
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-brand-600"><?= $countLowStock ?> <span class="text-xs font-semibold text-slate-400">Item</span></div>
                    <div class="text-xs text-slate-500 mt-0.5">Sisa stok fisik $\le$ batas minimum</div>
                </div>
            </a>

            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=fast_burn" class="p-5 rounded-2xl bg-white border <?= $filter === 'fast_burn' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-100' ?> shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Habis dlm ≤ 14 Hari</span>
                    <div class="rounded-xl bg-amber-100 p-2 text-amber-600">
                        <i data-lucide="trending-down" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-amber-600"><?= $countFastBurn ?> <span class="text-xs font-semibold text-slate-400">Item</span></div>
                    <div class="text-xs text-slate-500 mt-0.5">Berdasarkan burn rate pemakaian 30 hari</div>
                </div>
            </a>

            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=expiring" class="p-5 rounded-2xl bg-white border <?= $filter === 'expiring' ? 'border-crimson-500 ring-2 ring-crimson-500/20' : 'border-slate-100' ?> shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kritis Kedaluwarsa</span>
                    <div class="rounded-xl bg-red-100 p-2 text-crimson-600">
                        <i data-lucide="calendar-x" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-crimson-600"><?= $countExpiringSoon ?> <span class="text-xs font-semibold text-slate-400">Batch</span></div>
                    <div class="text-xs text-slate-500 mt-0.5">Expired atau kedaluwarsa dlm $\le$ 30 hari</div>
                </div>
            </a>

        </div>

        <!-- Filter Tab Pills -->
        <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-2">
            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=all" class="rounded-xl px-4 py-2 text-xs font-bold transition whitespace-nowrap <?= $filter === 'all' ? 'bg-slate-900 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                Semua Barang & Prediksi
            </a>
            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=low_stock" class="rounded-xl px-4 py-2 text-xs font-bold transition whitespace-nowrap <?= $filter === 'low_stock' ? 'bg-brand-600 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                ⚠️ Stok Menipis (<?= $countLowStock ?>)
            </a>
            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=fast_burn" class="rounded-xl px-4 py-2 text-xs font-bold transition whitespace-nowrap <?= $filter === 'fast_burn' ? 'bg-amber-600 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                📉 Cepat Habis $\le$ 14 Hari (<?= $countFastBurn ?>)
            </a>
            <a href="<?= BASE_URL ?>index.php?page=forecasting&filter=expiring" class="rounded-xl px-4 py-2 text-xs font-bold transition whitespace-nowrap <?= $filter === 'expiring' ? 'bg-crimson-600 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                📅 Batch Dekat Expired (<?= $countExpiringSoon ?>)
            </a>
        </div>

        <!-- SECTION 1: TABEL PERKIRAAN HABIS (BURN RATE FORECASTING) -->
        <?php if ($filter !== 'expiring'): ?>
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100 mb-8">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Analisis Perkiraan Stok Habis (Burn Rate 30 Hari)</h2>
                        <p class="text-[11px] text-slate-400">Formula: Burn Rate = $\frac{\sum \text{Qty Keluar 30 Hari}}{30}$ &bull; Estimasi Sisa Hari = $\frac{\text{Sisa Stok}}{\text{Burn Rate}}$</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="forecast-table" class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">SKU & Barang</th>
                                <th class="px-4 py-3">Lokasi Rak</th>
                                <th class="px-4 py-3 text-right">Stok Min</th>
                                <th class="px-4 py-3 text-right">Sisa Stok</th>
                                <th class="px-4 py-3 text-right">Keluar 30 Hari</th>
                                <th class="px-4 py-3 text-right">Burn Rate/Hari</th>
                                <th class="px-4 py-3 text-center">Estimasi Habis</th>
                                <th class="px-4 py-3 text-center">Perkiraan Tanggal</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($forecastList as $item): 
                                $f = $item['forecast'];
                            ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">
                                            <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $item['id'] ?>" class="hover:text-brand-600">
                                                <?= sanitize($item['name']) ?>
                                            </a>
                                        </div>
                                        <div class="font-mono text-[10px] text-brand-700 font-bold"><?= sanitize($item['sku']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">
                                        <?= sanitize($item['rack_code'] ?? '-') ?>
                                        <span class="block text-[10px] text-slate-400"><?= sanitize($item['warehouse_name'] ?? '') ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-400"><?= $item['min_stock'] ?></td>
                                    <td class="px-4 py-3 text-right font-black <?= $item['is_low'] ? 'text-crimson-600' : 'text-slate-900' ?>">
                                        <?= number_format($item['current_stock']) ?> <span class="text-[10px] font-normal text-slate-400"><?= sanitize($item['unit_symbol'] ?? '') ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700">
                                        <?= $f['total_out_30_days'] ?> unit
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-brand-700">
                                        <?= $f['burn_rate_formatted'] ?>
                                    </td>
                                    <td class="px-4 py-3 text-center font-black <?= $f['status'] === 'critical' ? 'text-crimson-600' : ($f['status'] === 'warning' ? 'text-amber-600' : 'text-slate-700') ?>">
                                        <?= $f['days_remaining_text'] ?>
                                    </td>
                                    <td class="px-4 py-3 text-center font-medium text-slate-600 whitespace-nowrap">
                                        <?= $f['runout_date_formatted'] ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($f['status'] === 'critical'): ?>
                                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-bold text-crimson-700">
                                                🔴 Kritis
                                            </span>
                                        <?php elseif ($f['status'] === 'warning'): ?>
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-700">
                                                🟡 Waspada
                                            </span>
                                        <?php else: ?>
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                                🟢 Aman
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a 
                                            href="<?= BASE_URL ?>index.php?page=inbound_create&product_id=<?= $item['id'] ?>" 
                                            class="inline-flex items-center gap-1 rounded-xl bg-brand-50 hover:bg-brand-100 px-2.5 py-1 text-[11px] font-bold text-brand-700 transition"
                                            title="Buat Pembelian Restock Masuk"
                                        >
                                            <i data-lucide="plus-circle" class="h-3.5 w-3.5"></i>
                                            <span>Restock</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- SECTION 2: TABEL PELACAKAN KEDALUWARSA BATCH -->
        <?php if ($filter === 'all' || $filter === 'expiring'): ?>
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Pelacakan Kedaluwarsa Batch & Lot (Expiry Control)</h2>
                        <p class="text-[11px] text-slate-400">Memberi sinyal dini untuk produk yang mendekati tanggal kedaluwarsa</p>
                    </div>
                </div>

                <?php if (empty($batches)): ?>
                    <div class="py-8 text-center text-slate-400 text-xs">
                        Tidak ada batch bertanggal kedaluwarsa yang tersimpan.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3">No. Batch</th>
                                    <th class="px-4 py-3">Nama Barang</th>
                                    <th class="px-4 py-3">Lokasi Rak</th>
                                    <th class="px-4 py-3 text-right">Sisa Qty Fisik</th>
                                    <th class="px-4 py-3">Tanggal Expired</th>
                                    <th class="px-4 py-3 text-center">Sisa Hari Kalender</th>
                                    <th class="px-4 py-3 text-center">Status Kedaluwarsa</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($batches as $b): 
                                    $exp = get_expiry_status($b['expiry_date']);
                                    if ($filter === 'expiring' && !in_array($exp['status'], ['critical', 'expired', 'warning'])) continue;
                                ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-4 py-3 font-mono font-bold text-slate-800"><?= sanitize($b['batch_no']) ?></td>
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-slate-900"><?= sanitize($b['product_name']) ?></div>
                                            <div class="text-[10px] font-mono text-slate-400"><?= sanitize($b['sku']) ?></div>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-700"><?= sanitize($b['rack_code'] ?? '-') ?></td>
                                        <td class="px-4 py-3 text-right font-black text-slate-900"><?= number_format($b['current_qty']) ?> <?= sanitize($b['unit_symbol'] ?? '') ?></td>
                                        <td class="px-4 py-3 font-medium whitespace-nowrap"><?= format_date($b['expiry_date']) ?></td>
                                        <td class="px-4 py-3 text-center font-bold">
                                            <?= $exp['days_left'] < 0 ? abs($exp['days_left']) . ' hari lewat' : $exp['days_left'] . ' hari lagi' ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold <?= $exp['badge_class'] ?>">
                                                <?= $exp['label'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<script>
function exportTableToExcel(tableId, filename = 'Analisis_Forecasting') {
    const table = document.getElementById(tableId);
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, { sheet: "Forecasting Stok" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

