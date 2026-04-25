<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

if (is_post() && (string) ($_POST['action'] ?? '') === 'save') {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF.');
        redirect('/pages/settings.php');
    }
    $st = mb_db()->prepare('INSERT INTO settings (s_key, s_value, updated_at) VALUES (?, ?, datetime(\'now\'))
        ON CONFLICT(s_key) DO UPDATE SET s_value=excluded.s_value, updated_at=datetime(\'now\')');
    foreach ((array) ($_POST['s'] ?? []) as $k => $v) {
        if (!is_string($k) || $k === '') {
            continue;
        }
        $st->execute([$k, (string) $v]);
    }
    $nks = (array) ($_POST['nkey'] ?? []);
    $nvs = (array) ($_POST['nval'] ?? []);
    $len = min(count($nks), count($nvs));
    for ($i = 0; $i < $len; $i++) {
        $k = trim((string) ($nks[$i] ?? ''));
        if ($k === '') {
            continue;
        }
        $v = (string) ($nvs[$i] ?? '');
        $st->execute([$k, $v]);
    }
    set_flash('ok', 'Настройки сохранены.');
    redirect('/pages/settings.php');
}

$rows = mb_all('SELECT s_key, s_value, updated_at FROM settings ORDER BY s_key');

page_start(['title' => 'Настройки', 'nav' => 'settings']);
render_flash();
$b = h(base_path());
?>
<form class="form panel" method="post" action="<?= $b ?>/pages/settings.php">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <h2 class="form__h">Системные настройки</h2>
  <p class="text-muted">Ключи — латиница, подчёркивания. Значения — строка (для «да/нет» часто 1/0).</p>
  <?php foreach ($rows as $r): $k = (string) ($r['s_key'] ?? ''); ?>
  <div class="form__row form__row--2">
    <div class="k"><code><?= h($k) ?></code> <span class="text-muted">(<?= h(nvl($r['updated_at'] ?? null, '')) ?>)</span></div>
    <input type="text" name="s[<?= h($k) ?>]" value="<?= h($r['s_value'] ?? '') ?>">
  </div>
  <?php endforeach; ?>
  <h3 class="form__h">Новая пара</h3>
  <div id="new-settings">
    <div class="form__row form__row--2 newpair">
      <input type="text" name="nkey[]" placeholder="новый_ключ" autocomplete="off">
      <input type="text" name="nval[]" placeholder="значение" autocomplete="off">
    </div>
  </div>
  <p><button class="btn" type="button" id="addPair">+ ещё поле</button></p>
  <div class="form__actions">
    <button class="btn btn--primary" type="submit">Сохранить</button>
  </div>
</form>
<template id="newpairTpl">
  <div class="form__row form__row--2 newpair">
    <input type="text" name="nkey[]" placeholder="новый_ключ" autocomplete="off">
    <input type="text" name="nval[]" placeholder="значение" autocomplete="off">
  </div>
</template>
<?php page_end(); ?>
