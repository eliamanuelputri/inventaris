# INVENTA PRO - Sistem Inventarisasi & Kontrol Stok Cerdas
> **Gaya Desain Sortly x Odoo Fusion** | Warna Utama: **Sunset Orange & Crimson Red** | Responsif PWA

Aplikasi inventaris modern yang dirancang bukan sekadar mencatat keluar-masuk barang, melainkan memberikan kendali bisnis penuh dengan algoritma prediksi kehabisan barang (*30-day burn rate forecasting*), kontrol masa kedaluwarsa batch, rekonsiliasi audit fisik (*stock opname*), dan multi-lokasi penataan rak/gudang.

---

## 🌟 Fitur Utama & Keunggulan

1. **Autentikasi & Multi-Role Berjenjang:**
   - **Admin (Pemilik):** Memiliki hak akses penuh mengelola seluruh master data, setting sistem, hapus transaksi, dan manajemen akun pengguna.
   - **Petugas Gudang:** Berfokus pada pencatatan transaksi barang masuk, barang keluar, audit hitungan fisik opname, dan cek stok harian.

2. **Master Data Barang & Multi-Lokasi Rak (Sortly Visual Style):**
   - Kode unik SKU & Barcode scanner ready.
   - Upload foto produk resolusi tinggi.
   - Toggle tampilan fleksibel: **Mode Kartu Visual (Sortly)** dengan foto besar atau **Mode Tabel Rinci (Odoo)**.
   - Penataan lokasi fisik barang hingga tingkat nama gudang dan kode rak (*Aisle / Rack / Bin*).

3. **Manajemen Pemasok (Data Supplier):**
   - Buku kontak supplier lengkap (nama, PIC, nomor WhatsApp, email, dan alamat gudang).
   - Akumulasi total transaksi dan riwayat barang yang dibeli dari supplier terkait.

4. **Barang Masuk (Inbound / Pembelian):**
   - Pencatatan tanda terima pembelian dari pemasok dengan nomor faktur/PO.
   - Nomor batch produksi dan tanggal kedaluwarsa (*expiry tracking*).
   - Penambahan stok fisik otomatis dan pembaruan modal harga beli.

5. **Barang Keluar (Outbound / Penjualan & Pemakaian):**
   - Alokasi pengeluaran barang: penjualan konsumen, pemakaian operasional internal divisi, barang rusak, atau retur ke supplier.
   - Validasi stok real-time (mencegah stok minus).
   - Pengurangan stok otomatis dan pencatatan audit log mutasi.

6. **Algoritma Cerdas: Peringatan & Perkiraan Habis (Forecasting):**
   - **Alarm Stok Kritis:** Menandai barang yang berada di bawah batas minimum (*min_stock*).
   - **Estimasi Hari Habis (Burn Rate 30 Hari):** Menghitung rata-rata laju konsumsi barang per hari selama 30 hari terakhir, lalu memproyeksikan sisa hari ketersediaan dan tanggal pasti kehabisan stok:
     $$\text{Daily Burn Rate} = \frac{\sum_{t=1}^{30} \text{Qty Keluar}_t}{30}$$
     $$\text{Estimasi Sisa Hari} = \frac{\text{Stok Saat Ini}}{\text{Daily Burn Rate}}$$
   - **Alarm Multi-Tier Kedaluwarsa:** Peringatan otomatis jika batch mendekati masa expired ($\le 30$ hari kalender) atau sudah kedaluwarsa.

7. **Stock Opname (Audit Hitungan Fisik & Rekonsiliasi Otomatis):**
   - Lembar hitung fisik real-time dengan perbandingan langsung terhadap stok sistem.
   - Perhitungan otomatis selisih kuantitas ($\Delta$) dan nilai kerugian/keuntungan finansial.
   - Pilihan alasan selisih (*koreksi hitung, rusak, hilang, kedaluwarsa*).
   - Saat difinalisasi, sistem **secara otomatis menyesuaikan saldo stok** dan mencatat jurnal mutasi penyesuaian.

8. **Laporan & Ekspor Data (Excel & PDF Resmi):**
   - Laporan Valuasi Aset Inventaris, Rekap Pembelian, Rekap Pengeluaran, dan Berita Acara Opname.
   - Unduh langsung ke file spreadsheet Excel (`.xlsx`) via 1 klik.
   - Format cetak PDF resmi ber-kop surat lengkap dengan kolom tanda tangan otorisasi.

9. **Progressive Web App (PWA) & Kamera Scanner Barcode:**
   - Dapat di-install langsung ke layar utama Android, iOS, maupun Windows desktop seperti aplikasi native.
   - Dilengkapi scanner kamera langsung di browser untuk membaca barcode/QR code fisik barang.

---

## 🚀 Panduan Menjalankan di Laragon

Aplikasi ini sudah siap pakai di folder `C:\laragon\www\inventaris`.

1. **Buka Aplikasi Laragon** di komputer Anda.
2. Klik tombol **"Start All"** (memulai service Apache dan MySQL).
3. Buka browser (Chrome / Edge / Firefox) dan kunjungi salah satu URL berikut:
   - `http://localhost/inventaris`
   - atau `http://inventaris.test`
4. **Auto-Database Installer:**
   - Saat pertama kali dibuka, aplikasi akan secara otomatis membuat database `inventaris_db`, meng-generate seluruh tabel, dan mengisi sample data demo yang lengkap!

---

## 🔑 Akun Demo Siap Pakai (1-Klik di Halaman Login)

| Role | Username / Email | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Admin (Owner)** | `admin` *(atau admin@inventaris.com)* | `admin123` | Kelola seluruh data, pengguna, master data, dan laporan |
| **Petugas Gudang** | `petugas` *(atau petugas@inventaris.com)* | `petugas123` | Mencatat barang masuk, barang keluar, dan stock opname |

---

## 📁 Struktur Direktori Proyek

```
c:\laragon\www\inventaris/
├── actions/                  # Controller pemroses formulir & CRUD
│   ├── auth_action.php       # Login, logout, dan ganti profil
│   ├── inbound_action.php    # Transaksi barang masuk
│   ├── location_action.php   # Manajemen gudang & rak
│   ├── opname_action.php     # Rekonsiliasi stock opname
│   ├── outbound_action.php   # Transaksi barang keluar
│   ├── product_action.php    # CRUD data barang & upload foto
│   ├── supplier_action.php   # CRUD data supplier
│   └── user_action.php       # Manajemen akun pengguna (Admin)
├── assets/                   # Icon PWA, font, dan stylesheet
├── config/                   # Konfigurasi aplikasi & database
│   ├── app.php               # Konstanta global & parameter bisnis
│   └── database.php          # Koneksi PDO MySQL & auto-migrator
├── database/                 # Skema SQL & seeder data awal
│   ├── schema.sql            # Struktur tabel relasional
│   ├── seed.sql              # Data awal master & transaksi demo
│   └── migrate.php           # Script migrasi mandiri CLI
├── includes/                 # Komponen antarmuka & helper
│   ├── footer.php            # Modal scanner barcode & PWA script
│   ├── functions.php         # Algoritma forecasting & expiry
│   ├── header.php            # Styling Tailwind & font Jakarta Sans
│   ├── navbar.php            # Global search, scanner trigger & lonceng alert
│   └── sidebar.php           # Navigasi Sortly/Odoo desktop & bottom nav HP
├── pages/                    # Halaman tampilan antarmuka
│   ├── dashboard.php         # Ringkasan KPI, grafik tren, & stok menipis
│   ├── forecasting/          # Analisis burn rate & pelacakan expired
│   ├── locations/            # Data rak & multi-gudang
│   ├── login.php             # Form masuk sistem dengan demo filler
│   ├── opname/               # Lembar hitung fisik & berita acara
│   ├── products/             # Katalog Sortly card & Odoo table
│   ├── profile.php           # Pengaturan profil pengguna
│   ├── reports/              # Pusat laporan, cetak PDF & ekspor Excel
│   ├── suppliers/            # Buku alamat pemasok
│   ├── transactions/         # Formulir masuk, keluar, dan kartu stok
│   └── users/                # Hak akses pengguna (Admin only)
├── uploads/                  # Penyimpanan berkas foto barang
├── .htaccess                 # Konfigurasi Apache rewrite & cache PWA
├── manifest.json             # Konfigurasi instalasi PWA
├── sw.js                     # Service Worker PWA offline cache
└── index.php                 # Router utama aplikasi
```

