<?php
$pageTitle = 'Edit Barang - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    flash_set('error', 'Barang tidak ditemukan.');
    header('Location: ' . BASE_URL . 'index.php?page=products');
    exit;
}

$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll() : [];
$units = $pdo ? $pdo->query("SELECT * FROM units ORDER BY name ASC")->fetchAll() : [];
$locations = $pdo ? $pdo->query("SELECT * FROM locations ORDER BY warehouse_name ASC, rack_code ASC")->fetchAll() : [];
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="<?= BASE_URL ?>index.php?page=products" class="hover:text-brand-600">Katalog Barang</a>
                <i data-lucide="chevron-right" class="h-3 w-3"></i>
                <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $product['id'] ?>" class="hover:text-brand-600"><?= sanitize($product['name']) ?></a>
                <i data-lucide="chevron-right" class="h-3 w-3"></i>
                <span class="text-slate-600 font-semibold">Edit Barang</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Edit Data: <?= sanitize($product['name']) ?>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Perbarui harga, lokasi rak gudang, batas stok minimum, atau foto barang.
            </p>
        </div>

        <form action="<?= BASE_URL ?>actions/product_action.php?action=update" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri: Foto & Visual Upload -->
                <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Foto Barang</h3>
                    
                    <div class="space-y-3">
                        <div class="relative aspect-square w-full rounded-2xl bg-slate-50 border-2 border-dashed border-slate-200 overflow-hidden flex items-center justify-center group" id="photo-preview-container">
                            <?php if (!empty($product['image']) && file_exists(UPLOAD_PATH . '/products/' . $product['image'])): ?>
                                <img id="photo-preview" src="<?= UPLOAD_URL . 'products/' . sanitize($product['image']) ?>" alt="Preview" class="w-full h-full object-cover">
                                <div id="photo-placeholder" class="text-center p-4 hidden">
                                    <i data-lucide="camera" class="mx-auto h-8 w-8 text-slate-300"></i>
                                    <span class="mt-2 block text-xs font-semibold text-slate-500">Ganti Foto</span>
                                </div>
                            <?php else: ?>
                                <img id="photo-preview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                                <div id="photo-placeholder" class="text-center p-4">
                                    <i data-lucide="camera" class="mx-auto h-8 w-8 text-slate-300"></i>
                                    <span class="mt-2 block text-xs font-semibold text-slate-500">Pilih Foto Barang</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <label class="block w-full text-center rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer transition">
                            <span>Ganti / Unggah Foto</span>
                            <input type="file" name="image" id="image-input" accept="image/*" class="hidden" onchange="previewImage(event)">
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="track_expiry" value="1" <?= $product['track_expiry'] ? 'checked' : '' ?> class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div>
                                <span class="text-xs font-bold text-slate-800">Lacak Tanggal Kedaluwarsa</span>
                                <p class="text-[11px] text-slate-400 leading-tight">Centang jika barang ini memiliki batch & expiry date.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Kolom Kanan: Rincian Data -->
                <div class="md:col-span-2 space-y-6">
                    
                    <!-- Card 1: Identifikasi Produk -->
                    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Identifikasi Produk</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kode SKU <span class="text-crimson-600">*</span></label>
                                <input type="text" name="sku" value="<?= sanitize($product['sku']) ?>" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono font-bold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Barcode Pabrik / EAN</label>
                                <input type="text" name="barcode" value="<?= sanitize($product['barcode'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Barang <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" value="<?= sanitize($product['name']) ?>" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori</label>
                                <select name="category_id" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <option value="">Pilih Kategori...</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Satuan Unit</label>
                                <select name="unit_id" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <option value="">Pilih Satuan...</option>
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $product['unit_id'] == $u['id'] ? 'selected' : '' ?>><?= sanitize($u['name']) ?> (<?= sanitize($u['symbol']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Rak Default</label>
                                <select name="location_id" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <option value="">Pilih Rak / Gudang...</option>
                                    <?php foreach ($locations as $l): ?>
                                        <option value="<?= $l['id'] ?>" <?= $product['location_id'] == $l['id'] ? 'selected' : '' ?>><?= sanitize($l['warehouse_name']) ?> - <?= sanitize($l['rack_code']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Harga & Ketentuan Stok -->
                    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Harga & Batas Stok</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Beli / Modal (Rp)</label>
                                <input type="number" name="purchase_price" value="<?= (float)$product['purchase_price'] ?>" min="0" step="100" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Jual / Nilai Keluar (Rp)</label>
                                <input type="number" name="selling_price" value="<?= (float)$product['selling_price'] ?>" min="0" step="100" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Batas Stok Minimum <span class="text-crimson-600">*</span></label>
                                <input type="number" name="min_stock" value="<?= (int)$product['min_stock'] ?>" min="0" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-semibold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Sisa Stok Sistem Saat Ini</label>
                                <div class="py-2 px-3 bg-slate-50 rounded-xl text-xs font-bold text-slate-700 border border-slate-200">
                                    <?= number_format($product['current_stock']) ?> Unit (Ubah via Barang Masuk/Keluar/Opname)
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi & Spesifikasi</label>
                            <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"><?= sanitize($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Tombol Aksi Simpan -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $product['id'] ?>" class="rounded-xl border border-slate-200 bg-white py-2.5 px-5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                            Batal
                        </a>
                        <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 py-2.5 px-6 text-xs font-bold text-white shadow-lg shadow-brand-500/25 hover:from-brand-700 hover:to-crimson-700 transition">
                            Perbarui Data Barang
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
        if (placeholder) placeholder.classList.add('hidden');
    }
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

