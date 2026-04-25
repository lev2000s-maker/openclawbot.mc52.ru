<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/api_bootstrap.php';
require_api_or_session_json();

$method = api_method();
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET' && $id > 0) {
    $r = mb_one(
        'SELECT u.*, s.name AS supplier_name FROM price_uploads u
         LEFT JOIN suppliers s ON s.id = u.supplier_id WHERE u.id = ?',
        [$id]
    );
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    if (!empty($r['stored_path']) && is_readable((string) $r['stored_path'])) {
        $r['stored_size_on_disk'] = (int) @filesize((string) $r['stored_path']);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET') {
    $rows = mb_all(
        'SELECT u.id, u.original_name, u.stored_path, u.file_size, u.mime_type, u.supplier_id, u.created_at, s.name AS supplier_name
         FROM price_uploads u LEFT JOIN suppliers s ON s.id = u.supplier_id ORDER BY u.id DESC'
    );
    json_out(['ok' => true, 'data' => $rows]);
}

$del = static function (int $i): void {
    if ($i <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    $row = mb_one('SELECT stored_path FROM price_uploads WHERE id = ?', [$i]);
    if ($row) {
        $p = (string) $row['stored_path'];
        if ($p !== '' && is_file($p)) {
            @unlink($p);
        }
    }
    mb_db()->prepare('DELETE FROM price_uploads WHERE id = ?')->execute([$i]);
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

json_out(['ok' => false, 'error' => 'method_not_allowed', 'hint' => 'Загрузка файла — через веб-форму /pages/price_uploads.php'], 405);
