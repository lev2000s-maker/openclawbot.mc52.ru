<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function mb_db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = mb_config();
    $path = (string) ($cfg['db_path'] ?? '');
    if ($path === '' || !is_file($path)) {
        throw new RuntimeException('База не найдена. Выполните: php db/seed.php');
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

/**
 * @return list<array<string, mixed>>
 */
function mb_all(string $sql, array $params = []): array
{
    $st = mb_db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * @return array<string, mixed>|null
 */
function mb_one(string $sql, array $params = []): ?array
{
    $st = mb_db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch();
    return $r === false ? null : $r;
}

/**
 * @return int|string|null last insert id for sequences
 */
function mb_exec(string $sql, array $params = []): int
{
    $st = mb_db()->prepare($sql);
    $st->execute($params);
    return (int) mb_db()->lastInsertId();
}
