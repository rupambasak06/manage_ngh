<?php
/**
 * Audit Log Writer
 * Records who changed what and when (user, room, option/extra/note, old/new value).
 */

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Audit
{
    public static function log(
        PDO $pdo,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $checkDate = null,
        ?int $roomId = null,
        ?string $roomNumber = null,
        ?string $itemLabel = null,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        try {
            $userId   = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            $userName = (string) ($_SESSION['user_name'] ?? 'System');

            $stmt = $pdo->prepare(
                "INSERT INTO audit_log
                    (user_id, user_name, action, entity_type, entity_id, check_date, room_id, room_number, item_label, old_value, new_value, ip_address)
                 VALUES
                    (:user_id, :user_name, :action, :entity_type, :entity_id, :check_date, :room_id, :room_number, :item_label, :old_value, :new_value, :ip)"
            );
            $stmt->execute([
                ':user_id'     => $userId,
                ':user_name'   => $userName,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':check_date'  => $checkDate,
                ':room_id'     => $roomId,
                ':room_number' => $roomNumber,
                ':item_label'  => $itemLabel,
                ':old_value'   => $oldValue,
                ':new_value'   => $newValue,
                ':ip'          => client_ip(),
            ]);
        } catch (\Throwable $e) {
            // Audit failure should never break primary checklist autosave
            error_log('Audit log write failed: ' . $e->getMessage());
        }
    }
}
