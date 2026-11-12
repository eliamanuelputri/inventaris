<?php
$flash = flash_get();
?>
    <!-- Barcode & QR Code Camera Scanner Modal -->
    <div 
        x-show="scannerOpen" 
        x-cloak 
        class="no-print relative z-50" 
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true"
    >
        <div 
            x-show="scannerOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm"
            @click="stopScanner()"
        ></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div 
                    x-show="scannerOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-6"
                >
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="rounded-xl bg-brand-100 p-2 text-brand-600">
                                <i data-lucide="scan" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Pemindai Barcode / QR Code</h3>
                                <p class="text-xs text-slate-500">Arahkan kamera smartphone atau webcam ke kode barang</p>
                            </div>
                        </div>
                        <button type="button" @click="stopScanner()" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <!-- Video Viewfinder Container -->
                    <div class="relative overflow-hidden rounded-2xl bg-black aspect-video flex items-center justify-center">
                        <div id="reader" class="w-full h-full"></div>
                    </div>

                    <!-- Scanner Status / Result Box -->
                    <div id="scanner-result" class="mt-4 p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                        <span id="scanner-status-text">Menyiapkan kamera...</span>
                        <div id="scanner-spinner" class="animate-spin h-4 w-4 border-2 border-brand-500 border-t-transparent rounded-full"></div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button type="button" @click="stopScanner()" class="w-full rounded-xl border border-slate-200 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Floating Flash Notification Toast -->
    <?php if ($flash): ?>
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 4500)" 
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="no-print fixed bottom-20 right-4 sm:bottom-6 sm:right-6 z-50 max-w-sm rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-slate-900/10 flex items-start gap-3 border-l-4 <?= $flash['type'] === 'success' ? 'border-emerald-500' : ($flash['type'] === 'error' ? 'border-crimson-600' : 'border-brand-500') ?>"
        >
            <div class="shrink-0 mt-0.5">
                <?php if ($flash['type'] === 'success'): ?>
                    <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-500"></i>
                <?php elseif ($flash['type'] === 'error'): ?>
                    <i data-lucide="alert-octagon" class="h-5 w-5 text-crimson-600"></i>
                <?php else: ?>
                    <i data-lucide="alert-triangle" class="h-5 w-5 text-brand-500"></i>
                <?php endif; ?>
            </div>
            <div class="flex-1 text-xs">
                <p class="font-bold text-slate-900 capitalize"><?= sanitize($flash['type']) ?></p>
                <p class="text-slate-600 mt-0.5"><?= sanitize($flash['message']) ?></p>
            </div>
            <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Scripts: PWA, Barcode, Lucide -->
    <script>
        // Inisialisasi Lucide Icons
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });

        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_URL ?>sw.js')
                    .then(reg => console.log('INVENTA PWA Service Worker terdaftar:', reg.scope))
                    .catch(err => console.log('Gagal daftar SW:', err));
            });
        }

        // PWA Install Prompt Handler
        let deferredPrompt;
        const installContainer = document.getElementById('pwa-install-container');
        const installBtn = document.getElementById('pwa-install-btn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (installContainer) {
                installContainer.style.display = 'block';
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('User menginstall PWA INVENTA');
                }
                deferredPrompt = null;
                if (installContainer) {
                    installContainer.style.display = 'none';
                }
            });
        }

        // HTML5 QR / Barcode Scanner Logic
        let html5QrCode = null;

        function startScanner() {
            const resultBox = document.getElementById('scanner-result');
            const statusText = document.getElementById('scanner-status-text');
            const spinner = document.getElementById('scanner-spinner');

            statusText.innerText = 'Membuka kamera...';
            spinner.style.display = 'block';

            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 180 } };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    // Berhasil scan!
                    statusText.innerText = 'Kode ditemukan: ' + decodedText;
                    spinner.style.display = 'none';
                    if (navigator.vibrate) navigator.vibrate(100);

                    // Arahkan ke pencarian barang / aksi otomatis
                    setTimeout(() => {
                        window.location.href = '<?= BASE_URL ?>index.php?page=products&q=' + encodeURIComponent(decodedText);
                    }, 500);
                },
                (errorMessage) => {
                    // Scanning berlangsung...
                    statusText.innerText = 'Mencari barcode/QR...';
                }
            ).catch(err => {
                statusText.innerText = 'Izin kamera ditolak atau tidak ada kamera.';
                spinner.style.display = 'none';
                console.error(err);
            });
        }

        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    html5QrCode = null;
                }).catch(err => console.error(err));
            }
            if (window.Alpine) {
                const el = document.querySelector('[x-data]');
                if (el && el._x_dataStack) {
                    el._x_dataStack[0].scannerOpen = false;
                }
            }
        }
    </script>
</body>
</html>

