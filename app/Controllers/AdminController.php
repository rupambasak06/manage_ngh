<?php
/**
 * Admin Controller
 * Manages Floors/Rooms, Sections, Options, Extras, Versioned Room Defaults, Users (Owner only), and Audit Log.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\AdminModel;
use PDO;

final class AdminController
{
    private AdminModel $model;

    public function __construct(private PDO $pdo)
    {
        $this->model = new AdminModel($pdo);
    }

    // -------------------------------------------------------------------------
    // 1. Floors & Rooms
    // -------------------------------------------------------------------------
    public function floorsRooms(): void
    {
        $user = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid(false);
            $act = (string) ($_POST['action'] ?? '');

            if ($act === 'save_floor') {
                $name = trim((string) ($_POST['name'] ?? ''));
                if ($name === '') {
                    flash_set('error', t('error_required_fields'));
                } else {
                    $this->model->saveFloor((int) ($_POST['id'] ?? 0) ?: null, $name, isset($_POST['is_active']) ? 1 : 0);
                    flash_set('success', t('saved_success'));
                }
            } elseif ($act === 'delete_floor') {
                if ($this->model->deleteFloor((int) ($_POST['id'] ?? 0))) {
                    flash_set('success', t('deleted_success'));
                } else {
                    flash_set('error', t('floor_has_rooms_error'));
                }
            } elseif ($act === 'move_floor') {
                $this->model->moveOrder('floors', (int) ($_POST['id'] ?? 0), (string) ($_POST['dir'] ?? 'up'));
            } elseif ($act === 'toggle_floor') {
                $this->model->toggleActive('floors', (int) ($_POST['id'] ?? 0));
            } elseif ($act === 'save_room') {
                $floorId = (int) ($_POST['floor_id'] ?? 0);
                $num     = trim((string) ($_POST['room_number'] ?? ''));
                if ($floorId <= 0 || $num === '') {
                    flash_set('error', t('error_required_fields'));
                } elseif (!$this->model->saveRoom((int) ($_POST['id'] ?? 0) ?: null, $floorId, $num, isset($_POST['is_active']) ? 1 : 0)) {
                    flash_set('error', t('room_exists_error'));
                } else {
                    flash_set('success', t('saved_success'));
                }
            } elseif ($act === 'delete_room') {
                $this->model->deleteRoom((int) ($_POST['id'] ?? 0));
                flash_set('success', t('deleted_success'));
            } elseif ($act === 'move_room') {
                $this->model->moveOrder('rooms', (int) ($_POST['id'] ?? 0), (string) ($_POST['dir'] ?? 'up'), 'floor_id');
            } elseif ($act === 'toggle_room') {
                $this->model->toggleActive('rooms', (int) ($_POST['id'] ?? 0));
            }

            redirect('?route=admin/floors');
        }

        render_view('admin/floors_rooms', [
            'pageTitle'   => t('admin_floors_rooms'),
            'activeNav'   => 'admin',
            'adminTab'    => 'floors',
            'currentUser' => $user,
            'floors'      => $this->model->listFloors(),
            'rooms'       => $this->model->listRooms(),
        ], true);
    }

    // -------------------------------------------------------------------------
    // 2. Sections
    // -------------------------------------------------------------------------
    public function sections(): void
    {
        $user = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid(false);
            $act = (string) ($_POST['action'] ?? '');

            if ($act === 'save_section') {
                $title = trim((string) ($_POST['title'] ?? ''));
                if ($title === '') {
                    flash_set('error', t('error_required_fields'));
                } else {
                    $this->model->saveSection(
                        (int) ($_POST['id'] ?? 0) ?: null,
                        $title,
                        isset($_POST['counts_toward_checkout']) ? 1 : 0,
                        isset($_POST['is_active']) ? 1 : 0
                    );
                    flash_set('success', t('saved_success'));
                }
            } elseif ($act === 'delete_section') {
                $this->model->deleteSection((int) ($_POST['id'] ?? 0));
                flash_set('success', t('deleted_success'));
            } elseif ($act === 'move_section') {
                $this->model->moveOrder('sections', (int) ($_POST['id'] ?? 0), (string) ($_POST['dir'] ?? 'up'));
            } elseif ($act === 'toggle_section') {
                $this->model->toggleActive('sections', (int) ($_POST['id'] ?? 0));
            }

            redirect('?route=admin/sections');
        }

        render_view('admin/sections', [
            'pageTitle'   => t('admin_sections'),
            'activeNav'   => 'admin',
            'adminTab'    => 'sections',
            'currentUser' => $user,
            'sections'    => $this->model->listSections(),
        ], true);
    }

    // -------------------------------------------------------------------------
    // 3. Options
    // -------------------------------------------------------------------------
    public function options(): void
    {
        $user = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid(false);
            $act = (string) ($_POST['action'] ?? '');

            if ($act === 'save_option') {
                $sectionId = (int) ($_POST['section_id'] ?? 0);
                $label     = trim((string) ($_POST['label'] ?? ''));
                if ($sectionId <= 0 || $label === '') {
                    flash_set('error', t('error_required_fields'));
                } else {
                    $selectedRooms = array_map('intval', (array) ($_POST['room_ids'] ?? []));
                    $this->model->saveOption(
                        (int) ($_POST['id'] ?? 0) ?: null,
                        $sectionId,
                        $label,
                        (string) ($_POST['type'] ?? 'checkbox'),
                        (int) ($_POST['max_count'] ?? 10),
                        (string) ($_POST['applies_to'] ?? 'all'),
                        $selectedRooms,
                        isset($_POST['is_active']) ? 1 : 0
                    );
                    flash_set('success', t('saved_success'));
                }
            } elseif ($act === 'delete_option') {
                $this->model->deleteOption((int) ($_POST['id'] ?? 0));
                flash_set('success', t('deleted_success'));
            } elseif ($act === 'move_option') {
                $this->model->moveOrder('options', (int) ($_POST['id'] ?? 0), (string) ($_POST['dir'] ?? 'up'), 'section_id');
            } elseif ($act === 'toggle_option') {
                $this->model->toggleActive('options', (int) ($_POST['id'] ?? 0));
            }

            redirect('?route=admin/options');
        }

        render_view('admin/options', [
            'pageTitle'   => t('admin_options'),
            'activeNav'   => 'admin',
            'adminTab'    => 'options',
            'currentUser' => $user,
            'sections'    => $this->model->listSections(),
            'rooms'       => $this->model->listRooms(),
            'options'     => $this->model->listOptions(),
        ], true);
    }

    // -------------------------------------------------------------------------
    // 4. Extras
    // -------------------------------------------------------------------------
    public function extras(): void
    {
        $user = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid(false);
            $act = (string) ($_POST['action'] ?? '');

            if ($act === 'save_extra') {
                $label = trim((string) ($_POST['label'] ?? ''));
                if ($label === '') {
                    flash_set('error', t('error_required_fields'));
                } else {
                    $this->model->saveExtra(
                        (int) ($_POST['id'] ?? 0) ?: null,
                        $label,
                        (int) ($_POST['max_count'] ?? 20),
                        isset($_POST['is_active']) ? 1 : 0
                    );
                    flash_set('success', t('saved_success'));
                }
            } elseif ($act === 'delete_extra') {
                $this->model->deleteExtra((int) ($_POST['id'] ?? 0));
                flash_set('success', t('deleted_success'));
            } elseif ($act === 'move_extra') {
                $this->model->moveOrder('extras', (int) ($_POST['id'] ?? 0), (string) ($_POST['dir'] ?? 'up'));
            } elseif ($act === 'toggle_extra') {
                $this->model->toggleActive('extras', (int) ($_POST['id'] ?? 0));
            }

            redirect('?route=admin/extras');
        }

        render_view('admin/extras', [
            'pageTitle'   => t('admin_extras'),
            'activeNav'   => 'admin',
            'adminTab'    => 'extras',
            'currentUser' => $user,
            'extras'      => $this->model->listExtras(),
        ], true);
    }

    // -------------------------------------------------------------------------
    // 5. Versioned Room Defaults
    // -------------------------------------------------------------------------
    public function defaults(): void
    {
        $user  = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);
        $rooms = $this->model->listRooms();

        $selectedRoomId = (int) ($_GET['room_id'] ?? ($_POST['room_id'] ?? 0));
        if ($selectedRoomId <= 0 && !empty($rooms)) {
            $selectedRoomId = (int) $rooms[0]['id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid(false);
            $act = (string) ($_POST['action'] ?? '');

            if ($act === 'save_defaults' && $selectedRoomId > 0) {
                $included = array_map('intval', (array) ($_POST['include'] ?? []));
                $qtys     = (array) ($_POST['qty'] ?? []);
                $allOpts  = array_map('intval', (array) ($_POST['all_option_ids'] ?? []));

                $payload = [];
                foreach ($allOpts as $oid) {
                    if (in_array($oid, $included, true)) {
                        $payload[$oid] = max(1, (int) ($qtys[$oid] ?? 1));
                    } else {
                        $payload[$oid] = 0;
                    }
                }
                $this->model->saveRoomDefaults($selectedRoomId, $payload, (int) $user['id']);
                flash_set('success', t('saved_success'));
            } elseif ($act === 'copy_all' && $selectedRoomId > 0) {
                $this->model->copyDefaultsToAllRooms($selectedRoomId, (int) $user['id']);
                flash_set('success', t('saved_success'));
            } elseif ($act === 'reset_all') {
                $this->model->resetAllDefaults((int) $user['id']);
                flash_set('success', t('saved_success'));
            }

            redirect('?route=admin/defaults&room_id=' . $selectedRoomId);
        }

        $defaults = $selectedRoomId > 0 ? $this->model->getRoomDefaults($selectedRoomId) : [];

        render_view('admin/defaults', [
            'pageTitle'      => t('admin_defaults'),
            'activeNav'      => 'admin',
            'adminTab'       => 'defaults',
            'currentUser'    => $user,
            'rooms'          => $rooms,
            'selectedRoomId' => $selectedRoomId,
            'defaults'       => $defaults,
        ], true);
    }

    // -------------------------------------------------------------------------
    // 6. Users Management (Owner ONLY)
    // -------------------------------------------------------------------------
    public function users(): void
    {
        $user = Auth::requireRoles($this->pdo, ['owner'], false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid(false);
            $act = (string) ($_POST['action'] ?? '');

            if ($act === 'create_user') {
                $name  = trim((string) ($_POST['name'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $pass  = (string) ($_POST['password'] ?? '');
                $role  = (string) ($_POST['role'] ?? 'staff');

                if ($name === '' || $email === '' || $pass === '') {
                    flash_set('error', t('error_required_fields'));
                } else {
                    $res = $this->model->createUser($name, $email, $pass, $role);
                    if ($res === true) {
                        flash_set('success', t('saved_success'));
                    } else {
                        flash_set('error', (string) $res);
                    }
                }
            } elseif ($act === 'toggle_user') {
                $targetId = (int) ($_POST['id'] ?? 0);
                if ($targetId === (int) $user['id']) {
                    flash_set('error', t('cannot_disable_self'));
                } else {
                    $this->model->toggleActive('users', $targetId);
                    flash_set('success', t('saved_success'));
                }
            } elseif ($act === 'reset_password') {
                $targetId = (int) ($_POST['id'] ?? 0);
                $newPass  = (string) ($_POST['new_password'] ?? '');
                $res = $this->model->resetUserPassword($targetId, $newPass);
                if ($res === true) {
                    flash_set('success', t('saved_success'));
                } else {
                    flash_set('error', (string) $res);
                }
            }

            redirect('?route=admin/users');
        }

        render_view('admin/users', [
            'pageTitle'   => t('admin_users'),
            'activeNav'   => 'admin',
            'adminTab'    => 'users',
            'currentUser' => $user,
            'users'       => $this->model->listUsers(),
        ], true);
    }

    // -------------------------------------------------------------------------
    // 7. Audit Log
    // -------------------------------------------------------------------------
    public function audit(): void
    {
        $user   = Auth::requireRoles($this->pdo, ['owner', 'manager'], false);
        $date   = trim((string) ($_GET['date'] ?? ''));
        $roomNo = trim((string) ($_GET['room'] ?? ''));

        render_view('admin/audit', [
            'pageTitle'   => t('admin_audit'),
            'activeNav'   => 'admin',
            'adminTab'    => 'audit',
            'currentUser' => $user,
            'logs'        => $this->model->listAuditLogs($date !== '' ? $date : null, $roomNo !== '' ? $roomNo : null, 200),
            'filterDate'  => $date,
            'filterRoom'  => $roomNo,
        ], true);
    }
}
