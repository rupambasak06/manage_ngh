<?php
/**
 * Admin: Audit Log Viewer
 */

declare(strict_types=1);

require APP_ROOT . '/app/Views/admin/nav.php';
?>

<div class="space-y-4">
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <h1 class="text-base sm:text-lg font-bold mb-3"><?= e(t('audit_title')) ?></h1>

        <form method="get" action="<?= e(app_url('')) ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 mb-4">
            <input type="hidden" name="route" value="admin/audit">
            <input type="date" name="date" value="<?= e($filterDate) ?>"
                   class="min-h-[44px] px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <input type="text" name="room" value="<?= e($filterRoom) ?>" placeholder="<?= e(t('room_number')) ?>"
                   class="min-h-[44px] px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <button type="submit" class="min-h-[44px] px-4 rounded-xl bg-indigo-600 text-white text-sm font-semibold">
                <?= e(t('filter')) ?>
            </button>
            <a href="<?= e(app_url('?route=admin/audit')) ?>"
               class="min-h-[44px] px-4 rounded-xl border border-slate-300 dark:border-slate-700 text-sm font-semibold inline-flex items-center justify-center">
                <?= e(t('clear_filter')) ?>
            </a>
        </form>

        <?php if (empty($logs)): ?>
            <p class="text-sm text-slate-500 py-6 text-center"><?= e(t('audit_empty')) ?></p>
        <?php else: ?>
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="w-full text-xs sm:text-sm text-left border-collapse min-w-[640px]">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/70 text-slate-600 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800">
                            <th class="py-2.5 px-3"><?= e(t('audit_time')) ?></th>
                            <th class="py-2.5 px-3"><?= e(t('audit_user')) ?></th>
                            <th class="py-2.5 px-3"><?= e(t('audit_room')) ?></th>
                            <th class="py-2.5 px-3"><?= e(t('audit_item')) ?></th>
                            <th class="py-2.5 px-3"><?= e(t('audit_change')) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="py-2.5 px-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                    <?= e(bn_datetime($log['created_at'])) ?>
                                </td>
                                <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">
                                    <?= e($log['user_name']) ?>
                                </td>
                                <td class="py-2.5 px-3">
                                    <?= $log['room_number'] ? e(t('room_prefix') . ' ' . $log['room_number']) : '—' ?>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="font-medium"><?= e($log['item_label'] ?: $log['action']) ?></span>
                                    <?php if (!empty($log['check_date'])): ?>
                                        <span class="text-xs text-slate-400 ml-1">(<?= e($log['check_date']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3">
                                    <?php if ($log['old_value'] !== null && $log['old_value'] !== ''): ?>
                                        <span class="text-slate-400 line-through mr-1"><?= e($log['old_value']) ?></span> →
                                    <?php endif; ?>
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400"><?= e($log['new_value'] ?? '') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
