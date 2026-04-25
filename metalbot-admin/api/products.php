<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/api_bootstrap.php';
require_api_or_session_json();

$method = api_method();
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET' && $id > 0) {
    $r = mb_one(
        'SELECT p.*, s.name AS supplier_name FROM products p
         LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE p.id = ?',
        [$id]
    );
    if ($r === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    json_out(['ok' => true, 'data' => $r]);
}
if ($method === 'GET') {
    $rows = mb_all('SELECT p.*, s.name AS supplier_name FROM products p
        LEFT JOIN suppliers s ON s.id = p.supplier_id ORDER BY p.name COLLATE NOCASE');
    json_out(['ok' => true, 'data' => $rows]);
}

$del = static function (int $i): void {
    if ($i <= 0) {
        json_out(['ok' => false, 'error' => 'id_required'], 400);
    }
    mb_db()->prepare('DELETE FROM products WHERE id = ?')->execute([$i]);
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
    $ex = mb_one('SELECT * FROM products WHERE id = ?', [$id]);
    if ($ex === null) {
        json_out(['ok' => false, 'error' => 'not_found'], 404);
    }
    $sid = (int) ($in['supplier_id'] ?? $ex['supplier_id'] ?? 0);
    $supplierId = $sid > 0 ? $sid : null;
    $sku = (string) ($in['sku'] ?? $ex['sku'] ?? '');
    $name = trim((string) ($in['name'] ?? (string) $ex['name']));
    $priceRaw = $in['price'] ?? $ex['price'] ?? null;
    $price = $priceRaw === null || $priceRaw === '' ? null : (float) $priceRaw;
    $unit = (string) ($in['unit'] ?? $ex['unit'] ?? '');
    $notes = (string) ($in['notes'] ?? $ex['notes'] ?? '');
    if ($name === '') {
        json_out(['ok' => false, 'error' => 'name_required'], 400);
    }
    mb_db()->prepare('UPDATE products SET supplier_id=?, sku=?, name=?, price=?, unit=?, notes=?, updated_at=datetime(\'now\') WHERE id=?')
        ->execute([$supplierId, $sku, $name, $price, $unit, $notes, $id]);
    $row = mb_one(
        'SELECT p.*, s.name AS supplier_name FROM products p
         LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE p.id = ?',
        [$id]
    );
    json_out(['ok' => true, 'data' => $row]);
}

if ($method === 'POST') {
    $in = api_json_in();
    $name = trim((string) ($in['name'] ?? ''));
    if ($name === '') {
        json_out(['ok' => false, 'error' => 'name_required'], 400);
    }
    $sid = (int) ($in['supplier_id'] ?? 0);
    $supplierId = $sid > 0 ? $sid : null;
    $sku = (string) ($in['sku'] ?? '');
    $priceRaw = $in['price'] ?? null;
    $price = $priceRaw === null || $priceRaw === '' ? null : (float) $priceRaw;
    $unit = (string) ($in['unit'] ?? '');
    $notes = (string) ($in['notes'] ?? '');
    $newId = (int) mb_exec(
        'INSERT INTO products (supplier_id, sku, name, price, unit, notes) VALUES (?,?,?,?,?,?)',
        [$supplierId, $sku, $name, $price, $unit, $notes]
    );
    $row = mb_one(
        'SELECT p.*, s.name AS supplier_name FROM products p
         LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE p.id = ?',
        [$newId]
    );
    json_out(['ok' => true, 'data' => $row], 201);
}

json_out(['ok' => false, 'error' => 'method_not_allowed', 'method' => $method], 405);
