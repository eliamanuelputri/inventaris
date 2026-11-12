<?php
$pageTitle = 'Lokasi Gudang & Rak - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$sql = "
    SELECT l.*, 
           COUNT(p.id) as total_products,
           COALESCE(SUM(p.current_stock), 0) as total_units_stored
    FROM locations l
    LEFT JOIN products p ON p.location_id = l.id
    GROUP BY l.id
    ORDER BY l.warehouse_name ASC, l.rack_code ASC
";
$stmt = $pdo ? $pdo->query($sql) : null;
$locations = $stmt ? $stmt->fetchAll() : [];
?>

<div class="flex-1 flex overflow-hidden" x-data="{ addModalOpen: false, editModalOpen: false, editData: {} }">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Lokasi Gudang & Penataan Rak
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Struktur multi-lokasi, lorong, dan rak/bin untuk mempercepat pencarian barang fisik saat audit & packing.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    @click="addModalOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Tambah Rak / Bin Baru</span>
                </button>
            </div>
        </div>

        <!-- Racks Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($locations as $l): ?>
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <div class="flex items-start justify-between">
                            <span class="rounded-lg bg-orange-100 px-2.5 py-1 text-xs font-mono font-bold text-brand-700">
                                <?= sanitize($l['rack_code']) ?>
                            </span>
                            <div class="flex items-center gap-1">
                                <button 
                                    type="button" 
                                    @click="editData = <?= htmlspecialchars(json_encode($l), ENT_QUOTES, 'UTF-8') ?>; editModalOpen = true" 
                                    class="rounded-lg p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-50 transition" 
                                    title="Edit Rak"
                                >
                                    <i data-lucide="edit-3" class="h-4 w-4"></i>
                                </button>
                                <?php if (is_admin()): ?>
                                    <a 
                                        href="<?= BASE_URL ?>actions/location_action.php?action=delete&id=<?= $l['id'] ?>" 
                                        onclick="return confirm('Hapus lokasi rak ini?')" 
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-crimson-600 hover:bg-slate-50 transition"
                                        title="Hapus Rak"
                                    >
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h3 class="text-sm font-bold text-slate-900 mt-3"><?= sanitize($l['warehouse_name']) ?></h3>
                        <?php if (!empty($l['description'])): ?>
                            <p class="text-xs text-slate-500 mt-1"><?= sanitize($l['description']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Total Item Terdaftar</span>
                        <div class="text-right">
                            <span class="font-black text-slate-900"><?= $l['total_products'] ?> SKU</span>
                            <span class="text-[10px] text-slate-400 block">(<?= number_format($l['total_units_stored']) ?> unit fisik)</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal Tambah Rak -->
        <div x-show="addModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Tambah Rak / Lokasi Baru</h3>
                        <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/location_action.php?action=create" method="POST" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Gudang / Bangunan <span class="text-crimson-600">*</span></label>
                            <input type="text" name="warehouse_name" required placeholder="Contoh: Gudang Utama A / Chiller / Depan" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Rak / Bin / Lorong <span class="text-crimson-600">*</span></label>
                            <input type="text" name="rack_code" required placeholder="Contoh: Rak A-01, Bin B-02, Pallet P-1" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono font-bold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Keterangan / Posisi Fisik</label>
                            <textarea name="description" rows="2" placeholder="Lantai 1 sisi kanan dekat pintu masuk" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button" @click="addModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600">Batal</button>
                            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2 font-bold text-white shadow hover:bg-brand-700">Simpan Lokasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Edit Rak -->
        <div x-show="editModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Edit Lokasi Rak</h3>
                        <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/location_action.php?action=update" method="POST" class="space-y-3 text-xs">
                        <input type="hidden" name="id" :value="editData.id">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Gudang / Bangunan <span class="text-crimson-600">*</span></label>
                            <input type="text" name="warehouse_name" :value="editData.warehouse_name" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Rak / Bin <span class="text-crimson-600">*</span></label>
                            <input type="text" name="rack_code" :value="editData.rack_code" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono font-bold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Keterangan / Posisi Fisik</label>
                            <textarea name="description" rows="2" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs" x-text="editData.description"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button" @click="editModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600">Batal</button>
                            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2 font-bold text-white shadow hover:bg-brand-700">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

