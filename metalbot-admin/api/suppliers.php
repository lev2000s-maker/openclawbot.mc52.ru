<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/api_bootstrap.php';
require_api_or_session_json();

$method = api_method();
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET' && $id > 0) {
    $r = mb_one('SELECT * FROM suppliers WHERE id = ?', [$id]);
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET') {
    $rows = mb_all('SELECT * FROM suppliers ORDER BY name COLLATE NOCASE');
    json_out(['ok' => true, 'data' => $rows]);
}

$tryDelete = static function (int $i): void {
    if ($i <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    try {
        mb_db()->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$i]);
        json_out(['ok' => true, 'deleted' => $i]);
    } catch (Throwable $e) {
        json_out(['ok' => false, 'error' => 'conflict', 'message' => $e->getMessage()], 409);
    }
};

if ($method === 'DELETE') {
    $tryDelete($id);
}

if ($method === 'POST' && (($_GET['op'] ?? '') === 'delete' || ($_GET['action'] ?? '') === 'delete')) {
    if ($id <= 0) {
        $in = api_json_in();
        $id = (int) ($in['id'] ?? 0);
    }
    $tryDelete($id);
}

if ($method === 'PUT' || $method === 'PATCH') {
    $in = api_json_in();
    if ($id <= 0) {
        $id = (int) ($in['id'] ?? 0);
    }
    if ($id <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    $ex = mb_one('SELECT * FROM suppliers WHERE id = ?', [$id]);
    if ($ex === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    $name = trim((string) ($in['name'] ?? (string) $ex['name']));
    $contact = (string) ($in['contact'] ?? $ex['contact']);
    $notes = (string) ($in['notes'] ?? $ex['notes']);
    mb_db()->prepare('UPDATE suppliers SET name=?, contact=?, notes=?, updated_at=datetime(\'now\') WHERE id=?')
        ->execute([$name, $contact, $notes, $id]);
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM suppliers WHERE id = ?', [$id])]);
}

if ($method === 'POST') {
    $in = api_json_in();
    $name = trim((string) ($in['name'] ?? ''));
    if ($name === '') {
        json_out(['ok' => false, 'error' => 'name_required'], 400);
    }
    $contact = (string) ($in['contact'] ?? '');
    $notes = (string) ($in['notes'] ?? '');
    $newId = mb_exec('INSERT INTO suppliers (name, contact, notes) VALUES (?,?,?)', [$name, $contact, $notes]);
    $row = mb_one('SELECT * FROM suppliers WHERE id = ?', [(int) $newId]);
    json_out(['ok' => true, 'data' => $row], 201);
}

json_out(['ok' => false, 'error' => 'method_not_allowed', 'method' => $method], 405);
