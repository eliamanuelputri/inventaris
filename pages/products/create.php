<?php
$pageTitle = 'Tambah Barang Baru - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll() : [];
$units = $pdo ? $pdo->query("SELECT * FROM units ORDER BY name ASC")->fetchAll() : [];
$locations = $pdo ? $pdo->query("SELECT * FROM locations ORDER BY warehouse_name ASC, rack_code ASC")->fetchAll() : [];
$autoSku = generate_sku('BRG');
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="<?= BASE_URL ?>index.php?page=products" class="hover:text-brand-600">Katalog Barang</a>
                <i data-lucide="chevron-right" class="h-3 w-3"></i>
                <span class="text-slate-600 font-semibold">Tambah Barang Baru</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Pendaftaran Barang Baru
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Lengkapi identitas SKU, posisi rak, harga, dan ketentuan kedaluwarsa.
            </p>
        </div>

        <form action="<?= BASE_URL ?>actions/product_action.php?action=create" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri: Foto & Visual Upload (Sortly Visual Focus) -->
                <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Foto Barang</h3>
                    
                    <div class="space-y-3">
                        <div class="relative aspect-square w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden flex items-center justify-center group" id="photo-preview-container">
                            <img id="photo-preview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                            <div id="photo-placeholder" class="text-center p-4">
                                <i data-lucide="camera" class="mx-auto h-8 w-8 text-slate-300"></i>
                                <span class="mt-2 block text-xs font-semibold text-slate-500">Pilih Foto Barang</span>
                                <span class="block text-[10px] text-slate-400">JPG, PNG, atau WEBP (Maks 2MB)</span>
                            </div>
                        </div>

                        <label class="block w-full text-center rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer transition">
                            <span>Unggah Foto</span>
                            <input type="file" name="image" id="image-input" accept="image/*" class="hidden" onchange="previewImage(event)">
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="track_expiry" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div>
                                <span class="text-xs font-bold text-slate-800">Lacak Tanggal Kedaluwarsa</span>
                                <p class="text-[11px] text-slate-400 leading-tight">Centang jika barang ini memiliki batch & expiry date (makanan, minuman, obat, kimia).</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Kolom Kanan: Rincian Data (Odoo Enterprise Focus) -->
                <div class="md:col-span-2 space-y-6">
                    
                    <!-- Card 1: Identifikasi Produk -->
                    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Identifikasi Produk</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kode SKU <span class="text-crimson-600">*</span></label>
                                <div class="flex gap-2">
                                    <input type="text" name="sku" id="sku" value="<?= $autoSku ?>" required class="flex-1 rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono font-bold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <button type="button" onclick="generateNewSku()" class="rounded-xl border border-slate-200 px-2.5 text-xs text-slate-500 hover:bg-slate-50" title="Generate SKU Otomatis">
                                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Barcode Pabrik / EAN (Opsional)</label>
                                <input type="text" name="barcode" placeholder="Jika ada barcode dari produsen" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Barang <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" required placeholder="Contoh: Biji Kopi Arabika Gayo 1kg" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori</label>
                                <select name="category_id" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <option value="">Pilih Kategori...</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Satuan Unit</label>
                                <select name="unit_id" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <option value="">Pilih Satuan...</option>
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= sanitize($u['name']) ?> (<?= sanitize($u['symbol']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Rak Default</label>
                                <select name="location_id" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <option value="">Pilih Rak / Gudang...</option>
                                    <?php foreach ($locations as $l): ?>
                                        <option value="<?= $l['id'] ?>"><?= sanitize($l['warehouse_name']) ?> - <?= sanitize($l['rack_code']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Harga & Ketentuan Stok -->
                    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Harga & Pengaturan Stok</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Beli / Modal (Rp)</label>
                                <input type="number" name="purchase_price" value="0" min="0" step="100" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Jual / Nilai Keluar (Rp)</label>
                                <input type="number" name="selling_price" value="0" min="0" step="100" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Batas Stok Minimum <span class="text-crimson-600">*</span></label>
                                <input type="number" name="min_stock" value="10" min="0" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                <span class="text-[10px] text-slate-400">Sistem akan memberi peringatan jika stok $\le$ batas ini.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Stok Awal Fisik Saat Ini</label>
                                <input type="number" name="initial_stock" value="0" min="0" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                <span class="text-[10px] text-slate-400">Otomatis dicatat sebagai saldo awal di kartu stok.</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi & Spesifikasi</label>
                            <textarea name="description" rows="3" placeholder="Informasi ukuran, material, supplier rekomendasi, dll." class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                        </div>
                    </div>

                    <!-- Tombol Aksi Simpan -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="<?= BASE_URL ?>index.php?page=products" class="rounded-xl border border-slate-200 bg-white py-2.5 px-5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                            Batal
                        </a>
                        <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 py-2.5 px-6 text-xs font-bold text-white shadow-lg shadow-brand-500/25 hover:from-brand-700 hover:to-crimson-700 transition">
                            Simpan Barang
                        </button>
                    </div>

                </div>
            </div>
        </form>

    </main>
</div>

<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const preview = document.getElementById('photo-preview');
        const placeholder = document.getElementById('photo-placeholder');
        preview.src = reader.result;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
    }
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

function generateNewSku() {
    const rand = Math.floor(100 + Math.random() * 900);
    const time = String(Date.now()).slice(-3);
    document.getElementById('sku').value = 'BRG-' + rand + time;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

