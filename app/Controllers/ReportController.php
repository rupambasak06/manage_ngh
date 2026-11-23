<?php
/**
 * Report Controller
 * Separate Reports page (single day or date range), UTF-8 CSV Export, and Printable Page.
 * Accessible to Owner and Manager roles.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\ChecklistModel;
use PDO;

final class ReportController
{
    private ChecklistModel $model;

    public function __construct(private PDO $pdo)
    {
        $this->model = new ChecklistModel($pdo);
    }

    public function index(): void
    {
        $user     = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);
        $dateFrom = normalize_date($_GET['from'] ?? date('Y-m-d'));
        $dateTo   = normalize_date($_GET['to'] ?? $dateFrom);
        $report   = $this->model->getRangeReport($dateFrom, $dateTo);

        render_view('reports/index', [
            'pageTitle'   => t('reports_title'),
            'activeNav'   => 'reports',
            'currentUser' => $user,
            'report'      => $report,
        ], true);
    }

    public function printView(): void
    {
        $user     = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);
        $dateFrom = normalize_date($_GET['from'] ?? date('Y-m-d'));
        $dateTo   = normalize_date($_GET['to'] ?? $dateFrom);
        $report   = $this->model->getRangeReport($dateFrom, $dateTo);

        render_view('reports/print', [
            'pageTitle'   => t('reports_title'),
            'currentUser' => $user,
            'report'      => $report,
        ], false);
    }

    /**
     * Export single day or date range as UTF-8 BOM CSV (Excel-friendly for Bengali).
     */
    public function exportCsv(): void
    {
        Auth::requireRoles($this->pdo, ['owner', 'manager'], false);
        $dateFrom = normalize_date($_GET['from'] ?? date('Y-m-d'));
        $dateTo   = normalize_date($_GET['to'] ?? $dateFrom);
        $report   = $this->model->getRangeReport($dateFrom, $dateTo);

        $filename = 'hotel-report-' . $report['date_from'] . '_to_' . $report['date_to'] . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        // UTF-8 BOM so Microsoft Excel renders Bengali text properly
        fwrite($out, "\xEF\xBB\xBF");

        // Section 1: Summary of Extras
        fputcsv($out, ['=== এক্সট্রা সামগ্রীর সর্বমোট (' . $report['date_from'] . ' থেকে ' . $report['date_to'] . ') ===']);
        fputcsv($out, ['এক্সট্রা আইটেম', 'মোট সংখ্যা']);
        foreach ($report['grand_extras'] as $ex) {
            fputcsv($out, [$ex['label'], $ex['total']]);
        }
        fputcsv($out, []);

        // Section 2: Day-by-Day Room Breakdown
        fputcsv($out, ['=== তারিখ ও রুম অনুযায়ী বিস্তারিত ===']);
        fputcsv($out, ['তারিখ', 'ফ্লোর', 'রুম', 'অবস্থা', 'চেকআউট সম্পূর্ণ', 'কম থাকা আইটেম', 'এক্সট্রা সামগ্রী', 'মন্তব্য', 'সর্বশেষ আপডেট']);

        foreach ($report['days'] as $day) {
            $d = $day['date'];
            $missingMap = [];
            foreach ($day['summary']['missing_by_room'] as $m) {
                $parts = [];
                foreach ($m['items'] as $it) {
                    $parts[] = $it['label'] . ' (' . $it['missing_qty'] . ' কম)';
                }
                $missingMap[$m['room_id']] = implode('; ', $parts);
            }

            foreach ($day['floors'] as $fl) {
                foreach ($fl['rooms'] as $rm) {
                    $extraParts = [];
                    foreach ($rm['extras'] as $ex) {
                        if ($ex['qty'] > 0) {
                            $extraParts[] = $ex['label'] . ': ' . $ex['qty'];
                        }
                    }

                    fputcsv($out, [
                        $d,
                        $fl['name'],
                        $rm['room_number'],
                        $rm['is_checked'] ? 'চেক হয়েছে' : 'এখনও চেক করা হয়নি',
                        $rm['checkout_complete'] ? 'হ্যাঁ' : 'না',
                        $rm['is_checked'] ? ($missingMap[$rm['id']] ?? 'কিছু কম নেই') : '-',
                        implode('; ', $extraParts),
                        $rm['note'],
                        $rm['updated_by_name'],
                    ]);
                }
            }
        }

        fclose($out);
        exit;
    }
}
