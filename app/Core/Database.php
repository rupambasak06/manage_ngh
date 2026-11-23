<?php
/**
 * Database Connection Singleton (PDO with Prepared Statements Only)
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    /**
     * Get shared PDO instance.
     *
     * @param array<string, mixed> $dbConfig
     */
    public static function getConnection(array $dbConfig): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['host'] ?? 'localhost',
            $dbConfig['port'] ?? '3306',
            $dbConfig['name'] ?? '',
            $dbConfig['charset'] ?? 'utf8mb4'
        );

        try {
            self::$instance = new PDO(
                $dsn,
                (string) ($dbConfig['user'] ?? ''),
                (string) ($dbConfig['pass'] ?? ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            // Ensure connection uses UTF8MB4 for Bengali text
            self::$instance->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('ডেটাবেস সংযোগ ব্যর্থ হয়েছে। অনুগ্রহ করে config/config.php যাচাই করুন।');
        }

        return self::$instance;
    }
}
