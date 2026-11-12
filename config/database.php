<?php
/**
 * Database Connection & Auto-Migration Handler
 * Modern Inventory System (Sortly x Odoo Style)
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'inventaris_db');

class Database {
    private static ?PDO $instance = null;
    private static ?string $connectionError = null;

    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        try {
            // 1. Coba hubungkan ke MySQL server terlebih dahulu
            $dsnNoDb = sprintf("mysql:host=%s;port=%s;charset=utf8mb4", DB_HOST, DB_PORT);
            $pdoInit = new PDO($dsnNoDb, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3
            ]);

            // 2. Buat database jika belum ada
            $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 3. Hubungkan ke database target
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // 4. Periksa apakah tabel sudah ada. Jika belum, lakukan auto-migrasi
            self::checkAndRunMigrations($pdo);

            self::$instance = $pdo;
            return self::$instance;

        } catch (PDOException $e) {
            self::$connectionError = $e->getMessage();
            return null;
        }
    }

    public static function getError(): ?string {
        return self::$connectionError;
    }

    private static function checkAndRunMigrations(PDO $pdo): void {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
            if ($stmt->rowCount() === 0) {
                // Jalankan schema.sql
                $schemaFile = __DIR__ . '/../database/schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }

                // Jalankan seed.sql
                $seedFile = __DIR__ . '/../database/seed.sql';
                if (file_exists($seedFile)) {
                    $sqlSeed = file_get_contents($seedFile);
                    $pdo->exec($sqlSeed);
                }
            }
        } catch (PDOException $ex) {
            error_log("Migration notice: " . $ex->getMessage());
        }
    }
}

