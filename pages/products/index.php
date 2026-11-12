<?php
$pageTitle = 'Katalog Barang & Stok - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$search = sanitize($_GET['q'] ?? '');
$catFilter = !empty($_GET['cat']) ? (int)$_GET['cat'] : 0;
$locFilter = !empty($_GET['loc']) ? (int)$_GET['loc'] : 0;
$stockFilter = sanitize($_GET['stock'] ?? '');

// Ambil list kategori dan lokasi untuk dropdown filter
$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll() : [];
$locations = $pdo ? $pdo->query("SELECT * FROM locations ORDER BY warehouse_name ASC, rack_code ASC")->fetchAll() : [];

// Query produk dengan filter
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE :q OR p.sku LIKE :q OR p.barcode LIKE :q)";
    $params['q'] = "%{$search}%";
}

if ($catFilter > 0) {
    $where[] = "p.category_id = :cat";
    $params['cat'] = $catFilter;
}

if ($locFilter > 0) {
    $where[] = "p.location_id = :loc";
    $params['loc'] = $locFilter;
}

if ($stockFilter === 'low') {
    $where[] = "p.current_stock <= p.min_stock";
} elseif ($stockFilter === 'safe') {
    $where[] = "p.current_stock > p.min_stock";
}

$sql = "
    SELECT p.*, c.name as category_name, c.color as category_color, u.symbol as unit_symbol, 
           l.warehouse_name, l.rack_code
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN locations l ON p.location_id = l.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="flex-1 flex overflow-hidden" x-data="{ viewMode: localStorage.getItem('inventa_view') || 'grid' }">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header & Action Controls -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Katalog Barang & Lokasi Rak
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Kelola data barang, posisi rak gudang, harga, dan indikator sisa stok fisik.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Toggle Mode Tampilan (Sortly Card vs Odoo Table) -->
                <div class="inline-flex rounded-xl bg-slate-200/80 p-1 text-slate-600">
                    <button 
                        type="button" 
                        @click="viewMode = 'grid'; localStorage.setItem('inventa_view', 'grid')" 
                        :class="viewMode === 'grid' ? 'bg-white text-brand-600 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800'"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs transition"
                        title="Tampilan Kartu Visual (Sortly)"
                    >
                        <i data-lucide="layout-grid" class="h-3.5 w-3.5"></i>
                        <span class="hidden sm:inline">Kartu Visual</span>
                    </button>
                    <button 
                        type="button" 
                        @click="viewMode = 'table'; localStorage.setItem('inventa_view', 'table')" 
                        :class="viewMode === 'table' ? 'bg-white text-brand-600 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800'"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs transition"
                        title="Tampilan Tabel Rinci (Odoo)"
                    >
                        <i data-lucide="table" class="h-3.5 w-3.5"></i>
                        <span class="hidden sm:inline">Tabel Rinci</span>
                    </button>
                </div>

                <!-- Export Excel -->
                <button 
                    type="button" 
                    onclick="exportTableToExcel('products-table', 'Katalog_Barang_Inventa')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <i data-lucide="file-spreadsheet" class="h-4 w-4 text-emerald-600"></i>
                    <span>Export Excel</span>
                </button>

                <!-- Tombol Tambah Barang Baru -->
                <a 
                    href="<?= BASE_URL ?>index.php?page=products_create" 
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Tambah Barang</span>
                </a>
            </div>
        </div>

        <!-- Filter Bar (Kategori, Lokasi, Status Stok) -->
        <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm border border-slate-100">
            <form action="<?= BASE_URL ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <input type="hidden" name="page" value="products">

                <!-- Search Input -->
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400"></i>
                    <input 
                        type="text" 
                        name="q" 
                        value="<?= sanitize($search) ?>" 
                        placeholder="Cari SKU / nama barang..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                </div>

                <!-- Kategori Filter -->
                <div>
                    <select 
                        name="cat" 
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                        <option value="0">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Lokasi Rak / Gudang Filter -->
                <div>
                    <select 
                        name="loc" 
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                        <option value="0">Semua Lokasi & Rak</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= $locFilter == $loc['id'] ? 'selected' : '' ?>>
                                <?= sanitize($loc['warehouse_name']) ?> - <?= sanitize($loc['rack_code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Stok Filter -->
                <div class="flex gap-2">
                    <select 
                        name="stock" 
                        onchange="this.form.submit()"
                        class="flex-1 rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                    >
                        <option value="">Semua Status Stok</option>
                        <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>⚠️ Stok Menipis</option>
                        <option value="safe" <?= $stockFilter === 'safe' ? 'selected' : '' ?>>✅ Stok Aman</option>
                    </select>

                    <?php if (!empty($search) || $catFilter > 0 || $locFilter > 0 || !empty($stockFilter)): ?>
                        <a href="<?= BASE_URL ?>index.php?page=products" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (empty($products)): ?>
            <div class="rounded-3xl bg-white p-12 text-center border border-slate-100 shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 text-brand-500 mb-3">
                    <i data-lucide="package-search" class="h-8 w-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">Tidak ada barang yang cocok</h3>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau ubah pilihan filter di atas.</p>
                <div class="mt-4">
                    <a href="<?= BASE_URL ?>index.php?page=products_create" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-4 py-2 text-xs font-bold text-white shadow hover:bg-brand-700">
                        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Barang Baru
                    </a>
                </div>
            </div>
        <?php else: ?>

            <!-- VIEW 1: SORTLY VISUAL CARD GRID VIEW -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($products as $p): 
                    $isLow = $p['current_stock'] <= $p['min_stock'];
                    $forecast = calculate_product_forecast((int)$p['id'], (int)$p['current_stock'], (int)$p['min_stock']);
                ?>
                    <div class="group flex flex-col justify-between rounded-2xl bg-white p-4 shadow-sm border border-slate-100 hover:shadow-md hover:border-brand-200 transition">
                        <div>
                            <!-- Image / Thumbnail Container -->
                            <div class="relative overflow-hidden rounded-xl bg-slate-100 aspect-video mb-3 flex items-center justify-center">
                                <?php if (!empty($p['image']) && file_exists(UPLOAD_PATH . '/products/' . $p['image'])): ?>
                                    <img src="<?= UPLOAD_URL . 'products/' . sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <?php else: ?>
                                    <div class="flex flex-col items-center justify-center text-slate-300">
                                        <i data-lucide="image" class="h-10 w-10"></i>
                                        <span class="text-[10px] text-slate-400 mt-1 font-semibold">Foto Belum Diunggah</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Stock Status Badge -->
                                <div class="absolute top-2 right-2">
                                    <?php if ($isLow): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-crimson-600/90 backdrop-blur px-2.5 py-0.5 text-[10px] font-bold text-white shadow-sm">
                                            <i data-lucide="alert-triangle" class="h-3 w-3"></i> Menipis
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600/90 backdrop-blur px-2.5 py-0.5 text-[10px] font-bold text-white shadow-sm">
                                            Aman
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Category Pill -->
                                <?php if (!empty($p['category_name'])): ?>
                                    <div class="absolute bottom-2 left-2">
                                        <span class="rounded-lg bg-slate-900/80 backdrop-blur px-2 py-0.5 text-[10px] font-semibold text-white">
                                            <?= sanitize($p['category_name']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Product Info -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="font-mono font-bold text-brand-700"><?= sanitize($p['sku']) ?></span>
                                    <span><?= sanitize($p['rack_code'] ?? 'Belum ada rak') ?></span>
                                </div>

                                <h3 class="text-sm font-bold text-slate-900 group-hover:text-brand-600 line-clamp-1 transition">
                                    <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $p['id'] ?>">
                                        <?= sanitize($p['name']) ?>
                                    </a>
                                </h3>

                                <div class="flex items-center justify-between text-xs pt-1">
                                    <span class="text-slate-500">Harga Beli:</span>
                                    <span class="font-semibold text-slate-800"><?= format_rupiah($p['purchase_price']) ?></span>
                                </div>

                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500">Sisa Stok:</span>
                                    <span class="font-black text-sm <?= $isLow ? 'text-crimson-600' : 'text-slate-900' ?>">
                                        <?= number_format($p['current_stock']) ?> <span class="text-xs font-normal text-slate-400"><?= sanitize($p['unit_symbol'] ?? '') ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 30-Day Forecast Indicator Bar (Sortly x Odoo Intelligence) -->
                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 flex items-center gap-1" title="Estimasi stok habis dihitung dari rata-rata pemakaian 30 hari terakhir">
                                <i data-lucide="trending-down" class="h-3 w-3 text-amber-500"></i> Habis dlm:
                            </span>
                            <span class="font-bold <?= $forecast['status'] === 'critical' ? 'text-crimson-600' : ($forecast['status'] === 'warning' ? 'text-amber-600' : 'text-slate-600') ?>">
                                <?= $forecast['days_remaining_text'] ?>
                            </span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-2 flex gap-1.5 pt-2">
                            <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $p['id'] ?>" class="flex-1 rounded-xl bg-slate-100 py-1.5 text-center text-xs font-semibold text-slate-700 hover:bg-brand-50 hover:text-brand-600 transition">
                                Detail
                            </a>
                            <a href="<?= BASE_URL ?>index.php?page=products_edit&id=<?= $p['id'] ?>" class="rounded-xl border border-slate-200 p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition" title="Edit Barang">
                                <i data-lucide="edit-3" class="h-4 w-4"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- VIEW 2: ODOO STRUCTURED DATA TABLE VIEW -->
            <div x-show="viewMode === 'table'" class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
                <div class="overflow-x-auto">
                    <table id="products-table" class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-100 font-bold">
                            <tr>
                                <th class="px-4 py-3">Kode SKU</th>
                                <th class="px-4 py-3">Nama Barang</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Lokasi / Rak</th>
                                <th class="px-4 py-3 text-right">Harga Beli</th>
                                <th class="px-4 py-3 text-right">Stok Min</th>
                                <th class="px-4 py-3 text-right">Sisa Stok</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Estimasi Habis</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($products as $p): 
                                $isLow = $p['current_stock'] <= $p['min_stock'];
                                $forecast = calculate_product_forecast((int)$p['id'], (int)$p['current_stock'], (int)$p['min_stock']);
                            ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3 font-mono font-bold text-brand-700">
                                        <?= sanitize($p['sku']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">
                                            <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $p['id'] ?>" class="hover:text-brand-600">
                                                <?= sanitize($p['name']) ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($p['barcode']) && $p['barcode'] !== $p['sku']): ?>
                                            <div class="text-[10px] text-slate-400 font-mono">Barcode: <?= sanitize($p['barcode']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                            <?= sanitize($p['category_name'] ?? 'Umum') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">
                                        <?= sanitize($p['rack_code'] ?? '-') ?>
                                        <span class="text-[10px] text-slate-400 block"><?= sanitize($p['warehouse_name'] ?? '') ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-800">
                                        <?= format_rupiah($p['purchase_price']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-400">
                                        <?= $p['min_stock'] ?> <?= sanitize($p['unit_symbol'] ?? '') ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black <?= $isLow ? 'text-crimson-600' : 'text-slate-900' ?>">
                                        <?= number_format($p['current_stock']) ?> <?= sanitize($p['unit_symbol'] ?? '') ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($isLow): ?>
                                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-bold text-crimson-700">
                                                Menipis
                                            </span>
                                        <?php else: ?>
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                                Aman
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center text-[11px] font-bold <?= $forecast['status'] === 'critical' ? 'text-crimson-600' : ($forecast['status'] === 'warning' ? 'text-amber-600' : 'text-slate-500') ?>">
                                        <?= $forecast['days_remaining_text'] ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="<?= BASE_URL ?>index.php?page=products_detail&id=<?= $p['id'] ?>" class="rounded-lg p-1.5 text-slate-400 hover:text-brand-600 hover:bg-slate-100" title="Lihat Detail">
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>index.php?page=products_edit&id=<?= $p['id'] ?>" class="rounded-lg p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-100" title="Edit">
                                                <i data-lucide="edit-3" class="h-4 w-4"></i>
                                            </a>
                                            <?php if (is_admin()): ?>
                                                <a 
                                                    href="<?= BASE_URL ?>actions/product_action.php?action=delete&id=<?= $p['id'] ?>" 
                                                    onclick="return confirm('Yakin ingin menghapus barang ini? Riwayat mutasi terkait akan terhapus.')" 
                                                    class="rounded-lg p-1.5 text-slate-400 hover:text-crimson-600 hover:bg-slate-100" 
                                                    title="Hapus"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </main>
</div>

<script>
function exportTableToExcel(tableId, filename = 'Data_Inventaris') {
    const table = document.getElementById(tableId);
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, { sheet: "Katalog Barang" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

