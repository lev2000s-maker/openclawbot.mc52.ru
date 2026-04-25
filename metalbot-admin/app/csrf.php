<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function csrf_field(): string
{
    $t = csrf_token();
    return '<input type="hidden" name="_csrf" value="' . h($t) . '">';
}

function csrf_verify(): bool
{
    if (!is_post()) {
        return true;
    }
    $p = (string) ($_POST['_csrf'] ?? '');
    $s = (string) ($_SESSION['csrf'] ?? '');
    if ($p === '' || $s === '' || !hash_equals($s, $p)) {
        return false;
    }
    return true;
}
