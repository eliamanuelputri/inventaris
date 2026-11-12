<?php
$pageTitle = 'Masuk ke ' . APP_NAME . ' - Kontrol Stok & Inventarisasi Cerdas';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50 relative overflow-hidden">
    <!-- Background Decor (Orange / Crimson glow) -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-brand-400/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-crimson-500/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center z-10">
        <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-tr from-brand-600 to-crimson-600 text-white shadow-xl shadow-brand-500/30 mb-4">
            <i data-lucide="boxes" class="h-8 w-8"></i>
        </div>
        <h2 class="text-3xl font-black tracking-tight text-slate-900">
            INVENTA<span class="text-brand-600">.</span>PRO
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Sistem Inventarisasi Modern (Sortly x Odoo Style)
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md z-10">
        <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/60 sm:rounded-3xl border border-slate-100 sm:px-10">
            
            <form action="<?= BASE_URL ?>actions/auth_action.php?action=login" method="POST" class="space-y-5">
                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Username atau Email
                    </label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            required 
                            placeholder="contoh: admin atau petugas"
                            class="block w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition"
                        >
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Password
                    </label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            placeholder="••••••••"
                            class="block w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition"
                        >
                    </div>
                </div>

                <div>
                    <button 
                        type="submit" 
                        class="w-full flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-crimson-600 py-3 px-4 text-sm font-bold text-white shadow-lg shadow-brand-500/30 hover:from-brand-700 hover:to-crimson-700 focus:outline-none transition active:scale-[0.98]"
                    >
                        <span>Masuk ke Sistem</span>
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </button>
                </div>
            </form>

            <!-- Demo Account Quick-Fill Buttons -->
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-center text-xs font-semibold text-slate-400 mb-3 uppercase tracking-wider">
                    Akun Uji Coba Cepat (1-Klik)
                </p>
                <div class="grid grid-cols-2 gap-2.5">
                    <button 
                        type="button" 
                        onclick="fillDemo('admin', 'admin123')"
                        class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-brand-200 bg-brand-50/60 hover:bg-brand-100/70 text-slate-800 transition"
                    >
                        <span class="text-xs font-bold text-brand-700">Akun Admin</span>
                        <span class="text-[10px] text-slate-500">Kelola Semua Data</span>
                    </button>
                    <button 
                        type="button" 
                        onclick="fillDemo('petugas', 'petugas123')"
                        class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-800 transition"
                    >
                        <span class="text-xs font-bold text-slate-700">Akun Petugas</span>
                        <span class="text-[10px] text-slate-500">Transaksi & Opname</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function fillDemo(username, password) {
    document.getElementById('username').value = username;
    document.getElementById('password').value = password;
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

