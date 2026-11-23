<?php
/**
 * Authentication, Session Security, Rate Limiting & Role Checks
 */

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    /**
     * Configure and start a hardened PHP session.
     *
     * @param array<string, mixed> $appConfig
     */
    public static function startSession(array $appConfig): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

        $lifetime = (int) ($appConfig['session_lifetime'] ?? 43200);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        session_name((string) ($appConfig['session_name'] ?? 'HCL_SESSID'));
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Check if current IP or email is rate-limited.
     */
    public static function isRateLimited(PDO $pdo, string $ip, string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM login_attempts
                WHERE (ip_address = :ip OR (email <> '' AND email = :email))
                  AND attempted_at >= (NOW() - INTERVAL " . self::LOCKOUT_MINUTES . " MINUTE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ip' => $ip, ':email' => strtolower(trim($email))]);
        return ((int) $stmt->fetchColumn()) >= self::MAX_ATTEMPTS;
    }

    public static function recordFailedAttempt(PDO $pdo, string $ip, string $email): void
    {
        // Prune old attempts opportunistically
        $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");

        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (:ip, :email)");
        $stmt->execute([':ip' => $ip, ':email' => strtolower(trim($email))]);
    }

    public static function clearAttempts(PDO $pdo, string $ip, string $email): void
    {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip OR email = :email");
        $stmt->execute([':ip' => $ip, ':email' => strtolower(trim($email))]);
    }

    /**
     * Attempt login. Returns ['ok' => bool, 'error' => string].
     *
     * @return array{ok: bool, error: string}
     */
    public static function attempt(PDO $pdo, string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $ip    = client_ip();

        if (self::isRateLimited($pdo, $ip, $email)) {
            return ['ok' => false, 'error' => t('login_rate_limited')];
        }

        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            self::recordFailedAttempt($pdo, $ip, $email);
            return ['ok' => false, 'error' => t('login_failed')];
        }

        if ((int) $user['is_active'] !== 1) {
            return ['ok' => false, 'error' => t('login_disabled')];
        }

        // Upgrade hash if PHP default algorithm/cost changed
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
            $up->execute([':h' => $newHash, ':id' => $user['id']]);
        }

        self::clearAttempts($pdo, $ip, $email);

        $upLogin = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $upLogin->execute([':id' => $user['id']]);

        session_regenerate_id(true);
        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['user_name'] = (string) $user['name'];
        $_SESSION['user_role'] = (string) $user['role'];

        Audit::log($pdo, 'login', 'user', (int) $user['id'], null, null, null, (string) $user['email'], null, 'লগইন');

        return ['ok' => true, 'error' => ''];
    }

    /**
     * Get currently authenticated active user from DB (verified per request).
     *
     * @return array<string, mixed>|null
     */
    public static function user(PDO $pdo): ?array
    {
        static $cached = false;
        if ($cached !== false) {
            return $cached;
        }

        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id <= 0) {
            $cached = null;
            return null;
        }

        $stmt = $pdo->prepare("SELECT id, name, email, role, is_active FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user || (int) $user['is_active'] !== 1) {
            self::logout();
            $cached = null;
            return null;
        }

        $_SESSION['user_name'] = (string) $user['name'];
        $_SESSION['user_role'] = (string) $user['role'];
        $cached = $user;
        return $cached;
    }

    /**
     * Require authenticated user.
     *
     * @return array<string, mixed>
     */
    public static function requireLogin(PDO $pdo, bool $isAjax = false): array
    {
        $user = self::user($pdo);
        if ($user === null) {
            if ($isAjax) {
                json_response(['ok' => false, 'error' => t('unauthorized'), 'auth_required' => true], 401);
            }
            redirect('?route=login');
        }
        return $user;
    }

    /**
     * Require user to have one of the allowed roles.
     *
     * @param string[] $roles
     * @return array<string, mixed>
     */
    public static function requireRoles(PDO $pdo, array $roles, bool $isAjax = false): array
    {
        $user = self::requireLogin($pdo, $isAjax);
        if (!in_array($user['role'], $roles, true)) {
            if ($isAjax) {
                json_response(['ok' => false, 'error' => t('unauthorized')], 403);
            }
            flash_set('error', t('unauthorized'));
            redirect('');
        }
        return $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
