<?php
/**
 * Main Daily Checklist View (Mobile-First, Responsive Grid, Live Sync, Bottom Summary)
 */

declare(strict_types=1);

use App\Core\Csrf;

$isToday = ($date === $today);
$stats   = $board['stats'];
$summary = $board['summary'];
?>

<div id="checklist-app"
     data-csrf="<?= e(Csrf::token()) ?>"
     data-date="<?= e($date) ?>"
     data-today="<?= e($today) ?>"
     data-api-base="<?= e(app_url('')) ?>"
     data-poll-interval="<?= (int) $pollInterval ?>"
     data-last-sync="<?= e($board['last_sync_at']) ?>">

    <!-- Sticky Date Bar -->
    <div class="sticky top-14 z-30 -mx-3 sm:-mx-4 lg:-mx-6 px-3 sm:px-4 lg:px-6 py-2.5 bg-slate-100/95 dark:bg-slate-950/95 backdrop-blur border-b border-slate-200 dark:border-slate-800">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <!-- Date Navigation: ‹ Date (tap for Today) › -->
            <div class="flex items-center gap-1.5 flex-1 min-w-[240px]">
                <a href="<?= e(app_url('?date=' . urlencode($prevDate))) ?>"
                   aria-label="<?= e(t('prev_day')) ?>"
                   class="tap-btn inline-flex items-center justify-center rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xl font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-sm shrink-0">
                    ‹
                </a>
                <a href="<?= e(app_url('?date=' . urlencode($today))) ?>"
                   title="<?= e(t('jump_today')) ?>"
                   class="flex-1 min-h-[44px] px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border <?= $isToday ? 'border-indigo-300 dark:border-indigo-700' : 'border-amber-400 dark:border-amber-600' ?> shadow-sm flex flex-col items-center justify-center text-center leading-tight">
                    <span class="font-bold text-sm sm:text-base text-slate-900 dark:text-white"><?= e(bn_date($date)) ?></span>
                    <span class="text-[11px] <?= $isToday ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600 dark:text-amber-400' ?>">
                        <?= $isToday ? e(t('today')) : e(t('viewing_history')) . ' · ' . e(t('jump_today')) ?>
                    </span>
                </a>
                <a href="<?= e(app_url('?date=' . urlencode($nextDate))) ?>"
                   aria-label="<?= e(t('next_day')) ?>"
                   class="tap-btn inline-flex items-center justify-center rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xl font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-sm shrink-0">
                    ›
                </a>
            </div>

            <!-- Live Save Status + Quick Stats -->
            <div class="flex items-center gap-2 ml-auto">
                <div class="hidden sm:flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800">
                    <span><?= e(t('checked_rooms_count')) ?>: <strong id="stat-checked" class="text-slate-900 dark:text-white"><?= e(bn_num($stats['checked_count']) . '/' . bn_num($stats['total_rooms'])) ?></strong></span>
                    <span class="text-slate-300 dark:text-slate-700">|</span>
                    <span><?= e(t('checkout_done_count')) ?>: <strong id="stat-checkout" class="text-emerald-600 dark:text-emerald-400"><?= e(bn_num($stats['checkout_complete_count']) . '/' . bn_num($stats['total_rooms'])) ?></strong></span>
                </div>
                <span id="save-status"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800">
                    <span id="save-status-dot" class="w-2 h-2 rounded-full inline-block bg-emerald-500 sync-dot-live"></span>
                    <span id="save-status-text"><?= e(t('saved')) ?></span>
                </span>
            </div>
        </div>

        <!-- Floor Filter Pills (horizontally scrollable on mobile) -->
        <?php if (!empty($board['floors'])): ?>
            <div class="mt-2 flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-0.5">
                <button type="button" data-floor-filter="all"
                        class="min-h-[36px] px-3.5 rounded-full text-xs sm:text-sm font-medium border whitespace-nowrap shrink-0 bg-indigo-600 text-white border-indigo-600">
                    <?= e(t('all_floors')) ?> (<?= e(bn_num($stats['total_rooms'])) ?>)
                </button>
                <?php foreach ($board['floors'] as $fl): ?>
                    <button type="button" data-floor-filter="<?= (int) $fl['id'] ?>"
                            class="min-h-[36px] px-3.5 rounded-full text-xs sm:text-sm font-medium border whitespace-nowrap shrink-0 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                        <?= e($fl['name']) ?> (<?= e(bn_num(count($fl['rooms']))) ?>)
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Top Totals Strip (Collapsible on mobile, expanded on desktop) -->
    <?php if (!empty($board['option_totals'])): ?>
        <section class="mt-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <button type="button" id="toggle-totals-btn"
                    class="w-full min-h-[44px] px-4 py-2.5 flex items-center justify-between text-left bg-slate-50/70 dark:bg-slate-800/50">
                <span class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200">
                    📊 <?= e(t('totals_strip_title')) ?>
                </span>
                <span data-chevron class="text-slate-400 transition-transform">▾</span>
            </button>
            <div id="totals-strip-body" class="p-3 flex flex-wrap gap-1.5 sm:gap-2">
                <?php foreach ($board['option_totals'] as $ot): ?>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/70 dark:border-slate-700">
                        <span><?= e($ot['label']) ?></span>
                        <span data-option-total="<?= (int) $ot['id'] ?>"
                              class="px-1.5 py-0.5 rounded-md bg-white dark:bg-slate-900 font-bold text-indigo-600 dark:text-indigo-400">
                            <?= e(bn_num($ot['total'])) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Floor Groups & Responsive Room Cards Grid -->
    <?php if (empty($board['floors'])): ?>
        <div class="mt-6 p-8 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 text-slate-500">
            <?= e(t('no_rooms_found')) ?>
        </div>
    <?php else: ?>
        <div class="mt-4 space-y-6">
            <?php foreach ($board['floors'] as $fl): ?>
                <section data-floor-section="<?= (int) $fl['id'] ?>">
                    <div class="flex items-center justify-between mb-2.5 px-1">
                        <h2 class="text-base sm:text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span>
                            <?= e($fl['name']) ?>
                        </h2>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?= e(bn_num(count($fl['rooms']))) ?> টি রুম</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                        <?php foreach ($fl['rooms'] as $rm): ?>
                            <article data-room-card="<?= (int) $rm['id'] ?>"
                                     class="room-card bg-white dark:bg-slate-900 rounded-2xl border <?= $rm['checkout_complete'] ? 'border-emerald-400 dark:border-emerald-700' : 'border-slate-200 dark:border-slate-800' ?> shadow-sm overflow-hidden flex flex-col">

                                <!-- Room Card Header -->
                                <div class="px-3.5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                                    <div>
                                        <div class="text-base font-bold text-slate-900 dark:text-white">
                                            <?= e(t('room_prefix')) ?> <?= e($rm['room_number']) ?>
                                        </div>
                                        <div data-room-updated class="text-[11px] text-slate-500 dark:text-slate-400">
                                            <?= $rm['updated_by_name'] !== '' ? 'আপডেট: ' . e($rm['updated_by_name']) : '' ?>
                                        </div>
                                    </div>

                                    <!-- Auto Checkout Complete Indicator (NOT clickable) -->
                                    <span data-checkout-badge
                                          class="<?= $rm['checkout_complete']
                                              ? 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700 select-none'
                                              : 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 select-none' ?>">
                                        <?php if ($rm['checkout_complete']): ?>
                                            <span>✓</span> <span><?= e(t('checkout_complete')) ?></span>
                                        <?php else: ?>
                                            <span>○</span> <span><?= e(t('checkout_pending')) ?> (<?= e(bn_num($rm['checkout_req_done']) . '/' . bn_num($rm['checkout_req_total'])) ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <!-- Card Body: Dynamic Sections -->
                                <div class="p-3.5 space-y-4 flex-1">
                                    <?php foreach ($rm['sections'] as $sec): ?>
                                        <?php if (empty($sec['options'])) continue; ?>
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2 flex items-center justify-between">
                                                <span><?= e($sec['title']) ?></span>
                                            </div>

                                            <?php
                                            $counters   = array_filter($sec['options'], static fn($o) => $o['type'] === 'counter');
                                            $checkboxes = array_filter($sec['options'], static fn($o) => $o['type'] === 'checkbox');
                                            ?>

                                            <!-- Counter Options -->
                                            <?php if (!empty($counters)): ?>
                                                <div class="space-y-1.5 mb-2">
                                                    <?php foreach ($counters as $opt):
                                                        $boxClass = $opt['is_mismatch']
                                                            ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-400 dark:border-amber-600'
                                                            : ($opt['is_done']
                                                                ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-400 dark:border-emerald-700'
                                                                : 'bg-slate-50 dark:bg-slate-800/70 border-slate-200 dark:border-slate-700');
                                                    ?>
                                                        <div data-opt-counter
                                                             data-room-id="<?= (int) $rm['id'] ?>"
                                                             data-option-id="<?= (int) $opt['id'] ?>"
                                                             data-qty="<?= (int) $opt['actual_qty'] ?>"
                                                             data-expected="<?= (int) $opt['expected_qty'] ?>"
                                                             data-max="<?= (int) $opt['max_count'] ?>"
                                                             class="counter-box rounded-xl border px-2.5 py-1 flex items-center justify-between gap-2 <?= $boxClass ?>">
                                                            <div class="min-w-0">
                                                                <div class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate"><?= e($opt['label']) ?></div>
                                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                                                    <span><?= e(t('expected_short')) ?>: <?= e(bn_num($opt['expected_qty'])) ?></span>
                                                                    <span data-mismatch-badge class="<?= $opt['is_mismatch'] ? '' : 'hidden' ?> text-amber-700 dark:text-amber-300 font-semibold">⚠ অমিল</span>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-1 shrink-0">
                                                                <button type="button" data-opt-step="-1" aria-label="কমান"
                                                                        class="counter-btn rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-lg font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">−</button>
                                                                <span data-counter-val class="w-7 text-center font-bold text-base text-slate-900 dark:text-white"><?= e(bn_num($opt['actual_qty'])) ?></span>
                                                                <button type="button" data-opt-step="1" aria-label="বাড়ান"
                                                                        class="counter-btn rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-lg font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">+</button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Checkbox Chips -->
                                            <?php if (!empty($checkboxes)): ?>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <?php foreach ($checkboxes as $opt):
                                                        $chipClass = $opt['is_done']
                                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                                            : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700';
                                                    ?>
                                                        <button type="button"
                                                                data-opt-toggle
                                                                data-room-id="<?= (int) $rm['id'] ?>"
                                                                data-option-id="<?= (int) $opt['id'] ?>"
                                                                data-qty="<?= (int) $opt['actual_qty'] ?>"
                                                                data-expected="<?= (int) $opt['expected_qty'] ?>"
                                                                class="opt-chip px-3 py-2 rounded-xl border text-xs sm:text-sm font-medium inline-flex items-center gap-1.5 <?= $chipClass ?>">
                                                            <span data-chip-check class="font-bold"><?= $opt['is_done'] ? '✓' : '' ?></span>
                                                            <span><?= e($opt['label']) ?></span>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Extras Section (Collapsible details on mobile for clean cards, open if any extra > 0) -->
                                    <?php if (!empty($rm['extras'])):
                                        $hasExtra = false;
                                        foreach ($rm['extras'] as $ex) {
                                            if ($ex['qty'] > 0) { $hasExtra = true; break; }
                                        }
                                    ?>
                                        <details class="group border-t border-slate-100 dark:border-slate-800 pt-2.5" <?= $hasExtra ? 'open' : '' ?>>
                                            <summary class="min-h-[38px] flex items-center justify-between cursor-pointer list-none text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                                                <span>➕ <?= e(t('extras_section_title')) ?></span>
                                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▾</span>
                                            </summary>
                                            <div class="mt-2 space-y-1.5">
                                                <?php foreach ($rm['extras'] as $ex): ?>
                                                    <div data-extra-counter
                                                         data-room-id="<?= (int) $rm['id'] ?>"
                                                         data-extra-id="<?= (int) $ex['id'] ?>"
                                                         data-qty="<?= (int) $ex['qty'] ?>"
                                                         data-max="<?= (int) $ex['max_count'] ?>"
                                                         class="counter-box rounded-xl border px-2.5 py-1 flex items-center justify-between gap-2 <?= $ex['qty'] > 0 ? 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-300 dark:border-indigo-700' : 'bg-slate-50 dark:bg-slate-800/70 border-slate-200 dark:border-slate-700' ?>">
                                                        <span class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate"><?= e($ex['label']) ?></span>
                                                        <div class="flex items-center gap-1 shrink-0">
                                                            <button type="button" data-extra-step="-1" aria-label="কমান"
                                                                    class="counter-btn rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-lg font-bold text-slate-700 dark:text-slate-200 flex items-center justify-center">−</button>
                                                            <span data-extra-val class="w-7 text-center font-bold text-base text-slate-900 dark:text-white"><?= e(bn_num($ex['qty'])) ?></span>
                                                            <button type="button" data-extra-step="1" aria-label="বাড়ান"
                                                                    class="counter-btn rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-lg font-bold text-slate-700 dark:text-slate-200 flex items-center justify-center">+</button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>

                                    <!-- Room Note -->
                                    <div class="border-t border-slate-100 dark:border-slate-800 pt-2.5">
                                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1.5">
                                            📝 <?= e(t('notes_section_title')) ?>
                                        </label>
                                        <textarea data-room-note
                                                  data-room-id="<?= (int) $rm['id'] ?>"
                                                  rows="2"
                                                  placeholder="<?= e(t('notes_placeholder')) ?>"
                                                  class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"><?= e($rm['note']) ?></textarea>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Bottom Daily Summary -->
    <section id="daily-summary" class="mt-8 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">
                📋 <?= e(t('summary_title')) ?> — <?= e(bn_date($date, false)) ?>
            </h2>
            <?php if (in_array($currentUser['role'], ['owner', 'manager'], true)): ?>
                <div class="flex items-center gap-2">
                    <a href="<?= e(app_url('?route=reports/csv&from=' . urlencode($date) . '&to=' . urlencode($date))) ?>"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold inline-flex items-center gap-1.5 shadow-sm">
                        ⬇ <?= e(t('export_csv')) ?>
                    </a>
                    <a href="<?= e(app_url('?route=reports/print&from=' . urlencode($date) . '&to=' . urlencode($date))) ?>"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-semibold inline-flex items-center gap-1.5 shadow-sm">
                        🖨 <?= e(t('print_report')) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- 1. Extras Given Today -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white mb-3"><?= e(t('summary_extras_title')) ?></h3>
                <div id="summary-extras-body">
                    <?php if (empty($summary['extras_by_room'])): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= e(t('no_extras_today')) ?></p>
                    <?php else: ?>
                        <div class="space-y-2 mb-4">
                            <?php foreach ($summary['extras_by_room'] as $r): ?>
                                <div class="flex flex-wrap items-center gap-2 text-sm py-1.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 min-w-[70px]"><?= e(t('room_prefix')) ?> <?= e($r['room_number']) ?></span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($r['items'] as $it): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                                                <?= e($it['label']) ?>: <?= e(bn_num($it['qty'])) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2"><?= e(t('grand_total')) ?></div>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($summary['extras_totals'] as $et): if ($et['total'] <= 0) continue; ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">
                                        <?= e($et['label']) ?>: <span class="text-indigo-600 dark:text-indigo-400"><?= e(bn_num($et['total'])) ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Missing Items (checked rooms) + Unchecked Rooms -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white mb-3"><?= e(t('summary_missing_title')) ?></h3>
                <div id="summary-missing-body">
                    <?php if (empty($summary['missing_by_room'])): ?>
                        <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-3"><?= e(t('no_missing_items')) ?></p>
                    <?php else: ?>
                        <div class="space-y-2 mb-4">
                            <?php foreach ($summary['missing_by_room'] as $r): ?>
                                <div class="flex flex-wrap items-center gap-2 text-sm py-1.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 min-w-[70px]"><?= e(t('room_prefix')) ?> <?= e($r['room_number']) ?></span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($r['items'] as $it): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 border border-amber-200 dark:border-amber-800">
                                                <?= e($it['label']) ?>
                                                (<?= $it['type'] === 'counter' ? e(bn_num($it['missing_qty']) . ' ' . t('less_suffix')) : 'বাকি' ?>)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">
                            <?= e(t('summary_unchecked_title')) ?> (<?= e(bn_num(count($summary['unchecked_rooms']))) ?>)
                        </div>
                        <?php if (empty($summary['unchecked_rooms'])): ?>
                            <p class="text-sm text-emerald-700 dark:text-emerald-400"><?= e(t('all_rooms_checked')) ?></p>
                        <?php else: ?>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($summary['unchecked_rooms'] as $u): ?>
                                    <span class="px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        <?= e($u['room_number']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 3. All Notes in Serial Order -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white mb-3"><?= e(t('summary_notes_title')) ?></h3>
                <div id="summary-notes-body">
                    <?php if (empty($summary['notes_list'])): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= e(t('no_notes_today')) ?></p>
                    <?php else: ?>
                        <ol class="space-y-2 text-sm">
                            <?php foreach ($summary['notes_list'] as $idx => $n): ?>
                                <li class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400"><?= e(bn_num($idx + 1)) ?>.</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-100"><?= e(t('room_prefix')) ?> <?= e($n['room_number']) ?>:</span>
                                    <span class="text-slate-700 dark:text-slate-200"><?= e($n['note']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>
