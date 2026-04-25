<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

if (is_post()) {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF.');
        redirect('/pages/scenarios.php');
    }
    $act = (string) ($_POST['action'] ?? '');
    if ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            mb_db()->prepare('DELETE FROM scenarios WHERE id = ?')->execute([$id]);
            set_flash('ok', 'Удалено.');
        }
        redirect('/pages/scenarios.php');
    }
    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '') {
            set_flash('error', 'Название обязательно.');
        } else {
            if ($id > 0) {
                mb_db()->prepare('UPDATE scenarios SET name=?, description=?, is_active=?, updated_at=datetime(\'now\') WHERE id=?')
                    ->execute([$name, $description, $active, $id]);
            } else {
                mb_exec('INSERT INTO scenarios (name, description, is_active) VALUES (?,?,?)', [$name, $description, $active]);
            }
            set_flash('ok', 'Сохранено.');
        }
        redirect('/pages/scenarios.php');
    }
}

$list = mb_all('SELECT * FROM scenarios ORDER BY name COLLATE NOCASE');
$editId = get_int('id', 0);
$new = isset($_GET['new']) ? 1 : 0;
$edit = $editId > 0 ? mb_one('SELECT * FROM scenarios WHERE id = ?', [$editId]) : null;
if ($editId > 0 && $edit === null) {
    $editId = 0;
}
if ($new) {
    $edit = null;
    $editId = 0;
}

page_start(['title' => 'Сценарии', 'nav' => 'scenarios']);
render_flash();
$b = h(base_path());
?>
<div class="toolbar"><a class="btn btn--primary" href="<?= $b ?>/pages/scenarios.php?new=1">Новый сценарий</a></div>
<?php if ($new || $editId > 0): ?>
<form class="form panel" method="post" action="<?= $b ?>/pages/scenarios.php">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
  <h2 class="form__h"><?= $new ? 'Создать' : 'Редактировать' ?></h2>
  <label class="form__row"><span>Название *</span><input name="name" required value="<?= h($edit['name'] ?? '') ?>"></label>
  <label class="form__row form__row--area"><span>Описание</span><textarea name="description" rows="3"><?= h($edit['description'] ?? '') ?></textarea></label>
  <label class="form__row form__row--check"><input type="checkbox" name="is_active" value="1" <?= (isset($edit['is_active']) && (int) $edit['is_active'] === 1) || $new ? 'checked' : '' ?>> <span>Активен</span></label>
  <div class="form__actions">
    <button class="btn btn--primary" type="submit">Сохранить</button>
    <a class="btn" href="<?= $b ?>/pages/scenarios.php">Отмена</a>
  </div>
</form>
<?php endif; ?>
<div class="tablewrap">
  <table class="table">
    <thead>
      <tr><th>ID</th><th>Название</th><th>Активен</th><th class="th-actions"></th></tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><a href="<?= $b ?>/pages/scenarios.php?id=<?= (int) $r['id'] ?>"><?= h($r['name'] ?? '') ?></a></td>
        <td><?= ((int) ($r['is_active'] ?? 0) === 1) ? 'да' : 'нет' ?></td>
        <td>
          <a class="btn btn--sm" href="<?= $b ?>/pages/scenarios.php?id=<?= (int) $r['id'] ?>">Изм.</a>
          <form class="form-inline" method="post" onsubmit="return confirm('Удалить?');">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn--sm btn--danger" type="submit">Удал.</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (count($list) === 0): ?><p class="empty">Сценариев нет.</p><?php endif; ?>
</div>
<?php page_end(); ?>
