<?php
declare(strict_types=1);

require_once __DIR__ . '/app/helpers.php';

if (empty($_SESSION['user_id'])) {
    redirect('/login.php');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        (string) session_name(),
        '',
        time() - 42000,
        $p['path'],
        (string) $p['domain'],
        (bool) $p['secure'],
        (bool) $p['httponly']
    );
}
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
redirect('/login.php?bye=1');
