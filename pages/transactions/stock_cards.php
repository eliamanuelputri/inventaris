<?php
$pageTitle = 'Kartu Mutasi Stok - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$productIdFilter = !empty($_GET['prod']) ? (int)$_GET['prod'] : 0;
$startDate = sanitize($_GET['start'] ?? '');
$endDate = sanitize($_GET['end'] ?? '');

$products = $pdo ? $pdo->query("SELECT id, sku, name FROM products ORDER BY name ASC")->fetchAll() : [];

$where = ["1=1"];
$params = [];

if ($productIdFilter > 0) {
    $where[] = "m.product_id = :pid";
    $params['pid'] = $productIdFilter;
}

if (!empty($startDate)) {
    $where[] = "m.date >= :sdate";
    $params['sdate'] = $startDate;
}

if (!empty($endDate)) {
    $where[] = "m.date <= :edate";
    $params['edate'] = $endDate;
}

$sql = "
    SELECT m.*, p.sku, p.name as product_name, u.symbol as unit_symbol,
           usr.name as user_name, l.warehouse_name, l.rack_code
    FROM stock_mutations m
    JOIN products p ON m.product_id = p.id
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN users usr ON m.user_id = usr.id
    LEFT JOIN locations l ON m.location_id = l.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY m.date DESC, m.id DESC
    LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mutations = $stmt->fetchAll();
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Kartu Mutasi Stok (Ledger Audit)
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Rekam jejak setiap pergerakan fisik barang: pembelian masuk, pemakaian keluar, dan penyesuaian opname.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    onclick="exportTableToExcel('mutations-table', 'Kartu_Mutasi_Stok')" 
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i>
                    <span>Export Excel</span>
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-100">
            <form action="<?= BASE_URL ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="hidden" name="page" value="stock_cards">

                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Pilih Barang</label>
                    <select name="prod" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="0">Semua Barang</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $productIdFilter == $p['id'] ? 'selected' : '' ?>>
                                <?= sanitize($p['sku']) ?> - <?= sanitize($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Dari Tanggal</label>
                    <input type="date" name="start" value="<?= $startDate ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>

                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end" value="<?= $endDate ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>
                    <?php if ($productIdFilter > 0 || !empty($startDate) || !empty($endDate)): ?>
                        <a href="<?= BASE_URL ?>index.php?page=stock_cards" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
            <?php if (empty($mutations)): ?>
                <div class="py-12 text-center text-slate-400 text-xs">
                    <i data-lucide="history" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                    Belum ada catatan mutasi stok sesuai kriteria pencarian.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table id="mutations-table" class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">SKU & Barang</th>
                                <th class="px-4 py-3">Tipe Mutasi</th>
                                <th class="px-4 py-3">No. Dokumen</th>
                                <th class="px-4 py-3 text-right text-emerald-600 font-bold">Masuk (+)</th>
                                <th class="px-4 py-3 text-right text-crimson-600 font-bold">Keluar (-)</th>
                                <th class="px-4 py-3 text-right font-black text-slate-900">Saldo Akhir</th>
                                <th class="px-4 py-3">Keterangan</th>
                                <th class="px-4 py-3">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($mutations as $m): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3 whitespace-nowrap"><?= format_date($m['date']) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">
                                            <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $m['product_id'] ?>" class="hover:text-brand-600">
                                                <?= sanitize($m['product_name']) ?>
                                            </a>
                                        </div>
                                        <div class="text-[10px] font-mono text-brand-700 font-bold"><?= sanitize($m['sku']) ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600">
                                            <?= sanitize(str_replace('_', ' ', $m['reference_type'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono font-semibold text-slate-800"><?= sanitize($m['reference_no']) ?></td>
                                    <td class="px-4 py-3 text-right font-bold text-emerald-600">
                                        <?= $m['qty_in'] > 0 ? ('+' . number_format($m['qty_in'])) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-crimson-600">
                                        <?= $m['qty_out'] > 0 ? ('-' . number_format($m['qty_out'])) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-slate-900 text-sm">
                                        <?= number_format($m['stock_after']) ?> <span class="text-[10px] font-normal text-slate-400"><?= sanitize($m['unit_symbol'] ?? '') ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500"><?= sanitize($m['notes'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-slate-500 font-medium"><?= sanitize($m['user_name'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
function exportTableToExcel(tableId, filename = 'Mutasi_Stok') {
    const table = document.getElementById(tableId);
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, { sheet: "Mutasi Stok" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

