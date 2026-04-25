<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/api_bootstrap.php';
require_api_or_session_json();

$method = api_method();
$id = (int) ($_GET['id'] ?? 0);
$keyQ = (string) ($_GET['key'] ?? '');

if ($method === 'GET' && $keyQ !== '' && $id === 0) {
    $r = mb_one('SELECT * FROM prompts WHERE prompt_key = ?', [$keyQ]);
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET' && $id > 0) {
    $r = mb_one('SELECT * FROM prompts WHERE id = ?', [$id]);
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET') {
    json_out(['ok' => true, 'data' => mb_all('SELECT * FROM prompts ORDER BY prompt_key COLLATE NOCASE')]);
}

$del = static function (int $i): void {
    if ($i <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    mb_db()->prepare('DELETE FROM prompts WHERE id = ?')->execute([$i]);
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
    $ex = mb_one('SELECT * FROM prompts WHERE id = ?', [$id]) ?? [];
    if ($ex === []) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    mb_db()->prepare('UPDATE prompts SET title=?, content=?, updated_at=datetime(\'now\') WHERE id=?')
        ->execute([
            (string) ($in['title'] ?? (string) $ex['title']),
            (string) ($in['content'] ?? (string) $ex['content']),
            $id,
        ]);
    json_out(['ok' => true, 'data' => mb_one('SELECT * FROM prompts WHERE id = ?', [$id])]);
}

if ($method === 'POST') {
    $in = api_json_in();
    $k = trim((string) ($in['prompt_key'] ?? ''));
    $title = trim((string) ($in['title'] ?? ''));
    if ($k === '' || $title === '') {
        json_out(['ok' => false, 'error' => 'key_and_title_required'], 400);
    }
    try {
        $newId = (int) mb_exec(
            'INSERT INTO prompts (prompt_key, title, content) VALUES (?,?,?)',
            [$k, $title, (string) ($in['content'] ?? '')]
        );
        json_out(['ok' => true, 'data' => mb_one('SELECT * FROM prompts WHERE id = ?', [$newId])], 201);
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'UNIQUE')) {
            json_out(['ok' => false, 'error' => 'key_exists'], 409);
        }
        json_out(['ok' => false, 'error' => 'save_failed'], 500);
    }
}

json_out(['ok' => false, 'error' => 'method_not_allowed', 'method' => $method], 405);
