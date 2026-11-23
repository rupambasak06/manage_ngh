<?php
/**
 * Shared Footer Layout + Mobile Bottom Navigation Bar + Toast Container
 */

declare(strict_types=1);

$currentUser  = $currentUser ?? null;
$activeNav    = $activeNav ?? '';
$isOwnerOrMgr = $currentUser && in_array($currentUser['role'], ['owner', 'manager'], true);
?>
</main>

<!-- Mobile Bottom Navigation Bar (thumb-friendly on phones) -->
<?php if ($currentUser): ?>
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-800 mobile-bottom-nav no-print">
        <div class="grid <?= $isOwnerOrMgr ? 'grid-cols-4' : 'grid-cols-2' ?> h-16">
            <a href="<?= e(app_url('')) ?>"
               class="flex flex-col items-center justify-center gap-0.5 text-xs font-medium <?= $activeNav === 'checklist' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400' ?>">
                <span class="text-lg leading-none">📋</span>
                <span><?= e(t('nav_checklist')) ?></span>
            </a>
            <a href="<?= e(app_url('#daily-summary')) ?>"
               class="flex flex-col items-center justify-center gap-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                <span class="text-lg leading-none">📊</span>
                <span><?= e(t('nav_summary')) ?></span>
            </a>
            <?php if ($isOwnerOrMgr): ?>
                <a href="<?= e(app_url('?route=reports')) ?>"
                   class="flex flex-col items-center justify-center gap-0.5 text-xs font-medium <?= $activeNav === 'reports' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400' ?>">
                    <span class="text-lg leading-none">📄</span>
                    <span><?= e(t('nav_reports')) ?></span>
                </a>
                <a href="<?= e(app_url('?route=admin/floors')) ?>"
                   class="flex flex-col items-center justify-center gap-0.5 text-xs font-medium <?= $activeNav === 'admin' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400' ?>">
                    <span class="text-lg leading-none">⚙️</span>
                    <span><?= e(t('nav_admin')) ?></span>
                </a>
            <?php endif; ?>
        </div>
    </nav>
<?php endif; ?>

<!-- Toast Notification Stack -->
<div id="toast-container" class="fixed bottom-20 md:bottom-5 right-4 z-50 flex flex-col gap-2 max-w-xs w-full pointer-events-none"></div>

<script src="<?= e(app_url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
