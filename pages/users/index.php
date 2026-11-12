<?php
$pageTitle = 'Kelola Pengguna & Hak Akses - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

require_admin(); // Hanya admin yang boleh akses
$pdo = db();
$users = $pdo ? $pdo->query("SELECT * FROM users ORDER BY role ASC, name ASC")->fetchAll() : [];
?>

<div class="flex-1 flex overflow-hidden" x-data="{ addModalOpen: false, editModalOpen: false, editData: {} }">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Manajemen Pengguna & Hak Akses
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Atur akses akun sistem: <strong>Admin (Pemilik)</strong> kelola seluruh data, <strong>Petugas</strong> catat transaksi & opname.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    @click="addModalOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:from-brand-700 hover:to-crimson-700 transition"
                >
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    <span>Tambah Akun Pengguna</span>
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3">Nama Pengguna</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email & Telepon</th>
                            <th class="px-4 py-3 text-center">Hak Akses (Role)</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr <?= $u['role'] === 'admin' ? 'from-brand-600 to-crimson-600' : 'from-blue-600 to-indigo-600' ?> text-white font-bold text-xs">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900"><?= sanitize($u['name']) ?></div>
                                            <div class="text-[10px] text-slate-400">Terdaftar sejak: <?= date('d M Y', strtotime($u['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-700"><?= sanitize($u['username']) ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800"><?= sanitize($u['email']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= sanitize($u['phone'] ?: '-') ?></div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="rounded-full bg-brand-100 px-2.5 py-0.5 text-[10px] font-bold text-brand-800">
                                            👑 Admin (Pemilik)
                                        </span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-bold text-blue-800">
                                            📦 Petugas Gudang
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($u['is_active']): ?>
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="editData = <?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>; editModalOpen = true" 
                                            class="rounded-lg p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition" 
                                            title="Edit Akun"
                                        >
                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                        </button>

                                        <?php if ($u['id'] !== (int)$_SESSION['user']['id']): ?>
                                            <a 
                                                href="<?= BASE_URL ?>actions/user_action.php?action=toggle_status&id=<?= $u['id'] ?>" 
                                                class="rounded-lg p-1.5 text-slate-400 hover:text-amber-600 hover:bg-slate-100 transition" 
                                                title="<?= $u['is_active'] ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>"
                                            >
                                                <i data-lucide="<?= $u['is_active'] ? 'user-x' : 'user-check' ?>" class="h-4 w-4"></i>
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

        <!-- Modal Tambah Akun -->
        <div x-show="addModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Tambah Akun Pengguna Baru</h3>
                        <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/user_action.php?action=create" method="POST" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" required placeholder="Nama personil" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Username <span class="text-crimson-600">*</span></label>
                                <input type="text" name="username" required placeholder="username" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Role / Hak Akses</label>
                                <select name="role" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-bold text-slate-800">
                                    <option value="petugas">Petugas Gudang</option>
                                    <option value="admin">Admin (Pemilik)</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Email <span class="text-crimson-600">*</span></label>
                            <input type="email" name="email" required placeholder="email@..." class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                                <input type="text" name="phone" placeholder="08..." class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Password Awal</label>
                                <input type="password" name="password" required value="123456" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button" @click="addModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600">Batal</button>
                            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2 font-bold text-white shadow hover:bg-brand-700">Buat Akun</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Edit Akun -->
        <div x-show="editModalOpen" x-cloak class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editModalOpen = false"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Edit Akun Pengguna</h3>
                        <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>

                    <form action="<?= BASE_URL ?>actions/user_action.php?action=update" method="POST" class="space-y-3 text-xs">
                        <input type="hidden" name="id" :value="editData.id">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-crimson-600">*</span></label>
                            <input type="text" name="name" :value="editData.name" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Username</label>
                                <input type="text" disabled :value="editData.username" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-mono bg-slate-50 text-slate-400">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Role / Hak Akses</label>
                                <select name="role" :value="editData.role" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs font-bold text-slate-800">
                                    <option value="petugas">Petugas Gudang</option>
                                    <option value="admin">Admin (Pemilik)</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Email <span class="text-crimson-600">*</span></label>
                            <input type="email" name="email" :value="editData.email" required class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                                <input type="text" name="phone" :value="editData.phone" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Ganti Password (Opsional)</label>
                                <input type="password" name="password" placeholder="Kosongkan jika tetap" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-xs">
                            </div>
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

