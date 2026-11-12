<?php
$pageTitle = 'Profil Saya - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

require_login();
$pdo = db();
$userId = (int)$_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$userData = $stmt->fetch();
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Profil Akun Saya
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Perbarui nama tampilan, nomor kontak, atau ganti kata sandi akun Anda.
            </p>
        </div>

        <div class="max-w-2xl bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100">
            <div class="flex items-center gap-4 pb-6 border-b border-slate-100 mb-6">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-brand-600 to-crimson-600 text-white font-black text-2xl shadow-lg shadow-brand-500/20">
                    <?= strtoupper(substr($userData['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900"><?= sanitize($userData['name']) ?></h2>
                    <p class="text-xs text-slate-400">@<?= sanitize($userData['username']) ?> &bull; 
                        <span class="font-bold uppercase <?= $userData['role'] === 'admin' ? 'text-brand-600' : 'text-blue-600' ?>">
                            <?= $userData['role'] === 'admin' ? 'Administrator (Pemilik)' : 'Petugas Gudang' ?>
                        </span>
                    </p>
                </div>
            </div>

            <form action="<?= BASE_URL ?>actions/auth_action.php?action=update_profile" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username (Tidak dapat diubah)</label>
                    <input type="text" disabled value="<?= sanitize($userData['username']) ?>" class="w-full rounded-xl border border-slate-200 py-2.5 px-3 bg-slate-50 text-slate-400 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-crimson-600">*</span></label>
                    <input type="text" name="name" value="<?= sanitize($userData['name']) ?>" required class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-slate-900 focus:border-brand-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email <span class="text-crimson-600">*</span></label>
                        <input type="email" name="email" value="<?= sanitize($userData['email']) ?>" required class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-slate-900 focus:border-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                        <input type="text" name="phone" value="<?= sanitize($userData['phone'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-slate-900 focus:border-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <label class="block font-bold text-slate-700 mb-1">Ganti Password Baru</label>
                    <input type="password" name="new_password" placeholder="Kosongkan jika tidak ingin mengubah kata sandi" class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-slate-900 focus:border-brand-500 focus:outline-none">
                    <p class="text-[10px] text-slate-400 mt-1">Gunakan kombinasi minimal 6 karakter demi keamanan akun.</p>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 py-2.5 px-6 font-bold text-white shadow-lg shadow-brand-500/25 hover:from-brand-700 hover:to-crimson-700 transition">
                        Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

