<?php
/**
 * Front Controller & Router
 * Hotel Room Daily Checklist & Inventory System
 */

declare(strict_types=1);

define('APP_ROOT', __DIR__);

// If config.php does not exist yet, send user to the one-time web installer
if (!is_file(APP_ROOT . '/config/config.php')) {
    if (is_file(APP_ROOT . '/install.php')) {
        header('Location: install.php');
        exit;
    }
    http_response_code(500);
    exit('config/config.php পাওয়া যায়নি। অনুগ্রহ করে install.php রান করুন অথবা config/config.sample.php কপি করুন।');
}

$config = require APP_ROOT . '/config/config.php';

// Timezone
date_default_timezone_set((string) ($config['app']['timezone'] ?? 'Asia/Kolkata'));

// Error reporting
if (!empty($config['app']['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// Determine base URL automatically if not explicitly set
$baseUrl = trim((string) ($config['app']['base_url'] ?? ''));
if ($baseUrl === '') {
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
    $baseUrl = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
}
$GLOBALS['APP_BASE_URL'] = $baseUrl;
$GLOBALS['APP_CONFIG']   = $config;

// Simple PSR-4-style autoloader for App\* (no Composer needed on shared hosting)
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = APP_ROOT . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once APP_ROOT . '/app/Core/Helpers.php';

use App\Core\Auth;
use App\Core\Database;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ChecklistController;
use App\Controllers\ReportController;

Auth::startSession($config['app'] ?? []);
$pdo = Database::getConnection($config['db'] ?? []);

$route  = trim((string) ($_GET['route'] ?? ''), '/');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

// Route dispatch
switch ($route) {
    case 'login':
        $ctrl = new AuthController($pdo);
        if ($method === 'POST') {
            $ctrl->loginSubmit();
        } else {
            $ctrl->loginForm();
        }
        break;

    case 'logout':
        (new AuthController($pdo))->logout();
        break;

    case '':
    case 'checklist':
        (new ChecklistController($pdo, $config))->index();
        break;

    case 'api/option':
        if ($method !== 'POST') {
            json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
        }
        (new ChecklistController($pdo, $config))->apiSaveOption();
        break;

    case 'api/extra':
        if ($method !== 'POST') {
            json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
        }
        (new ChecklistController($pdo, $config))->apiSaveExtra();
        break;

    case 'api/note':
        if ($method !== 'POST') {
            json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
        }
        (new ChecklistController($pdo, $config))->apiSaveNote();
        break;

    case 'api/poll':
        (new ChecklistController($pdo, $config))->apiPoll();
        break;

    case 'reports':
        (new ReportController($pdo))->index();
        break;

    case 'reports/print':
        (new ReportController($pdo))->printView();
        break;

    case 'reports/csv':
        (new ReportController($pdo))->exportCsv();
        break;

    case 'admin':
    case 'admin/floors':
        (new AdminController($pdo))->floorsRooms();
        break;

    case 'admin/sections':
        (new AdminController($pdo))->sections();
        break;

    case 'admin/options':
        (new AdminController($pdo))->options();
        break;

    case 'admin/extras':
        (new AdminController($pdo))->extras();
        break;

    case 'admin/defaults':
        (new AdminController($pdo))->defaults();
        break;

    case 'admin/users':
        (new AdminController($pdo))->users();
        break;

    case 'admin/audit':
        (new AdminController($pdo))->audit();
        break;

    default:
        http_response_code(404);
        echo '404 - পেজটি পাওয়া যায়নি। <a href="' . e(app_url('')) . '">হোম পেজে ফিরুন</a>';
        break;
}
