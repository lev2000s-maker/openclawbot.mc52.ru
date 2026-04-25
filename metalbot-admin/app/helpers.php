<?php
declare(strict_types=1);

if (!isset($GLOBALS['metalbot_config']) || !is_array($GLOBALS['metalbot_config'])) {
    $path = dirname(__DIR__) . '/config.php';
    if (!is_readable($path)) {
        throw new RuntimeException('config.php not found. Copy from config.local.php.example');
    }
    $loaded = require $path;
    if (!is_array($loaded) && isset($GLOBALS['metalbot_config']) && is_array($GLOBALS['metalbot_config'])) {
        $loaded = $GLOBALS['metalbot_config'];
    }
    if (!is_array($loaded)) {
        throw new RuntimeException('config.php must return a configuration array');
    }
    $GLOBALS['metalbot_config'] = $loaded;
}

function mb_config(): array
{
    $c = $GLOBALS['metalbot_config'] ?? null;
    if (!is_array($c)) {
        throw new RuntimeException('Configuration not loaded');
    }
    return $c;
}

function h(?string $s): string
{
    if ($s === null) {
        return '';
    }
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): never
{
    if ($to !== '' && preg_match('#^https?://#i', $to)) {
        header('Location: ' . $to, true, 302);
        exit;
    }
    if ($to === '' || $to[0] !== '/') {
        $to = '/' . ltrim($to, '/');
    }
    $base = rtrim((string) (mb_config()['base_path'] ?? ''), '/');
    header('Location: ' . $base . $to, true, 302);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function is_get(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
}

function post(string $key, string $default = ''): string
{
    if (!isset($_POST[$key])) {
        return $default;
    }
    return is_string($_POST[$key]) ? $_POST[$key] : $default;
}

function get_int(string $key, int $default = 0): int
{
    if (!isset($_GET[$key]) || !is_scalar($_GET[$key])) {
        return $default;
    }
    return (int) $_GET[$key];
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_login_json(): void
{
    if (empty($_SESSION['user_id'])) {
        json_out(['ok' => false, 'error' => 'unauthorized'], 401);
    }
}

function require_api_or_session_json(): void
{
    $cfg = mb_config();
    $key = $cfg['api_key'] ?? '';
    $h = (string) ($_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($h !== '' && $key !== '' && (hash_equals($key, $h) || (str_starts_with($h, 'Bearer ') && hash_equals($key, substr($h, 7))))) {
        return;
    }
    if (!empty($_SESSION['user_id'])) {
        return;
    }
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
}

function log_line(string $message): void
{
    $cfg = mb_config();
    $f = (string) ($cfg['log_file'] ?? '');
    if ($f === '' || !is_dir(dirname($f))) {
        return;
    }
    $line = date('Y-m-d H:i:s') . ' ' . $message . "\n";
    @file_put_contents($f, $line, FILE_APPEND | LOCK_EX);
}

function flash(string $key): ?string
{
    if (empty($_SESSION['flash'][$key])) {
        return null;
    }
    $m = (string) $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $m;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function render_flash(): void
{
    $o = flash('ok');
    $e = flash('error');
    if ($o !== null) {
        echo '<p class="msg msg--ok">' . h($o) . '</p>';
    }
    if ($e !== null) {
        echo '<p class="msg msg--err">' . h($e) . '</p>';
    }
}

/**
 * @param array{title?:string, nav?:string} $opts
 */
function page_start(array $opts = []): void
{
    $title = (string) ($opts['title'] ?? (mb_config()['app_name'] ?? 'Admin'));
    $active = (string) ($opts['nav'] ?? '');
    require_once dirname(__DIR__) . '/app/layout.php';
    layout_header($title, $active);
}

function page_end(): void
{
    layout_footer();
}

function base_path(): string
{
    return rtrim((string) (mb_config()['base_path'] ?? ''), '/');
}

/**
 * @param int|float|string|null $v
 */
function nvl($v, string $empty = '—'): string
{
    if ($v === null || $v === '') {
        return $empty;
    }
    return (string) $v;
}
