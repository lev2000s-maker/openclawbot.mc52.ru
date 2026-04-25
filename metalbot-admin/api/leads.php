<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/api_bootstrap.php';
require_api_or_session_json();

$method = api_method();
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET' && $id > 0) {
    $r = mb_one('SELECT * FROM leads WHERE id = ?', [$id]);
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET') {
    json_out(['ok' => true, 'data' => mb_all('SELECT * FROM leads ORDER BY id DESC')]);
}

$del = static function (int $i): void {
    if ($i <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    mb_db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$i]);
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
    $ex = mb_one('SELECT * FROM leads WHERE id = ?', [$id]) ?? [];
    if ($ex === []) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    $name = trim((string) ($in['name'] ?? (string) $ex['name']));
    if ($name === '') {
        json_out(['ok' => false, 'error' => 'name_required'], 400);
    }
    mb_db()->prepare('UPDATE leads SET name=?, phone=?, email=?, source=?, status=?, notes=?, updated_at=datetime(\'now\') WHERE id=?')
        ->execute([
            $name,
            (string) ($in['phone'] ?? $ex['phone']),
            (string) ($in['email'] ?? $ex['email']),
            (string) ($in['source'] ?? $ex['source']),
            (string) ($in['status'] ?? $ex['status']),
            (string) ($in['notes'] ?? $ex['notes']),
            $id,
        ]);
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM leads WHERE id = ?', [$id])]);
}

if ($method === 'POST') {
    $in = api_json_in();
    $name = trim((string) ($in['name'] ?? ''));
    if ($name === '') {
        json_out(['ok' => false, 'error' => 'name_required'], 400);
    }
    $nid = (int) mb_exec(
        'INSERT INTO leads (name, phone, email, source, status, notes) VALUES (?,?,?,?,?,?)',
        [
            $name,
            (string) ($in['phone'] ?? ''),
            (string) ($in['email'] ?? ''),
            (string) ($in['source'] ?? ''),
            (string) ($in['status'] ?? 'new'),
            (string) ($in['notes'] ?? ''),
        ]
    );
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM leads WHERE id = ?', [$nid])], 201);
}

json_out(['ok' => false, 'error' => 'method_not_allowed', 'method' => $method], 405);
