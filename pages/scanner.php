<?php
$pageTitle = 'Pemindai Barcode Cerdas - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = db();
// Ambil beberapa sample SKU & Barcode untuk tombol uji coba 1-klik di desktop
$sampleProducts = $pdo ? $pdo->query("SELECT sku, barcode, name FROM products LIMIT 5")->fetchAll() : [];
?>

<div class="flex-1 flex overflow-hidden">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto md:ml-64 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Pusat Pemindaian Barcode & QR Code
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Scan kode barang fisik di rak gudang untuk <strong>cek stok instan</strong>, <strong>tambah barang masuk</strong>, atau <strong>kurangi pemakaian</strong> secara cepat tanpa ketik.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-6xl">
            
            <!-- Kolom Kiri: Kamera Scanner & Input Manual (5 Cols) -->
            <div class="lg:col-span-5 space-y-4">
                
                <!-- Viewfinder Kamera Scanner -->
                <div class="rounded-3xl bg-slate-900 p-4 shadow-xl border border-slate-800 text-white relative overflow-hidden">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Kamera Siap Memindai</span>
                        </div>
                        <div class="flex gap-1.5">
                            <button type="button" onclick="startCamera()" class="rounded-lg bg-slate-800 hover:bg-slate-700 p-1.5 text-xs text-slate-300" title="Nyalakan Kamera">
                                <i data-lucide="camera" class="h-4 w-4"></i>
                            </button>
                            <button type="button" onclick="stopCamera()" class="rounded-lg bg-slate-800 hover:bg-slate-700 p-1.5 text-xs text-slate-300" title="Matikan Kamera">
                                <i data-lucide="camera-off" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Viewfinder Area -->
                    <div class="relative aspect-video sm:aspect-square rounded-2xl bg-black overflow-hidden flex items-center justify-center border border-slate-800">
                        <div id="page-reader" class="w-full h-full"></div>
                        <div id="cam-overlay" class="absolute inset-0 flex flex-col items-center justify-center p-4 text-center pointer-events-none">
                            <div class="w-48 h-32 border-2 border-brand-500/80 rounded-2xl flex items-center justify-center shadow-lg shadow-brand-500/10 relative">
                                <div class="absolute -top-1 left-2 bg-brand-500 text-[9px] font-bold px-1.5 rounded text-white">AREA SCAN</div>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-3 font-medium">Arahkan barcode / QR barang ke kotak di atas</span>
                        </div>
                    </div>

                    <!-- Status Scanner Text -->
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                        <span id="scan-status">Menghubungkan kamera perangkat...</span>
                        <div id="scan-loader" class="h-3.5 w-3.5 border-2 border-brand-500 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>

                <!-- Input Manual / Scanner Gun Bluetooth Ready -->
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-100">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Input Manual / Barcode Scanner Gun</label>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="manual-code-input" 
                            placeholder="Ketik SKU atau scan dengan alat gun..." 
                            class="flex-1 rounded-xl border border-slate-200 py-2.5 px-3 text-xs font-mono font-bold text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            onkeypress="if(event.key === 'Enter') handleManualSearch()"
                        >
                        <button 
                            type="button" 
                            onclick="handleManualSearch()" 
                            class="rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-xs font-bold text-white transition flex items-center gap-1.5"
                        >
                            <i data-lucide="search" class="h-4 w-4"></i>
                            <span>Cari</span>
                        </button>
                    </div>

                    <!-- Quick Demo Sample Buttons (For Testing Without Physical Barcode) -->
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-2">Uji Coba Cepat (Klik Contoh Barang):</p>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($sampleProducts as $sp): ?>
                                <button 
                                    type="button" 
                                    onclick="lookupCode('<?= sanitize($sp['sku']) ?>')"
                                    class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-brand-50 hover:border-brand-300 hover:text-brand-700 px-2 py-1 text-[11px] font-mono font-semibold text-slate-700 transition"
                                >
                                    <?= sanitize($sp['sku']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Kartu Hasil Scan & Aksi Cepat (7 Cols) -->
            <div class="lg:col-span-7">
                
                <!-- Placeholder Kosong Saat Belum Scan -->
                <div id="scan-empty-state" class="rounded-3xl bg-white p-12 text-center border border-slate-100 shadow-sm flex flex-col items-center justify-center min-h-[420px]">
                    <div class="h-16 w-16 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
                        <i data-lucide="scan" class="h-8 w-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Menunggu Pemindaian Barcode</h3>
                    <p class="text-xs text-slate-500 max-w-sm mt-1 leading-relaxed">
                        Arahkan kamera ke barcode kemasan barang atau klik salah satu kode uji coba di samping untuk melihat kartu stok dan opsi aksi cepat.
                    </p>
                </div>

                <!-- Kartu Hasil Scan (Muncul Saat Barcode Ditemukan) -->
                <div id="scan-result-card" class="hidden rounded-3xl bg-white p-6 shadow-sm border border-slate-100 space-y-6">
                    
                    <!-- Header Info Barang -->
                    <div class="flex flex-col sm:flex-row gap-4 pb-6 border-b border-slate-100">
                        <div class="h-24 w-24 rounded-2xl bg-slate-100 overflow-hidden shrink-0 flex items-center justify-center border border-slate-100">
                            <img id="res-photo" src="" alt="Foto" class="h-full w-full object-cover hidden">
                            <i id="res-photo-placeholder" data-lucide="image" class="h-8 w-8 text-slate-300"></i>
                        </div>

                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <span id="res-sku" class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded-md"></span>
                                <span id="res-category" class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md"></span>
                            </div>
                            <h2 id="res-name" class="text-lg font-black text-slate-900 leading-tight"></h2>
                            <div class="text-xs text-slate-500 flex items-center gap-1.5 pt-0.5">
                                <i data-lucide="map-pin" class="h-3.5 w-3.5 text-brand-600"></i>
                                <span id="res-location" class="font-semibold text-slate-700"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Indikator Metrik Kunci -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Sisa Stok Fisik</span>
                            <div id="res-stock" class="text-xl font-black text-slate-900 mt-0.5">0</div>
                            <span id="res-stock-badge" class="text-[9px] font-bold px-2 py-0.5 rounded-full inline-block mt-0.5"></span>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Burn Rate 30 Hari</span>
                            <div id="res-burn-rate" class="text-base font-black text-brand-600 mt-0.5">0 / hari</div>
                            <span class="text-[10px] text-slate-400">Rata-rata keluar</span>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Estimasi Habis</span>
                            <div id="res-days-left" class="text-xs font-black text-slate-800 mt-1">Aman</div>
                            <span id="res-runout-date" class="text-[9px] text-slate-400 block"></span>
                        </div>
                    </div>

                    <!-- Expiry Alert Section (Jika ada batch expired) -->
                    <div id="res-expiry-section" class="hidden p-3 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-crimson-800">
                        <div class="flex items-center gap-2 font-bold mb-1">
                            <i data-lucide="alert-octagon" class="h-4 w-4 text-crimson-600"></i>
                            <span>Peringatan Kedaluwarsa Batch:</span>
                        </div>
                        <div id="res-expiry-list" class="space-y-1 text-[11px]"></div>
                    </div>

                    <!-- Kotak Aksi Cepat Transaksi (Quick In / Quick Out) -->
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Aksi Cepat di Rak Gudang</span>
                            <span class="text-[11px] text-slate-400">Ketik kuantitas lalu klik tombol aksi:</span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <div class="w-full sm:w-36">
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Jumlah (Unit):</label>
                                <input type="number" id="quick-qty" value="1" min="1" class="w-full rounded-xl border border-slate-200 py-2 px-3 text-center font-black text-slate-900 text-sm focus:border-brand-500 focus:outline-none">
                            </div>

                            <div class="w-full sm:flex-1 grid grid-cols-2 gap-2 pt-2 sm:pt-4">
                                <button 
                                    type="button" 
                                    onclick="executeQuickAdjust('in')"
                                    class="rounded-xl bg-emerald-600 hover:bg-emerald-700 py-2.5 px-3 text-xs font-bold text-white shadow-sm flex items-center justify-center gap-1.5 transition active:scale-95"
                                >
                                    <i data-lucide="arrow-down-left" class="h-4 w-4"></i>
                                    <span>+ Masuk (Restock)</span>
                                </button>

                                <button 
                                    type="button" 
                                    onclick="executeQuickAdjust('out')"
                                    class="rounded-xl bg-crimson-600 hover:bg-crimson-700 py-2.5 px-3 text-xs font-bold text-white shadow-sm flex items-center justify-center gap-1.5 transition active:scale-95"
                                >
                                    <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                                    <span>- Keluar (Pakai)</span>
                                </button>
                            </div>
                        </div>

                        <div id="quick-feedback" class="hidden p-2 rounded-xl text-center text-xs font-bold"></div>
                    </div>

                    <!-- Bottom Navigation to Product Detail -->
                    <div class="pt-2 flex items-center justify-between text-xs">
                        <button type="button" onclick="resetScannerView()" class="text-slate-400 hover:text-slate-600 font-semibold">
                            &larr; Scan Barang Lain
                        </button>

                        <a id="res-detail-link" href="#" class="inline-flex items-center gap-1 font-bold text-brand-600 hover:text-brand-700">
                            <span>Buka Riwayat Lengkap & Kartu Stok</span>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </a>
                    </div>

                </div>

            </div>

        </div>

    </main>
</div>

<script>
let pageScanner = null;
let currentScannedProductId = null;

// Audio beep feedback
function playBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.value = 0.1;
        osc.start();
        setTimeout(() => { osc.stop(); audioCtx.close(); }, 120);
    } catch(e) {}
    if (navigator.vibrate) navigator.vibrate(100);
}

function startCamera() {
    const statusText = document.getElementById('scan-status');
    const loader = document.getElementById('scan-loader');
    statusText.innerText = 'Menyalakan kamera...';
    loader.style.display = 'block';

    pageScanner = new Html5Qrcode("page-reader");
    pageScanner.start(
        { facingMode: "environment" },
        { fps: 12, qrbox: { width: 260, height: 200 } },
        (decodedText) => {
            playBeep();
            lookupCode(decodedText);
        },
        () => {
            statusText.innerText = 'Mencari kode fisik...';
            loader.style.display = 'none';
        }
    ).catch(err => {
        statusText.innerText = 'Izin kamera ditolak atau kamera tidak aktif.';
        loader.style.display = 'none';
    });
}

function stopCamera() {
    if (pageScanner) {
        pageScanner.stop().then(() => {
            pageScanner.clear();
            pageScanner = null;
            document.getElementById('scan-status').innerText = 'Kamera dinonaktifkan.';
        });
    }
}

function handleManualSearch() {
    const val = document.getElementById('manual-code-input').value.trim();
    if (val) lookupCode(val);
}

// Cari data barang via AJAX API
function lookupCode(code) {
    document.getElementById('manual-code-input').value = code;
    document.getElementById('scan-status').innerText = 'Memuat data untuk ' + code + '...';

    fetch('<?= BASE_URL ?>api/scan_lookup.php?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message);
                return;
            }

            renderProductCard(data);
        })
        .catch(err => {
            console.error(err);
            alert('Gagal mengambil data dari server.');
        });
}

function renderProductCard(data) {
    const p = data.product;
    const f = data.forecast;
    currentScannedProductId = p.id;

    document.getElementById('scan-empty-state').classList.add('hidden');
    document.getElementById('scan-result-card').classList.remove('hidden');

    document.getElementById('res-sku').innerText = p.sku;
    document.getElementById('res-category').innerText = p.category_name;
    document.getElementById('res-name').innerText = p.name;
    document.getElementById('res-location').innerText = p.warehouse_name + ' • ' + p.rack_code;
    document.getElementById('res-stock').innerText = p.current_stock + ' ' + p.unit_symbol;

    const badge = document.getElementById('res-stock-badge');
    if (p.is_low_stock) {
        badge.innerText = '⚠️ Di Bawah Min (' + p.min_stock + ')';
        badge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full inline-block mt-0.5 bg-red-100 text-crimson-700';
    } else {
        badge.innerText = '✅ Stok Aman';
        badge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full inline-block mt-0.5 bg-emerald-100 text-emerald-800';
    }

    document.getElementById('res-burn-rate').innerText = f.burn_rate_formatted;
    document.getElementById('res-days-left').innerText = f.days_remaining_text;
    document.getElementById('res-runout-date').innerText = f.runout_date_formatted;

    // Foto
    const imgEl = document.getElementById('res-photo');
    const iconEl = document.getElementById('res-photo-placeholder');
    if (p.photo_url) {
        imgEl.src = p.photo_url;
        imgEl.classList.remove('hidden');
        iconEl.classList.add('hidden');
    } else {
        imgEl.classList.add('hidden');
        iconEl.classList.remove('hidden');
    }

    // Expiry Section
    const expSec = document.getElementById('res-expiry-section');
    const expList = document.getElementById('res-expiry-list');
    if (data.batches && data.batches.length > 0) {
        expSec.classList.remove('hidden');
        expList.innerHTML = '';
        data.batches.forEach(b => {
            expList.innerHTML += `<div class="flex justify-between">
                <span>Lot: <strong>${b.batch_no}</strong> (Sisa: ${b.current_qty})</span>
                <span class="${b.badge_class} px-1.5 py-0.2 rounded font-bold">${b.label}</span>
            </div>`;
        });
    } else {
        expSec.classList.add('hidden');
    }

    document.getElementById('res-detail-link').href = '<?= BASE_URL ?>index.php?page=products_detail&id=' + p.id;
    document.getElementById('quick-feedback').classList.add('hidden');

    if (window.lucide) lucide.createIcons();
}

// Aksi Cepat Tambah/Kurang Stok
function executeQuickAdjust(actionType) {
    if (!currentScannedProductId) return;

    const qty = parseInt(document.getElementById('quick-qty').value) || 1;
    const feedback = document.getElementById('quick-feedback');

    feedback.innerText = 'Menyimpan transaksi...';
    feedback.className = 'p-2 rounded-xl text-center text-xs font-bold bg-slate-100 text-slate-700 block';

    fetch('<?= BASE_URL ?>api/scan_lookup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            product_id: currentScannedProductId,
            action_type: actionType,
            qty: qty,
            notes: 'Aksi Cepat via Scan Barcode'
        })
    })
    .then(res => res.json())
    .then(resp => {
        if (!resp.success) {
            feedback.innerText = 'Gagal: ' + resp.message;
            feedback.className = 'p-2 rounded-xl text-center text-xs font-bold bg-rose-100 text-crimson-800 block';
        } else {
            feedback.innerText = '✅ ' + resp.message;
            feedback.className = 'p-2 rounded-xl text-center text-xs font-bold bg-emerald-100 text-emerald-800 block';

            // Refresh data terbaru
            const code = document.getElementById('manual-code-input').value;
            lookupCode(code);
        }
    })
    .catch(err => {
        console.error(err);
        feedback.innerText = 'Terjadi kesalahan jaringan.';
        feedback.className = 'p-2 rounded-xl text-center text-xs font-bold bg-rose-100 text-crimson-800 block';
    });
}

function resetScannerView() {
    document.getElementById('scan-result-card').classList.add('hidden');
    document.getElementById('scan-empty-state').classList.remove('hidden');
    document.getElementById('manual-code-input').value = '';
    currentScannedProductId = null;
}

document.addEventListener('DOMContentLoaded', () => {
    startCamera();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

