<?php
$pageTitle = 'Mulai Sesi Stock Opname - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$catFilter = !empty($_GET['cat']) ? (int)$_GET['cat'] : 0;
$locFilter = !empty($_GET['loc']) ? (int)$_GET['loc'] : 0;

$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll() : [];
$locations = $pdo ? $pdo->query("SELECT * FROM locations ORDER BY warehouse_name ASC, rack_code ASC")->fetchAll() : [];

$where = ["1=1"];
$params = [];
if ($catFilter > 0) {
    $where[] = "p.category_id = :cat";
    $params['cat'] = $catFilter;
}
if ($locFilter > 0) {
    $where[] = "p.location_id = :loc";
    $params['loc'] = $locFilter;
}

$stmtP = $pdo->prepare("
    SELECT p.*, c.name as category_name, u.symbol as unit_symbol, l.warehouse_name, l.rack_code
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN locations l ON p.location_id = l.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY l.rack_code ASC, p.name ASC
");
$stmtP->execute($params);
$products = $stmtP->fetchAll();
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="<?= BASE_URL ?>index.php?page=opname" class="hover:text-brand-600">Stock Opname</a>
                <i data-lucide="chevron-right" class="h-3 w-3"></i>
                <span class="text-slate-600 font-semibold">Lembar Hitung Fisik Baru</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Lembar Pencocokan Hitungan Fisik (Stock Opname)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Cocokkan stok di sistem dengan hitungan riil di rak/gudang. Selisih akan terhitung otomatis beserta nilai rupiahnya.
            </p>
        </div>

        <form action="<?= BASE_URL ?>actions/opname_action.php?action=create" method="POST" id="opname-form" class="space-y-6 max-w-6xl">
            
            <!-- Header Dokumen Opname -->
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Audit Lapangan <span class="text-crimson-600">*</span></label>
                        <input type="date" name="audit_date" value="<?= date('Y-m-d') ?>" required class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Area Audit</label>
                        <input type="text" name="notes" placeholder="Contoh: Audit Rutin Bulanan Gudang Bahan Baku & Pendingin" class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>
                </div>
            </div>

            <!-- Table Pencocokan Hitungan Fisik -->
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Pencocokan Stok Fisik vs Sistem</h3>
                        <p class="text-[11px] text-slate-400">Ketik jumlah fisik hasil hitung. Jika selisih $\ne 0$, pilih alasan selisih.</p>
                    </div>
                    
                    <button type="button" onclick="matchAll()" class="rounded-xl bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition" title="Isi semua hitungan fisik sama dengan stok sistem">
                        Cocokkan Semua (Default)
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="p-3">SKU & Nama Barang</th>
                                <th class="p-3">Posisi Rak</th>
                                <th class="p-3 text-right">Stok Sistem</th>
                                <th class="p-3 text-right w-28">Hitungan Fisik</th>
                                <th class="p-3 text-right">Selisih (+/-)</th>
                                <th class="p-3 w-40">Alasan Selisih</th>
                                <th class="p-3 w-40">Catatan Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="opname-items-body">
                            <?php foreach ($products as $idx => $p): ?>
                                <tr id="row-<?= $idx ?>" class="hover:bg-slate-50/80 transition">
                                    <input type="hidden" name="items[<?= $idx ?>][product_id]" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="items[<?= $idx ?>][location_id]" value="<?= $p['location_id'] ?>">
                                    <input type="hidden" name="items[<?= $idx ?>][unit_price]" value="<?= $p['purchase_price'] ?>">
                                    <input type="hidden" name="items[<?= $idx ?>][system_qty]" id="sys-<?= $idx ?>" value="<?= $p['current_stock'] ?>">

                                    <td class="p-3">
                                        <div class="font-bold text-slate-900"><?= sanitize($p['name']) ?></div>
                                        <div class="font-mono text-[10px] text-brand-700 font-bold"><?= sanitize($p['sku']) ?></div>
                                    </td>
                                    <td class="p-3 font-semibold text-slate-700">
                                        <?= sanitize($p['rack_code'] ?? 'Belum ada rak') ?>
                                    </td>
                                    <td class="p-3 text-right font-black text-slate-800 text-sm">
                                        <?= number_format($p['current_stock']) ?> <span class="text-[10px] font-normal text-slate-400"><?= sanitize($p['unit_symbol'] ?? '') ?></span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <input 
                                            type="number" 
                                            name="items[<?= $idx ?>][physical_qty]" 
                                            id="phy-<?= $idx ?>" 
                                            value="<?= $p['current_stock'] ?>" 
                                            min="0" 
                                            required 
                                            oninput="calcVariance(<?= $idx ?>)"
                                            class="w-full rounded-xl border border-slate-200 py-1.5 px-2 text-xs font-black text-right text-slate-900 focus:border-brand-500 focus:outline-none"
                                        >
                                    </td>
                                    <td class="p-3 text-right font-black" id="var-cell-<?= $idx ?>">
                                        <span class="text-slate-400">0</span>
                                    </td>
                                    <td class="p-3">
                                        <select 
                                            name="items[<?= $idx ?>][reason]" 
                                            id="reason-<?= $idx ?>" 
                                            disabled 
                                            class="w-full rounded-xl border border-slate-200 py-1 px-2 text-[11px] text-slate-800 focus:border-brand-500 disabled:bg-slate-50 disabled:text-slate-300"
                                        >
                                            <option value="koreksi_hitung">Koreksi Salah Hitung</option>
                                            <option value="barang_rusak">Barang Rusak / Pecah</option>
                                            <option value="barang_hilang">Barang Hilang / Selisih</option>
                                            <option value="kedaluwarsa">Kedaluwarsa / Basi</option>
                                            <option value="lainnya">Lainnya</option>
                                        </select>
                                    </td>
                                    <td class="p-3">
                                        <input 
                                            type="text" 
                                            name="items[<?= $idx ?>][notes]" 
                                            id="notes-<?= $idx ?>" 
                                            placeholder="Keterangan..." 
                                            disabled
                                            class="w-full rounded-xl border border-slate-200 py-1 px-2 text-[11px] text-slate-900 disabled:bg-slate-50 disabled:placeholder-slate-300"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50 font-bold">
                            <tr>
                                <td colspan="4" class="p-3 text-right text-slate-700">Ringkasan Total Selisih:</td>
                                <td id="total-var-qty" class="p-3 text-right font-black text-slate-900">0 Unit</td>
                                <td colspan="2" id="total-var-val" class="p-3 text-slate-500 text-[11px]">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Submit Options -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="<?= BASE_URL ?>index.php?page=opname" class="rounded-xl border border-slate-200 bg-white py-2.5 px-5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Batal
                </a>

                <button type="submit" name="finalize" value="0" class="rounded-xl border border-slate-300 bg-white py-2.5 px-6 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    Simpan sebagai Draft
                </button>

                <button type="submit" name="finalize" value="1" onclick="return confirm('PENTING: Menyetujui opname akan otomatis menyesuaikan saldo stok sistem dengan hitungan fisik lapangan. Lanjutkan?')" class="rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 py-2.5 px-7 text-xs font-bold text-white shadow-lg shadow-brand-500/25 hover:from-brand-700 hover:to-crimson-700 transition">
                    Finalisasi & Sesuaikan Stok Sistem
                </button>
            </div>

        </form>

    </main>
</div>

<script>
function calcVariance(idx) {
    const sys = parseInt(document.getElementById(`sys-${idx}`).value) || 0;
    const phy = parseInt(document.getElementById(`phy-${idx}`).value) || 0;
    const diff = phy - sys;

    const cell = document.getElementById(`var-cell-${idx}`);
    const reasonSel = document.getElementById(`reason-${idx}`);
    const notesInput = document.getElementById(`notes-${idx}`);
    const tr = document.getElementById(`row-${idx}`);

    if (diff === 0) {
        cell.innerHTML = '<span class="text-slate-400">0</span>';
        reasonSel.disabled = true;
        notesInput.disabled = true;
        tr.classList.remove('bg-orange-50/70', 'bg-red-50/70');
    } else if (diff > 0) {
        cell.innerHTML = `<span class="text-emerald-700 font-bold">+${diff}</span>`;
        reasonSel.disabled = false;
        notesInput.disabled = false;
        tr.classList.add('bg-orange-50/70');
    } else {
        cell.innerHTML = `<span class="text-crimson-600 font-bold">${diff}</span>`;
        reasonSel.disabled = false;
        notesInput.disabled = false;
        tr.classList.add('bg-red-50/70');
    }

    recalcSummary();
}

function matchAll() {
    const rows = document.querySelectorAll('#opname-items-body tr');
    rows.forEach((tr, idx) => {
        const sys = document.getElementById(`sys-${idx}`);
        const phy = document.getElementById(`phy-${idx}`);
        if (sys && phy) {
            phy.value = sys.value;
            calcVariance(idx);
        }
    });
}

function recalcSummary() {
    let totDiff = 0;
    const rows = document.querySelectorAll('#opname-items-body tr');
    rows.forEach((tr, idx) => {
        const sys = parseInt(document.getElementById(`sys-${idx}`)?.value || 0);
        const phy = parseInt(document.getElementById(`phy-${idx}`)?.value || 0);
        totDiff += (phy - sys);
    });

    const totCell = document.getElementById('total-var-qty');
    if (totCell) {
        totCell.innerText = (totDiff > 0 ? ('+' + totDiff) : totDiff) + ' Unit';
        totCell.className = 'p-3 text-right font-black ' + (totDiff < 0 ? 'text-crimson-600' : (totDiff > 0 ? 'text-emerald-700' : 'text-slate-900'));
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

