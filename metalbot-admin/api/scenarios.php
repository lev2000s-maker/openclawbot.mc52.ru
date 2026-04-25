<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/api_bootstrap.php';
require_api_or_session_json();

$method = api_method();
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET' && $id > 0) {
    $r = mb_one('SELECT * FROM scenarios WHERE id = ?', [$id]);
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET') {
    json_out(['ok' => true, 'data' => mb_all('SELECT * FROM scenarios ORDER BY name COLLATE NOCASE')]);
}

$del = static function (int $i): void {
    if ($i <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    mb_db()->prepare('DELETE FROM scenarios WHERE id = ?')->execute([$i]);
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
    $ex = mb_one('SELECT * FROM scenarios WHERE id = ?', [$id]) ?? [];
    if ($ex === []) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    $name = trim((string) ($in['name'] ?? (string) $ex['name']));
    if ($name === '') {
        json_out(['ok' => false, 'error' => 'name_required'], 400);
    }
    $ac = (int) ($in['is_active'] ?? (int) ($ex['is_active'] ?? 0));
    mb_db()->prepare('UPDATE scenarios SET name=?, description=?, is_active=?, updated_at=datetime(\'now\') WHERE id=?')
        ->execute([
            $name,
            (string) ($in['description'] ?? $ex['description']),
            $ac ? 1 : 0,
            $id,
        ]);
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM scenarios WHERE id = ?', [$id])]);
}

if ($method === 'POST') {
    $in = api_json_in();
    $name = trim((string) ($in['name'] ?? ''));
    if ($name === '') {
        json_out(['ok' => false, 'error' => 'name_required'], 400);
    }
    $ac = (int) ($in['is_active'] ?? 1);
    $nid = (int) mb_exec(
        'INSERT INTO scenarios (name, description, is_active) VALUES (?,?,?)',
        [$name, (string) ($in['description'] ?? ''), $ac ? 1 : 0]
    );
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM scenarios WHERE id = ?', [$nid])], 201);
}

json_out(['ok' => false, 'error' => 'method_not_allowed', 'method' => $method], 405);
