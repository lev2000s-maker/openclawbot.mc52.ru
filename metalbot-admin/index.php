<?php
declare(strict_types=1);

require_once __DIR__ . '/app/helpers.php';

if (!empty($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}
redirect('/login.php');
