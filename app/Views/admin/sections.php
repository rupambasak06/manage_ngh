<?php
/**
 * Admin: Sections Management
 */

declare(strict_types=1);

use App\Core\Csrf;

require APP_ROOT . '/app/Views/admin/nav.php';
?>

<div class="space-y-5">
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <h2 class="text-base sm:text-lg font-bold mb-3"><?= e(t('add_section')) ?></h2>
        <form method="post" action="<?= e(app_url('?route=admin/sections')) ?>" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <?= Csrf::input() ?>
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="is_active" value="1">
            <input type="text" name="title" required placeholder="<?= e(t('section_title')) ?>"
                   class="flex-1 min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 min-h-[44px]">
                <input type="checkbox" name="counts_toward_checkout" value="1" class="w-5 h-5 rounded text-indigo-600">
                <span><?= e(t('counts_checkout_label')) ?></span>
            </label>
            <button type="submit" class="min-h-[44px] px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shrink-0">
                + <?= e(t('add_new')) ?>
            </button>
        </form>
    </section>

    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3">
        <?php foreach ($sections as $sec): ?>
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <form method="post" action="<?= e(app_url('?route=admin/sections')) ?>" class="flex flex-col sm:flex-row sm:items-center gap-2 flex-1">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="action" value="save_section">
                    <input type="hidden" name="id" value="<?= (int) $sec['id'] ?>">
                    <input type="hidden" name="is_active" value="<?= (int) $sec['is_active'] ?>">
                    <input type="text" name="title" value="<?= e($sec['title']) ?>" required
                           class="flex-1 min-h-[42px] px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-medium">
                    <label class="inline-flex items-center gap-1.5 text-xs sm:text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="counts_toward_checkout" value="1" <?= (int) $sec['counts_toward_checkout'] ? 'checked' : '' ?> class="w-4 h-4 rounded">
                        <span><?= e(t('counts_checkout_label')) ?></span>
                    </label>
                    <button type="submit" class="min-h-[40px] px-3.5 rounded-lg bg-slate-800 dark:bg-indigo-600 text-white text-xs font-semibold shrink-0"><?= e(t('save')) ?></button>
                </form>

                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="text-xs text-slate-500 mr-1">(<?= e(bn_num($sec['option_count'])) ?> অপশন)</span>
                    <form method="post" class="inline">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="move_section">
                        <input type="hidden" name="id" value="<?= (int) $sec['id'] ?>">
                        <input type="hidden" name="dir" value="up">
                        <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900">↑</button>
                    </form>
                    <form method="post" class="inline">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="move_section">
                        <input type="hidden" name="id" value="<?= (int) $sec['id'] ?>">
                        <input type="hidden" name="dir" value="down">
                        <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900">↓</button>
                    </form>
                    <form method="post" class="inline">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="toggle_section">
                        <input type="hidden" name="id" value="<?= (int) $sec['id'] ?>">
                        <button type="submit" class="min-h-[38px] px-2.5 rounded-lg text-xs font-semibold border <?= (int) $sec['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-200 text-slate-600 border-slate-300 dark:bg-slate-800 dark:text-slate-400' ?>">
                            <?= (int) $sec['is_active'] ? e(t('active')) : e(t('inactive')) ?>
                        </button>
                    </form>
                    <form method="post" class="inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="delete_section">
                        <input type="hidden" name="id" value="<?= (int) $sec['id'] ?>">
                        <button type="submit" class="min-h-[38px] px-2.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800 text-xs font-semibold">✕</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</div>
