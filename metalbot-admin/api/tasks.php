<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/api_bootstrap.php';
require_api_or_session_json();

$method = api_method();
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET' && $id > 0) {
    $r = mb_one('SELECT * FROM tasks WHERE id = ?', [$id]);
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET') {
    json_out(['ok' => true, 'data' => mb_all('SELECT * FROM tasks ORDER BY id DESC')]);
}

$del = static function (int $i): void {
    if ($i <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    mb_db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$i]);
    json_out(['ok' => true, 'deleted' => $i]);
};

if ($method === 'DELETE') {
    $del($id);
}
if ($method === 'POST' && (($_GET['op'] ?? '') === 'delete' || ($_GET['action'] ?? '') === 'delete')) {
    if ($id <= 0) {
        $in = api_json_in();
        $id = (int) ($in['id'] ?? 0);
    }
    $del($id);
}

if ($method === 'PUT' || $method === 'PATCH') {
    $in = api_json_in();
    if ($id <= 0) {
        $id = (int) ($in['id'] ?? 0);
    }
    if ($id <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    $ex = mb_one('SELECT * FROM tasks WHERE id = ?', [$id]) ?? [];
    if ($ex === []) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    $title = trim((string) ($in['title'] ?? (string) $ex['title']));
    if ($title === '') {
        json_out(['ok' => false, 'error' => 'title_required'], 400);
    }
    $due = $in['due_at'] ?? $ex['due_at'] ?? null;
    $dueAt = $due === null || $due === '' ? null : (string) $due;
    mb_db()->prepare('UPDATE tasks SET title=?, description=?, status=?, due_at=?, updated_at=datetime(\'now\') WHERE id=?')
        ->execute([
            $title,
            (string) ($in['description'] ?? $ex['description']),
            (string) ($in['status'] ?? $ex['status']),
            $dueAt,
            $id,
        ]);
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM tasks WHERE id = ?', [$id])]);
}

if ($method === 'POST') {
    $in = api_json_in();
    $title = trim((string) ($in['title'] ?? ''));
    if ($title === '') {
        json_out(['ok' => false, 'error' => 'title_required'], 400);
    }
    $due = $in['due_at'] ?? null;
    $dueAt = $due === null || $due === '' ? null : (string) $due;
    $nid = (int) mb_exec(
        'INSERT INTO tasks (title, description, status, due_at) VALUES (?,?,?,?)',
        [
            $title,
            (string) ($in['description'] ?? ''),
            (string) ($in['status'] ?? 'open'),
            $dueAt,
        ]
    );
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM tasks WHERE id = ?', [$nid])], 201);
}

json_out(['ok' => false, 'error' => 'method_not_allowed', 'method' => $method], 405);
