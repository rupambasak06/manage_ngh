<?php
/**
 * Admin: Versioned Room Defaults (effective_from = today)
 */

declare(strict_types=1);

use App\Core\Csrf;

require APP_ROOT . '/app/Views/admin/nav.php';
?>

<div class="space-y-5">
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white"><?= e(t('defaults_title')) ?></h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5"><?= e(t('defaults_help')) ?></p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                <?= e(t('effective_from_today')) ?> (<?= e(bn_date(date('Y-m-d'), false)) ?>)
            </span>
        </div>

        <!-- Room Selector -->
        <form method="get" action="<?= e(app_url('')) ?>" class="flex flex-wrap items-center gap-2 mb-5">
            <input type="hidden" name="route" value="admin/defaults">
            <label for="room_id" class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?= e(t('select_room')) ?>:</label>
            <select id="room_id" name="room_id" onchange="this.form.submit()"
                    class="min-h-[44px] px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-semibold">
                <?php foreach ($rooms as $rm): ?>
                    <option value="<?= (int) $rm['id'] ?>" <?= (int) $rm['id'] === (int) $selectedRoomId ? 'selected' : '' ?>>
                        <?= e(t('room_prefix')) ?> <?= e($rm['room_number']) ?> (<?= e($rm['floor_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Defaults Editor Form -->
        <?php if (!empty($defaults)): ?>
            <form method="post" action="<?= e(app_url('?route=admin/defaults')) ?>" class="space-y-4">
                <?= Csrf::input() ?>
                <input type="hidden" name="action" value="save_defaults">
                <input type="hidden" name="room_id" value="<?= (int) $selectedRoomId ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                    <?php foreach ($defaults as $item):
                        $oid = (int) $item['option_id'];
                        $checked = ((int) $item['qty']) > 0;
                        $qtyVal  = $checked ? (int) $item['qty'] : 1;
                    ?>
                        <input type="hidden" name="all_option_ids[]" value="<?= $oid ?>">
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-center justify-between gap-2">
                            <label class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0">
                                <input type="checkbox" name="include[]" value="<?= $oid ?>" <?= $checked ? 'checked' : '' ?>
                                       class="w-5 h-5 rounded text-indigo-600 shrink-0">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= e($item['label']) ?></div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= e($item['section_title']) ?></div>
                                </div>
                            </label>
                            <?php if ($item['type'] === 'counter'): ?>
                                <input type="number" name="qty[<?= $oid ?>]" min="1" max="<?= (int) $item['max_count'] ?>" value="<?= $qtyVal ?>"
                                       class="w-16 min-h-[40px] px-2 text-center rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-bold shrink-0">
                            <?php else: ?>
                                <input type="hidden" name="qty[<?= $oid ?>]" value="1">
                                <span class="text-xs px-2 py-1 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 shrink-0">হ্যাঁ/না</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <button type="submit" class="min-h-[44px] px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-sm">
                        💾 <?= e(t('save')) ?>
                    </button>
                </div>
            </form>

            <!-- Bulk Actions: Copy to All Rooms / Reset All Defaults -->
            <div class="mt-5 pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-wrap gap-2.5">
                <form method="post" action="<?= e(app_url('?route=admin/defaults')) ?>" data-confirm="<?= e(t('confirm_copy_defaults')) ?>">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="action" value="copy_all">
                    <input type="hidden" name="room_id" value="<?= (int) $selectedRoomId ?>">
                    <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 text-white text-sm font-semibold">
                        📋 <?= e(t('copy_to_all_rooms')) ?>
                    </button>
                </form>

                <form method="post" action="<?= e(app_url('?route=admin/defaults')) ?>" data-confirm="<?= e(t('confirm_reset_defaults')) ?>">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="action" value="reset_all">
                    <input type="hidden" name="room_id" value="<?= (int) $selectedRoomId ?>">
                    <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 text-sm font-semibold">
                        ↺ <?= e(t('reset_all_defaults')) ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </section>
</div>
