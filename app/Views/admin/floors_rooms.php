<?php
/**
 * Admin: Floors & Rooms Management
 */

declare(strict_types=1);

use App\Core\Csrf;

require APP_ROOT . '/app/Views/admin/nav.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <!-- FLOORS PANEL -->
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <h2 class="text-base sm:text-lg font-bold mb-3"><?= e(t('add_floor')) ?></h2>
        <form method="post" action="<?= e(app_url('?route=admin/floors')) ?>" class="flex flex-col sm:flex-row gap-2 mb-5">
            <?= Csrf::input() ?>
            <input type="hidden" name="action" value="save_floor">
            <input type="hidden" name="is_active" value="1">
            <input type="text" name="name" required placeholder="<?= e(t('floor_name')) ?>"
                   class="flex-1 min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shrink-0">
                + <?= e(t('add_new')) ?>
            </button>
        </form>

        <div class="space-y-2">
            <?php foreach ($floors as $fl): ?>
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-wrap items-center justify-between gap-2">
                    <form method="post" action="<?= e(app_url('?route=admin/floors')) ?>" class="flex items-center gap-2 flex-1 min-w-[180px]">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="save_floor">
                        <input type="hidden" name="id" value="<?= (int) $fl['id'] ?>">
                        <input type="hidden" name="is_active" value="<?= (int) $fl['is_active'] ?>">
                        <input type="text" name="name" value="<?= e($fl['name']) ?>" required
                               class="w-full min-h-[40px] px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-medium">
                        <button type="submit" class="min-h-[40px] px-3 rounded-lg bg-slate-800 dark:bg-indigo-600 text-white text-xs font-semibold shrink-0"><?= e(t('save')) ?></button>
                    </form>
                    <div class="flex items-center gap-1">
                        <span class="text-xs text-slate-500 mr-1">(<?= e(bn_num($fl['room_count'])) ?> রুম)</span>
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="move_floor">
                            <input type="hidden" name="id" value="<?= (int) $fl['id'] ?>">
                            <input type="hidden" name="dir" value="up">
                            <button type="submit" title="<?= e(t('move_up')) ?>" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">↑</button>
                        </form>
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="move_floor">
                            <input type="hidden" name="id" value="<?= (int) $fl['id'] ?>">
                            <input type="hidden" name="dir" value="down">
                            <button type="submit" title="<?= e(t('move_down')) ?>" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">↓</button>
                        </form>
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="toggle_floor">
                            <input type="hidden" name="id" value="<?= (int) $fl['id'] ?>">
                            <button type="submit" class="min-h-[38px] px-2.5 rounded-lg text-xs font-semibold border <?= (int) $fl['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-200 text-slate-600 border-slate-300 dark:bg-slate-800 dark:text-slate-400' ?>">
                                <?= (int) $fl['is_active'] ? e(t('active')) : e(t('inactive')) ?>
                            </button>
                        </form>
                        <form method="post" class="inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="delete_floor">
                            <input type="hidden" name="id" value="<?= (int) $fl['id'] ?>">
                            <button type="submit" class="min-h-[38px] px-2.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800 text-xs font-semibold">✕</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ROOMS PANEL -->
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <h2 class="text-base sm:text-lg font-bold mb-3"><?= e(t('add_room')) ?></h2>
        <form method="post" action="<?= e(app_url('?route=admin/floors')) ?>" class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-5">
            <?= Csrf::input() ?>
            <input type="hidden" name="action" value="save_room">
            <input type="hidden" name="is_active" value="1">
            <select name="floor_id" required class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <option value=""><?= e(t('select_floor')) ?></option>
                <?php foreach ($floors as $fl): ?>
                    <option value="<?= (int) $fl['id'] ?>"><?= e($fl['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="room_number" required placeholder="<?= e(t('room_number')) ?>"
                   class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm">
                + <?= e(t('add_new')) ?>
            </button>
        </form>

        <div class="space-y-2 max-h-[540px] overflow-y-auto pr-1">
            <?php foreach ($rooms as $rm): ?>
                <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-sm">
                            <?= e($rm['room_number']) ?>
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($rm['floor_name']) ?></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="move_room">
                            <input type="hidden" name="id" value="<?= (int) $rm['id'] ?>">
                            <input type="hidden" name="dir" value="up">
                            <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">↑</button>
                        </form>
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="move_room">
                            <input type="hidden" name="id" value="<?= (int) $rm['id'] ?>">
                            <input type="hidden" name="dir" value="down">
                            <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">↓</button>
                        </form>
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="toggle_room">
                            <input type="hidden" name="id" value="<?= (int) $rm['id'] ?>">
                            <button type="submit" class="min-h-[38px] px-2.5 rounded-lg text-xs font-semibold border <?= (int) $rm['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-200 text-slate-600 border-slate-300 dark:bg-slate-800 dark:text-slate-400' ?>">
                                <?= (int) $rm['is_active'] ? e(t('active')) : e(t('inactive')) ?>
                            </button>
                        </form>
                        <form method="post" class="inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="delete_room">
                            <input type="hidden" name="id" value="<?= (int) $rm['id'] ?>">
                            <button type="submit" class="min-h-[38px] px-2.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800 text-xs font-semibold">✕</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
