<?php
/**
 * Checklist Model
 * Handles daily board loading (with versioned room_defaults), idempotent upserts,
 * automatic checkout completion calculation, summaries, live polling, and range reports.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Audit;
use PDO;

final class ChecklistModel
{
    public function __construct(private PDO $pdo) {}

    /**
     * Load the complete daily checklist state for a given YYYY-MM-DD date.
     *
     * @return array<string, mixed>
     */
    public function getDailyBoard(string $date): array
    {
        // 1. Active floors
        $floors = $this->pdo->query(
            "SELECT id, name, sort_order FROM floors WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
        )->fetchAll();

        // 2. Active rooms belonging to active floors
        $roomsRaw = $this->pdo->query(
            "SELECT r.id, r.floor_id, r.room_number, r.sort_order, f.name AS floor_name
             FROM rooms r
             INNER JOIN floors f ON f.id = r.floor_id AND f.is_active = 1
             WHERE r.is_active = 1
             ORDER BY f.sort_order ASC, f.id ASC, r.sort_order ASC, r.room_number ASC"
        )->fetchAll();

        // 3. Active sections
        $sections = $this->pdo->query(
            "SELECT id, title, counts_toward_checkout, sort_order
             FROM sections
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC"
        )->fetchAll();

        // 4. Active options
        $optionsRaw = $this->pdo->query(
            "SELECT o.id, o.section_id, o.label, o.type, o.max_count, o.applies_to, o.sort_order
             FROM options o
             INNER JOIN sections s ON s.id = o.section_id AND s.is_active = 1
             WHERE o.is_active = 1
             ORDER BY s.sort_order ASC, s.id ASC, o.sort_order ASC, o.id ASC"
        )->fetchAll();

        $optionsById = [];
        $optionsBySection = [];
        foreach ($optionsRaw as $opt) {
            $opt['id']         = (int) $opt['id'];
            $opt['section_id'] = (int) $opt['section_id'];
            $opt['max_count']  = (int) $opt['max_count'];
            $optionsById[$opt['id']] = $opt;
            $optionsBySection[$opt['section_id']][] = $opt;
        }

        // 5. Option-to-room applicability map (for applies_to = 'selected')
        $optRoomsRaw = $this->pdo->query("SELECT option_id, room_id FROM option_rooms")->fetchAll();
        $optionRoomsMap = [];
        foreach ($optRoomsRaw as $row) {
            $optionRoomsMap[(int) $row['option_id']][(int) $row['room_id']] = true;
        }

        // 6. Active extras
        $extras = $this->pdo->query(
            "SELECT id, label, max_count, sort_order
             FROM extras
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
        foreach ($extras as &$ex) {
            $ex['id']        = (int) $ex['id'];
            $ex['max_count'] = (int) $ex['max_count'];
        }
        unset($ex);

        // 7. Versioned room defaults effective on or before $date
        $stmtDef = $this->pdo->prepare(
            "SELECT rd.room_id, rd.option_id, rd.qty
             FROM room_defaults rd
             INNER JOIN (
                 SELECT room_id, option_id, MAX(effective_from) AS max_eff
                 FROM room_defaults
                 WHERE effective_from <= :d1
                 GROUP BY room_id, option_id
             ) latest
               ON rd.room_id = latest.room_id
              AND rd.option_id = latest.option_id
              AND rd.effective_from = latest.max_eff"
        );
        $stmtDef->execute([':d1' => $date]);
        $defaultsMap = []; // [room_id][option_id] => qty
        $roomHasDefaults = []; // [room_id] => bool (true if at least one qty > 0)
        foreach ($stmtDef->fetchAll() as $d) {
            $rid = (int) $d['room_id'];
            $oid = (int) $d['option_id'];
            $qty = (int) $d['qty'];
            $defaultsMap[$rid][$oid] = $qty;
            if ($qty > 0) {
                $roomHasDefaults[$rid] = true;
            }
        }

        // 8. Daily room notes & metadata
        $stmtDR = $this->pdo->prepare(
            "SELECT dr.room_id, dr.note, dr.updated_at, u.name AS updated_by_name
             FROM daily_rooms dr
             LEFT JOIN users u ON u.id = dr.updated_by
             WHERE dr.check_date = :d"
        );
        $stmtDR->execute([':d' => $date]);
        $dailyRoomsMap = [];
        $lastSyncAt = '';
        foreach ($stmtDR->fetchAll() as $dr) {
            $dailyRoomsMap[(int) $dr['room_id']] = $dr;
            if ($dr['updated_at'] > $lastSyncAt) {
                $lastSyncAt = (string) $dr['updated_at'];
            }
        }

        // 9. Daily option values
        $stmtDO = $this->pdo->prepare(
            "SELECT room_id, option_id, qty, updated_at
             FROM daily_options
             WHERE check_date = :d"
        );
        $stmtDO->execute([':d' => $date]);
        $dailyOptionsMap = [];
        foreach ($stmtDO->fetchAll() as $do) {
            $dailyOptionsMap[(int) $do['room_id']][(int) $do['option_id']] = (int) $do['qty'];
            if ($do['updated_at'] > $lastSyncAt) {
                $lastSyncAt = (string) $do['updated_at'];
            }
        }

        // 10. Daily extra values
        $stmtDE = $this->pdo->prepare(
            "SELECT room_id, extra_id, qty, updated_at
             FROM daily_extras
             WHERE check_date = :d"
        );
        $stmtDE->execute([':d' => $date]);
        $dailyExtrasMap = [];
        foreach ($stmtDE->fetchAll() as $de) {
            $dailyExtrasMap[(int) $de['room_id']][(int) $de['extra_id']] = (int) $de['qty'];
            if ($de['updated_at'] > $lastSyncAt) {
                $lastSyncAt = (string) $de['updated_at'];
            }
        }

        // Prepare aggregate containers
        $optionTotals = [];
        foreach ($optionsRaw as $opt) {
            $optionTotals[(int) $opt['id']] = [
                'id'    => (int) $opt['id'],
                'label' => (string) $opt['label'],
                'type'  => (string) $opt['type'],
                'total' => 0,
            ];
        }

        $extrasTotals = [];
        foreach ($extras as $ex) {
            $extrasTotals[$ex['id']] = [
                'id'    => $ex['id'],
                'label' => $ex['label'],
                'total' => 0,
            ];
        }

        $roomsByFloor = [];
        $extrasByRoom = [];
        $missingByRoom = [];
        $uncheckedRooms = [];
        $notesList = [];
        $checkedCount = 0;
        $checkoutCompleteCount = 0;

        foreach ($roomsRaw as $room) {
            $rid = (int) $room['id'];
            $fid = (int) $room['floor_id'];
            $hasDefs = !empty($roomHasDefaults[$rid]);

            $note = (string) ($dailyRoomsMap[$rid]['note'] ?? '');
            $updatedByName = (string) ($dailyRoomsMap[$rid]['updated_by_name'] ?? '');
            $updatedAt = (string) ($dailyRoomsMap[$rid]['updated_at'] ?? '');

            $hasAnyOptionTicked = false;
            $hasAnyExtraGiven   = false;
            $roomSectionsData   = [];
            $checkoutReqTotal   = 0;
            $checkoutReqDone    = 0;
            $roomMissing        = [];

            foreach ($sections as $sec) {
                $sid = (int) $sec['id'];
                $countsCheckout = ((int) $sec['counts_toward_checkout']) === 1;
                $secOptions = [];

                foreach ($optionsBySection[$sid] ?? [] as $opt) {
                    $oid = (int) $opt['id'];

                    // Per-room applicability check
                    if ($opt['applies_to'] === 'selected' && empty($optionRoomsMap[$oid][$rid])) {
                        continue;
                    }

                    // Versioned room default check
                    if ($hasDefs) {
                        $expectedQty = (int) ($defaultsMap[$rid][$oid] ?? 0);
                        if ($expectedQty <= 0) {
                            continue; // Not in this room's default for this date
                        }
                    } else {
                        $expectedQty = 1; // No defaults configured => show all applicable options
                    }

                    $actualQty = (int) ($dailyOptionsMap[$rid][$oid] ?? 0);
                    if ($actualQty > 0) {
                        $hasAnyOptionTicked = true;
                    }

                    $optionTotals[$oid]['total'] += $actualQty;

                    $isDone     = $actualQty >= $expectedQty;
                    $isMismatch = ($actualQty > 0 && $actualQty !== $expectedQty);

                    if ($countsCheckout) {
                        $checkoutReqTotal++;
                        if ($isDone) {
                            $checkoutReqDone++;
                        }
                    }

                    if ($actualQty < $expectedQty) {
                        $roomMissing[] = [
                            'option_id'    => $oid,
                            'label'        => $opt['label'],
                            'type'         => $opt['type'],
                            'expected_qty' => $expectedQty,
                            'actual_qty'   => $actualQty,
                            'missing_qty'  => $expectedQty - $actualQty,
                        ];
                    }

                    $secOptions[] = [
                        'id'           => $oid,
                        'label'        => $opt['label'],
                        'type'         => $opt['type'],
                        'max_count'    => $opt['max_count'],
                        'expected_qty' => $expectedQty,
                        'actual_qty'   => $actualQty,
                        'is_done'      => $isDone,
                        'is_mismatch'  => $isMismatch,
                    ];
                }

                $roomSectionsData[] = [
                    'id'                     => $sid,
                    'title'                  => $sec['title'],
                    'counts_toward_checkout' => $countsCheckout,
                    'options'                => $secOptions,
                ];
            }

            // Extras for this room
            $roomExtrasData = [];
            $roomExtrasNonZero = [];
            foreach ($extras as $ex) {
                $eid = $ex['id'];
                $qty = (int) ($dailyExtrasMap[$rid][$eid] ?? 0);
                if ($qty > 0) {
                    $hasAnyExtraGiven = true;
                    $extrasTotals[$eid]['total'] += $qty;
                    $roomExtrasNonZero[] = [
                        'id'    => $eid,
                        'label' => $ex['label'],
                        'qty'   => $qty,
                    ];
                }
                $roomExtrasData[] = [
                    'id'        => $eid,
                    'label'     => $ex['label'],
                    'max_count' => $ex['max_count'],
                    'qty'       => $qty,
                ];
            }

            $isChecked = $hasAnyOptionTicked || $hasAnyExtraGiven || trim($note) !== '';
            $checkoutComplete = ($checkoutReqTotal > 0 && $checkoutReqDone === $checkoutReqTotal);

            if ($isChecked) {
                $checkedCount++;
                if (!empty($roomMissing)) {
                    $missingByRoom[] = [
                        'room_id'     => $rid,
                        'room_number' => $room['room_number'],
                        'floor_name'  => $room['floor_name'],
                        'items'       => $roomMissing,
                    ];
                }
            } else {
                $uncheckedRooms[] = [
                    'room_id'     => $rid,
                    'room_number' => $room['room_number'],
                    'floor_name'  => $room['floor_name'],
                ];
            }

            if ($checkoutComplete) {
                $checkoutCompleteCount++;
            }

            if (!empty($roomExtrasNonZero)) {
                $extrasByRoom[] = [
                    'room_id'     => $rid,
                    'room_number' => $room['room_number'],
                    'floor_name'  => $room['floor_name'],
                    'items'       => $roomExtrasNonZero,
                ];
            }

            if (trim($note) !== '') {
                $notesList[] = [
                    'room_id'     => $rid,
                    'room_number' => $room['room_number'],
                    'floor_name'  => $room['floor_name'],
                    'note'        => trim($note),
                ];
            }

            $roomsByFloor[$fid][] = [
                'id'                => $rid,
                'floor_id'          => $fid,
                'floor_name'        => $room['floor_name'],
                'room_number'       => $room['room_number'],
                'sections'          => $roomSectionsData,
                'extras'            => $roomExtrasData,
                'note'              => $note,
                'updated_by_name'   => $updatedByName,
                'updated_at'        => $updatedAt,
                'is_checked'        => $isChecked,
                'checkout_complete' => $checkoutComplete,
                'checkout_req_done' => $checkoutReqDone,
                'checkout_req_total'=> $checkoutReqTotal,
            ];
        }

        // Build ordered floor list with attached rooms
        $groupedFloors = [];
        foreach ($floors as $fl) {
            $fid = (int) $fl['id'];
            if (empty($roomsByFloor[$fid])) {
                continue;
            }
            $groupedFloors[] = [
                'id'    => $fid,
                'name'  => $fl['name'],
                'rooms' => $roomsByFloor[$fid],
            ];
        }

        return [
            'date'                    => $date,
            'last_sync_at'            => $lastSyncAt,
            'floors'                  => $groupedFloors,
            'option_totals'           => array_values($optionTotals),
            'extras_list'             => $extras,
            'stats'                   => [
                'total_rooms'             => count($roomsRaw),
                'checked_count'           => $checkedCount,
                'checkout_complete_count' => $checkoutCompleteCount,
            ],
            'summary'                 => [
                'extras_by_room'  => $extrasByRoom,
                'extras_totals'   => array_values($extrasTotals),
                'missing_by_room' => $missingByRoom,
                'unchecked_rooms' => $uncheckedRooms,
                'notes_list'      => $notesList,
            ],
        ];
    }

    /**
     * Idempotent upsert for a single room option value.
     *
     * @return array<string, mixed>
     */
    public function upsertOption(string $date, int $roomId, int $optionId, int $qty, int $userId): array
    {
        $room = $this->findRoom($roomId);
        if (!$room) {
            return ['ok' => false, 'error' => 'রুম পাওয়া যায়নি।'];
        }

        $stmtOpt = $this->pdo->prepare("SELECT id, label, type, max_count FROM options WHERE id = :id AND is_active = 1");
        $stmtOpt->execute([':id' => $optionId]);
        $opt = $stmtOpt->fetch();
        if (!$opt) {
            return ['ok' => false, 'error' => 'অপশন পাওয়া যায়নি।'];
        }

        $max = ($opt['type'] === 'checkbox') ? 1 : max(1, (int) $opt['max_count']);
        $qty = max(0, min($max, $qty));

        // Fetch old value for audit log
        $stmtOld = $this->pdo->prepare(
            "SELECT qty FROM daily_options WHERE check_date = :d AND room_id = :r AND option_id = :o"
        );
        $stmtOld->execute([':d' => $date, ':r' => $roomId, ':o' => $optionId]);
        $oldQty = $stmtOld->fetchColumn();
        $oldQtyInt = ($oldQty === false) ? 0 : (int) $oldQty;

        // Idempotent upsert
        $stmtUp = $this->pdo->prepare(
            "INSERT INTO daily_options (check_date, room_id, option_id, qty, updated_by, updated_at)
             VALUES (:d, :r, :o, :q, :u, NOW())
             ON DUPLICATE KEY UPDATE qty = VALUES(qty), updated_by = VALUES(updated_by), updated_at = NOW()"
        );
        $stmtUp->execute([
            ':d' => $date,
            ':r' => $roomId,
            ':o' => $optionId,
            ':q' => $qty,
            ':u' => $userId,
        ]);

        $this->touchDailyRoom($date, $roomId, $userId);

        if ($oldQtyInt !== $qty) {
            Audit::log(
                $this->pdo,
                'update_option',
                'daily_option',
                $optionId,
                $date,
                $roomId,
                (string) $room['room_number'],
                (string) $opt['label'],
                (string) $oldQtyInt,
                (string) $qty
            );
        }

        return ['ok' => true, 'qty' => $qty];
    }

    /**
     * Idempotent upsert for a room extra item counter.
     *
     * @return array<string, mixed>
     */
    public function upsertExtra(string $date, int $roomId, int $extraId, int $qty, int $userId): array
    {
        $room = $this->findRoom($roomId);
        if (!$room) {
            return ['ok' => false, 'error' => 'রুম পাওয়া যায়নি।'];
        }

        $stmtEx = $this->pdo->prepare("SELECT id, label, max_count FROM extras WHERE id = :id AND is_active = 1");
        $stmtEx->execute([':id' => $extraId]);
        $extra = $stmtEx->fetch();
        if (!$extra) {
            return ['ok' => false, 'error' => 'এক্সট্রা আইটেম পাওয়া যায়নি।'];
        }

        $max = max(1, (int) $extra['max_count']);
        $qty = max(0, min($max, $qty));

        $stmtOld = $this->pdo->prepare(
            "SELECT qty FROM daily_extras WHERE check_date = :d AND room_id = :r AND extra_id = :e"
        );
        $stmtOld->execute([':d' => $date, ':r' => $roomId, ':e' => $extraId]);
        $oldQty = $stmtOld->fetchColumn();
        $oldQtyInt = ($oldQty === false) ? 0 : (int) $oldQty;

        $stmtUp = $this->pdo->prepare(
            "INSERT INTO daily_extras (check_date, room_id, extra_id, qty, updated_by, updated_at)
             VALUES (:d, :r, :e, :q, :u, NOW())
             ON DUPLICATE KEY UPDATE qty = VALUES(qty), updated_by = VALUES(updated_by), updated_at = NOW()"
        );
        $stmtUp->execute([
            ':d' => $date,
            ':r' => $roomId,
            ':e' => $extraId,
            ':q' => $qty,
            ':u' => $userId,
        ]);

        $this->touchDailyRoom($date, $roomId, $userId);

        if ($oldQtyInt !== $qty) {
            Audit::log(
                $this->pdo,
                'update_extra',
                'daily_extra',
                $extraId,
                $date,
                $roomId,
                (string) $room['room_number'],
                'এক্সট্রা: ' . (string) $extra['label'],
                (string) $oldQtyInt,
                (string) $qty
            );
        }

        return ['ok' => true, 'qty' => $qty];
    }

    /**
     * Idempotent upsert for a room's daily note.
     *
     * @return array<string, mixed>
     */
    public function upsertNote(string $date, int $roomId, string $note, int $userId): array
    {
        $room = $this->findRoom($roomId);
        if (!$room) {
            return ['ok' => false, 'error' => 'রুম পাওয়া যায়নি।'];
        }

        $note = mb_substr(trim($note), 0, 1000, 'UTF-8');

        $stmtOld = $this->pdo->prepare("SELECT note FROM daily_rooms WHERE check_date = :d AND room_id = :r");
        $stmtOld->execute([':d' => $date, ':r' => $roomId]);
        $oldNote = (string) ($stmtOld->fetchColumn() ?: '');

        $stmtUp = $this->pdo->prepare(
            "INSERT INTO daily_rooms (check_date, room_id, note, updated_by, updated_at)
             VALUES (:d, :r, :n, :u, NOW())
             ON DUPLICATE KEY UPDATE note = VALUES(note), updated_by = VALUES(updated_by), updated_at = NOW()"
        );
        $stmtUp->execute([
            ':d' => $date,
            ':r' => $roomId,
            ':n' => $note,
            ':u' => $userId,
        ]);

        if ($oldNote !== $note) {
            Audit::log(
                $this->pdo,
                'update_note',
                'daily_room',
                $roomId,
                $date,
                $roomId,
                (string) $room['room_number'],
                'মন্তব্য',
                $oldNote,
                $note
            );
        }

        return ['ok' => true, 'note' => $note];
    }

    /**
     * Build a multi-day report (or single-day report when dateFrom === dateTo).
     *
     * @return array<string, mixed>
     */
    public function getRangeReport(string $dateFrom, string $dateTo): array
    {
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Cap range at 93 days to protect shared hosting memory/time limits
        $startTs = strtotime($dateFrom);
        $endTs   = strtotime($dateTo);
        $maxEnd  = strtotime('+92 days', $startTs);
        if ($endTs > $maxEnd) {
            $endTs  = $maxEnd;
            $dateTo = date('Y-m-d', $endTs);
        }

        $days = [];
        $grandExtras = [];
        $totalNotes  = 0;

        for ($ts = $startTs; $ts <= $endTs; $ts = strtotime('+1 day', $ts)) {
            $d = date('Y-m-d', $ts);
            $board = $this->getDailyBoard($d);
            $days[] = $board;

            foreach ($board['summary']['extras_totals'] as $ex) {
                $eid = $ex['id'];
                if (!isset($grandExtras[$eid])) {
                    $grandExtras[$eid] = ['id' => $eid, 'label' => $ex['label'], 'total' => 0];
                }
                $grandExtras[$eid]['total'] += (int) $ex['total'];
            }
            $totalNotes += count($board['summary']['notes_list']);
        }

        return [
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'day_count'    => count($days),
            'days'         => $days,
            'grand_extras' => array_values($grandExtras),
            'total_notes'  => $totalNotes,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRoom(int $roomId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, room_number FROM rooms WHERE id = :id AND is_active = 1");
        $stmt->execute([':id' => $roomId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function touchDailyRoom(string $date, int $roomId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO daily_rooms (check_date, room_id, updated_by, updated_at)
             VALUES (:d, :r, :u, NOW())
             ON DUPLICATE KEY UPDATE updated_by = VALUES(updated_by), updated_at = NOW()"
        );
        $stmt->execute([':d' => $date, ':r' => $roomId, ':u' => $userId]);
    }
}
