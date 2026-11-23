<?php
/**
 * Dedicated Printable Report View (Print CSS optimized)
 */

declare(strict_types=1);

$from = $report['date_from'];
$to   = $report['date_to'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('reports_title')) ?> (<?= e($from) ?> - <?= e($to) ?>)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="bg-white text-slate-900 p-4 sm:p-8 max-w-5xl mx-auto">

<div class="no-print mb-6 flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-slate-200">
    <a href="<?= e(app_url('?route=reports&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>"
       class="min-h-[44px] px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold inline-flex items-center">
        ← <?= e(t('print_back')) ?>
    </a>
    <button type="button" onclick="window.print()"
            class="min-h-[44px] px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold">
        🖨 <?= e(t('print_now')) ?>
    </button>
</div>

<header class="mb-6 border-b-2 border-slate-800 pb-3">
    <h1 class="text-2xl font-bold"><?= e(t('app_title')) ?> — <?= e(t('reports_title')) ?></h1>
    <div class="text-sm text-slate-600 mt-1 flex flex-wrap justify-between gap-2">
        <span><?= e(t('report_date_range')) ?>: <strong><?= e(bn_date($from, false)) ?></strong> থেকে <strong><?= e(bn_date($to, false)) ?></strong></span>
        <span><?= e(t('printed_at')) ?>: <?= e(bn_datetime(date('Y-m-d H:i:s'))) ?></span>
    </div>
</header>

<!-- Grand Extras Table -->
<section class="mb-6 print-break-inside-avoid">
    <h2 class="text-base font-bold mb-2"><?= e(t('grand_total')) ?> — <?= e(t('extras_section_title')) ?></h2>
    <table class="w-full text-sm border-collapse border border-slate-300">
        <thead>
            <tr class="bg-slate-100">
                <?php foreach ($report['grand_extras'] as $ex): ?>
                    <th class="border border-slate-300 px-2 py-1.5 text-center"><?= e($ex['label']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php foreach ($report['grand_extras'] as $ex): ?>
                    <td class="border border-slate-300 px-2 py-1.5 text-center font-bold"><?= e(bn_num($ex['total'])) ?></td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
</section>

<!-- Daily Details -->
<?php foreach ($report['days'] as $day):
    $s  = $day['summary'];
    $st = $day['stats'];
?>
    <section class="mb-8 print-break-inside-avoid">
        <div class="bg-slate-100 px-3 py-2 font-bold text-base border border-slate-300 flex justify-between">
            <span><?= e(bn_date($day['date'])) ?></span>
            <span><?= e(t('checked_rooms_count')) ?>: <?= e(bn_num($st['checked_count']) . '/' . bn_num($st['total_rooms'])) ?> | <?= e(t('checkout_done_count')) ?>: <?= e(bn_num($st['checkout_complete_count'])) ?></span>
        </div>

        <table class="w-full text-xs sm:text-sm border-collapse border border-slate-300 mt-2">
            <thead>
                <tr class="bg-slate-50">
                    <th class="border border-slate-300 px-2 py-1.5 text-left w-1/3"><?= e(t('summary_extras_title')) ?></th>
                    <th class="border border-slate-300 px-2 py-1.5 text-left w-1/3"><?= e(t('summary_missing_title')) ?></th>
                    <th class="border border-slate-300 px-2 py-1.5 text-left w-1/3"><?= e(t('summary_notes_title')) ?></th>
                </tr>
            </thead>
            <tbody>
                <tr class="align-top">
                    <td class="border border-slate-300 px-2 py-2">
                        <?php if (empty($s['extras_by_room'])): ?>
                            <?= e(t('no_extras_today')) ?>
                        <?php else: ?>
                            <?php foreach ($s['extras_by_room'] as $r): ?>
                                <div class="mb-1">
                                    <strong>রুম <?= e($r['room_number']) ?>:</strong>
                                    <?= e(implode(', ', array_map(static fn($i) => $i['label'] . ' ' . bn_num($i['qty']), $r['items']))) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td class="border border-slate-300 px-2 py-2">
                        <?php if (empty($s['missing_by_room'])): ?>
                            <div><?= e(t('no_missing_items')) ?></div>
                        <?php else: ?>
                            <?php foreach ($s['missing_by_room'] as $r): ?>
                                <div class="mb-1">
                                    <strong>রুম <?= e($r['room_number']) ?>:</strong>
                                    <?= e(implode(', ', array_map(static fn($i) => $i['label'] . ' (' . ($i['type'] === 'counter' ? bn_num($i['missing_qty']) . ' কম' : 'বাকি') . ')', $r['items']))) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($s['unchecked_rooms'])): ?>
                            <div class="mt-2 pt-1 border-t border-slate-200 text-xs">
                                <strong><?= e(t('summary_unchecked_title')) ?>:</strong>
                                <?= e(implode(', ', array_map(static fn($u) => $u['room_number'], $s['unchecked_rooms']))) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="border border-slate-300 px-2 py-2">
                        <?php if (empty($s['notes_list'])): ?>
                            <?= e(t('no_notes_today')) ?>
                        <?php else: ?>
                            <?php foreach ($s['notes_list'] as $idx => $n): ?>
                                <div class="mb-1">
                                    <strong><?= e(bn_num($idx + 1)) ?>. রুম <?= e($n['room_number']) ?>:</strong> <?= e($n['note']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
<?php endforeach; ?>

</body>
</html>
