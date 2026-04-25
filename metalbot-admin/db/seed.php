<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$cfg = require $root . '/config.php';
$dbPath = (string) ($cfg['db_path'] ?? $root . '/db/database.sqlite');
$schema = (string) ($cfg['db_schema'] ?? $root . '/db/schema.sql');

$dir = dirname($dbPath);
if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create directory: $dir\n");
    exit(1);
}

if (is_file($dbPath) && in_array('--reset', $argv, true)) {
    unlink($dbPath);
    echo "Removed existing database.\n";
}

$newFile = !is_file($dbPath);

if (!is_file($schema)) {
    fwrite(STDERR, "Schema not found: $schema\n");
    exit(1);
}

$sql = file_get_contents($schema);
if ($sql === false) {
    fwrite(STDERR, "Cannot read schema.\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec($sql);

$login = 'admin';
$pass = 'admin1234';

$st = $pdo->prepare('SELECT id FROM users WHERE login = ?');
$st->execute([$login]);
if ($st->fetch() === false) {
    $h = password_hash($pass, PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (login, password_hash) VALUES (?, ?)')->execute([$login, $h]);
    echo "User created: login=admin  password=admin1234  (смените после первого входа)\n";
} else {
    echo "User already exists, skip user creation. Use --reset to rebuild DB (destructive).\n";
}

$defaults = [
    'site_name' => 'Metalbot',
    'bot_active'  => '1',
];
foreach ($defaults as $k => $v) {
    $c = (int) $pdo->query("SELECT COUNT(*) FROM settings WHERE s_key = " . $pdo->quote($k))->fetchColumn();
    if ($c === 0) {
        $pdo->prepare('INSERT INTO settings (s_key, s_value) VALUES (?, ?)')->execute([$k, $v]);
    }
}

$mark = 'initial_schema';
$c = (int) $pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE name = " . $pdo->quote($mark))->fetchColumn();
if ($c === 0) {
    $pdo->prepare('INSERT INTO schema_migrations (name) VALUES (?)')->execute([$mark]);
}

echo "Database ready: $dbPath\n";
if ($newFile) {
    echo "Created new SQLite file.\n";
}

exit(0);
