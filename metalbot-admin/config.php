<?php
declare(strict_types=1);

const MB_CONFIG_FILE = __DIR__ . '/config.local.php';
const MB_CONFIG_EXAMPLE = __DIR__ . '/config.local.php.example';

$mbDefaults = [
    'app_name'     => 'Metalbot Admin',
    'app_secret'   => 'change-me-in-config-local',
    'db_path'      => __DIR__ . '/db/database.sqlite',
    'db_schema'    => __DIR__ . '/db/schema.sql',
    'api_key'      => 'change-me-in-config-local',
    'session_name' => 'mb_admin',
    'upload_dir'   => __DIR__ . '/uploads/prices',
    'log_file'     => __DIR__ . '/logs/app.log',
    'default_locale' => 'ru',
    'time_zone'    => 'Europe/Moscow',
    'base_path'    => '',
];

$mbLocal = [];
if (is_readable(MB_CONFIG_FILE)) {
    $loaded = require MB_CONFIG_FILE;
    if (is_array($loaded)) {
        $mbLocal = $loaded;
    }
}
$mbConfig = array_merge($mbDefaults, $mbLocal);
$mbConfig['base_path'] = (string) ($mbConfig['base_path'] ?? $mbDefaults['base_path']);

date_default_timezone_set($mbConfig['time_zone']);

if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name($mbConfig['session_name']);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

$GLOBALS['metalbot_config'] = $mbConfig;

return $mbConfig;
