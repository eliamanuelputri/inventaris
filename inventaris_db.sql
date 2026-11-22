-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 22, 2026 at 11:08 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaris_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT '#f97316',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `code`, `color`, `description`, `created_at`) VALUES
(1, 'Bahan Baku & Pangan', 'CAT-RAW', '#f97316', 'Bahan baku makanan, minuman, dan bahan mentah', '2026-11-12 11:50:17'),
(2, 'Kemasan & Packaging', 'CAT-PKG', '#ea580c', 'Kardus, standing pouch, bubble wrap, stiker label', '2026-11-12 11:50:17'),
(3, 'Elektronik & Sparepart', 'CAT-ELC', '#dc2626', 'Komponen kelistrikan, sensor, kabel, perlengkapan digital', '2026-11-12 11:50:17'),
(4, 'Perlengkapan Operasional', 'CAT-OPS', '#9a3412', 'Alat tulis kantor, kebersihan, dan logistik gudang', '2026-11-12 11:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int NOT NULL,
  `warehouse_name` varchar(100) NOT NULL,
  `rack_code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `warehouse_name`, `rack_code`, `description`, `created_at`) VALUES
(1, 'Gudang Utama A', 'Rak A-01', 'Rak besi lantai 1 - Kategori Bahan Baku', '2026-11-12 11:50:17'),
(2, 'Gudang Utama A', 'Rak A-02', 'Rak besi lantai 1 - Bahan Baku Cepat Saji', '2026-11-12 11:50:17'),
(3, 'Gudang Kemasan B', 'Rak B-01', 'Area kardus, kemasan & bubble wrap', '2026-11-12 11:50:17'),
(4, 'Gudang Kemasan B', 'Rak B-02', 'Area standing pouch & botol plastik', '2026-11-12 11:50:17'),
(5, 'Gudang Dingin (Chiller)', 'Rak C-01', 'Ruang pendingin suhu 4°C untuk produk khusus', '2026-11-12 11:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `sku` varchar(50) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int DEFAULT NULL,
  `unit_id` int DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `min_stock` int NOT NULL DEFAULT '5',
  `current_stock` int NOT NULL DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `track_expiry` tinyint(1) NOT NULL DEFAULT '0',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `barcode`, `name`, `category_id`, `unit_id`, `location_id`, `purchase_price`, `selling_price`, `min_stock`, `current_stock`, `image`, `track_expiry`, `description`, `created_at`, `updated_at`) VALUES
(1, 'BRG-001', '899123456001', 'Biji Kopi Arabika Gayo 1kg', 1, 3, 1, '95000.00', '135000.00', 15, 38, 'kopi-arabika.jpg', 1, 'Biji kopi sangrai kualitas premium specialty grade', '2026-11-12 11:50:17', '2026-11-12 11:50:17'),
(2, 'BRG-002', '899123456002', 'Susu Evaporasi UHT 1 Liter', 1, 4, 5, '18500.00', '24000.00', 20, 8, 'susu-uht.jpg', 1, 'Susu cair evaporasi krimer barista (Mendekati Minimum!)', '2026-11-12 11:50:17', '2026-11-12 11:50:17'),
(3, 'BRG-003', '899123456003', 'Sirup Karamel Premium 750ml', 1, 4, 2, '85000.00', '115000.00', 10, 4, 'sirup-karamel.jpg', 1, 'Sirup perasa karamel untuk racikan minuman (Stok Menipis!)', '2026-11-12 11:50:17', '2026-11-12 11:50:17'),
(4, 'BRG-004', '899123456004', 'Standing Pouch Kraft Foil 250g', 2, 5, 4, '1200.00', '2000.00', 100, 450, 'standing-pouch.jpg', 0, 'Kemasan kertas kraft zipper dengan lapisan aluminium', '2026-11-12 11:50:17', '2026-11-12 11:50:17'),
(5, 'BRG-005', '899123456005', 'Kardus Master Box 40x30x25 cm', 2, 1, 3, '6500.00', '9500.00', 50, 120, 'master-box.jpg', 0, 'Kardus karton tebal corrugated single wall', '2026-11-12 11:50:17', '2026-11-12 11:50:17'),
(6, 'BRG-006', '899123456006', 'Thermal Paper Roll 58x30 mm', 4, 6, 3, '3500.00', '6000.00', 30, 85, 'thermal-paper.jpg', 0, 'Kertas struk kasir dan cetak barcode thermal', '2026-11-12 11:50:17', '2026-11-12 11:50:17'),
(7, 'BRG-007', '899123456007', 'Bubuk Cokelat Dark Kakao 1kg', 1, 3, 2, '62000.00', '88000.00', 12, 19, 'bubuk-cokelat.jpg', 1, 'Bubuk murni kakao tanpa pemanis', '2026-11-12 11:50:17', '2026-11-12 11:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `batch_no` varchar(50) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `initial_qty` int NOT NULL DEFAULT '0',
  `current_qty` int NOT NULL DEFAULT '0',
  `location_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_batches`
--

INSERT INTO `product_batches` (`id`, `product_id`, `batch_no`, `expiry_date`, `initial_qty`, `current_qty`, `location_id`, `created_at`) VALUES
(1, 1, 'BATCH-KOP-202610', '2027-05-11', 40, 38, 1, '2026-11-12 11:50:17'),
(2, 2, 'BATCH-SSU-202609', '2026-11-26', 30, 8, 5, '2026-11-12 11:50:17'),
(3, 3, 'BATCH-SRP-202608', '2026-12-27', 20, 4, 2, '2026-11-12 11:50:17'),
(4, 7, 'BATCH-CKL-202611', '2027-07-10', 25, 19, 2, '2026-11-12 11:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'INVENTA'),
('company_name', 'Inventa Logistik Mandiri'),
('currency_symbol', 'Rp'),
('expiry_alert_days', '30'),
('forecast_days', '30'),
('low_stock_auto_alert', '1'),
('theme_color', 'sunset-orange');

-- --------------------------------------------------------

--
-- Table structure for table `stock_mutations`
--

CREATE TABLE `stock_mutations` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `qty_in` int NOT NULL DEFAULT '0',
  `qty_out` int NOT NULL DEFAULT '0',
  `stock_after` int NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_opnames`
--

CREATE TABLE `stock_opnames` (
  `id` int NOT NULL,
  `opname_no` varchar(50) NOT NULL,
  `audit_date` date NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('draft','completed','cancelled') NOT NULL DEFAULT 'draft',
  `total_items` int NOT NULL DEFAULT '0',
  `total_variance_qty` int NOT NULL DEFAULT '0',
  `total_variance_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_opnames`
--

INSERT INTO `stock_opnames` (`id`, `opname_no`, `audit_date`, `user_id`, `status`, `total_items`, `total_variance_qty`, `total_variance_value`, `notes`, `completed_at`, `created_at`) VALUES
(1, 'SOP-202611-001', '2026-11-05', 1, 'completed', 3, -2, '-19000.00', 'Audit rutin awal bulan gudang utama', '2026-11-05 11:50:17', '2026-11-12 11:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname_items`
--

CREATE TABLE `stock_opname_items` (
  `id` int NOT NULL,
  `opname_id` int NOT NULL,
  `product_id` int NOT NULL,
  `location_id` int DEFAULT NULL,
  `system_qty` int NOT NULL,
  `physical_qty` int NOT NULL,
  `variance_qty` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `variance_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reason` varchar(50) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_opname_items`
--

INSERT INTO `stock_opname_items` (`id`, `opname_id`, `product_id`, `location_id`, `system_qty`, `physical_qty`, `variance_qty`, `unit_price`, `variance_value`, `reason`, `notes`) VALUES
(1, 1, 1, 1, 40, 40, 0, '95000.00', '0.00', 'koreksi_hitung', 'Fisik dan sistem cocok tepat'),
(2, 1, 2, 5, 22, 20, -2, '18500.00', '-37000.00', 'barang_rusak', '2 pcs bocor kemasan saat pemindahan'),
(3, 1, 4, 4, 450, 450, 0, '1200.00', '0.00', 'koreksi_hitung', 'Hitungan sesuai');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `code`, `name`, `contact_person`, `phone`, `email`, `address`, `notes`, `created_at`) VALUES
(1, 'SUP-001', 'PT Sumber Makmur Sentosa', 'Hendra Wijaya', '081122334455', 'sales@sumbermakmur.co.id', 'Jl. Industri Raya No. 45, Kawasan Industri Jababeka, Cikarang', 'Pemasok utama bahan baku pangan & kopi', '2026-11-12 11:50:17'),
(2, 'SUP-002', 'CV Bintang Kemasan Mandiri', 'Siti Rahma', '082233445566', 'order@bintangkemasan.com', 'Jl. Raya Daan Mogot KM 12, Jakarta Barat', 'Supplier kemasan kardus, kantong vakum, pouch', '2026-11-12 11:50:17'),
(3, 'SUP-003', 'PT Indotech Global Solusi', 'Kevin Pratama', '083344556677', 'info@indotechglobal.com', 'Jl. Gatot Subroto Kav 88, Bandung', 'Distributor komponen elektronik & barcode', '2026-11-12 11:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `transaction_no` varchar(50) NOT NULL,
  `type` enum('in','out') NOT NULL,
  `reason` varchar(50) NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `destination` varchar(150) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `user_id` int NOT NULL,
  `total_items` int NOT NULL DEFAULT '0',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_no`, `type`, `reason`, `supplier_id`, `destination`, `reference_no`, `transaction_date`, `user_id`, `total_items`, `total_amount`, `notes`, `created_at`) VALUES
(1, 'TRX-IN-202610-001', 'in', 'pembelian', 1, NULL, 'PO-2026-101', '2026-10-18', 1, 50, '4750000.00', 'Pembelian restock biji kopi arabika', '2026-11-12 11:50:17'),
(2, 'TRX-IN-202610-002', 'in', 'pembelian', 1, NULL, 'PO-2026-102', '2026-10-21', 1, 40, '740000.00', 'Pengiriman batch susu evaporasi UHT', '2026-11-12 11:50:17'),
(3, 'TRX-OUT-202610-001', 'out', 'pemakaian_internal', NULL, 'Barista Outlet A', 'WO-OUT-001', '2026-10-23', 2, 12, '1140000.00', 'Pemakaian bahan baku outlet cabang 1', '2026-11-12 11:50:17'),
(4, 'TRX-OUT-202610-002', 'out', 'penjualan', NULL, 'Customer Retail Toko', 'INV-2026-089', '2026-10-28', 2, 10, '185000.00', 'Penjualan eceran susu uht', '2026-11-12 11:50:17'),
(5, 'TRX-OUT-202610-003', 'out', 'pemakaian_internal', NULL, 'Barista Outlet B', 'WO-OUT-002', '2026-11-04', 2, 12, '222000.00', 'Pemakaian susu UHT & sirup', '2026-11-12 11:50:17'),
(6, 'TRX-OUT-202611-001', 'out', 'penjualan', NULL, 'Cafe Partner Mitra', 'INV-2026-102', '2026-11-09', 2, 8, '680000.00', 'Distribusi sirup dan kopi bubuk', '2026-11-12 11:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `product_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `qty` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaction_items`
--

INSERT INTO `transaction_items` (`id`, `transaction_id`, `product_id`, `batch_id`, `location_id`, `qty`, `unit_price`, `subtotal`, `expiry_date`) VALUES
(1, 1, 1, 1, 1, 50, '95000.00', '4750000.00', '2027-05-11'),
(2, 2, 2, 2, 5, 40, '18500.00', '740000.00', '2026-11-26'),
(3, 3, 1, 1, 1, 12, '95000.00', '1140000.00', NULL),
(4, 4, 2, 2, 5, 10, '18500.00', '185000.00', NULL),
(5, 5, 2, 2, 5, 12, '18500.00', '222000.00', NULL),
(6, 6, 3, 3, 2, 8, '85000.00', '680000.00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `symbol`) VALUES
(1, 'Pieces / Buah', 'Pcs'),
(2, 'Kotak / Box', 'Box'),
(3, 'Kilogram', 'Kg'),
(4, 'Liter', 'Liter'),
(5, 'Pak / Pack', 'Pak'),
(6, 'Roll / Gulung', 'Roll');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas') NOT NULL DEFAULT 'petugas',
  `phone` varchar(30) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `role`, `phone`, `avatar`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Rian Hidayat (Owner)', 'admin@inventaris.com', '$2y$10$j2Svl8Ktwhf.Yo7g5Qqhy.0JXqxaZHiQgN1CrYw3n.IG9nD/6zOMu', 'admin', '081234567890', NULL, 1, '2026-11-12 11:50:17', '2026-11-12 11:50:17'),
(2, 'petugas', 'Budi Santoso (Staff Gudang)', 'petugas@inventaris.com', '$2y$10$OyjF0pd4umWKJXwptxrn5Oai283CRKT4kojqmwLsDVMgBzycYmRZq', 'petugas', '089876543210', NULL, 1, '2026-11-12 11:50:17', '2026-11-12 11:50:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `stock_mutations`
--
ALTER TABLE `stock_mutations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `opname_no` (`opname_no`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opname_id` (`opname_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_no` (`transaction_no`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_mutations`
--
ALTER TABLE `stock_mutations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `product_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_batches_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_mutations`
--
ALTER TABLE `stock_mutations`
  ADD CONSTRAINT `stock_mutations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_mutations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  ADD CONSTRAINT `stock_opnames_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD CONSTRAINT `stock_opname_items_ibfk_1` FOREIGN KEY (`opname_id`) REFERENCES `stock_opnames` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_opname_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_opname_items_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_items_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaction_items_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
