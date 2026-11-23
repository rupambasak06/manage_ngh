<?php
/**
 * Checklist Controller
 * Main daily checklist screen + AJAX autosave endpoints + 10s live refresh polling endpoint.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\ChecklistModel;
use PDO;

final class ChecklistController
{
    private ChecklistModel $model;

    public function __construct(private PDO $pdo, private array $config)
    {
        $this->model = new ChecklistModel($pdo);
    }

    public function index(): void
    {
        $user  = Auth::requireLogin($this->pdo, false);
        $date  = normalize_date($_GET['date'] ?? null);
        $today = date('Y-m-d');
        $board = $this->model->getDailyBoard($date);

        $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));

        render_view('checklist/index', [
            'pageTitle'    => t('app_title'),
            'activeNav'    => 'checklist',
            'currentUser'  => $user,
            'board'        => $board,
            'date'         => $date,
            'today'        => $today,
            'prevDate'     => $prevDate,
            'nextDate'     => $nextDate,
            'pollInterval' => (int) ($this->config['app']['poll_interval'] ?? 10000),
        ], true);
    }

    /**
     * AJAX: Save option checkbox/counter value.
     */
    public function apiSaveOption(): void
    {
        $user = Auth::requireLogin($this->pdo, true);
        Csrf::requireValid(true);

        $input    = $this->jsonInput();
        $date     = normalize_date($input['date'] ?? null);
        $roomId   = (int) ($input['room_id'] ?? 0);
        $optionId = (int) ($input['option_id'] ?? 0);
        $qty      = (int) ($input['qty'] ?? 0);

        $res = $this->model->upsertOption($date, $roomId, $optionId, $qty, (int) $user['id']);
        if (!$res['ok']) {
            json_response($res, 400);
        }

        $board = $this->model->getDailyBoard($date);
        json_response([
            'ok'    => true,
            'qty'   => $res['qty'],
            'board' => $board,
        ]);
    }

    /**
     * AJAX: Save extra counter value.
     */
    public function apiSaveExtra(): void
    {
        $user = Auth::requireLogin($this->pdo, true);
        Csrf::requireValid(true);

        $input   = $this->jsonInput();
        $date    = normalize_date($input['date'] ?? null);
        $roomId  = (int) ($input['room_id'] ?? 0);
        $extraId = (int) ($input['extra_id'] ?? 0);
        $qty     = (int) ($input['qty'] ?? 0);

        $res = $this->model->upsertExtra($date, $roomId, $extraId, $qty, (int) $user['id']);
        if (!$res['ok']) {
            json_response($res, 400);
        }

        $board = $this->model->getDailyBoard($date);
        json_response([
            'ok'    => true,
            'qty'   => $res['qty'],
            'board' => $board,
        ]);
    }

    /**
     * AJAX: Save room note.
     */
    public function apiSaveNote(): void
    {
        $user = Auth::requireLogin($this->pdo, true);
        Csrf::requireValid(true);

        $input  = $this->jsonInput();
        $date   = normalize_date($input['date'] ?? null);
        $roomId = (int) ($input['room_id'] ?? 0);
        $note   = (string) ($input['note'] ?? '');

        $res = $this->model->upsertNote($date, $roomId, $note, (int) $user['id']);
        if (!$res['ok']) {
            json_response($res, 400);
        }

        $board = $this->model->getDailyBoard($date);
        json_response([
            'ok'    => true,
            'note'  => $res['note'],
            'board' => $board,
        ]);
    }

    /**
     * AJAX: Live refresh poll (every 10 seconds).
     * Returns full board only if server timestamp changed (or on forced refresh), plus server_today for midnight rollover.
     */
    public function apiPoll(): void
    {
        Auth::requireLogin($this->pdo, true);

        $date        = normalize_date($_GET['date'] ?? null);
        $since       = trim((string) ($_GET['since'] ?? ''));
        $serverToday = date('Y-m-d');

        $board = $this->model->getDailyBoard($date);
        $changed = ($since === '' || $board['last_sync_at'] !== $since);

        json_response([
            'ok'           => true,
            'changed'      => $changed,
            'server_today' => $serverToday,
            'last_sync_at' => $board['last_sync_at'],
            'board'        => $changed ? $board : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $_POST;
    }
}
