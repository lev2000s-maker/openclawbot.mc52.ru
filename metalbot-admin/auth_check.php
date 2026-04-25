<?php
declare(strict_types=1);

require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/csrf.php';

if (empty($_SESSION['user_id'])) {
    set_flash('error', 'Требуется вход.');
    redirect('/login.php');
}
