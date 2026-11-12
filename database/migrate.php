<?php
/**
 * Database Migration & Seeder CLI/Web Runner
 */

require_once __DIR__ . '/../config/database.php';

echo "=== INVENTA PRO Database Setup & Migration ===\n";

try {
    $dsnNoDb = sprintf("mysql:host=%s;port=%s;charset=utf8mb4", DB_HOST, DB_PORT);
    $pdoInit = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "[1/4] Connecting to MySQL Server at " . DB_HOST . ":" . DB_PORT . "...\n";
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[2/4] Database '" . DB_NAME . "' ensured.\n";

    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "[3/4] Running schema.sql...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);
    echo "      Tables created successfully.\n";

    echo "[4/4] Running seed.sql...\n";
    $seedSql = file_get_contents(__DIR__ . '/seed.sql');
    $pdo->exec($seedSql);
    echo "      Sample data & demo accounts seeded successfully!\n";

    echo "\n=== Migration Selesai dengan Sukses! ===\n";
    echo "Akun Admin   : admin / admin123\n";
    echo "Akun Petugas : petugas / petugas123\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migration gagal: " . $e->getMessage() . "\n";
    echo "Pastikan service MySQL di Laragon sudah dinyalakan (klik 'Start All').\n";
}

