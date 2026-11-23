<?php
/**
 * Admin Model
 * Full dynamic CRUD, reordering, applicability, versioned room defaults, user management, and audit logs.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Audit;
use PDO;

final class AdminModel
{
    public function __construct(private PDO $pdo) {}

    // =========================================================================
    // FLOORS & ROOMS
    // =========================================================================

    public function listFloors(): array
    {
        return $this->pdo->query(
            "SELECT f.*, (SELECT COUNT(*) FROM rooms r WHERE r.floor_id = f.id) AS room_count
             FROM floors f
             ORDER BY f.sort_order ASC, f.id ASC"
        )->fetchAll();
    }

    public function listRooms(): array
    {
        return $this->pdo->query(
            "SELECT r.*, f.name AS floor_name
             FROM rooms r
             INNER JOIN floors f ON f.id = r.floor_id
             ORDER BY f.sort_order ASC, f.id ASC, r.sort_order ASC, r.room_number ASC"
        )->fetchAll();
    }

    public function saveFloor(?int $id, string $name, int $isActive): void
    {
        $name = trim($name);
        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE floors SET name = :n, is_active = :a WHERE id = :id");
            $stmt->execute([':n' => $name, ':a' => $isActive, ':id' => $id]);
            Audit::log($this->pdo, 'update_floor', 'floor', $id, null, null, null, $name, null, $isActive ? 'সক্রিয়' : 'নিষ্ক্রিয়');
        } else {
            $nextOrder = (int) $this->pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM floors")->fetchColumn();
            $stmt = $this->pdo->prepare("INSERT INTO floors (name, sort_order, is_active) VALUES (:n, :o, :a)");
            $stmt->execute([':n' => $name, ':o' => $nextOrder, ':a' => $isActive]);
            Audit::log($this->pdo, 'create_floor', 'floor', (int) $this->pdo->lastInsertId(), null, null, null, $name, null, 'নতুন ফ্লোর');
        }
    }

    public function deleteFloor(int $id): bool
    {
        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM rooms WHERE floor_id = :id");
        $stmtCount->execute([':id' => $id]);
        if ((int) $stmtCount->fetchColumn() > 0) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM floors WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Audit::log($this->pdo, 'delete_floor', 'floor', $id, null, null, null, 'ফ্লোর #' . $id, null, 'মুছে ফেলা হয়েছে');
        return true;
    }

    public function saveRoom(?int $id, int $floorId, string $roomNumber, int $isActive): bool
    {
        $roomNumber = trim($roomNumber);
        // Check duplicate room number
        $stmtDup = $this->pdo->prepare("SELECT id FROM rooms WHERE room_number = :num AND id <> :id");
        $stmtDup->execute([':num' => $roomNumber, ':id' => (int) ($id ?? 0)]);
        if ($stmtDup->fetch()) {
            return false;
        }

        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE rooms SET floor_id = :f, room_number = :num, is_active = :a WHERE id = :id");
            $stmt->execute([':f' => $floorId, ':num' => $roomNumber, ':a' => $isActive, ':id' => $id]);
            Audit::log($this->pdo, 'update_room', 'room', $id, null, $id, $roomNumber, 'রুম ' . $roomNumber, null, $isActive ? 'সক্রিয়' : 'নিষ্ক্রিয়');
        } else {
            $stmtMax = $this->pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM rooms WHERE floor_id = :f");
            $stmtMax->execute([':f' => $floorId]);
            $nextOrder = (int) $stmtMax->fetchColumn();
            $stmt = $this->pdo->prepare("INSERT INTO rooms (floor_id, room_number, sort_order, is_active) VALUES (:f, :num, :o, :a)");
            $stmt->execute([':f' => $floorId, ':num' => $roomNumber, ':o' => $nextOrder, ':a' => $isActive]);
            $newId = (int) $this->pdo->lastInsertId();
            Audit::log($this->pdo, 'create_room', 'room', $newId, null, $newId, $roomNumber, 'রুম ' . $roomNumber, null, 'নতুন রুম');
        }
        return true;
    }

    public function deleteRoom(int $id): void
    {
        $stmt = $this->pdo->prepare("SELECT room_number FROM rooms WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $num = (string) ($stmt->fetchColumn() ?: $id);

        $del = $this->pdo->prepare("DELETE FROM rooms WHERE id = :id");
        $del->execute([':id' => $id]);
        Audit::log($this->pdo, 'delete_room', 'room', $id, null, null, $num, 'রুম ' . $num, null, 'মুছে ফেলা হয়েছে');
    }

    // =========================================================================
    // SECTIONS
    // =========================================================================

    public function listSections(): array
    {
        return $this->pdo->query(
            "SELECT s.*, (SELECT COUNT(*) FROM options o WHERE o.section_id = s.id) AS option_count
             FROM sections s
             ORDER BY s.sort_order ASC, s.id ASC"
        )->fetchAll();
    }

    public function saveSection(?int $id, string $title, int $countsCheckout, int $isActive): void
    {
        $title = trim($title);
        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare(
                "UPDATE sections SET title = :t, counts_toward_checkout = :c, is_active = :a WHERE id = :id"
            );
            $stmt->execute([':t' => $title, ':c' => $countsCheckout, ':a' => $isActive, ':id' => $id]);
            Audit::log($this->pdo, 'update_section', 'section', $id, null, null, null, $title, null, 'আপডেট');
        } else {
            $nextOrder = (int) $this->pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM sections")->fetchColumn();
            $stmt = $this->pdo->prepare(
                "INSERT INTO sections (title, counts_toward_checkout, sort_order, is_active) VALUES (:t, :c, :o, :a)"
            );
            $stmt->execute([':t' => $title, ':c' => $countsCheckout, ':o' => $nextOrder, ':a' => $isActive]);
            Audit::log($this->pdo, 'create_section', 'section', (int) $this->pdo->lastInsertId(), null, null, null, $title, null, 'নতুন সেকশন');
        }
    }

    public function deleteSection(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM sections WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Audit::log($this->pdo, 'delete_section', 'section', $id, null, null, null, 'সেকশন #' . $id, null, 'মুছে ফেলা হয়েছে');
    }

    // =========================================================================
    // OPTIONS & APPLICABILITY
    // =========================================================================

    public function listOptions(): array
    {
        $rows = $this->pdo->query(
            "SELECT o.*, s.title AS section_title
             FROM options o
             INNER JOIN sections s ON s.id = o.section_id
             ORDER BY s.sort_order ASC, s.id ASC, o.sort_order ASC, o.id ASC"
        )->fetchAll();

        $mapRows = $this->pdo->query(
            "SELECT or_m.option_id, or_m.room_id, r.room_number
             FROM option_rooms or_m
             INNER JOIN rooms r ON r.id = or_m.room_id
             ORDER BY r.room_number ASC"
        )->fetchAll();

        $roomIdsByOpt = [];
        $roomNumsByOpt = [];
        foreach ($mapRows as $m) {
            $oid = (int) $m['option_id'];
            $roomIdsByOpt[$oid][]  = (int) $m['room_id'];
            $roomNumsByOpt[$oid][] = (string) $m['room_number'];
        }

        foreach ($rows as &$r) {
            $oid = (int) $r['id'];
            $r['selected_room_ids']  = $roomIdsByOpt[$oid] ?? [];
            $r['selected_room_nums'] = $roomNumsByOpt[$oid] ?? [];
        }
        unset($r);
        return $rows;
    }

    /**
     * @param int[] $selectedRoomIds
     */
    public function saveOption(
        ?int $id,
        int $sectionId,
        string $label,
        string $type,
        int $maxCount,
        string $appliesTo,
        array $selectedRoomIds,
        int $isActive
    ): void {
        $label     = trim($label);
        $type      = ($type === 'counter') ? 'counter' : 'checkbox';
        $maxCount  = ($type === 'checkbox') ? 1 : max(1, min(99, $maxCount));
        $appliesTo = ($appliesTo === 'selected') ? 'selected' : 'all';

        $this->pdo->beginTransaction();
        try {
            if ($id && $id > 0) {
                $stmt = $this->pdo->prepare(
                    "UPDATE options
                     SET section_id = :s, label = :l, type = :t, max_count = :m, applies_to = :ap, is_active = :a
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':s'  => $sectionId,
                    ':l'  => $label,
                    ':t'  => $type,
                    ':m'  => $maxCount,
                    ':ap' => $appliesTo,
                    ':a'  => $isActive,
                    ':id' => $id,
                ]);
                $optionId = $id;
                Audit::log($this->pdo, 'update_option_cfg', 'option', $optionId, null, null, null, $label, null, 'আপডেট');
            } else {
                $stmtMax = $this->pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM options WHERE section_id = :s");
                $stmtMax->execute([':s' => $sectionId]);
                $nextOrder = (int) $stmtMax->fetchColumn();

                $stmt = $this->pdo->prepare(
                    "INSERT INTO options (section_id, label, type, max_count, applies_to, sort_order, is_active)
                     VALUES (:s, :l, :t, :m, :ap, :o, :a)"
                );
                $stmt->execute([
                    ':s'  => $sectionId,
                    ':l'  => $label,
                    ':t'  => $type,
                    ':m'  => $maxCount,
                    ':ap' => $appliesTo,
                    ':o'  => $nextOrder,
                    ':a'  => $isActive,
                ]);
                $optionId = (int) $this->pdo->lastInsertId();
                Audit::log($this->pdo, 'create_option_cfg', 'option', $optionId, null, null, null, $label, null, 'নতুন অপশন');
            }

            // Update option_rooms mapping
            $delMap = $this->pdo->prepare("DELETE FROM option_rooms WHERE option_id = :oid");
            $delMap->execute([':oid' => $optionId]);

            if ($appliesTo === 'selected' && !empty($selectedRoomIds)) {
                $insMap = $this->pdo->prepare("INSERT IGNORE INTO option_rooms (option_id, room_id) VALUES (:oid, :rid)");
                foreach ($selectedRoomIds as $rid) {
                    $rid = (int) $rid;
                    if ($rid > 0) {
                        $insMap->execute([':oid' => $optionId, ':rid' => $rid]);
                    }
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteOption(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM options WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Audit::log($this->pdo, 'delete_option_cfg', 'option', $id, null, null, null, 'অপশন #' . $id, null, 'মুছে ফেলা হয়েছে');
    }

    // =========================================================================
    // EXTRAS
    // =========================================================================

    public function listExtras(): array
    {
        return $this->pdo->query("SELECT * FROM extras ORDER BY sort_order ASC, id ASC")->fetchAll();
    }

    public function saveExtra(?int $id, string $label, int $maxCount, int $isActive): void
    {
        $label    = trim($label);
        $maxCount = max(1, min(99, $maxCount));

        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE extras SET label = :l, max_count = :m, is_active = :a WHERE id = :id");
            $stmt->execute([':l' => $label, ':m' => $maxCount, ':a' => $isActive, ':id' => $id]);
            Audit::log($this->pdo, 'update_extra_cfg', 'extra', $id, null, null, null, $label, null, 'আপডেট');
        } else {
            $nextOrder = (int) $this->pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM extras")->fetchColumn();
            $stmt = $this->pdo->prepare("INSERT INTO extras (label, max_count, sort_order, is_active) VALUES (:l, :m, :o, :a)");
            $stmt->execute([':l' => $label, ':m' => $maxCount, ':o' => $nextOrder, ':a' => $isActive]);
            Audit::log($this->pdo, 'create_extra_cfg', 'extra', (int) $this->pdo->lastInsertId(), null, null, null, $label, null, 'নতুন এক্সট্রা');
        }
    }

    public function deleteExtra(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM extras WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Audit::log($this->pdo, 'delete_extra_cfg', 'extra', $id, null, null, null, 'এক্সট্রা #' . $id, null, 'মুছে ফেলা হয়েছে');
    }

    // =========================================================================
    // GENERIC REORDER & TOGGLE HELPERS (Allowlisted tables only)
    // =========================================================================

    public function moveOrder(string $table, int $id, string $direction, ?string $groupCol = null): void
    {
        $allowed = ['floors', 'rooms', 'sections', 'options', 'extras'];
        if (!in_array($table, $allowed, true)) {
            return;
        }
        $allowedGroup = ['floor_id', 'section_id', null];
        if (!in_array($groupCol, $allowedGroup, true)) {
            return;
        }

        if ($groupCol !== null) {
            $stmtCur = $this->pdo->prepare("SELECT id, sort_order, {$groupCol} AS grp FROM {$table} WHERE id = :id");
        } else {
            $stmtCur = $this->pdo->prepare("SELECT id, sort_order FROM {$table} WHERE id = :id");
        }
        $stmtCur->execute([':id' => $id]);
        $cur = $stmtCur->fetch();
        if (!$cur) {
            return;
        }

        // Normalize sort orders in this group first so swaps are deterministic
        if ($groupCol !== null) {
            $stmtList = $this->pdo->prepare("SELECT id FROM {$table} WHERE {$groupCol} = :g ORDER BY sort_order ASC, id ASC");
            $stmtList->execute([':g' => $cur['grp']]);
        } else {
            $stmtList = $this->pdo->query("SELECT id FROM {$table} ORDER BY sort_order ASC, id ASC");
        }
        $ids = array_map('intval', $stmtList->fetchAll(PDO::FETCH_COLUMN));
        $idx = array_search($id, $ids, true);
        if ($idx === false) {
            return;
        }

        $swapIdx = ($direction === 'up') ? $idx - 1 : $idx + 1;
        if (!isset($ids[$swapIdx])) {
            return;
        }

        [$ids[$idx], $ids[$swapIdx]] = [$ids[$swapIdx], $ids[$idx]];

        $stmtUpd = $this->pdo->prepare("UPDATE {$table} SET sort_order = :ord WHERE id = :id");
        foreach ($ids as $pos => $itemId) {
            $stmtUpd->execute([':ord' => $pos + 1, ':id' => $itemId]);
        }
    }

    public function toggleActive(string $table, int $id): void
    {
        $allowed = ['floors', 'rooms', 'sections', 'options', 'extras', 'users'];
        if (!in_array($table, $allowed, true)) {
            return;
        }
        $stmt = $this->pdo->prepare("UPDATE {$table} SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    // =========================================================================
    // VERSIONED ROOM DEFAULTS (effective_from = today)
    // =========================================================================

    /**
     * Get current effective defaults for a room as of today, along with applicable active options.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoomDefaults(int $roomId): array
    {
        $today = date('Y-m-d');

        // Applicable active options for this room
        $stmtOpts = $this->pdo->prepare(
            "SELECT o.id, o.section_id, o.label, o.type, o.max_count, s.title AS section_title
             FROM options o
             INNER JOIN sections s ON s.id = o.section_id AND s.is_active = 1
             WHERE o.is_active = 1
               AND (o.applies_to = 'all' OR EXISTS (
                    SELECT 1 FROM option_rooms or_m
                    WHERE or_m.option_id = o.id AND or_m.room_id = :rid
               ))
             ORDER BY s.sort_order ASC, s.id ASC, o.sort_order ASC, o.id ASC"
        );
        $stmtOpts->execute([':rid' => $roomId]);
        $options = $stmtOpts->fetchAll();

        $stmtDef = $this->pdo->prepare(
            "SELECT rd.option_id, rd.qty, rd.effective_from
             FROM room_defaults rd
             INNER JOIN (
                 SELECT option_id, MAX(effective_from) AS max_eff
                 FROM room_defaults
                 WHERE room_id = :rid1 AND effective_from <= :today
                 GROUP BY option_id
             ) latest
               ON rd.option_id = latest.option_id
              AND rd.effective_from = latest.max_eff
             WHERE rd.room_id = :rid2"
        );
        $stmtDef->execute([':rid1' => $roomId, ':rid2' => $roomId, ':today' => $today]);
        $defMap = [];
        foreach ($stmtDef->fetchAll() as $row) {
            $defMap[(int) $row['option_id']] = [
                'qty'            => (int) $row['qty'],
                'effective_from' => (string) $row['effective_from'],
            ];
        }

        $result = [];
        foreach ($options as $opt) {
            $oid = (int) $opt['id'];
            $result[] = [
                'option_id'      => $oid,
                'section_title'  => $opt['section_title'],
                'label'          => $opt['label'],
                'type'           => $opt['type'],
                'max_count'      => (int) $opt['max_count'],
                'qty'            => $defMap[$oid]['qty'] ?? 0,
                'effective_from' => $defMap[$oid]['effective_from'] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Save versioned defaults for one room effective from today (never alters past dates).
     *
     * @param array<int, int> $optionQtys [option_id => qty] (0 means excluded)
     */
    public function saveRoomDefaults(int $roomId, array $optionQtys, int $userId): void
    {
        $today = date('Y-m-d');
        $stmtUp = $this->pdo->prepare(
            "INSERT INTO room_defaults (room_id, option_id, qty, effective_from, updated_by)
             VALUES (:r, :o, :q, :eff, :u)
             ON DUPLICATE KEY UPDATE qty = VALUES(qty), updated_by = VALUES(updated_by)"
        );

        $this->pdo->beginTransaction();
        try {
            foreach ($optionQtys as $oid => $qty) {
                $stmtUp->execute([
                    ':r'   => $roomId,
                    ':o'   => (int) $oid,
                    ':q'   => max(0, (int) $qty),
                    ':eff' => $today,
                    ':u'   => $userId,
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $stmtNum = $this->pdo->prepare("SELECT room_number FROM rooms WHERE id = :id");
        $stmtNum->execute([':id' => $roomId]);
        $rNum = (string) ($stmtNum->fetchColumn() ?: $roomId);

        Audit::log($this->pdo, 'update_defaults', 'room_defaults', $roomId, $today, $roomId, $rNum, 'রুম ডিফল্ট আপডেট', null, 'কার্যকর: ' . $today);
    }

    /**
     * Copy source room's current defaults to all active rooms effective from today
     * (respecting per-room option_rooms applicability).
     */
    public function copyDefaultsToAllRooms(int $sourceRoomId, int $userId): void
    {
        $today = date('Y-m-d');
        $sourceDefaults = $this->getRoomDefaults($sourceRoomId);
        $sourceMap = [];
        foreach ($sourceDefaults as $sd) {
            $sourceMap[$sd['option_id']] = (int) $sd['qty'];
        }

        $rooms = $this->pdo->query("SELECT id FROM rooms WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        $options = $this->pdo->query("SELECT id, applies_to FROM options WHERE is_active = 1")->fetchAll();
        $optRoomsRaw = $this->pdo->query("SELECT option_id, room_id FROM option_rooms")->fetchAll();
        $appMap = [];
        foreach ($optRoomsRaw as $m) {
            $appMap[(int) $m['option_id']][(int) $m['room_id']] = true;
        }

        $stmtUp = $this->pdo->prepare(
            "INSERT INTO room_defaults (room_id, option_id, qty, effective_from, updated_by)
             VALUES (:r, :o, :q, :eff, :u)
             ON DUPLICATE KEY UPDATE qty = VALUES(qty), updated_by = VALUES(updated_by)"
        );

        $this->pdo->beginTransaction();
        try {
            foreach ($rooms as $rid) {
                $rid = (int) $rid;
                foreach ($options as $opt) {
                    $oid = (int) $opt['id'];
                    if ($opt['applies_to'] === 'selected' && empty($appMap[$oid][$rid])) {
                        continue;
                    }
                    if (!array_key_exists($oid, $sourceMap)) {
                        continue;
                    }
                    $stmtUp->execute([
                        ':r'   => $rid,
                        ':o'   => $oid,
                        ':q'   => $sourceMap[$oid],
                        ':eff' => $today,
                        ':u'   => $userId,
                    ]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        Audit::log($this->pdo, 'copy_defaults_all', 'room_defaults', $sourceRoomId, $today, $sourceRoomId, null, 'সব রুমে ডিফল্ট কপি', null, 'কার্যকর: ' . $today);
    }

    /**
     * Reset all rooms' defaults effective from today (sets qty = 0 from today onward,
     * preserving past dates while causing daily board to show all applicable options).
     */
    public function resetAllDefaults(int $userId): void
    {
        $today = date('Y-m-d');
        $rooms = $this->pdo->query("SELECT id FROM rooms WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        $options = $this->pdo->query("SELECT id FROM options WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);

        $stmtUp = $this->pdo->prepare(
            "INSERT INTO room_defaults (room_id, option_id, qty, effective_from, updated_by)
             VALUES (:r, :o, 0, :eff, :u)
             ON DUPLICATE KEY UPDATE qty = 0, updated_by = VALUES(updated_by)"
        );

        $this->pdo->beginTransaction();
        try {
            foreach ($rooms as $rid) {
                foreach ($options as $oid) {
                    $stmtUp->execute([
                        ':r'   => (int) $rid,
                        ':o'   => (int) $oid,
                        ':eff' => $today,
                        ':u'   => $userId,
                    ]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        Audit::log($this->pdo, 'reset_defaults_all', 'room_defaults', null, $today, null, null, 'সব ডিফল্ট রিসেট', null, 'কার্যকর: ' . $today);
    }

    // =========================================================================
    // USERS (Owner only)
    // =========================================================================

    public function listUsers(): array
    {
        return $this->pdo->query(
            "SELECT id, name, email, role, is_active, last_login_at, created_at
             FROM users
             ORDER BY FIELD(role, 'owner', 'manager', 'staff'), id ASC"
        )->fetchAll();
    }

    public function createUser(string $name, string $email, string $password, string $role): string|bool
    {
        $email = strtolower(trim($email));
        $name  = trim($name);
        $role  = in_array($role, ['owner', 'manager', 'staff'], true) ? $role : 'staff';

        if (strlen($password) < 6) {
            return t('password_min_error');
        }

        $stmtDup = $this->pdo->prepare("SELECT id FROM users WHERE email = :e");
        $stmtDup->execute([':e' => $email]);
        if ($stmtDup->fetch()) {
            return t('email_exists_error');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:n, :e, :h, :r, 1)"
        );
        $stmt->execute([':n' => $name, ':e' => $email, ':h' => $hash, ':r' => $role]);

        Audit::log($this->pdo, 'create_user', 'user', (int) $this->pdo->lastInsertId(), null, null, null, $email, null, $role);
        return true;
    }

    public function resetUserPassword(int $userId, string $newPassword): string|bool
    {
        if (strlen($newPassword) < 6) {
            return t('password_min_error');
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        $stmt->execute([':h' => $hash, ':id' => $userId]);
        Audit::log($this->pdo, 'reset_password', 'user', $userId, null, null, null, 'ইউজার #' . $userId, null, 'পাসওয়ার্ড রিসেট');
        return true;
    }

    // =========================================================================
    // AUDIT LOG
    // =========================================================================

    public function listAuditLogs(?string $date = null, ?string $roomNumber = null, int $limit = 150): array
    {
        $where = [];
        $params = [];

        if ($date !== null && $date !== '') {
            $where[] = "(check_date = :d OR DATE(created_at) = :d2)";
            $params[':d']  = $date;
            $params[':d2'] = $date;
        }
        if ($roomNumber !== null && trim($roomNumber) !== '') {
            $where[] = "room_number = :rn";
            $params[':rn'] = trim($roomNumber);
        }

        $sql = "SELECT * FROM audit_log";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY id DESC LIMIT " . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
