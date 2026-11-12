<?php
/**
 * Application Global Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

define('APP_NAME', 'INVENTA');
define('APP_TAGLINE', 'Smart Inventory & Stock Control');
define('APP_VERSION', '1.0.0');

// Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/') . '/';
define('BASE_URL', $baseUrl);

// Path constants
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Business Logic Thresholds
define('DEFAULT_MIN_STOCK', 10);
define('EXPIRY_WARNING_DAYS', 30); // Alert jika kedaluwarsa <= 30 hari
define('EXPIRY_ATTENTION_DAYS', 60); // Waspada jika <= 60 hari
define('FORECAST_HISTORY_DAYS', 30); // Analisis burn rate 30 hari terakhir

