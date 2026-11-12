<?php
$pageTitle = 'Data Supplier - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$pdo = db();
$search = sanitize($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];
if (!empty($search)) {
    $where[] = "(s.name LIKE :q OR s.code LIKE :q OR s.contact_person LIKE :q OR s.phone LIKE :q)";
    $params['q'] = "%{$search}%";
}

$sql = "
    SELECT s.*, 
           COUNT(DISTINCT t.id) as total_orders,
           COALESCE(SUM(t.total_amount), 0) as total_spent
    FROM suppliers s
    LEFT JOIN transactions t ON t.supplier_id = s.id AND t.type = 'in'
    WHERE " . implode(' AND ', $where) . "
    GROUP BY s.id
    ORDER BY s.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();
?>

<div class="flex-1 flex overflow-hidden" x-data="{ addModalOpen: false, editModalOpen: false, editData: {} }">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Data Pemasok (Suppliers)
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Daftar kontak distributor, riwayat frekuensi pembelian, dan akumulasi transaksi.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    @click="addModalOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Tambah Supplier</span>
                </button>
            </div>
        </div>

        <!-- Supplier Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($suppliers as $s): ?>
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <div class="flex items-start justify-between">
                            <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-mono font-bold text-slate-600">
                                <?= sanitize($s['code']) ?>
                            </span>
                            <div class="flex items-center gap-1">
                                <button 
                                    type="button" 
                                    @click="editData = <?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>; editModalOpen = true" 
                                    class="rounded-lg p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-50 transition" 
                                    title="Edit Data"
                                >
                                    <i data-lucide="edit-3" class="h-4 w-4"></i>
                                </button>
                                <?php if (is_admin()): ?>
                                    <a 
                                        href="<?= BASE_URL ?>actions/supplier_action.php?action=delete&id=<?= $s['id'] ?>" 
                                        onclick="return confirm('Hapus supplier <?= sanitize($s['name']) ?>?')"
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-crimson-600 hover:bg-slate-50 transition"
                                        title="Hapus Supplier"
                                    >
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 mt-2"><?= sanitize($s['name']) ?></h3>
                        <?php if (!empty($s['contact_person'])): ?>
                            <p class="text-xs font-semibold text-brand-700 mt-0.5">PIC: <?= sanitize($s['contact_person']) ?></p>
                        <?php endif; ?>

                        <div class="mt-3 space-y-1.5 text-xs text-slate-500">
                            <?php if (!empty($s['phone'])): ?>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="phone" class="h-3.5 w-3.5 text-slate-400"></i>
                                    <span><?= sanitize($s['phone']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($s['email'])): ?>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="mail" class="h-3.5 w-3.5 text-slate-400"></i>
                                    <span class="truncate"><?= sanitize($s['email']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($s['address'])): ?>
                                <div class="flex items-start gap-2">
                                    <i data-lucide="map-pin" class="h-3.5 w-3.5 text-slate-400 shrink-0 mt-0.5"></i>
                                    <span class="line-clamp-2"><?= sanitize($s['address']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <div>
                            <span class="text-slate-400 block">Total Transaksi</span>
                            <span class="font-bold text-slate-800"><?= $s['total_orders'] ?> Pengiriman</span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-400 block">Akumulasi Beli</span>
                            <span class="font-black text-emerald-700"><?= format_rupiah($s['total_spent']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal Tambah Supplier -->
        <div x-show="addModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Tambah Pemasok / Supplier Baru</h3>
                        <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/supplier_action.php?action=create" method="POST" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Perusahaan / Supplier <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" required placeholder="PT / CV / Toko..." class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Contact Person (PIC)</label>
                                <input type="text" name="contact_person" placeholder="Nama PIC" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                                <input type="text" name="phone" placeholder="08..." class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" placeholder="sales@..." class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Alamat Kantor / Gudang</label>
                            <textarea name="address" rows="2" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button" @click="addModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600">Batal</button>
                            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2 font-bold text-white shadow hover:bg-brand-700">Simpan Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Edit Supplier -->
        <div x-show="editModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Edit Data Supplier</h3>
                        <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/supplier_action.php?action=update" method="POST" class="space-y-3 text-xs">
                        <input type="hidden" name="id" :value="editData.id">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Perusahaan / Supplier <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" :value="editData.name" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Contact Person (PIC)</label>
                                <input type="text" name="contact_person" :value="editData.contact_person" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                                <input type="text" name="phone" :value="editData.phone" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" :value="editData.email" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Alamat Kantor / Gudang</label>
                            <textarea name="address" rows="2" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs" x-text="editData.address"></textarea>
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

