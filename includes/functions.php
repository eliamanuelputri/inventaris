<?php
/**
 * Global Helper Functions & Business Logic
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function db(): ?PDO {
    return Database::getConnection();
}

/**
 * Pembersihan input string
 */
function sanitize($input): string {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format Rupiah Indonesia
 */
function format_rupiah($number): string {
    $num = (float)$number;
    return 'Rp ' . number_format($num, 0, ',', '.');
}

/**
 * Format Tanggal Indonesia
 */
function format_date(?string $dateStr, bool $includeTime = false): string {
    if (empty($dateStr) || $dateStr === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return '-';

    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $day = date('d', $timestamp);
    $m = (int)date('m', $timestamp);
    $year = date('Y', $timestamp);
    $result = "$day {$months[$m]} $year";

    if ($includeTime) {
        $result .= ' ' . date('H:i', $timestamp) . ' WIB';
    }

    return $result;
}

/**
 * Flash Notification (Alert Success/Error/Warning)
 */
function flash_set(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function flash_get(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Cek Login & Role Petugas/Admin
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        flash_set('warning', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . BASE_URL . 'index.php?page=login');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        flash_set('error', 'Akses ditolak. Fitur ini hanya dapat dikelola oleh Admin/Pemilik.');
        header('Location: ' . BASE_URL . 'index.php?page=dashboard');
        exit;
    }
}

/**
 * Generate Nomor Transaksi Unik
 */
function generate_trx_no(string $type = 'in'): string {
    $prefix = ($type === 'in') ? 'TRX-IN' : 'TRX-OUT';
    $yearMonth = date('Ym');
    $rand = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "{$prefix}-{$yearMonth}-{$rand}";
}

function generate_opname_no(): string {
    $yearMonth = date('Ym');
    $rand = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "SOP-{$yearMonth}-{$rand}";
}

function generate_sku(string $categoryCode = 'BRG'): string {
    $rand = mt_rand(100, 999);
    $time = substr(time(), -3);
    return strtoupper($categoryCode) . '-' . $rand . $time;
}

/**
 * ALGORITMA FORECASTING:
 * Menghitung Rata-rata Pemakaian 30 Hari Terakhir (Burn Rate) & Estimasi Tanggal Habis Stok
 */
function calculate_product_forecast(int $productId, int $currentStock, int $minStock): array {
    $pdo = db();
    if (!$pdo) {
        return [
            'burn_rate' => 0,
            'burn_rate_formatted' => '0 / hari',
            'days_remaining' => 999,
            'days_remaining_text' => 'Tidak Terbatas',
            'runout_date' => null,
            'runout_date_formatted' => 'Aman / Belum Ada Pemakaian',
            'status' => ($currentStock <= $minStock) ? 'critical' : 'safe'
        ];
    }

    // Ambil total pengeluaran barang dalam 30 hari terakhir
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(ti.qty), 0) AS total_out
        FROM transaction_items ti
        INNER JOIN transactions t ON ti.transaction_id = t.id
        WHERE ti.product_id = :product_id 
          AND t.type = 'out'
          AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute(['product_id' => $productId]);
    $row = $stmt->fetch();
    $totalOut30Days = (int)($row['total_out'] ?? 0);

    // Rata-rata pemakaian per hari
    $dailyBurnRate = $totalOut30Days / 30.0;

    if ($dailyBurnRate <= 0.001) {
        $daysRemaining = 999;
        $runoutDate = null;
        $runoutDateText = 'Belum Ada Pemakaian 30 Hari';
        $status = ($currentStock <= $minStock) ? 'critical' : 'safe';
    } else {
        $daysRemaining = (int)floor($currentStock / $dailyBurnRate);
        $runoutDate = date('Y-m-d', strtotime("+{$daysRemaining} days"));
        $runoutDateText = format_date($runoutDate);

        if ($currentStock <= $minStock || $daysRemaining <= 7) {
            $status = 'critical'; // Merah
        } elseif ($daysRemaining <= 20) {
            $status = 'warning';  // Oranye/Kuning
        } else {
            $status = 'safe';     // Hijau
        }
    }

    return [
        'total_out_30_days' => $totalOut30Days,
        'burn_rate' => round($dailyBurnRate, 2),
        'burn_rate_formatted' => number_format($dailyBurnRate, 2, ',', '.') . ' / hari',
        'days_remaining' => $daysRemaining,
        'days_remaining_text' => ($daysRemaining >= 999) ? 'Sangat Aman (>1 tahun)' : "± {$daysRemaining} hari lagi",
        'runout_date' => $runoutDate,
        'runout_date_formatted' => $runoutDateText,
        'status' => $status
    ];
}

/**
 * ALGORITMA KEDALUWARSA (EXPIRY STATUS):
 * Menentukan status kedaluwarsa suatu batch/barang
 */
function get_expiry_status(?string $expiryDate): array {
    if (empty($expiryDate) || $expiryDate === '0000-00-00') {
        return [
            'status' => 'no_expiry',
            'days_left' => null,
            'label' => 'Non-Expired',
            'badge_class' => 'bg-gray-100 text-gray-700'
        ];
    }

    $today = new DateTime('today');
    $expDate = new DateTime($expiryDate);
    $diff = (int)$today->diff($expDate)->format('%r%a'); // Negatif jika sudah lewat

    if ($diff < 0) {
        return [
            'status' => 'expired',
            'days_left' => $diff,
            'label' => 'KEDALUWARSA (' . abs($diff) . ' hari lalu)',
            'badge_class' => 'bg-red-600 text-white animate-pulse'
        ];
    } elseif ($diff <= EXPIRY_WARNING_DAYS) {
        return [
            'status' => 'critical',
            'days_left' => $diff,
            'label' => "Segera Expired ({$diff} hari)",
            'badge_class' => 'bg-red-500 text-white'
        ];
    } elseif ($diff <= EXPIRY_ATTENTION_DAYS) {
        return [
            'status' => 'warning',
            'days_left' => $diff,
            'label' => "Mendekati ({$diff} hari)",
            'badge_class' => 'bg-amber-500 text-white'
        ];
    } else {
        return [
            'status' => 'safe',
            'days_left' => $diff,
            'label' => "Aman ({$diff} hari)",
            'badge_class' => 'bg-emerald-100 text-emerald-800'
        ];
    }
}

/**
 * Catat Riwayat Mutasi Stok (Audit Ledger)
 */
function record_stock_mutation(
    PDO $pdo,
    int $productId,
    ?int $batchId,
    ?int $locationId,
    string $refType,
    string $refNo,
    int $qtyIn,
    int $qtyOut,
    int $newStock,
    string $notes = '',
    ?int $userId = null
): void {
    $uid = $userId ?? ($_SESSION['user']['id'] ?? 1);
    $stmt = $pdo->prepare("
        INSERT INTO stock_mutations (product_id, batch_id, location_id, reference_type, reference_no, qty_in, qty_out, stock_after, notes, user_id, date)
        VALUES (:product_id, :batch_id, :location_id, :ref_type, :ref_no, :qty_in, :qty_out, :stock_after, :notes, :user_id, CURDATE())
    ");
    $stmt->execute([
        'product_id' => $productId,
        'batch_id' => $batchId,
        'location_id' => $locationId,
        'ref_type' => $refType,
        'ref_no' => $refNo,
        'qty_in' => $qtyIn,
        'qty_out' => $qtyOut,
        'stock_after' => $newStock,
        'notes' => $notes,
        'user_id' => $uid
    ]);
}

