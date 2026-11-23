<?php
/**
 * Reports Page (Single Day or Date Range) with CSV Export & Print Button
 */

declare(strict_types=1);

$from = $report['date_from'];
$to   = $report['date_to'];
?>

<div class="space-y-5">
    <!-- Header & Date Range Form -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">📄 <?= e(t('reports_title')) ?></h1>
            <div class="flex flex-wrap gap-2">
                <a href="<?= e(app_url('?route=reports/csv&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>"
                   class="min-h-[44px] px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold inline-flex items-center gap-1.5 shadow-sm">
                    ⬇ <?= e(t('export_csv')) ?>
                </a>
                <a href="<?= e(app_url('?route=reports/print&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>"
                   target="_blank" rel="noopener"
                   class="min-h-[44px] px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold inline-flex items-center gap-1.5 shadow-sm">
                    🖨 <?= e(t('print_report')) ?>
                </a>
            </div>
        </div>

        <form method="get" action="<?= e(app_url('')) ?>" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <input type="hidden" name="route" value="reports">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1"><?= e(t('date_from')) ?></label>
                <input type="date" name="from" value="<?= e($from) ?>" required
                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1"><?= e(t('date_to')) ?></label>
                <input type="date" name="to" value="<?= e($to) ?>" required
                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            </div>
            <button type="submit"
                    class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 text-white font-semibold text-sm">
                <?= e(t('show_report')) ?>
            </button>
        </form>
    </div>

    <!-- Range Grand Totals for Extras -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="font-bold text-base text-slate-900 dark:text-white">
                <?= e(t('grand_total')) ?> — <?= e(t('extras_section_title')) ?>
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                <?= e(bn_date($from, false)) ?> — <?= e(bn_date($to, false)) ?> (<?= e(bn_num($report['day_count'])) ?> দিন)
            </span>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($report['grand_extras'] as $ex): ?>
                <div class="px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm">
                    <span class="text-slate-600 dark:text-slate-300"><?= e($ex['label']) ?>:</span>
                    <strong class="ml-1 text-indigo-600 dark:text-indigo-400"><?= e(bn_num($ex['total'])) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Day-by-Day Breakdown -->
    <?php foreach ($report['days'] as $day):
        $s = $day['summary'];
        $st = $day['stats'];
    ?>
        <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-4 sm:px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
                <a href="<?= e(app_url('?date=' . urlencode($day['date']))) ?>"
                   class="font-bold text-base text-indigo-700 dark:text-indigo-400 hover:underline">
                    📅 <?= e(bn_date($day['date'])) ?>
                </a>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="px-2.5 py-1 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                        <?= e(t('checked_rooms_count')) ?>: <strong><?= e(bn_num($st['checked_count']) . '/' . bn_num($st['total_rooms'])) ?></strong>
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <?= e(t('checkout_done_count')) ?>: <strong><?= e(bn_num($st['checkout_complete_count'])) ?></strong>
                    </span>
                </div>
            </div>

            <div class="p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Extras -->
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2"><?= e(t('summary_extras_title')) ?></h3>
                    <?php if (empty($s['extras_by_room'])): ?>
                        <p class="text-xs text-slate-500"><?= e(t('no_extras_today')) ?></p>
                    <?php else: ?>
                        <ul class="space-y-1.5 text-xs sm:text-sm">
                            <?php foreach ($s['extras_by_room'] as $r): ?>
                                <li class="py-1 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <strong><?= e(t('room_prefix')) ?> <?= e($r['room_number']) ?>:</strong>
                                    <?php
                                    $parts = [];
                                    foreach ($r['items'] as $it) {
                                        $parts[] = e($it['label']) . ' ' . e(bn_num($it['qty']));
                                    }
                                    echo implode(', ', $parts);
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Missing & Unchecked -->
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2"><?= e(t('summary_missing_title')) ?></h3>
                    <?php if (empty($s['missing_by_room'])): ?>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-2"><?= e(t('no_missing_items')) ?></p>
                    <?php else: ?>
                        <ul class="space-y-1.5 text-xs sm:text-sm mb-3">
                            <?php foreach ($s['missing_by_room'] as $r): ?>
                                <li class="py-1 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <strong><?= e(t('room_prefix')) ?> <?= e($r['room_number']) ?>:</strong>
                                    <?php
                                    $parts = [];
                                    foreach ($r['items'] as $it) {
                                        $parts[] = e($it['label']) . ' (' . ($it['type'] === 'counter' ? e(bn_num($it['missing_qty']) . ' ' . t('less_suffix')) : 'বাকি') . ')';
                                    }
                                    echo '<span class="text-amber-700 dark:text-amber-300">' . implode(', ', $parts) . '</span>';
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        <strong><?= e(t('summary_unchecked_title')) ?> (<?= e(bn_num(count($s['unchecked_rooms']))) ?>):</strong>
                        <?php if (empty($s['unchecked_rooms'])): ?>
                            <?= e(t('all_rooms_checked')) ?>
                        <?php else: ?>
                            <?= e(implode(', ', array_map(static fn($u) => $u['room_number'], $s['unchecked_rooms']))) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2"><?= e(t('summary_notes_title')) ?></h3>
                    <?php if (empty($s['notes_list'])): ?>
                        <p class="text-xs text-slate-500"><?= e(t('no_notes_today')) ?></p>
                    <?php else: ?>
                        <ol class="space-y-1.5 text-xs sm:text-sm">
                            <?php foreach ($s['notes_list'] as $idx => $n): ?>
                                <li>
                                    <strong><?= e(bn_num($idx + 1)) ?>. <?= e(t('room_prefix')) ?> <?= e($n['room_number']) ?>:</strong>
                                    <?= e($n['note']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>
