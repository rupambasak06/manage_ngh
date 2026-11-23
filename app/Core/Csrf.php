<?php
/**
 * CSRF Token Protection (Form POST + AJAX Header)
 */

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function input(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $provided = null): bool
    {
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if ($sessionToken === '') {
            return false;
        }
        if ($provided === null) {
            $provided = $_POST['_csrf']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? '';
        }
        return is_string($provided) && hash_equals($sessionToken, $provided);
    }

    public static function requireValid(bool $isAjax = false): void
    {
        if (!self::verify()) {
            if ($isAjax) {
                json_response(['ok' => false, 'error' => t('csrf_invalid')], 403);
            }
            flash_set('error', t('csrf_invalid'));
            $ref = $_SERVER['HTTP_REFERER'] ?? app_url('');
            header('Location: ' . $ref);
            exit;
        }
    }
}
