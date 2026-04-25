<?php
declare(strict_types=1);

require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/csrf.php';

if (!empty($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$bye = isset($_GET['bye']) ? 'Вы вышли из системы.' : null;

$error = null;
if (is_post()) {
    if (!csrf_verify()) {
        $error = 'Некорректный CSRF. Обновите страницу и попробуйте снова.';
    } else {
        $login = trim(post('login'));
        $pass = (string) ($_POST['password'] ?? '');
        if ($login === '' || $pass === '') {
            $error = 'Введите логин и пароль.';
        } else {
            try {
                $row = mb_one('SELECT id, password_hash FROM users WHERE login = ?', [$login]);
                if ($row && password_verify($pass, (string) $row['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $row['id'];
                    $_SESSION['login'] = $login;
                    set_flash('ok', 'Добро пожаловать.');
                    log_line("login user_id={$row['id']}");
                    redirect('/dashboard.php');
                }
                if ($error === null) {
                    $error = 'Неверный логин или пароль.';
                }
            } catch (Throwable $e) {
                log_line('login error: ' . $e->getMessage());
                $error = 'Системная ошибка. Проверьте базу (seed) и настройки.';
            }
        }
    }
}

$flashErr = flash('error');
$app = h((string) (mb_config()['app_name'] ?? 'Admin'));
$title = 'Вход';
$css = h(base_path() . '/assets/css/admin.css');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> — <?= $app ?></title>
  <link rel="stylesheet" href="<?= $css ?>">
</head>
<body class="admin admin--center">
  <div class="login">
    <h1 class="login__title"><?= $app ?></h1>
    <p class="login__sub">Панель администрирования</p>
    <?php if ($error !== null): ?><p class="msg msg--err"><?= h($error) ?></p><?php endif; ?>
    <?php if ($flashErr !== null): ?><p class="msg msg--err"><?= h($flashErr) ?></p><?php endif; ?>
    <?php if ($bye !== null && $error === null && $flashErr === null): ?><p class="msg msg--ok"><?= h($bye) ?></p><?php endif; ?>
    <form method="post" action="<?= h(base_path() . '/login.php') ?>" class="form form--tight" autocomplete="off">
      <?= csrf_field() ?>
      <label class="form__row"><span>Логин</span><input type="text" name="login" required value="<?= h(post('login')) ?>"></label>
      <label class="form__row"><span>Пароль</span><input type="password" name="password" required></label>
      <button type="submit" class="btn btn--primary">Войти</button>
    </form>
  </div>
</body>
</html>
