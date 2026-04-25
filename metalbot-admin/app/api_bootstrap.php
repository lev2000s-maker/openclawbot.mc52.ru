<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/helpers.php';
require_once dirname(__DIR__) . '/app/db.php';

/**
 * @return 'GET'|'POST'|'PUT'|'DELETE'|'PATCH'|'HEAD'|'OPTIONS'
 */
function api_method(): string
{
    $m = (string) ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
    return strtoupper($m) ?: 'GET';
}

/**
 * @return array<string, mixed>
 */
function api_json_in(): array
{
    if (!empty($_POST) && (empty($_SERVER['CONTENT_TYPE']) || !str_contains((string) $_SERVER['CONTENT_TYPE'], 'json'))) {
        $out = $_POST;
        if (is_array($out)) {
            /** @var array<string, mixed> $a */
            $a = $out;
            return $a;
        }
    }
    $raw = (string) file_get_contents('php://input');
    if ($raw === '') {
        return [];
    }
    $j = json_decode($raw, true);
    if (!is_array($j)) {
        json_out(['ok' => false, 'error' => 'invalid_json'], 400);
    }
    return $j;
}
