<?php
$pageTitle = 'Berita Acara Stock Opname - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name
    FROM stock_opnames o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = :id
");
$stmt->execute(['id' => $id]);
$opname = $stmt->fetch();

if (!$opname) {
    flash_set('error', 'Sesi stock opname tidak ditemukan.');
    header('Location: ' . BASE_URL . 'index.php?page=opname');
    exit;
}

// Ambil item
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.sku, u.symbol as unit_symbol,
           l.warehouse_name, l.rack_code
    FROM stock_opname_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN locations l ON oi.location_id = l.id
    WHERE oi.opname_id = :oid
    ORDER BY l.rack_code ASC, p.name ASC
");
$stmtItems->execute(['oid' => $id]);
$items = $stmtItems->fetchAll();

$countMatch = 0;
$countSurplus = 0;
$countDeficit = 0;

foreach ($items as $it) {
    if ($it['variance_qty'] == 0) $countMatch++;
    elseif ($it['variance_qty'] > 0) $countSurplus++;
    else $countDeficit++;
}
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Breadcrumb & Top Bar -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between no-print">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                    <a href="<?= BASE_URL ?>index.php?page=opname" class="hover:text-brand-600">Stock Opname</a>
                    <i data-lucide="chevron-right" class="h-3 w-3"></i>
                    <span class="text-slate-600 font-semibold"><?= sanitize($opname['opname_no']) ?></span>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Berita Acara Stock Opname #<?= sanitize($opname['opname_no']) ?>
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span>Cetak Berita Acara</span>
                </button>

                <button type="button" onclick="exportTableToExcel('opname-detail-table', 'Berita_Acara_<?= sanitize($opname['opname_no']) ?>')" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i>
                    <span>Export Excel</span>
                </button>

                <?php if ($opname['status'] === 'draft'): ?>
                    <a 
                        href="<?= BASE_URL ?>actions/opname_action.php?action=finalize&id=<?= $opname['id'] ?>" 
                        onclick="return confirm('Setujui sesi opname ini? Stok di sistem akan disesuaikan otomatis dengan hasil hitung fisik!')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                    >
                        <i data-lucide="check-check" class="h-4 w-4"></i>
                        <span>Setujui & Sesuaikan Stok Sistem</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Official Print Header (Only visible on print) -->
        <div class="hidden print-only mb-6 border-b-2 border-slate-900 pb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-black"><?= APP_NAME ?> PRO INVENTORY</h2>
                    <p class="text-xs">Berita Acara Rekonsiliasi & Audit Fisik Inventaris</p>
                </div>
                <div class="text-right text-xs">
                    <p class="font-bold">No. Dokumen: <?= sanitize($opname['opname_no']) ?></p>
                    <p>Tanggal: <?= format_date($opname['audit_date']) ?></p>
                </div>
            </div>
        </div>

        <!-- Document Details Card -->
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 mb-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Nomor Dokumen</span>
                    <span class="font-mono font-bold text-slate-900 text-sm"><?= sanitize($opname['opname_no']) ?></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Tanggal Audit</span>
                    <span class="font-bold text-slate-800"><?= format_date($opname['audit_date']) ?></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Petugas Auditor</span>
                    <span class="font-bold text-slate-800"><?= sanitize($opname['user_name'] ?? 'Petugas') ?></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Status Dokumen</span>
                    <?php if ($opname['status'] === 'completed'): ?>
                        <span class="inline-block mt-0.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800">
                            Resmi Disetujui (Selesai)
                        </span>
                    <?php else: ?>
                        <span class="inline-block mt-0.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-800">
                            Draft (Menunggu Persetujuan)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($opname['notes'])): ?>
                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600">
                    <span class="font-bold text-slate-700">Catatan Audit:</span> <?= sanitize($opname['notes']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4 Summary Metrics (Odoo Audit Style) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total SKU Diaudit</span>
                <div class="text-xl font-black text-slate-900 mt-1"><?= count($items) ?> Item</div>
                <span class="text-[10px] text-emerald-600 font-semibold"><?= $countMatch ?> Cocok Sempurna</span>
            </div>

            <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Selisih Lebih (+)</span>
                <div class="text-xl font-black text-emerald-600 mt-1"><?= $countSurplus ?> SKU</div>
                <span class="text-[10px] text-slate-400">Fisik > Sistem</span>
            </div>

            <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Selisih Kurang (-)</span>
                <div class="text-xl font-black text-crimson-600 mt-1"><?= $countDeficit ?> SKU</div>
                <span class="text-[10px] text-slate-400">Fisik < Sistem</span>
            </div>

            <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Net Nilai Finansial</span>
                <div class="text-xl font-black <?= $opname['total_variance_value'] < 0 ? 'text-crimson-600' : 'text-slate-900' ?> mt-1">
                    <?= format_rupiah($opname['total_variance_value']) ?>
                </div>
                <span class="text-[10px] text-slate-400">Total selisih harga modal</span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100 mb-8">
            <div class="p-4 border-b border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Rincian Hitungan Fisik & Selisih per Barang</h3>
            </div>

            <div class="overflow-x-auto">
                <table id="opname-detail-table" class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Nama Barang</th>
                            <th class="px-4 py-3">Lokasi Rak</th>
                            <th class="px-4 py-3 text-right">Stok Sistem</th>
                            <th class="px-4 py-3 text-right">Hitungan Fisik</th>
                            <th class="px-4 py-3 text-right">Selisih Qty</th>
                            <th class="px-4 py-3 text-right">Nilai Selisih</th>
                            <th class="px-4 py-3">Alasan Selisih</th>
                            <th class="px-4 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $it): 
                            $diff = (int)$it['variance_qty'];
                        ?>
                            <tr class="<?= $diff != 0 ? 'bg-orange-50/30' : '' ?> hover:bg-slate-50 transition">
                                <td class="px-4 py-3 font-mono font-bold text-slate-700"><?= sanitize($it['sku']) ?></td>
                                <td class="px-4 py-3 font-semibold text-slate-900"><?= sanitize($it['product_name']) ?></td>
                                <td class="px-4 py-3 font-medium text-slate-600"><?= sanitize($it['rack_code'] ?? '-') ?></td>
                                <td class="px-4 py-3 text-right text-slate-500"><?= number_format($it['system_qty']) ?> <?= sanitize($it['unit_symbol'] ?? '') ?></td>
                                <td class="px-4 py-3 text-right font-black text-slate-900"><?= number_format($it['physical_qty']) ?> <?= sanitize($it['unit_symbol'] ?? '') ?></td>
                                <td class="px-4 py-3 text-right font-black <?= $diff < 0 ? 'text-crimson-600' : ($diff > 0 ? 'text-emerald-700' : 'text-slate-400') ?>">
                                    <?= $diff > 0 ? ('+' . $diff) : ($diff < 0 ? $diff : '0') ?>
                                </td>
                                <td class="px-4 py-3 text-right font-bold <?= $it['variance_value'] < 0 ? 'text-crimson-600' : 'text-slate-800' ?>">
                                    <?= format_rupiah($it['variance_value']) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($diff != 0): ?>
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 uppercase">
                                            <?= sanitize(str_replace('_', ' ', $it['reason'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[10px] font-medium">Cocok</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-[11px]"><?= sanitize($it['notes'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Official Signatures Area (For Print / Audit Formality) -->
        <div class="mt-8 pt-6 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <p class="text-slate-500 mb-16">Petugas Auditor Lapangan,</p>
                <p class="font-bold text-slate-900 underline"><?= sanitize($opname['user_name'] ?? 'Petugas Gudang') ?></p>
                <p class="text-[10px] text-slate-400">Staff Bagian Logistik</p>
            </div>
            <div>
                <p class="text-slate-500 mb-16">Disetujui oleh Owner / Kepala Gudang,</p>
                <p class="font-bold text-slate-900 underline">( ............................................ )</p>
                <p class="text-[10px] text-slate-400">Pemilik / Manajer Operasional</p>
            </div>
        </div>

    </main>
</div>

<script>
function exportTableToExcel(tableId, filename = 'Berita_Acara_Opname') {
    const table = document.getElementById(tableId);
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, { sheet: "Hasil Opname" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

