<?php
$pageTitle = 'Barang Masuk (Pembelian) - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$search = sanitize($_GET['q'] ?? '');
$supplierFilter = !empty($_GET['sup']) ? (int)$_GET['sup'] : 0;
$startDate = sanitize($_GET['start'] ?? '');
$endDate = sanitize($_GET['end'] ?? '');

$suppliers = $pdo ? $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll() : [];

$where = ["t.type = 'in'"];
$params = [];

if (!empty($search)) {
    $where[] = "(t.transaction_no LIKE :q OR t.reference_no LIKE :q OR s.name LIKE :q)";
    $params['q'] = "%{$search}%";
}

if ($supplierFilter > 0) {
    $where[] = "t.supplier_id = :sup";
    $params['sup'] = $supplierFilter;
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
    SELECT t.*, s.name as supplier_name, s.phone as supplier_phone, u.name as user_name
    FROM transactions t
    LEFT JOIN suppliers s ON t.supplier_id = s.id
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
                    Barang Masuk (Inbound / Pembelian)
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Catat penerimaan barang dari pemasok, nomor batch, tanggal kedaluwarsa, dan penambahan stok otomatis.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    onclick="exportTableToExcel('inbound-table', 'Laporan_Barang_Masuk')" 
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i>
                    <span>Export Excel</span>
                </button>

                <a 
                    href="<?= BASE_URL ?>index.php?page=inbound_create" 
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-emerald-500/20 hover:from-emerald-700 hover:to-teal-800 transition"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Catat Barang Masuk</span>
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-100">
            <form action="<?= BASE_URL ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <input type="hidden" name="page" value="inbound">

                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400"></i>
                    <input 
                        type="text" 
                        name="q" 
                        value="<?= sanitize($search) ?>" 
                        placeholder="No. Transaksi / No. PO / Supplier..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                </div>

                <div>
                    <select name="sup" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="0">Semua Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $supplierFilter == $s['id'] ? 'selected' : '' ?>>
                                <?= sanitize($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <input type="date" name="start" value="<?= $startDate ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500" placeholder="Dari Tanggal">
                </div>

                <div class="flex gap-2">
                    <input type="date" name="end" value="<?= $endDate ?>" onchange="this.form.submit()" class="flex-1 rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500" placeholder="Sampai Tanggal">
                    <?php if (!empty($search) || $supplierFilter > 0 || !empty($startDate) || !empty($endDate)): ?>
                        <a href="<?= BASE_URL ?>index.php?page=inbound" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Transactions Table (Odoo Style) -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
            <?php if (empty($transactions)): ?>
                <div class="py-12 text-center text-slate-400 text-xs">
                    <i data-lucide="inbox" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                    Belum ada riwayat transaksi barang masuk sesuai kriteria.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table id="inbound-table" class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">No. Transaksi</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Pemasok / Supplier</th>
                                <th class="px-4 py-3">No. Faktur / PO</th>
                                <th class="px-4 py-3 text-right">Total Item</th>
                                <th class="px-4 py-3 text-right">Nilai Pembelian</th>
                                <th class="px-4 py-3">Petugas</th>
                                <th class="px-4 py-3 text-center">Rincian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" x-data="{ expanded: null }">
                            <?php foreach ($transactions as $tx): 
                                // Ambil items untuk transaksi ini
                                $stmtItems = $pdo->prepare("
                                    SELECT ti.*, p.name as product_name, p.sku, u.symbol as unit_symbol,
                                           pb.batch_no, pb.expiry_date, l.rack_code, l.warehouse_name
                                    FROM transaction_items ti
                                    JOIN products p ON ti.product_id = p.id
                                    LEFT JOIN units u ON p.unit_id = u.id
                                    LEFT JOIN product_batches pb ON ti.batch_id = pb.id
                                    LEFT JOIN locations l ON ti.location_id = l.id
                                    WHERE ti.transaction_id = :tid
                                ");
                                $stmtItems->execute(['tid' => $tx['id']]);
                                $tItems = $stmtItems->fetchAll();
                            ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3 font-mono font-bold text-emerald-700">
                                        <?= sanitize($tx['transaction_no']) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= format_date($tx['transaction_date']) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900"><?= sanitize($tx['supplier_name'] ?? 'Supplier Langsung') ?></div>
                                        <?php if (!empty($tx['supplier_phone'])): ?>
                                            <div class="text-[10px] text-slate-400"><?= sanitize($tx['supplier_phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-slate-600"><?= sanitize($tx['reference_no'] ?: '-') ?></td>
                                    <td class="px-4 py-3 text-right font-black text-emerald-600">
                                        +<?= number_format($tx['total_items']) ?>
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
                                                <span>Item Barang Masuk: #<?= sanitize($tx['transaction_no']) ?></span>
                                                <?php if (!empty($tx['notes'])): ?>
                                                    <span class="text-slate-400 font-normal">Catatan: <?= sanitize($tx['notes']) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-slate-50 text-[10px] text-slate-400 font-bold">
                                                    <tr>
                                                        <th class="p-2">SKU</th>
                                                        <th class="p-2">Nama Barang</th>
                                                        <th class="p-2">Nomor Batch & Expiry</th>
                                                        <th class="p-2">Penempatan Rak</th>
                                                        <th class="p-2 text-right">Kuantitas</th>
                                                        <th class="p-2 text-right">Harga Beli</th>
                                                        <th class="p-2 text-right">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <?php foreach ($tItems as $it): ?>
                                                        <tr>
                                                            <td class="p-2 font-mono font-bold text-slate-700"><?= sanitize($it['sku']) ?></td>
                                                            <td class="p-2 font-semibold text-slate-900"><?= sanitize($it['product_name']) ?></td>
                                                            <td class="p-2">
                                                                <?php if (!empty($it['batch_no'])): ?>
                                                                    <span class="font-mono text-slate-700"><?= sanitize($it['batch_no']) ?></span>
                                                                    <?php if (!empty($it['expiry_date'])): ?>
                                                                        <span class="text-[10px] text-slate-400 block">Exp: <?= format_date($it['expiry_date']) ?></span>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="text-slate-400">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="p-2 text-slate-600"><?= sanitize($it['rack_code'] ?? '-') ?></td>
                                                            <td class="p-2 text-right font-bold text-emerald-700">+<?= number_format($it['qty']) ?> <?= sanitize($it['unit_symbol'] ?? '') ?></td>
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
function exportTableToExcel(tableId, filename = 'Barang_Masuk') {
    const table = document.getElementById(tableId);
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, { sheet: "Barang Masuk" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

