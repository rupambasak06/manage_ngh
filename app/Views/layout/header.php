<?php
/**
 * Shared Responsive Mobile-First Header Layout
 * Uses Tailwind CSS CDN (no server build step needed) + Noto Sans Bengali + custom app.css.
 * Note: For offline/self-hosted CSS, you may compile Tailwind locally and replace the CDN script with your built CSS.
 */

declare(strict_types=1);

use App\Core\Csrf;

$currentUser = $currentUser ?? null;
$activeNav   = $activeNav ?? '';
$pageTitle   = $pageTitle ?? t('app_title');
$flash       = flash_get();
$isOwnerOrMgr = $currentUser && in_array($currentUser['role'], ['owner', 'manager'], true);
?>
<!DOCTYPE html>
<html lang="bn" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <title><?= e($pageTitle) ?> — <?= e(t('app_title')) ?></title>

    <!-- Noto Sans Bengali Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Dark mode initialization before paint to avoid flash -->
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

    <!-- Tailwind CSS Play CDN (zero-build shared hosting ready) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Noto Sans Bengali"', 'system-ui', 'sans-serif'],
                    }
                }
            }
        };
    </script>

    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-full bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased pb-mobile-nav">

<!-- Top Navigation Bar -->
<header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-slate-200 dark:border-slate-800 no-print">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 h-14 flex items-center justify-between gap-2">
        <!-- Brand -->
        <a href="<?= e(app_url('')) ?>" class="flex items-center gap-2.5 min-w-0">
            <span class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">✓</span>
            <div class="truncate">
                <div class="font-bold text-sm sm:text-base leading-tight truncate text-slate-900 dark:text-white"><?= e(t('app_title')) ?></div>
                <?php if ($currentUser): ?>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        <?= e($currentUser['name']) ?> · <?= e(t('role_' . $currentUser['role'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </a>

        <!-- Desktop Navigation -->
        <?php if ($currentUser): ?>
            <nav class="hidden md:flex items-center gap-1">
                <a href="<?= e(app_url('')) ?>"
                   class="px-3.5 py-2 rounded-xl text-sm font-medium transition <?= $activeNav === 'checklist' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                    <?= e(t('nav_checklist')) ?>
                </a>
                <?php if ($isOwnerOrMgr): ?>
                    <a href="<?= e(app_url('?route=reports')) ?>"
                       class="px-3.5 py-2 rounded-xl text-sm font-medium transition <?= $activeNav === 'reports' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <?= e(t('nav_reports')) ?>
                    </a>
                    <a href="<?= e(app_url('?route=admin/floors')) ?>"
                       class="px-3.5 py-2 rounded-xl text-sm font-medium transition <?= $activeNav === 'admin' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <?= e(t('nav_admin')) ?>
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Right Actions -->
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button" data-toggle-theme aria-label="<?= e(t('nav_dark_mode')) ?>"
                        class="tap-btn inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
                    <span data-theme-icon="sun" class="hidden text-lg">☀️</span>
                    <span data-theme-icon="moon" class="text-lg">🌙</span>
                </button>
                <form method="post" action="<?= e(app_url('?route=logout')) ?>" class="inline">
                    <?= Csrf::input() ?>
                    <button type="submit"
                            class="tap-btn px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                        <?= e(t('nav_logout')) ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</header>

<!-- Flash Message -->
<?php if ($flash): ?>
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 mt-3 no-print">
        <div class="px-4 py-3 rounded-xl text-sm font-medium border <?= $flash['type'] === 'error'
            ? 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/60 dark:border-rose-800 dark:text-rose-200'
            : 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/60 dark:border-emerald-800 dark:text-emerald-200' ?>">
            <?= e($flash['message']) ?>
        </div>
    </div>
<?php endif; ?>

<main class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 pt-3">
