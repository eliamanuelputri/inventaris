<?php
$pageTitle = 'Laporan & Unduh Data - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$reportType = sanitize($_GET['type'] ?? 'stock_valuation'); // stock_valuation, inbound_summary, outbound_summary, opname_summary
$startDate = sanitize($_GET['start'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end'] ?? date('Y-m-d'));

$results = [];
$reportTitle = 'Laporan Inventaris';

if ($pdo) {
    if ($reportType === 'stock_valuation') {
        $reportTitle = 'Laporan Valuasi Aset & Sisa Stok Fisik';
        $stmt = $pdo->query("
            SELECT p.*, c.name as category_name, u.symbol as unit_symbol, l.rack_code, l.warehouse_name,
                   (p.current_stock * p.purchase_price) as valuation
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN units u ON p.unit_id = u.id
            LEFT JOIN locations l ON p.location_id = l.id
            ORDER BY c.name ASC, p.name ASC
        ");
        $results = $stmt->fetchAll();

    } elseif ($reportType === 'inbound_summary') {
        $reportTitle = "Laporan Rekapitulasi Pembelian (Barang Masuk): {$startDate} s/d {$endDate}";
        $stmt = $pdo->prepare("
            SELECT ti.*, t.transaction_no, t.transaction_date, t.reference_no, s.name as supplier_name,
                   p.sku, p.name as product_name, u.symbol as unit_symbol
            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN suppliers s ON t.supplier_id = s.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE t.type = 'in' AND t.transaction_date BETWEEN :sdate AND :edate
            ORDER BY t.transaction_date DESC, t.id DESC
        ");
        $stmt->execute(['sdate' => $startDate, 'edate' => $endDate]);
        $results = $stmt->fetchAll();

    } elseif ($reportType === 'outbound_summary') {
        $reportTitle = "Laporan Rekapitulasi Distribusi (Barang Keluar): {$startDate} s/d {$endDate}";
        $stmt = $pdo->prepare("
            SELECT ti.*, t.transaction_no, t.transaction_date, t.reason, t.destination,
                   p.sku, p.name as product_name, u.symbol as unit_symbol
            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE t.type = 'out' AND t.transaction_date BETWEEN :sdate AND :edate
            ORDER BY t.transaction_date DESC, t.id DESC
        ");
        $stmt->execute(['sdate' => $startDate, 'edate' => $endDate]);
        $results = $stmt->fetchAll();

    } elseif ($reportType === 'opname_summary') {
        $reportTitle = "Laporan Rekapitulasi Audit Stock Opname: {$startDate} s/d {$endDate}";
        $stmt = $pdo->prepare("
            SELECT oi.*, o.opname_no, o.audit_date, o.status,
                   p.sku, p.name as product_name, u.symbol as unit_symbol, l.rack_code
            FROM stock_opname_items oi
            JOIN stock_opnames o ON oi.opname_id = o.id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN units u ON p.unit_id = u.id
            LEFT JOIN locations l ON oi.location_id = l.id
            WHERE o.audit_date BETWEEN :sdate AND :edate
            ORDER BY o.audit_date DESC, o.id DESC
        ");
        $stmt->execute(['sdate' => $startDate, 'edate' => $endDate]);
        $results = $stmt->fetchAll();
    }
}
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header & Action Buttons -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between no-print">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Pusat Laporan & Ekspor Data
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Cetak dokumen resmi PDF ber-kop atau unduh spreadsheet Excel per periode transaksi.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Export Excel -->
                <button 
                    type="button" 
                    onclick="exportTableToExcel('report-table', '<?= str_replace(' ', '_', $reportTitle) ?>')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i>
                    <span>Unduh Excel (.xlsx)</span>
                </button>

                <!-- Print / PDF -->
                <button 
                    type="button" 
                    onclick="window.print()" 
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-2 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span>Cetak / Simpan PDF</span>
                </button>
            </div>
        </div>

        <!-- Filter Controls (Screen Only) -->
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-100 no-print">
            <form action="<?= BASE_URL ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <input type="hidden" name="page" value="reports">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jenis Laporan</label>
                    <select name="type" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-800">
                        <option value="stock_valuation" <?= $reportType === 'stock_valuation' ? 'selected' : '' ?>>📊 Valuasi & Sisa Stok Fisik</option>
                        <option value="inbound_summary" <?= $reportType === 'inbound_summary' ? 'selected' : '' ?>>📥 Rekap Pembelian (Barang Masuk)</option>
                        <option value="outbound_summary" <?= $reportType === 'outbound_summary' ? 'selected' : '' ?>>📤 Rekap Pengeluaran (Barang Keluar)</option>
                        <option value="opname_summary" <?= $reportType === 'opname_summary' ? 'selected' : '' ?>>📋 Rekap Audit Stock Opname</option>
                    </select>
                </div>

                <?php if ($reportType !== 'stock_valuation'): ?>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="start" value="<?= $startDate ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                    </div>

                    <div class="flex gap-2 items-end">
                        <div class="flex-1">
                            <label class="block font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                            <input type="date" name="end" value="<?= $endDate ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Official Report Paper Container -->
        <div class="rounded-3xl bg-white p-6 sm:p-8 shadow-sm border border-slate-100 print:shadow-none print:border-none print:p-0">
            
            <!-- Official Header Letterhead (Kop Surat) -->
            <div class="border-b-2 border-slate-900 pb-4 mb-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-900">INVENTA<span class="text-brand-600">.</span>PRO</h2>
                        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Sistem Inventarisasi & Manajemen Kontrol Stok</p>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        <p class="font-bold text-slate-900"><?= $reportTitle ?></p>
                        <p>Dicetak pada: <?= date('d F Y, H:i') ?> WIB</p>
                    </div>
                </div>
            </div>

            <!-- Report Tables according to type -->
            <?php if (empty($results)): ?>
                <div class="py-12 text-center text-slate-400 text-xs">
                    Tidak ada data transaksi yang ditemukan untuk periode ini.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table id="report-table" class="w-full text-left text-xs text-slate-700">
                        
                        <?php if ($reportType === 'stock_valuation'): 
                            $totalValuation = 0;
                            $totalStockUnits = 0;
                        ?>
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="p-3">SKU</th>
                                    <th class="p-3">Nama Barang</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3">Lokasi Rak</th>
                                    <th class="p-3 text-right">Harga Modal</th>
                                    <th class="p-3 text-right">Stok Min</th>
                                    <th class="p-3 text-right">Sisa Stok</th>
                                    <th class="p-3 text-right">Total Valuasi (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($results as $r): 
                                    $val = (float)$r['valuation'];
                                    $totalValuation += $val;
                                    $totalStockUnits += (int)$r['current_stock'];
                                ?>
                                    <tr>
                                        <td class="p-3 font-mono font-bold text-brand-700"><?= sanitize($r['sku']) ?></td>
                                        <td class="p-3 font-semibold text-slate-900"><?= sanitize($r['name']) ?></td>
                                        <td class="p-3"><?= sanitize($r['category_name'] ?? '-') ?></td>
                                        <td class="p-3"><?= sanitize($r['rack_code'] ?? '-') ?></td>
                                        <td class="p-3 text-right"><?= format_rupiah($r['purchase_price']) ?></td>
                                        <td class="p-3 text-right text-slate-400"><?= $r['min_stock'] ?></td>
                                        <td class="p-3 text-right font-black"><?= number_format($r['current_stock']) ?> <?= sanitize($r['unit_symbol'] ?? '') ?></td>
                                        <td class="p-3 text-right font-black text-slate-900"><?= format_rupiah($val) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-t-2 border-slate-900 bg-slate-50 font-black">
                                <tr>
                                    <td colspan="6" class="p-3 text-right">TOTAL KESELURUHAN:</td>
                                    <td class="p-3 text-right"><?= number_format($totalStockUnits) ?> Unit</td>
                                    <td class="p-3 text-right text-brand-700 text-sm"><?= format_rupiah($totalValuation) ?></td>
                                </tr>
                            </tfoot>

                        <?php elseif ($reportType === 'inbound_summary'): 
                            $totalQtyIn = 0;
                            $totalSpent = 0;
                        ?>
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">No. Transaksi</th>
                                    <th class="p-3">Supplier</th>
                                    <th class="p-3">SKU & Nama Barang</th>
                                    <th class="p-3 text-right">Kuantitas</th>
                                    <th class="p-3 text-right">Harga Beli</th>
                                    <th class="p-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($results as $r): 
                                    $totalQtyIn += (int)$r['qty'];
                                    $totalSpent += (float)$r['subtotal'];
                                ?>
                                    <tr>
                                        <td class="p-3 whitespace-nowrap"><?= format_date($r['transaction_date']) ?></td>
                                        <td class="p-3 font-mono font-bold"><?= sanitize($r['transaction_no']) ?></td>
                                        <td class="p-3 font-semibold"><?= sanitize($r['supplier_name'] ?? '-') ?></td>
                                        <td class="p-3">
                                            <span class="font-bold text-slate-900"><?= sanitize($r['product_name']) ?></span>
                                            <span class="text-[10px] font-mono text-slate-400 block"><?= sanitize($r['sku']) ?></span>
                                        </td>
                                        <td class="p-3 text-right font-bold text-emerald-700">+<?= number_format($r['qty']) ?> <?= sanitize($r['unit_symbol'] ?? '') ?></td>
                                        <td class="p-3 text-right"><?= format_rupiah($r['unit_price']) ?></td>
                                        <td class="p-3 text-right font-black"><?= format_rupiah($r['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-t-2 border-slate-900 bg-slate-50 font-black">
                                <tr>
                                    <td colspan="4" class="p-3 text-right">TOTAL PEMBELIAN:</td>
                                    <td class="p-3 text-right text-emerald-700">+<?= number_format($totalQtyIn) ?></td>
                                    <td></td>
                                    <td class="p-3 text-right text-emerald-800 text-sm"><?= format_rupiah($totalSpent) ?></td>
                                </tr>
                            </tfoot>

                        <?php elseif ($reportType === 'outbound_summary'): 
                            $totalQtyOut = 0;
                            $totalOutVal = 0;
                        ?>
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">No. Transaksi</th>
                                    <th class="p-3">Peruntukan & Tujuan</th>
                                    <th class="p-3">SKU & Nama Barang</th>
                                    <th class="p-3 text-right">Kuantitas</th>
                                    <th class="p-3 text-right">Harga Satuan</th>
                                    <th class="p-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($results as $r): 
                                    $totalQtyOut += (int)$r['qty'];
                                    $totalOutVal += (float)$r['subtotal'];
                                ?>
                                    <tr>
                                        <td class="p-3 whitespace-nowrap"><?= format_date($r['transaction_date']) ?></td>
                                        <td class="p-3 font-mono font-bold text-crimson-700"><?= sanitize($r['transaction_no']) ?></td>
                                        <td class="p-3 font-semibold"><?= sanitize(ucwords(str_replace('_', ' ', $r['reason']))) ?> (<?= sanitize($r['destination'] ?: 'Internal') ?>)</td>
                                        <td class="p-3">
                                            <span class="font-bold text-slate-900"><?= sanitize($r['product_name']) ?></span>
                                            <span class="text-[10px] font-mono text-slate-400 block"><?= sanitize($r['sku']) ?></span>
                                        </td>
                                        <td class="p-3 text-right font-bold text-crimson-600">-<?= number_format($r['qty']) ?> <?= sanitize($r['unit_symbol'] ?? '') ?></td>
                                        <td class="p-3 text-right"><?= format_rupiah($r['unit_price']) ?></td>
                                        <td class="p-3 text-right font-black"><?= format_rupiah($r['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-t-2 border-slate-900 bg-slate-50 font-black">
                                <tr>
                                    <td colspan="4" class="p-3 text-right">TOTAL BARANG KELUAR:</td>
                                    <td class="p-3 text-right text-crimson-600">-<?= number_format($totalQtyOut) ?></td>
                                    <td></td>
                                    <td class="p-3 text-right text-slate-900 text-sm"><?= format_rupiah($totalOutVal) ?></td>
                                </tr>
                            </tfoot>

                        <?php elseif ($reportType === 'opname_summary'): 
                            $netVarQty = 0;
                            $netVarVal = 0;
                        ?>
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">No. Opname</th>
                                    <th class="p-3">SKU & Barang</th>
                                    <th class="p-3 text-right">Stok Sistem</th>
                                    <th class="p-3 text-right">Hitungan Fisik</th>
                                    <th class="p-3 text-right">Selisih (+/-)</th>
                                    <th class="p-3">Alasan Selisih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($results as $r): 
                                    $diff = (int)$r['variance_qty'];
                                    $netVarQty += $diff;
                                    $netVarVal += (float)$r['variance_value'];
                                ?>
                                    <tr>
                                        <td class="p-3 whitespace-nowrap"><?= format_date($r['audit_date']) ?></td>
                                        <td class="p-3 font-mono font-bold"><?= sanitize($r['opname_no']) ?></td>
                                        <td class="p-3">
                                            <span class="font-bold text-slate-900"><?= sanitize($r['product_name']) ?></span>
                                            <span class="text-[10px] font-mono text-slate-400 block"><?= sanitize($r['sku']) ?></span>
                                        </td>
                                        <td class="p-3 text-right"><?= number_format($r['system_qty']) ?></td>
                                        <td class="p-3 text-right font-bold"><?= number_format($r['physical_qty']) ?></td>
                                        <td class="p-3 text-right font-black <?= $diff < 0 ? 'text-crimson-600' : ($diff > 0 ? 'text-emerald-700' : 'text-slate-400') ?>">
                                            <?= $diff > 0 ? ('+' . $diff) : $diff ?>
                                        </td>
                                        <td class="p-3"><?= sanitize(str_replace('_', ' ', $r['reason'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-t-2 border-slate-900 bg-slate-50 font-black">
                                <tr>
                                    <td colspan="5" class="p-3 text-right">NET SELISIH FISIK:</td>
                                    <td class="p-3 text-right <?= $netVarQty < 0 ? 'text-crimson-600' : 'text-slate-900' ?>">
                                        <?= $netVarQty > 0 ? ('+' . $netVarQty) : $netVarQty ?> Unit
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>

                    </table>
                </div>
            <?php endif; ?>

            <!-- Signatures Section for Official Reports -->
            <div class="mt-12 pt-8 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs">
                <div>
                    <p class="text-slate-500 mb-16">Dibuat oleh Petugas Logistik,</p>
                    <p class="font-bold text-slate-900 underline"><?= sanitize($_SESSION['user']['name'] ?? 'Petugas') ?></p>
                </div>
                <div>
                    <p class="text-slate-500 mb-16">Mengetahui & Menyetujui (Owner),</p>
                    <p class="font-bold text-slate-900 underline">( ............................................ )</p>
                </div>
            </div>

        </div>

    </main>
</div>

<script>
function exportTableToExcel(tableId, filename = 'Laporan_Inventaris') {
    const table = document.getElementById(tableId);
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, { sheet: "Laporan" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

