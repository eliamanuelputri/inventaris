<?php
$pageTitle = 'Catat Barang Keluar - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$products = $pdo ? $pdo->query("SELECT id, sku, name, selling_price, purchase_price, current_stock, track_expiry FROM products WHERE current_stock > 0 ORDER BY name ASC")->fetchAll() : [];
$presetProductId = (int)($_GET['product_id'] ?? 0);
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="<?= BASE_URL ?>index.php?page=outbound" class="hover:text-brand-600">Barang Keluar</a>
                <i data-lucide="chevron-right" class="h-3 w-3"></i>
                <span class="text-slate-600 font-semibold">Form Transaksi Pengeluaran</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Pencatatan Barang Keluar (Distribusi / Penjualan)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Catat pengeluaran barang. Sistem akan memvalidasi kecukupan stok dan menguranginya secara real-time.
            </p>
        </div>

        <form action="<?= BASE_URL ?>actions/outbound_action.php?action=create" method="POST" id="outbound-form" class="space-y-6 max-w-5xl">
            
            <!-- Header Data (Peruntukan, Tujuan, Tanggal) -->
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Informasi Pengeluaran Barang</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tujuan / Peruntukan Keluar <span class="text-crimson-600">*</span></label>
                        <select name="reason" required class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="penjualan">Penjualan ke Konsumen / Mitra</option>
                            <option value="pemakaian_internal">Pemakaian Internal / Divisi Operasional</option>
                            <option value="kerusakan">Barang Rusak / Afkir / Kadaluarsa</option>
                            <option value="retur">Retur Pengembalian ke Supplier</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Penerima / Customer / Divisi</label>
                        <input type="text" name="destination" placeholder="Contoh: Barista Outlet Cabang 1 / Toko Retail" class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" required class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Referensi (No Invoice / Work Order)</label>
                        <input type="text" name="reference_no" placeholder="Contoh: INV-2026-11 atau WO-OUT-004" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
                        <input type="text" name="notes" placeholder="Catatan tambahan mengenai pengeluaran barang" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Daftar Barang yang Dikeluarkan</h3>
                        <p class="text-[11px] text-slate-400">Pastikan kuantitas yang dikeluarkan tidak melebihi stok yang tersedia</p>
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
                                <th class="p-3 w-1/2">Pilih Barang (Stok Tersedia)</th>
                                <th class="p-3 w-32 text-right">Kuantitas Keluar</th>
                                <th class="p-3 w-36 text-right">Nilai Satuan (Rp)</th>
                                <th class="p-3 w-36 text-right">Subtotal (Rp)</th>
                                <th class="p-3 w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-100">
                            <!-- Rows dynamic -->
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50/50">
                            <tr>
                                <td class="p-3 font-bold text-slate-700 text-right">Total Barang Keluar:</td>
                                <td id="total-qty-cell" class="p-3 font-black text-right text-crimson-600">0</td>
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
                <a href="<?= BASE_URL ?>index.php?page=outbound" class="rounded-xl border border-slate-200 bg-white py-2.5 px-5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="rounded-xl bg-gradient-to-r from-crimson-600 to-rose-700 py-2.5 px-7 text-xs font-bold text-white shadow-lg shadow-crimson-500/25 hover:from-crimson-700 hover:to-rose-800 transition">
                    Simpan & Kurangi Stok
                </button>
            </div>

        </form>

    </main>
</div>

<script>
const productsData = <?= json_encode($products) ?>;
let rowIndex = 0;

function addRow(presetPid = 0) {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.id = 'row-' + rowIndex;
    tr.className = 'hover:bg-slate-50/80 transition';

    let productOptions = '<option value="">Pilih Barang...</option>';
    productsData.forEach(p => {
        const selected = (presetPid > 0 && p.id == presetPid) ? 'selected' : '';
        const price = (p.selling_price > 0) ? p.selling_price : p.purchase_price;
        productOptions += `<option value="${p.id}" data-price="${price}" data-stock="${p.current_stock}" ${selected}>${p.sku} - ${p.name} (Tersedia: ${p.current_stock})</option>`;
    });

    tr.innerHTML = `
        <td class="p-3">
            <select name="items[${rowIndex}][product_id]" required onchange="onProductChange(this, ${rowIndex})" class="w-full rounded-xl border border-slate-200 py-2 px-2.5 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                ${productOptions}
            </select>
            <div id="stock-hint-${rowIndex}" class="text-[10px] text-slate-400 mt-0.5"></div>
        </td>
        <td class="p-3">
            <input type="number" name="items[${rowIndex}][qty]" id="qty-${rowIndex}" value="1" min="1" required oninput="validateAndCalculate(${rowIndex})" class="w-full rounded-xl border border-slate-200 py-2 px-2 text-xs font-bold text-right text-crimson-600">
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
    const stock = parseInt(selectedOption.getAttribute('data-stock') || 0);

    const priceInput = document.getElementById(`price-${idx}`);
    if (priceInput) priceInput.value = parseFloat(price);

    const hint = document.getElementById(`stock-hint-${idx}`);
    if (hint && stock > 0) {
        hint.innerText = `Maksimal stok keluar: ${stock} unit`;
        hint.className = 'text-[10px] text-slate-400 mt-0.5';
    }

    const qtyInput = document.getElementById(`qty-${idx}`);
    if (qtyInput) qtyInput.max = stock;

    validateAndCalculate(idx);
}

function validateAndCalculate(idx) {
    const tr = document.getElementById('row-' + idx);
    if (!tr) return;

    const selectEl = tr.querySelector('select');
    const selectedOption = selectEl ? selectEl.options[selectEl.selectedIndex] : null;
    const maxStock = parseInt(selectedOption ? (selectedOption.getAttribute('data-stock') || 999999) : 999999);

    const qtyInput = document.getElementById(`qty-${idx}`);
    const qty = parseInt(qtyInput ? qtyInput.value : 0) || 0;
    const hint = document.getElementById(`stock-hint-${idx}`);

    if (qty > maxStock) {
        qtyInput.classList.add('border-crimson-500', 'bg-rose-50');
        if (hint) {
            hint.innerText = `Peringatan: Stok hanya tersedia ${maxStock}!`;
            hint.className = 'text-[10px] font-bold text-crimson-600 mt-0.5';
        }
    } else {
        qtyInput.classList.remove('border-crimson-500', 'bg-rose-50');
        if (hint && selectEl.value) {
            hint.innerText = `Maksimal stok keluar: ${maxStock} unit`;
            hint.className = 'text-[10px] text-slate-400 mt-0.5';
        }
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

document.addEventListener('DOMContentLoaded', () => {
    addRow(<?= $presetProductId ?>);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

