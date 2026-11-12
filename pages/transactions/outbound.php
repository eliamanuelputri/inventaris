<?php
$pageTitle = 'Barang Keluar (Penjualan / Pemakaian) - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$search = sanitize($_GET['q'] ?? '');
$reasonFilter = sanitize($_GET['reason'] ?? '');
$startDate = sanitize($_GET['start'] ?? '');
$endDate = sanitize($_GET['end'] ?? '');

$where = ["t.type = 'out'"];
$params = [];

if (!empty($search)) {
    $where[] = "(t.transaction_no LIKE :q OR t.destination LIKE :q OR t.reference_no LIKE :q)";
    $params['q'] = "%{$search}%";
}

if (!empty($reasonFilter)) {
    $where[] = "t.reason = :reason";
    $params['reason'] = $reasonFilter;
}

if (!empty($startDate)) {
    $where[] = "t.transaction_date >= :sdate";
    $params['sdate'] = $startDate;
}

if (!empty($endDate)) {
    $where[] = "t.transaction_date <= :edate";
    $params['edate'] = $endDate;
}

$sql = "
    SELECT t.*, u.name as user_name
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY t.transaction_date DESC, t.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Barang Keluar (Outbound / Distribusi)
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Catat penjualan, pemakaian operasional internal, atau retur dengan pengurangan stok otomatis.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    onclick="exportTableToExcel('outbound-table', 'Laporan_Barang_Keluar')" 
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i>
                    <span>Export Excel</span>
                </button>

                <a 
                    href="<?= BASE_URL ?>index.php?page=outbound_create" 
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-crimson-600 to-rose-700 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-crimson-500/20 hover:from-crimson-700 hover:to-rose-800 transition"
                >
                    <i data-lucide="minus" class="h-4 w-4"></i>
                    <span>Catat Barang Keluar</span>
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-100">
            <form action="<?= BASE_URL ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <input type="hidden" name="page" value="outbound">

                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400"></i>
                    <input 
                        type="text" 
                        name="q" 
                        value="<?= sanitize($search) ?>" 
                        placeholder="No. Transaksi / Tujuan..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                </div>

                <div>
                    <select name="reason" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">Semua Peruntukan Keluar</option>
                        <option value="penjualan" <?= $reasonFilter === 'penjualan' ? 'selected' : '' ?>>Penjualan Konsumen</option>
                        <option value="pemakaian_internal" <?= $reasonFilter === 'pemakaian_internal' ? 'selected' : '' ?>>Pemakaian Internal / Divisi</option>
                        <option value="kerusakan" <?= $reasonFilter === 'kerusakan' ? 'selected' : '' ?>>Barang Rusak / Afkir</option>
                        <option value="retur" <?= $reasonFilter === 'retur' ? 'selected' : '' ?>>Retur ke Supplier</option>
                    </select>
                </div>

                <div>
                    <input type="date" name="start" value="<?= $startDate ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>

                <div class="flex gap-2">
                    <input type="date" name="end" value="<?= $endDate ?>" onchange="this.form.submit()" class="flex-1 rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <?php if (!empty($search) || !empty($reasonFilter) || !empty($startDate) || !empty($endDate)): ?>
                        <a href="<?= BASE_URL ?>index.php?page=outbound" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
            <?php if (empty($transactions)): ?>
                <div class="py-12 text-center text-slate-400 text-xs">
                    <i data-lucide="package-minus" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                    Belum ada riwayat transaksi barang keluar sesuai kriteria.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table id="outbound-table" class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">No. Transaksi</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Peruntukan</th>
                                <th class="px-4 py-3">Tujuan / Penerima</th>
                                <th class="px-4 py-3 text-right">Total Item</th>
                                <th class="px-4 py-3 text-right">Nilai Barang</th>
                                <th class="px-4 py-3">Petugas</th>
                                <th class="px-4 py-3 text-center">Rincian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" x-data="{ expanded: null }">
                            <?php foreach ($transactions as $tx): 
                                $stmtItems = $pdo->prepare("
                                    SELECT ti.*, p.name as product_name, p.sku, u.symbol as unit_symbol,
                                           pb.batch_no, pb.expiry_date
                                    FROM transaction_items ti
                                    JOIN products p ON ti.product_id = p.id
                                    LEFT JOIN units u ON p.unit_id = u.id
                                    LEFT JOIN product_batches pb ON ti.batch_id = pb.id
                                    WHERE ti.transaction_id = :tid
                                ");
                                $stmtItems->execute(['tid' => $tx['id']]);
                                $tItems = $stmtItems->fetchAll();
                            ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3 font-mono font-bold text-crimson-700">
                                        <?= sanitize($tx['transaction_no']) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= format_date($tx['transaction_date']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold <?= $tx['reason'] === 'penjualan' ? 'bg-emerald-100 text-emerald-800' : ($tx['reason'] === 'kerusakan' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') ?>">
                                            <?= sanitize(ucwords(str_replace('_', ' ', $tx['reason']))) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-800"><?= sanitize($tx['destination'] ?: 'Internal Operasional') ?></td>
                                    <td class="px-4 py-3 text-right font-black text-crimson-600">
                                        -<?= number_format($tx['total_items']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">
                                        <?= format_rupiah($tx['total_amount']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 font-medium"><?= sanitize($tx['user_name'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <button 
                                            type="button" 
                                            @click="expanded === <?= $tx['id'] ?> ? expanded = null : expanded = <?= $tx['id'] ?>"
                                            class="inline-flex items-center gap-1 rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-600 px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition"
                                        >
                                            <span x-text="expanded === <?= $tx['id'] ?> ? 'Tutup' : 'Lihat Item'"></span>
                                            <i data-lucide="chevron-down" class="h-3 w-3 transition-transform" :class="expanded === <?= $tx['id'] ?> ? 'rotate-180' : ''"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Expandable Details Row -->
                                <tr x-show="expanded === <?= $tx['id'] ?>" x-cloak class="bg-slate-50/70 border-b border-slate-200">
                                    <td colspan="8" class="p-4">
                                        <div class="rounded-xl bg-white p-3 border border-slate-200 shadow-sm space-y-2">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex justify-between">
                                                <span>Item Barang Keluar: #<?= sanitize($tx['transaction_no']) ?></span>
                                                <?php if (!empty($tx['notes'])): ?>
                                                    <span class="text-slate-400 font-normal">Catatan: <?= sanitize($tx['notes']) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-slate-50 text-[10px] text-slate-400 font-bold">
                                                    <tr>
                                                        <th class="p-2">SKU</th>
                                                        <th class="p-2">Nama Barang</th>
                                                        <th class="p-2">Batch Digunakan</th>
                                                        <th class="p-2 text-right">Kuantitas</th>
                                                        <th class="p-2 text-right">Harga Satuan</th>
                                                        <th class="p-2 text-right">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <?php foreach ($tItems as $it): ?>
                                                        <tr>
                                                            <td class="p-2 font-mono font-bold text-slate-700"><?= sanitize($it['sku']) ?></td>
                                                            <td class="p-2 font-semibold text-slate-900"><?= sanitize($it['product_name']) ?></td>
                                                            <td class="p-2 font-mono text-slate-600"><?= sanitize($it['batch_no'] ?? 'Batch Bebas') ?></td>
                                                            <td class="p-2 text-right font-bold text-crimson-600">-<?= number_format($it['qty']) ?> <?= sanitize($it['unit_symbol'] ?? '') ?></td>
                                                            <td class="p-2 text-right"><?= format_rupiah($it['unit_price']) ?></td>
                                                            <td class="p-2 text-right font-black text-slate-900"><?= format_rupiah($it['subtotal']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
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
function exportTableToExcel(tableId, filename = 'Barang_Keluar') {
    const table = document.getElementById(tableId);
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, { sheet: "Barang Keluar" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

