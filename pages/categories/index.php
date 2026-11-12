<?php
$pageTitle = 'Master Kategori Barang - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$search = sanitize($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];
if (!empty($search)) {
    $where[] = "(c.name LIKE :q OR c.code LIKE :q OR c.description LIKE :q)";
    $params['q'] = "%{$search}%";
}

$sql = "
    SELECT c.*, 
           COUNT(p.id) as total_products,
           COALESCE(SUM(p.current_stock), 0) as total_stock_units,
           COALESCE(SUM(p.current_stock * p.purchase_price), 0) as total_valuation
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY c.id
    ORDER BY c.name ASC
";
$stmt = $pdo ? $pdo->prepare($sql) : null;
if ($stmt) {
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
} else {
    $categories = [];
}
?>

<div class="flex-1 flex overflow-hidden" x-data="{ addModalOpen: false, editModalOpen: false, editData: {}, selectedColor: '#ea580c' }">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header & Action Button -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                    <span>Master Data</span>
                    <i data-lucide="chevron-right" class="h-3 w-3"></i>
                    <span class="text-slate-600 font-semibold">Kategori Barang</span>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Kategori Barang (Product Categories)
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Kelola pengelompokan jenis produk, warna tag identitas visual, dan pantau total nilai stok tiap divisi barang.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    @click="addModalOpen = true; selectedColor = '#ea580c'"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Tambah Kategori Baru</span>
                </button>
            </div>
        </div>

        <!-- Filter Search Bar -->
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-100">
            <form action="<?= BASE_URL ?>" method="GET" class="flex gap-2 max-w-md">
                <input type="hidden" name="page" value="categories">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400"></i>
                    <input 
                        type="text" 
                        name="q" 
                        value="<?= sanitize($search) ?>" 
                        placeholder="Cari nama atau kode kategori..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 transition">
                    Cari
                </button>
                <?php if (!empty($search)): ?>
                    <a href="<?= BASE_URL ?>index.php?page=categories" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100" title="Reset">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Categories Grid (Sortly x Odoo Style Cards) -->
        <?php if (empty($categories)): ?>
            <div class="rounded-3xl bg-white p-12 text-center border border-slate-100 shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 text-brand-500 mb-3">
                    <i data-lucide="folder" class="h-8 w-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">Belum ada kategori yang cocok</h3>
                <p class="text-xs text-slate-500 mt-1">Buat kategori baru untuk mulai mengelompokkan katalog barang Anda.</p>
                <div class="mt-4">
                    <button type="button" @click="addModalOpen = true" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-4 py-2 text-xs font-bold text-white shadow hover:bg-brand-700">
                        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Kategori
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($categories as $cat): 
                    $color = !empty($cat['color']) ? $cat['color'] : '#ea580c';
                ?>
                    <div class="group rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-slate-200 transition flex flex-col justify-between">
                        <div>
                            <!-- Top Bar: Color Badge & Actions -->
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-3.5 w-3.5 rounded-full shadow-sm" style="background-color: <?= htmlspecialchars($color) ?>;"></span>
                                    <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-mono font-bold text-slate-600">
                                        <?= sanitize($cat['code']) ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button 
                                        type="button" 
                                        @click="editData = <?= htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8') ?>; selectedColor = '<?= htmlspecialchars($color) ?>'; editModalOpen = true" 
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-50 transition" 
                                        title="Edit Kategori"
                                    >
                                        <i data-lucide="edit-3" class="h-4 w-4"></i>
                                    </button>
                                    <?php if (is_admin()): ?>
                                        <a 
                                            href="<?= BASE_URL ?>actions/category_action.php?action=delete&id=<?= $cat['id'] ?>" 
                                            onclick="return confirm('Hapus kategori <?= addslashes(sanitize($cat['name'])) ?>? Barang dalam kategori ini akan berubah menjadi Belum Dikategorikan.')"
                                            class="rounded-lg p-1.5 text-slate-400 hover:text-crimson-600 hover:bg-slate-50 transition" 
                                            title="Hapus Kategori"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Name & Description -->
                            <h3 class="text-base font-bold text-slate-900 mt-3 group-hover:text-brand-600 transition">
                                <?= sanitize($cat['name']) ?>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2 min-h-[32px]">
                                <?= sanitize($cat['description'] ?: 'Tidak ada deskripsi.') ?>
                            </p>
                        </div>

                        <!-- Statistics Row: Total SKU & Valuation -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Jumlah Barang:</span>
                                <span class="font-black text-slate-800"><?= $cat['total_products'] ?> <span class="text-[10px] text-slate-400 font-normal">SKU (<?= number_format($cat['total_stock_units']) ?> unit)</span></span>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Total Nilai Stok:</span>
                                <span class="font-black text-brand-700"><?= format_rupiah($cat['total_valuation']) ?></span>
                            </div>

                            <div class="pt-2">
                                <a 
                                    href="<?= BASE_URL ?>index.php?page=products&cat=<?= $cat['id'] ?>" 
                                    class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-50 hover:bg-brand-50 hover:text-brand-600 py-1.5 px-3 text-xs font-semibold text-slate-700 transition"
                                >
                                    <span>Lihat Katalog Barang</span>
                                    <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- MODAL 1: TAMBAH KATEGORI BARU -->
        <div x-show="addModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded-xl bg-brand-100 text-brand-600 flex items-center justify-center font-bold">
                                <i data-lucide="folder-plus" class="h-4 w-4"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Tambah Kategori Barang Baru</h3>
                        </div>
                        <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/category_action.php?action=create" method="POST" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Kategori <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" required placeholder="Contoh: Bahan Baku & Pangan" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Kategori (Opsional / Auto-generate)</label>
                            <input type="text" name="code" placeholder="Contoh: CAT-RAW (Kosongkan untuk otomatis)" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono font-bold text-slate-900 focus:border-brand-500 focus:outline-none">
                            <p class="text-[10px] text-slate-400 mt-1">Digunakan sebagai prefix kode SKU barang.</p>
                        </div>

                        <!-- Pilihan Warna Tag Label (Presets) -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Warna Aksen Tag Visual</label>
                            <div class="flex items-center gap-2">
                                <template x-for="color in ['#ea580c', '#dc2626', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#0f172a']">
                                    <button 
                                        type="button" 
                                        @click="selectedColor = color" 
                                        class="h-7 w-7 rounded-full transition transform hover:scale-110 flex items-center justify-center"
                                        :style="'background-color: ' + color"
                                    >
                                        <i data-lucide="check" class="h-3.5 w-3.5 text-white" x-show="selectedColor === color"></i>
                                    </button>
                                </template>
                                <input type="hidden" name="color" :value="selectedColor">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Deskripsi & Ruang Lingkup</label>
                            <textarea name="description" rows="3" placeholder="Informasi jenis barang yang masuk ke dalam kategori ini..." class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="addModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600 hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-5 py-2 font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700">
                                Simpan Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL 2: EDIT KATEGORI -->
        <div x-show="editModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                                <i data-lucide="edit-3" class="h-4 w-4"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Edit Data Kategori</h3>
                        </div>
                        <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/category_action.php?action=update" method="POST" class="space-y-4 text-xs">
                        <input type="hidden" name="id" :value="editData.id">

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Kategori <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" :value="editData.name" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Kategori <span class="text-crimson-600">*</span></label>
                            <input type="text" name="code" :value="editData.code" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono font-bold text-slate-900 focus:border-brand-500 focus:outline-none">
                        </div>

                        <!-- Pilihan Warna Tag Label -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Warna Aksen Tag Visual</label>
                            <div class="flex items-center gap-2">
                                <template x-for="color in ['#ea580c', '#dc2626', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#0f172a']">
                                    <button 
                                        type="button" 
                                        @click="selectedColor = color" 
                                        class="h-7 w-7 rounded-full transition transform hover:scale-110 flex items-center justify-center"
                                        :style="'background-color: ' + color"
                                    >
                                        <i data-lucide="check" class="h-3.5 w-3.5 text-white" x-show="selectedColor === color"></i>
                                    </button>
                                </template>
                                <input type="hidden" name="color" :value="selectedColor">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Deskripsi & Catatan</label>
                            <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs text-slate-900 focus:border-brand-500 focus:outline-none" x-text="editData.description"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="editModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600 hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-5 py-2 font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

