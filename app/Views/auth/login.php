<?php
/**
 * Mobile-First Bengali Login View
 */

declare(strict_types=1);

use App\Core\Csrf;
?>
<!DOCTYPE html>
<html lang="bn" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e(t('login_title')) ?> — <?= e(t('app_title')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('hcl_theme');
                if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['"Noto Sans Bengali"', 'sans-serif'] } } } };
    </script>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-full bg-gradient-to-br from-indigo-50 via-slate-100 to-slate-200 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 flex items-center justify-center p-4">

<div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl font-bold shadow-md mb-3">✓</div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white"><?= e(t('app_title')) ?></h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?= e(t('login_subtitle')) ?></p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-200 text-sm font-medium">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(app_url('?route=login')) ?>" class="space-y-4">
        <?= Csrf::input() ?>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5"><?= e(t('email')) ?></label>
            <input id="email" name="email" type="email" required autocomplete="email" autofocus
                   value="<?= e($email ?? '') ?>"
                   class="w-full min-h-[46px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-base focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5"><?= e(t('password')) ?></label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="w-full min-h-[46px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-base focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit"
                class="w-full min-h-[48px] rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white font-semibold text-base shadow-sm transition">
            <?= e(t('login_button')) ?>
        </button>
    </form>
</div>

</body>
</html>
