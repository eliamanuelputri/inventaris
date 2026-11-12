-- Seed Data Inventarisasi Modern (Sortly x Odoo Style)

-- 1. Users (admin: admin123, petugas: petugas123)
INSERT INTO users (id, username, name, email, password, role, phone, is_active) VALUES
(1, 'admin', 'Rian Hidayat (Owner)', 'admin@inventaris.com', '$2y$10$j2Svl8Ktwhf.Yo7g5Qqhy.0JXqxaZHiQgN1CrYw3n.IG9nD/6zOMu', 'admin', '081234567890', 1),
(2, 'petugas', 'Budi Santoso (Staff Gudang)', 'petugas@inventaris.com', '$2y$10$OyjF0pd4umWKJXwptxrn5Oai283CRKT4kojqmwLsDVMgBzycYmRZq', 'petugas', '089876543210', 1);

-- 2. Categories
INSERT INTO categories (id, name, code, color, description) VALUES
(1, 'Bahan Baku & Pangan', 'CAT-RAW', '#f97316', 'Bahan baku makanan, minuman, dan bahan mentah'),
(2, 'Kemasan & Packaging', 'CAT-PKG', '#ea580c', 'Kardus, standing pouch, bubble wrap, stiker label'),
(3, 'Elektronik & Sparepart', 'CAT-ELC', '#dc2626', 'Komponen kelistrikan, sensor, kabel, perlengkapan digital'),
(4, 'Perlengkapan Operasional', 'CAT-OPS', '#9a3412', 'Alat tulis kantor, kebersihan, dan logistik gudang');

-- 3. Units
INSERT INTO units (id, name, symbol) VALUES
(1, 'Pieces / Buah', 'Pcs'),
(2, 'Kotak / Box', 'Box'),
(3, 'Kilogram', 'Kg'),
(4, 'Liter', 'Liter'),
(5, 'Pak / Pack', 'Pak'),
(6, 'Roll / Gulung', 'Roll');

-- 4. Locations (Gudang & Rak)
INSERT INTO locations (id, warehouse_name, rack_code, description) VALUES
(1, 'Gudang Utama A', 'Rak A-01', 'Rak besi lantai 1 - Kategori Bahan Baku'),
(2, 'Gudang Utama A', 'Rak A-02', 'Rak besi lantai 1 - Bahan Baku Cepat Saji'),
(3, 'Gudang Kemasan B', 'Rak B-01', 'Area kardus, kemasan & bubble wrap'),
(4, 'Gudang Kemasan B', 'Rak B-02', 'Area standing pouch & botol plastik'),
(5, 'Gudang Dingin (Chiller)', 'Rak C-01', 'Ruang pendingin suhu 4°C untuk produk khusus');

-- 5. Suppliers
INSERT INTO suppliers (id, code, name, contact_person, phone, email, address, notes) VALUES
(1, 'SUP-001', 'PT Sumber Makmur Sentosa', 'Hendra Wijaya', '081122334455', 'sales@sumbermakmur.co.id', 'Jl. Industri Raya No. 45, Kawasan Industri Jababeka, Cikarang', 'Pemasok utama bahan baku pangan & kopi'),
(2, 'SUP-002', 'CV Bintang Kemasan Mandiri', 'Siti Rahma', '082233445566', 'order@bintangkemasan.com', 'Jl. Raya Daan Mogot KM 12, Jakarta Barat', 'Supplier kemasan kardus, kantong vakum, pouch'),
(3, 'SUP-003', 'PT Indotech Global Solusi', 'Kevin Pratama', '083344556677', 'info@indotechglobal.com', 'Jl. Gatot Subroto Kav 88, Bandung', 'Distributor komponen elektronik & barcode');

-- 6. Products
INSERT INTO products (id, sku, barcode, name, category_id, unit_id, location_id, purchase_price, selling_price, min_stock, current_stock, image, track_expiry, description) VALUES
(1, 'BRG-001', '899123456001', 'Biji Kopi Arabika Gayo 1kg', 1, 3, 1, 95000.00, 135000.00, 15, 38, 'kopi-arabika.jpg', 1, 'Biji kopi sangrai kualitas premium specialty grade'),
(2, 'BRG-002', '899123456002', 'Susu Evaporasi UHT 1 Liter', 1, 4, 5, 18500.00, 24000.00, 20, 8, 'susu-uht.jpg', 1, 'Susu cair evaporasi krimer barista (Mendekati Minimum!)'),
(3, 'BRG-003', '899123456003', 'Sirup Karamel Premium 750ml', 1, 4, 2, 85000.00, 115000.00, 10, 4, 'sirup-karamel.jpg', 1, 'Sirup perasa karamel untuk racikan minuman (Stok Menipis!)'),
(4, 'BRG-004', '899123456004', 'Standing Pouch Kraft Foil 250g', 2, 5, 4, 1200.00, 2000.00, 100, 450, 'standing-pouch.jpg', 0, 'Kemasan kertas kraft zipper dengan lapisan aluminium'),
(5, 'BRG-005', '899123456005', 'Kardus Master Box 40x30x25 cm', 2, 1, 3, 6500.00, 9500.00, 50, 120, 'master-box.jpg', 0, 'Kardus karton tebal corrugated single wall'),
(6, 'BRG-006', '899123456006', 'Thermal Paper Roll 58x30 mm', 4, 6, 3, 3500.00, 6000.00, 30, 85, 'thermal-paper.jpg', 0, 'Kertas struk kasir dan cetak barcode thermal'),
(7, 'BRG-007', '899123456007', 'Bubuk Cokelat Dark Kakao 1kg', 1, 3, 2, 62000.00, 88000.00, 12, 19, 'bubuk-cokelat.jpg', 1, 'Bubuk murni kakao tanpa pemanis');

-- 7. Product Batches (dengan variasi tanggal kedaluwarsa untuk demo fitur smart alert)
INSERT INTO product_batches (id, product_id, batch_no, expiry_date, initial_qty, current_qty, location_id) VALUES
(1, 1, 'BATCH-KOP-202610', DATE_ADD(CURDATE(), INTERVAL 180 DAY), 40, 38, 1),
(2, 2, 'BATCH-SSU-202609', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 30, 8, 5), -- Mendekati Expired (< 30 hari!)
(3, 3, 'BATCH-SRP-202608', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 20, 4, 2), -- Mendekati Expired (< 60 hari)
(4, 7, 'BATCH-CKL-202611', DATE_ADD(CURDATE(), INTERVAL 240 DAY), 25, 19, 2);

-- 8. Transactions (Masuk & Keluar dalam 30 hari terakhir agar kalkulasi Burn Rate & Forecasting langsung akurat)
INSERT INTO transactions (id, transaction_no, type, reason, supplier_id, destination, reference_no, transaction_date, user_id, total_items, total_amount, notes) VALUES
(1, 'TRX-IN-202610-001', 'in', 'pembelian', 1, NULL, 'PO-2026-101', DATE_SUB(CURDATE(), INTERVAL 25 DAY), 1, 50, 4750000.00, 'Pembelian restock biji kopi arabika'),
(2, 'TRX-IN-202610-002', 'in', 'pembelian', 1, NULL, 'PO-2026-102', DATE_SUB(CURDATE(), INTERVAL 22 DAY), 1, 40, 740000.00, 'Pengiriman batch susu evaporasi UHT'),
(3, 'TRX-OUT-202610-001', 'out', 'pemakaian_internal', NULL, 'Barista Outlet A', 'WO-OUT-001', DATE_SUB(CURDATE(), INTERVAL 20 DAY), 2, 12, 1140000.00, 'Pemakaian bahan baku outlet cabang 1'),
(4, 'TRX-OUT-202610-002', 'out', 'penjualan', NULL, 'Customer Retail Toko', 'INV-2026-089', DATE_SUB(CURDATE(), INTERVAL 15 DAY), 2, 10, 185000.00, 'Penjualan eceran susu uht'),
(5, 'TRX-OUT-202610-003', 'out', 'pemakaian_internal', NULL, 'Barista Outlet B', 'WO-OUT-002', DATE_SUB(CURDATE(), INTERVAL 8 DAY), 2, 12, 222000.00, 'Pemakaian susu UHT & sirup'),
(6, 'TRX-OUT-202611-001', 'out', 'penjualan', NULL, 'Cafe Partner Mitra', 'INV-2026-102', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 2, 8, 680000.00, 'Distribusi sirup dan kopi bubuk');

-- 9. Transaction Items
INSERT INTO transaction_items (id, transaction_id, product_id, batch_id, location_id, qty, unit_price, subtotal, expiry_date) VALUES
(1, 1, 1, 1, 1, 50, 95000.00, 4750000.00, DATE_ADD(CURDATE(), INTERVAL 180 DAY)),
(2, 2, 2, 2, 5, 40, 18500.00, 740000.00, DATE_ADD(CURDATE(), INTERVAL 14 DAY)),
(3, 3, 1, 1, 1, 12, 95000.00, 1140000.00, NULL),
(4, 4, 2, 2, 5, 10, 18500.00, 185000.00, NULL),
(5, 5, 2, 2, 5, 12, 18500.00, 222000.00, NULL),
(6, 6, 3, 3, 2, 8, 85000.00, 680000.00, NULL);

-- 10. Sample Stock Opname (Sesi Opname Audit Terakhir)
INSERT INTO stock_opnames (id, opname_no, audit_date, user_id, status, total_items, total_variance_qty, total_variance_value, notes, completed_at) VALUES
(1, 'SOP-202611-001', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 1, 'completed', 3, -2, -19000.00, 'Audit rutin awal bulan gudang utama', DATE_SUB(NOW(), INTERVAL 7 DAY));

INSERT INTO stock_opname_items (id, opname_id, product_id, location_id, system_qty, physical_qty, variance_qty, unit_price, variance_value, reason, notes) VALUES
(1, 1, 1, 1, 40, 40, 0, 95000.00, 0.00, 'koreksi_hitung', 'Fisik dan sistem cocok tepat'),
(2, 1, 2, 5, 22, 20, -2, 18500.00, -37000.00, 'barang_rusak', '2 pcs bocor kemasan saat pemindahan'),
(3, 1, 4, 4, 450, 450, 0, 1200.00, 0.00, 'koreksi_hitung', 'Hitungan sesuai');

-- 11. Initial Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('app_name', 'INVENTA'),
('company_name', 'Inventa Logistik Mandiri'),
('currency_symbol', 'Rp'),
('expiry_alert_days', '30'),
('forecast_days', '30'),
('theme_color', 'sunset-orange'),
('low_stock_auto_alert', '1');

