<?php
/**
 * Global Helper Functions
 * Escaping, translation, Bengali digits/dates, URLs, JSON responses, flash messages.
 */

declare(strict_types=1);

/**
 * Load and cache Bengali language strings.
 *
 * @return array<string, mixed>
 */
function lang_all(): array
{
    static $strings = null;
    if ($strings === null) {
        $strings = require APP_ROOT . '/lang/bn.php';
    }
    return $strings;
}

/**
 * Get a translated UI string by key.
 */
function t(string $key, ?string $fallback = null): string
{
    $strings = lang_all();
    if (isset($strings[$key]) && is_string($strings[$key])) {
        return $strings[$key];
    }
    return $fallback ?? $key;
}

/**
 * Escape output safely for HTML context.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Convert ASCII digits 0-9 into Bengali digits ০-৯.
 */
function bn_num(int|float|string $number): string
{
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($en, $bn, (string) $number);
}

/**
 * Format YYYY-MM-DD into a human-friendly Bengali date string.
 * Example: "সোমবার, ২৩ নভেম্বর ২০২৬"
 */
function bn_date(string $dateStr, bool $withWeekday = true): string
{
    $ts = strtotime($dateStr);
    if ($ts === false) {
        return $dateStr;
    }
    $strings  = lang_all();
    $day      = bn_num((int) date('j', $ts));
    $month    = $strings['months'][(int) date('n', $ts)] ?? date('M', $ts);
    $year     = bn_num(date('Y', $ts));
    $weekday  = $strings['weekdays'][(int) date('w', $ts)] ?? '';

    if ($withWeekday && $weekday !== '') {
        return "{$weekday}, {$day} {$month} {$year}";
    }
    return "{$day} {$month} {$year}";
}

/**
 * Format DATETIME string into Bengali date + time (12h).
 */
function bn_datetime(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return t('never');
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $datePart = bn_date(date('Y-m-d', $ts), false);
    $hour24   = (int) date('G', $ts);
    $period   = $hour24 < 12 ? 'সকাল' : ($hour24 < 16 ? 'দুপুর' : ($hour24 < 19 ? 'বিকাল' : 'রাত'));
    $timePart = bn_num(date('g:i', $ts));
    return "{$datePart}, {$period} {$timePart}";
}

/**
 * Validate YYYY-MM-DD date string; fallback to today.
 */
function normalize_date(?string $date): string
{
    if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $parts = explode('-', $date);
        if (checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return $date;
        }
    }
    return date('Y-m-d');
}

/**
 * Build a URL relative to the application base path.
 */
function app_url(string $path = ''): string
{
    $base = rtrim((string) ($GLOBALS['APP_BASE_URL'] ?? ''), '/');
    $path = ltrim($path, '/');
    return $path === '' ? ($base === '' ? '/' : $base . '/') : $base . '/' . $path;
}

/**
 * Redirect and terminate.
 */
function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

/**
 * Send JSON response and terminate.
 *
 * @param array<string, mixed> $payload
 */
function json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Set a one-time session flash message.
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Consume and return the current flash message (if any).
 *
 * @return array{type: string, message: string}|null
 */
function flash_get(): ?array
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

/**
 * Get client IP address safely (for audit log & login rate limit).
 */
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr((string) $ip, 0, 45);
}

/**
 * Render a PHP view file inside layout or standalone.
 *
 * @param array<string, mixed> $data
 */
function render_view(string $viewPath, array $data = [], bool $withLayout = true): void
{
    extract($data, EXTR_SKIP);
    $fullPath = APP_ROOT . '/app/Views/' . ltrim($viewPath, '/') . '.php';
    if (!is_file($fullPath)) {
        http_response_code(500);
        exit('View not found: ' . e($viewPath));
    }
    if ($withLayout) {
        require APP_ROOT . '/app/Views/layout/header.php';
        require $fullPath;
        require APP_ROOT . '/app/Views/layout/footer.php';
    } else {
        require $fullPath;
    }
}
