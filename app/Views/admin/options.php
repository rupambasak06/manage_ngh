<?php
/**
 * Admin: Options (Entities) Management with Per-Room Applicability
 */

declare(strict_types=1);

use App\Core\Csrf;

require APP_ROOT . '/app/Views/admin/nav.php';
?>

<div class="space-y-5">
    <!-- Add New Option Form -->
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <h2 class="text-base sm:text-lg font-bold mb-3"><?= e(t('add_option')) ?></h2>
        <form method="post" action="<?= e(app_url('?route=admin/options')) ?>" class="space-y-3">
            <?= Csrf::input() ?>
            <input type="hidden" name="action" value="save_option">
            <input type="hidden" name="is_active" value="1">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                <select name="section_id" required class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= (int) $sec['id'] ?>"><?= e($sec['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="label" required placeholder="<?= e(t('option_label')) ?>"
                       class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <select name="type" class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                    <option value="checkbox"><?= e(t('type_checkbox')) ?></option>
                    <option value="counter"><?= e(t('type_counter')) ?></option>
                </select>
                <input type="number" name="max_count" min="1" max="99" value="10" placeholder="<?= e(t('max_count')) ?>"
                       class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <select name="applies_to" onchange="document.getElementById('new-opt-rooms').classList.toggle('hidden', this.value !== 'selected')"
                        class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                    <option value="all"><?= e(t('applies_all')) ?></option>
                    <option value="selected"><?= e(t('applies_selected')) ?></option>
                </select>
            </div>

            <div id="new-opt-rooms" class="hidden p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-2"><?= e(t('select_rooms')) ?>:</div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($rooms as $rm): ?>
                        <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-medium cursor-pointer">
                            <input type="checkbox" name="room_ids[]" value="<?= (int) $rm['id'] ?>" class="w-4 h-4">
                            <span><?= e($rm['room_number']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="min-h-[44px] px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm">
                + <?= e(t('add_new')) ?>
            </button>
        </form>
    </section>

    <!-- Existing Options List -->
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3">
        <?php foreach ($options as $opt):
            $oid = (int) $opt['id'];
        ?>
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-2.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <span class="font-bold text-sm text-slate-900 dark:text-white"><?= e($opt['label']) ?></span>
                        <span class="ml-1.5 px-2 py-0.5 rounded-md text-xs bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300"><?= e($opt['section_title']) ?></span>
                        <span class="ml-1 px-2 py-0.5 rounded-md text-xs bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                            <?= $opt['type'] === 'counter' ? e(t('type_counter')) . ' (সর্বোচ্চ ' . e(bn_num($opt['max_count'])) . ')' : e(t('type_checkbox')) ?>
                        </span>
                        <span class="ml-1 px-2 py-0.5 rounded-md text-xs bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300">
                            <?= $opt['applies_to'] === 'selected'
                                ? e(t('applies_selected')) . ': ' . e(implode(', ', $opt['selected_room_nums']))
                                : e(t('applies_all')) ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="move_option">
                            <input type="hidden" name="id" value="<?= $oid ?>">
                            <input type="hidden" name="dir" value="up">
                            <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900">↑</button>
                        </form>
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="move_option">
                            <input type="hidden" name="id" value="<?= $oid ?>">
                            <input type="hidden" name="dir" value="down">
                            <button type="submit" class="min-h-[38px] min-w-[38px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900">↓</button>
                        </form>
                        <form method="post" class="inline">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="toggle_option">
                            <input type="hidden" name="id" value="<?= $oid ?>">
                            <button type="submit" class="min-h-[38px] px-2.5 rounded-lg text-xs font-semibold border <?= (int) $opt['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-200 text-slate-600 border-slate-300 dark:bg-slate-800 dark:text-slate-400' ?>">
                                <?= (int) $opt['is_active'] ? e(t('active')) : e(t('inactive')) ?>
                            </button>
                        </form>
                        <form method="post" class="inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="delete_option">
                            <input type="hidden" name="id" value="<?= $oid ?>">
                            <button type="submit" class="min-h-[38px] px-2.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800 text-xs font-semibold">✕</button>
                        </form>
                    </div>
                </div>

                <!-- Collapsible Inline Edit Form -->
                <details>
                    <summary class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 cursor-pointer select-none py-1">✎ <?= e(t('edit')) ?></summary>
                    <form method="post" action="<?= e(app_url('?route=admin/options')) ?>" class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2.5">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="save_option">
                        <input type="hidden" name="id" value="<?= $oid ?>">
                        <input type="hidden" name="is_active" value="<?= (int) $opt['is_active'] ?>">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">
                            <select name="section_id" class="min-h-[40px] px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= (int) $sec['id'] ?>" <?= (int) $sec['id'] === (int) $opt['section_id'] ? 'selected' : '' ?>><?= e($sec['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="label" value="<?= e($opt['label']) ?>" required
                                   class="min-h-[40px] px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                            <select name="type" class="min-h-[40px] px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                                <option value="checkbox" <?= $opt['type'] === 'checkbox' ? 'selected' : '' ?>><?= e(t('type_checkbox')) ?></option>
                                <option value="counter" <?= $opt['type'] === 'counter' ? 'selected' : '' ?>><?= e(t('type_counter')) ?></option>
                            </select>
                            <input type="number" name="max_count" min="1" max="99" value="<?= (int) $opt['max_count'] ?>"
                                   class="min-h-[40px] px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                            <select name="applies_to" onchange="document.getElementById('edit-opt-rooms-<?= $oid ?>').classList.toggle('hidden', this.value !== 'selected')"
                                    class="min-h-[40px] px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                                <option value="all" <?= $opt['applies_to'] === 'all' ? 'selected' : '' ?>><?= e(t('applies_all')) ?></option>
                                <option value="selected" <?= $opt['applies_to'] === 'selected' ? 'selected' : '' ?>><?= e(t('applies_selected')) ?></option>
                            </select>
                        </div>

                        <div id="edit-opt-rooms-<?= $oid ?>" class="<?= $opt['applies_to'] === 'selected' ? '' : 'hidden' ?> p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($rooms as $rm):
                                    $checked = in_array((int) $rm['id'], $opt['selected_room_ids'], true);
                                ?>
                                    <label class="inline-flex items-center gap-1 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 text-xs cursor-pointer">
                                        <input type="checkbox" name="room_ids[]" value="<?= (int) $rm['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                        <span><?= e($rm['room_number']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="min-h-[40px] px-4 rounded-lg bg-indigo-600 text-white text-xs font-semibold"><?= e(t('save')) ?></button>
                    </form>
                </details>
            </div>
        <?php endforeach; ?>
    </section>
</div>
