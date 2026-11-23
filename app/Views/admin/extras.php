<?php
/**
 * Admin: Extras List Management
 */

declare(strict_types=1);

use App\Core\Csrf;

require APP_ROOT . '/app/Views/admin/nav.php';
?>

<div class="space-y-5">
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <h2 class="text-base sm:text-lg font-bold mb-3"><?= e(t('add_extra')) ?></h2>
        <form method="post" action="<?= e(app_url('?route=admin/extras')) ?>" class="flex flex-col sm:flex-row gap-2.5">
            <?= Csrf::input() ?>
            <input type="hidden" name="action" value="save_extra">
            <input type="hidden" name="is_active" value="1">
            <input type="text" name="label" required placeholder="<?= e(t('extra_label')) ?>"
                   class="flex-1 min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <input type="number" name="max_count" min="1" max="99" value="20" placeholder="<?= e(t('max_count')) ?>"
                   class="w-full sm:w-32 min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <button type="submit" class="min-h-[44px] px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shrink-0">
                + <?= e(t('add_new')) ?>
            </button>
        </form>
    </section>

    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-2.5">
        <?php foreach ($extras as $ex): ?>
            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                <form method="post" action="<?= e(app_url('?route=admin/extras')) ?>" class="flex flex-wrap sm:flex-nowrap items-center gap-2 flex-1">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="action" value="save_extra">
                    <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
                    <input type="hidden" name="is_active" value="<?= (int) $ex['is_active'] ?>">
                    <input type="text" name="label" value="<?= e($ex['label']) ?>" required
                           class="flex-1 min-w-[140px] min-h-[40px] px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-medium">
                    <input type="number" name="max_count" min="1" max="99" value="<?= (int) $ex['max_count'] ?>"
                           class="w-20 min-h-[40px] px-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                    <button type="submit" class="min-h-[40px] px-3 rounded-lg bg-slate-800 dark:bg-indigo-600 text-white text-xs font-semibold shrink-0"><?= e(t('save')) ?></button>
                </form>

                <div class="flex items-center gap-1 shrink-0">
                    <form method="post" class="inline">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="move_extra">
                        <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
                        <input type="hidden" name="dir" value="up">
                        <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900">↑</button>
                    </form>
                    <form method="post" class="inline">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="move_extra">
                        <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
                        <input type="hidden" name="dir" value="down">
                        <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900">↓</button>
                    </form>
                    <form method="post" class="inline">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="toggle_extra">
                        <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
                        <button type="submit" class="min-h-[38px] px-2.5 rounded-lg text-xs font-semibold border <?= (int) $ex['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-200 text-slate-600 border-slate-300 dark:bg-slate-800 dark:text-slate-400' ?>">
                            <?= (int) $ex['is_active'] ? e(t('active')) : e(t('inactive')) ?>
                        </button>
                    </form>
                    <form method="post" class="inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="delete_extra">
                        <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
                        <button type="submit" class="min-h-[38px] px-2.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800 text-xs font-semibold">✕</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</div>
