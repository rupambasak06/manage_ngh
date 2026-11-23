<?php
/**
 * One-Time Web Installer for Shared Hosting (cPanel / Apache / PHP 8 / MySQL)
 *
 * 1. Tests database connection.
 * 2. Imports sql/database.sql (schema + Bengali seed data).
 * 3. Creates the initial Owner account (with password_hash).
 * 4. Writes config/config.php.
 * 5. Creates install.lock to permanently lock itself.
 *
 * For extra security, delete install.php from File Manager after installation.
 */

declare(strict_types=1);

$lockFile   = __DIR__ . '/install.lock';
$configFile = __DIR__ . '/config/config.php';
$sqlFile    = __DIR__ . '/sql/database.sql';

if (is_file($lockFile)) {
    http_response_code(403);
    exit('<!DOCTYPE html><html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ইনস্টলার লক করা আছে</title></head><body style="font-family:sans-serif;padding:2rem;text-align:center;"><h2>ইনস্টলেশন ইতিমধ্যেই সম্পন্ন ও লক করা হয়েছে।</h2><p>নিরাপত্তার জন্য cPanel File Manager থেকে <code>install.php</code> ফাইলটি মুছে ফেলুন।</p><p><a href="index.php">লগইন পেজে যান →</a></p></body></html>');
}

session_start();
if (empty($_SESSION['_install_csrf'])) {
    $_SESSION['_install_csrf'] = bin2hex(random_bytes(32));
}

$errors  = [];
$success = false;
$form    = [
    'db_host'    => 'localhost',
    'db_port'    => '3306',
    'db_name'    => '',
    'db_user'    => '',
    'db_pass'    => '',
    'timezone'   => 'Asia/Kolkata',
    'owner_name' => 'মালিক',
    'owner_email'=> '',
    'owner_pass' => '',
];

// Environment checks
$phpOk       = version_compare(PHP_VERSION, '8.0.0', '>=');
$pdoMysqlOk  = extension_loaded('pdo_mysql');
$configDirOk = is_writable(__DIR__ . '/config');
$rootDirOk   = is_writable(__DIR__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['_csrf'] ?? '');
    if (!hash_equals((string) $_SESSION['_install_csrf'], $csrf)) {
        $errors[] = 'সিকিউরিটি টোকেন সঠিক নয়। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।';
    }

    foreach (array_keys($form) as $k) {
        $form[$k] = trim((string) ($_POST[$k] ?? $form[$k]));
    }

    if ($form['db_name'] === '' || $form['db_user'] === '') {
        $errors[] = 'ডেটাবেসের নাম এবং ইউজারনেম আবশ্যক।';
    }
    if ($form['owner_name'] === '' || !filter_var($form['owner_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'মালিকের সঠিক নাম এবং ইমেইল দিন।';
    }
    if (strlen($form['owner_pass']) < 6) {
        $errors[] = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
    }
    if (!$phpOk || !$pdoMysqlOk) {
        $errors[] = 'সার্ভারে PHP 8.0+ এবং PDO MySQL এক্সটেনশন চালু থাকতে হবে।';
    }
    if (!is_file($sqlFile)) {
        $errors[] = 'sql/database.sql ফাইলটি পাওয়া যায়নি।';
    }

    if (empty($errors)) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $form['db_host'],
                $form['db_port'],
                $form['db_name']
            );
            $pdo = new PDO($dsn, $form['db_user'], $form['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Execute SQL schema + seed statements safely
            $sqlContent = (string) file_get_contents($sqlFile);
            $lines = preg_split('/\R/', $sqlContent) ?: [];
            $cleanLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }
                $cleanLines[] = $line;
            }
            $statements = array_filter(array_map('trim', explode(";\n", implode("\n", $cleanLines))));
            foreach ($statements as $stmtSql) {
                $stmtSql = rtrim($stmtSql, "; \t\n\r");
                if ($stmtSql !== '') {
                    $pdo->exec($stmtSql);
                }
            }

            // Create Owner Account (or update if re-installing on existing DB)
            $hash = password_hash($form['owner_pass'], PASSWORD_DEFAULT);
            $stmtOwner = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, role, is_active)
                 VALUES (:n, :e, :h, 'owner', 1)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = 'owner', is_active = 1"
            );
            $stmtOwner->execute([
                ':n' => $form['owner_name'],
                ':e' => strtolower($form['owner_email']),
                ':h' => $hash,
            ]);

            // Write config/config.php
            $cfgPhp = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export([
                'db' => [
                    'host'    => $form['db_host'],
                    'port'    => $form['db_port'],
                    'name'    => $form['db_name'],
                    'user'    => $form['db_user'],
                    'pass'    => $form['db_pass'],
                    'charset' => 'utf8mb4',
                ],
                'app' => [
                    'name'             => 'হোটেল রুম ডেইলি চেকলিস্ট',
                    'timezone'         => $form['timezone'] ?: 'Asia/Kolkata',
                    'base_url'         => '',
                    'poll_interval'    => 10000,
                    'session_name'     => 'HCL_SESSID',
                    'session_lifetime' => 43200,
                    'debug'            => false,
                ],
            ], true) . ";\n";

            if (file_put_contents($configFile, $cfgPhp, LOCK_EX) === false) {
                throw new RuntimeException('config/config.php লেখা যায়নি। config ফোল্ডারের পারমিশন (755) চেক করুন।');
            }

            // Lock installer
            file_put_contents($lockFile, 'Installed at ' . date('c') . "\n", LOCK_EX);
            $success = true;
        } catch (Throwable $e) {
            $errors[] = 'ইনস্টলেশন ত্রুটি: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইনস্টলেশন — হোটেল রুম ডেইলি চেকলিস্ট</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Noto Sans Bengali', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

<div class="w-full max-w-xl bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sm:p-8">
    <div class="text-center mb-6">
        <div class="w-12 h-12 mx-auto rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-xl font-bold mb-2">✓</div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">হোটেল রুম ডেইলি চেকলিস্ট ইনস্টলার</h1>
        <p class="text-sm text-slate-500 mt-1">একবার সেটআপ করুন — টেবিল, বাংলা ডেটা ও মালিকের অ্যাকাউন্ট স্বয়ংক্রিয়ভাবে তৈরি হবে</p>
    </div>

    <?php if ($success): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 space-y-2">
            <div class="font-bold text-base">🎉 ইনস্টলেশন সফলভাবে সম্পন্ন হয়েছে!</div>
            <p class="text-sm">ডেটাবেস টেবিল, বাংলা সিড ডেটা এবং মালিকের অ্যাকাউন্ট তৈরি হয়েছে। <code>install.lock</code> তৈরি করে ইনস্টলার লক করা হয়েছে।</p>
            <p class="text-sm font-semibold text-rose-700">নিরাপত্তার জন্য এখনই cPanel File Manager থেকে <code>install.php</code> ফাইলটি মুছে ফেলুন।</p>
            <div class="pt-2">
                <a href="index.php" class="inline-block px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold text-sm">লগইন পেজে যান →</a>
            </div>
        </div>
    <?php else: ?>
        <!-- System Checks -->
        <div class="mb-5 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm space-y-1">
            <div>PHP 8.0+: <strong class="<?= $phpOk ? 'text-emerald-600' : 'text-rose-600' ?>"><?= htmlspecialchars(PHP_VERSION) ?> <?= $phpOk ? '✓' : '✕' ?></strong></div>
            <div>PDO MySQL: <strong class="<?= $pdoMysqlOk ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $pdoMysqlOk ? 'চালু আছে ✓' : 'নেই ✕' ?></strong></div>
            <div>config/ লেখা যাবে: <strong class="<?= $configDirOk ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $configDirOk ? 'হ্যাঁ ✓' : 'না ✕' ?></strong></div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm space-y-1">
                <?php foreach ($errors as $err): ?>
                    <div>• <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $_SESSION['_install_csrf'], ENT_QUOTES, 'UTF-8') ?>">

            <h2 class="font-bold text-sm text-indigo-700 border-b pb-1">১. MySQL ডেটাবেস তথ্য (cPanel থেকে প্রাপ্ত)</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">ডেটাবেস হোস্ট</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($form['db_host']) ?>" required class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">পোর্ট</label>
                    <input type="text" name="db_port" value="<?= htmlspecialchars($form['db_port']) ?>" required class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">ডেটাবেসের নাম</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($form['db_name']) ?>" required placeholder="cpaneluser_hotel" class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">ডেটাবেস ইউজারনেম</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($form['db_user']) ?>" required placeholder="cpaneluser_user" class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">ডেটাবেস পাসওয়ার্ড</label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($form['db_pass']) ?>" class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">টাইমজোন</label>
                    <select name="timezone" class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                        <option value="Asia/Kolkata" <?= $form['timezone'] === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (ভারত - IST)</option>
                        <option value="Asia/Dhaka" <?= $form['timezone'] === 'Asia/Dhaka' ? 'selected' : '' ?>>Asia/Dhaka (বাংলাদেশ - BST)</option>
                    </select>
                </div>
            </div>

            <h2 class="font-bold text-sm text-indigo-700 border-b pb-1 pt-2">২. মালিক (Owner) অ্যাকাউন্ট তৈরি করুন</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">মালিকের নাম</label>
                    <input type="text" name="owner_name" value="<?= htmlspecialchars($form['owner_name']) ?>" required class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">মালিকের ইমেইল</label>
                    <input type="email" name="owner_email" value="<?= htmlspecialchars($form['owner_email']) ?>" required placeholder="owner@example.com" class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold mb-1">মালিকের পাসওয়ার্ড (কমপক্ষে ৬ অক্ষর)</label>
                    <input type="password" name="owner_pass" required minlength="6" class="w-full min-h-[44px] px-3 rounded-xl border border-slate-300 text-sm">
                </div>
            </div>

            <button type="submit" class="w-full min-h-[48px] rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-base shadow-sm">
                ইনস্টল করুন →
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
