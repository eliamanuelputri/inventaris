<?php
$pageTitle = 'Detail Barang - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.color as category_color, u.symbol as unit_symbol,
           l.warehouse_name, l.rack_code, l.description as loc_desc
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN locations l ON p.location_id = l.id
    WHERE p.id = :id
");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    flash_set('error', 'Barang tidak ditemukan.');
    header('Location: ' . BASE_URL . 'index.php?page=products');
    exit;
}

// Analisis 30 Hari Burn Rate & Forecasting
$forecast = calculate_product_forecast((int)$product['id'], (int)$product['current_stock'], (int)$product['min_stock']);

// Ambil Batch Barang (jika track expiry)
$stmtBatches = $pdo->prepare("
    SELECT pb.*, l.rack_code, l.warehouse_name 
    FROM product_batches pb
    LEFT JOIN locations l ON pb.location_id = l.id
    WHERE pb.product_id = :id 
    ORDER BY pb.expiry_date ASC
");
$stmtBatches->execute(['id' => $id]);
$batches = $stmtBatches->fetchAll();

// Ambil Mutasi Stok Terakhir (Kartu Stok)
$stmtMutations = $pdo->prepare("
    SELECT m.*, u.name as user_name
    FROM stock_mutations m
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.product_id = :id
    ORDER BY m.created_at DESC, m.id DESC
    LIMIT 20
");
$stmtMutations->execute(['id' => $id]);
$mutations = $stmtMutations->fetchAll();
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Breadcrumb & Top Bar -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                    <a href="<?= BASE_URL ?>index.php?page=products" class="hover:text-brand-600">Katalog Barang</a>
                    <i data-lucide="chevron-right" class="h-3 w-3"></i>
                    <span class="text-slate-600 font-semibold"><?= sanitize($product['name']) ?></span>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    <?= sanitize($product['name']) ?>
                </h1>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="window.print()" class="no-print inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i data-lucide="printer" class="h-4 w-4"></i> Cetak Dokumen
                </button>
                <a href="<?= BASE_URL ?>index.php?page=products_edit&id=<?= $product['id'] ?>" class="no-print inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i data-lucide="edit-3" class="h-4 w-4 text-blue-600"></i> Edit
                </a>
                <a href="<?= BASE_URL ?>index.php?page=inbound_create&product_id=<?= $product['id'] ?>" class="no-print inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700">
                    <i data-lucide="plus" class="h-4 w-4"></i> Restock Masuk
                </a>
                <a href="<?= BASE_URL ?>index.php?page=outbound_create&product_id=<?= $product['id'] ?>" class="no-print inline-flex items-center gap-1.5 rounded-xl bg-crimson-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-crimson-700">
                    <i data-lucide="minus" class="h-4 w-4"></i> Barang Keluar
                </a>
            </div>
        </div>

        <!-- Main Product Card & Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            
            <!-- Left: Product Photo & Printable Barcode Tag (Sortly Style) -->
            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div>
                    <div class="relative overflow-hidden rounded-2xl bg-slate-100 aspect-square mb-4 flex items-center justify-center">
                        <?php if (!empty($product['image']) && file_exists(UPLOAD_PATH . '/products/' . $product['image'])): ?>
                            <img src="<?= UPLOAD_URL . 'products/' . sanitize($product['image']) ?>" alt="<?= sanitize($product['name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center text-slate-300">
                                <i data-lucide="image" class="h-16 w-16"></i>
                                <span class="text-xs text-slate-400 mt-2 font-medium">Foto Belum Diunggah</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Label Barcode Cetak Fisik -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1">Barcode Label SKU</div>
                        <!-- Barcode Simulation bars -->
                        <div class="flex items-center justify-center gap-0.5 h-10 my-2 px-2">
                            <?php 
                            $skuStr = $product['barcode'] ?: $product['sku'];
                            for ($i = 0; $i < 28; $i++): 
                                $w = (($i * 7 + 3) % 4) + 1;
                            ?>
                                <div class="bg-slate-900 h-full" style="width: <?= $w ?>px;"></div>
                            <?php endfor; ?>
                        </div>
                        <div class="font-mono text-xs font-bold text-slate-900 tracking-widest"><?= sanitize($skuStr) ?></div>
                        <div class="text-[10px] text-slate-500 mt-0.5 font-semibold"><?= sanitize($product['name']) ?></div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-2">
                    <div class="flex justify-between">
                        <span>Kategori:</span>
                        <span class="font-bold text-slate-800"><?= sanitize($product['category_name'] ?? 'Umum') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Satuan:</span>
                        <span class="font-bold text-slate-800"><?= sanitize($product['unit_symbol'] ?? 'Unit') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Penempatan Rak:</span>
                        <span class="font-bold text-brand-700"><?= sanitize($product['warehouse_name'] ?? '-') ?> &bull; <?= sanitize($product['rack_code'] ?? '-') ?></span>
                    </div>
                </div>
            </div>

            <!-- Right: Stock Metrics, Forecast Burn Rate, & Specifications -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- 4 Metrics Row -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sisa Stok</span>
                        <div class="text-2xl font-black <?= $product['current_stock'] <= $product['min_stock'] ? 'text-crimson-600' : 'text-slate-900' ?> mt-1">
                            <?= number_format($product['current_stock']) ?>
                        </div>
                        <span class="text-[10px] text-slate-400 font-semibold"><?= sanitize($product['unit_symbol'] ?? '') ?> (Min: <?= $product['min_stock'] ?>)</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Nilai Valuasi</span>
                        <div class="text-lg font-black text-slate-900 mt-1">
                            <?= format_rupiah($product['current_stock'] * $product['purchase_price']) ?>
                        </div>
                        <span class="text-[10px] text-slate-400">Modal Rp <?= number_format($product['purchase_price'], 0, ',', '.') ?></span>
                    </div>

                    <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Burn Rate 30 Hari</span>
                        <div class="text-lg font-black text-brand-600 mt-1">
                            <?= $forecast['burn_rate_formatted'] ?>
                        </div>
                        <span class="text-[10px] text-slate-400">Keluar <?= $forecast['total_out_30_days'] ?> dlm 30 hari</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-white shadow-sm border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Perkiraan Habis</span>
                        <div class="text-sm font-black <?= $forecast['status'] === 'critical' ? 'text-crimson-600' : 'text-slate-800' ?> mt-1">
                            <?= $forecast['days_remaining_text'] ?>
                        </div>
                        <span class="text-[10px] text-slate-400"><?= $forecast['runout_date_formatted'] ?></span>
                    </div>
                </div>

                <!-- Intelligence Alert Banner -->
                <?php if ($product['current_stock'] <= $product['min_stock']): ?>
                    <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 flex items-start gap-3">
                        <i data-lucide="alert-octagon" class="h-5 w-5 text-crimson-600 shrink-0 mt-0.5"></i>
                        <div class="text-xs">
                            <p class="font-bold text-crimson-900">Peringatan: Stok Barang Menipis!</p>
                            <p class="text-crimson-700 mt-0.5">
                                Sisa stok fisik (<?= $product['current_stock'] ?>) telah menyentuh batas minimum pemesanan (<?= $product['min_stock'] ?>). 
                                Berdasarkan kecepatan pemakaian 30 hari terakhir, stok diperkirakan habis sekitar tanggal <strong><?= $forecast['runout_date_formatted'] ?></strong>. Segera buat transaksi barang masuk / restock.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Product Batches Table (If Track Expiry) -->
                <?php if ($product['track_expiry']): ?>
                    <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Daftar Batch & Tanggal Kedaluwarsa</h3>
                                <p class="text-[11px] text-slate-400">Pelacakan lot produksi dan tanggal kedaluwarsa</p>
                            </div>
                        </div>

                        <?php if (empty($batches)): ?>
                            <div class="py-6 text-center text-slate-400 text-xs">
                                Belum ada nomor batch tercatat. Batch akan otomatis dibuat saat Anda mencatat Barang Masuk (Pembelian).
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400">
                                        <tr>
                                            <th class="px-3 py-2">Nomor Batch</th>
                                            <th class="px-3 py-2">Kedaluwarsa</th>
                                            <th class="px-3 py-2 text-right">Sisa Qty</th>
                                            <th class="px-3 py-2 text-center">Status Expiry</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach ($batches as $b): 
                                            $expStatus = get_expiry_status($b['expiry_date']);
                                        ?>
                                            <tr>
                                                <td class="px-3 py-2 font-mono font-bold text-slate-800"><?= sanitize($b['batch_no']) ?></td>
                                                <td class="px-3 py-2"><?= format_date($b['expiry_date']) ?></td>
                                                <td class="px-3 py-2 text-right font-bold"><?= number_format($b['current_qty']) ?></td>
                                                <td class="px-3 py-2 text-center">
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold <?= $expStatus['badge_class'] ?>">
                                                        <?= $expStatus['label'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Description / Specs -->
                <?php if (!empty($product['description'])): ?>
                    <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Deskripsi & Catatan Spesifikasi</h3>
                        <p class="text-xs text-slate-600 whitespace-pre-line leading-relaxed"><?= sanitize($product['description']) ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Section: Kartu Stok (Stock Mutation Ledger) -->
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Kartu Stok & Mutasi Terakhir</h2>
                    <p class="text-xs text-slate-400">Riwayat audit pergerakan keluar-masuk barang</p>
                </div>
            </div>

            <?php if (empty($mutations)): ?>
                <div class="py-8 text-center text-slate-400 text-xs">
                    Belum ada riwayat mutasi stok untuk barang ini.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Tipe Mutasi</th>
                                <th class="px-4 py-3">No. Referensi</th>
                                <th class="px-4 py-3 text-right text-emerald-600">Masuk (+)</th>
                                <th class="px-4 py-3 text-right text-crimson-600">Keluar (-)</th>
                                <th class="px-4 py-3 text-right text-slate-900 font-bold">Saldo Stok</th>
                                <th class="px-4 py-3">Keterangan</th>
                                <th class="px-4 py-3">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($mutations as $m): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap"><?= format_date($m['date']) ?></td>
                                    <td class="px-4 py-3 font-semibold uppercase text-[10px] text-slate-500">
                                        <?= sanitize(str_replace('_', ' ', $m['reference_type'])) ?>
                                    </td>
                                    <td class="px-4 py-3 font-mono font-bold text-slate-800"><?= sanitize($m['reference_no']) ?></td>
                                    <td class="px-4 py-3 text-right font-bold text-emerald-600">
                                        <?= $m['qty_in'] > 0 ? ('+' . number_format($m['qty_in'])) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-crimson-600">
                                        <?= $m['qty_out'] > 0 ? ('-' . number_format($m['qty_out'])) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-slate-900">
                                        <?= number_format($m['stock_after']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500"><?= sanitize($m['notes'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-slate-500 font-medium"><?= sanitize($m['user_name'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

