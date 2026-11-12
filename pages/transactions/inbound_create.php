<?php
$pageTitle = 'Catat Barang Masuk - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$suppliers = $pdo ? $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll() : [];
$locations = $pdo ? $pdo->query("SELECT * FROM locations ORDER BY warehouse_name ASC, rack_code ASC")->fetchAll() : [];
$products = $pdo ? $pdo->query("SELECT id, sku, name, purchase_price, current_stock, track_expiry, location_id FROM products ORDER BY name ASC")->fetchAll() : [];

$presetProductId = (int)($_GET['product_id'] ?? 0);
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="<?= BASE_URL ?>index.php?page=inbound" class="hover:text-brand-600">Barang Masuk</a>
                <i data-lucide="chevron-right" class="h-3 w-3"></i>
                <span class="text-slate-600 font-semibold">Form Transaksi Baru</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Pencatatan Barang Masuk (Pembelian)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Input tanda terima barang, tentukan nomor batch & tanggal kedaluwarsa untuk menambah stok otomatis.
            </p>
        </div>

        <form action="<?= BASE_URL ?>actions/inbound_action.php?action=create" method="POST" id="inbound-form" class="space-y-6 max-w-5xl">
            
            <!-- Header Data (Supplier, Tanggal, No. Surat Jalan) -->
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Informasi Dokumen Pembelian</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pemasok / Supplier <span class="text-crimson-600">*</span></label>
                        <select name="supplier_id" required class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">Pilih Supplier...</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?> (<?= sanitize($s['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Faktur / PO / Surat Jalan</label>
                        <input type="text" name="reference_no" placeholder="Contoh: SJ-2026-089 atau PO-102" class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs font-mono text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Terima Fisik</label>
                        <input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" required class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Transaksi</label>
                    <input type="text" name="notes" placeholder="Catatan kondisi fisik kemasan atau instruksi khusus gudang" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Items Dynamic Table -->
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Daftar Barang yang Diterima</h3>
                        <p class="text-[11px] text-slate-400">Tambahkan barang, kuantitas, harga modal, dan tanggal kedaluwarsa</p>
                    </div>
                    <button type="button" onclick="addRow()" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-600 px-3 py-1.5 text-xs font-bold text-slate-700 transition">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <span>Tambah Baris</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600" id="items-table">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400">
                            <tr>
                                <th class="p-3 w-1/3">Pilih Barang</th>
                                <th class="p-3 w-28 text-center">Batch / Lot No</th>
                                <th class="p-3 w-36 text-center">Tgl Kedaluwarsa</th>
                                <th class="p-3 w-36">Lokasi Rak</th>
                                <th class="p-3 w-24 text-right">Kuantitas</th>
                                <th class="p-3 w-32 text-right">Harga Beli (Rp)</th>
                                <th class="p-3 w-32 text-right">Subtotal (Rp)</th>
                                <th class="p-3 w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-100">
                            <!-- Rows inserted dynamically -->
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50/50">
                            <tr>
                                <td colspan="4" class="p-3 font-bold text-slate-700 text-right">Total Keseluruhan:</td>
                                <td id="total-qty-cell" class="p-3 font-black text-right text-emerald-700">0</td>
                                <td></td>
                                <td id="total-amount-cell" class="p-3 font-black text-right text-slate-900 text-sm">Rp 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="<?= BASE_URL ?>index.php?page=inbound" class="rounded-xl border border-slate-200 bg-white py-2.5 px-5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 py-2.5 px-7 text-xs font-bold text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-700 hover:to-teal-800 transition">
                    Simpan & Tambah Stok Masuk
                </button>
            </div>

        </form>

    </main>
</div>

<script>
const productsData = <?= json_encode($products) ?>;
const locationsData = <?= json_encode($locations) ?>;
let rowIndex = 0;

function addRow(presetPid = 0) {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.id = 'row-' + rowIndex;
    tr.className = 'hover:bg-slate-50/80 transition';

    let productOptions = '<option value="">Pilih Barang...</option>';
    productsData.forEach(p => {
        const selected = (presetPid > 0 && p.id == presetPid) ? 'selected' : '';
        productOptions += `<option value="${p.id}" data-price="${p.purchase_price}" data-loc="${p.location_id || ''}" data-expiry="${p.track_expiry}" ${selected}>${p.sku} - ${p.name} (Stok: ${p.current_stock})</option>`;
    });

    let locationOptions = '<option value="">Lokasi Rak...</option>';
    locationsData.forEach(l => {
        locationOptions += `<option value="${l.id}">${l.warehouse_name} - ${l.rack_code}</option>`;
    });

    tr.innerHTML = `
        <td class="p-3">
            <select name="items[${rowIndex}][product_id]" required onchange="onProductChange(this, ${rowIndex})" class="w-full rounded-xl border border-slate-200 py-2 px-2.5 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                ${productOptions}
            </select>
        </td>
        <td class="p-3">
            <input type="text" name="items[${rowIndex}][batch_no]" placeholder="LOT-..." class="w-full rounded-xl border border-slate-200 py-2 px-2 text-xs font-mono text-slate-900 text-center">
        </td>
        <td class="p-3">
            <input type="date" name="items[${rowIndex}][expiry_date]" class="w-full rounded-xl border border-slate-200 py-2 px-2 text-xs text-slate-900 text-center">
        </td>
        <td class="p-3">
            <select name="items[${rowIndex}][location_id]" id="loc-select-${rowIndex}" class="w-full rounded-xl border border-slate-200 py-2 px-2 text-xs text-slate-900">
                ${locationOptions}
            </select>
        </td>
        <td class="p-3">
            <input type="number" name="items[${rowIndex}][qty]" value="1" min="1" required oninput="calculateTotals()" class="w-full rounded-xl border border-slate-200 py-2 px-2 text-xs font-bold text-right text-emerald-700">
        </td>
        <td class="p-3">
            <input type="number" name="items[${rowIndex}][unit_price]" id="price-${rowIndex}" value="0" min="0" step="100" required oninput="calculateTotals()" class="w-full rounded-xl border border-slate-200 py-2 px-2 text-xs font-semibold text-right text-slate-900">
        </td>
        <td class="p-3 text-right font-black text-slate-900" id="subtotal-${rowIndex}">
            Rp 0
        </td>
        <td class="p-3 text-center">
            <button type="button" onclick="removeRow(${rowIndex})" class="rounded-lg p-1.5 text-slate-300 hover:text-crimson-600 hover:bg-slate-100" title="Hapus Baris">
                <i data-lucide="trash" class="h-4 w-4"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    if (window.lucide) lucide.createIcons();

    // Trigger onProductChange jika ada preset
    if (presetPid > 0) {
        const sel = tr.querySelector('select');
        onProductChange(sel, rowIndex);
    }

    rowIndex++;
    calculateTotals();
}

function onProductChange(selectEl, idx) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || 0;
    const locId = selectedOption.getAttribute('data-loc') || '';

    const priceInput = document.getElementById(`price-${idx}`);
    if (priceInput) {
        priceInput.value = parseFloat(price);
    }

    const locSelect = document.getElementById(`loc-select-${idx}`);
    if (locSelect && locId) {
        locSelect.value = locId;
    }

    calculateTotals();
}

function removeRow(idx) {
    const row = document.getElementById('row-' + idx);
    if (row) {
        row.remove();
        calculateTotals();
    }
}

function calculateTotals() {
    let totalQty = 0;
    let totalAmount = 0;

    const rows = document.querySelectorAll('#items-body tr');
    rows.forEach(tr => {
        const qtyInput = tr.querySelector('input[name*="[qty]"]');
        const priceInput = tr.querySelector('input[name*="[unit_price]"]');
        const subtotalCell = tr.querySelector('[id^="subtotal-"]');

        const qty = parseInt(qtyInput ? qtyInput.value : 0) || 0;
        const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
        const sub = qty * price;

        if (subtotalCell) {
            subtotalCell.innerText = 'Rp ' + sub.toLocaleString('id-ID');
        }

        totalQty += qty;
        totalAmount += sub;
    });

    document.getElementById('total-qty-cell').innerText = totalQty.toLocaleString('id-ID');
    document.getElementById('total-amount-cell').innerText = 'Rp ' + totalAmount.toLocaleString('id-ID');
}

// Inisialisasi baris pertama saat form dibuka
document.addEventListener('DOMContentLoaded', () => {
    addRow(<?= $presetProductId ?>);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

