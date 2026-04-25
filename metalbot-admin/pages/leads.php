<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

if (is_post()) {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF: обновите страницу.');
        redirect('/pages/leads.php');
    }
    $act = (string) ($_POST['action'] ?? '');
    if ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            mb_db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
            set_flash('ok', 'Удалено.');
        }
        redirect('/pages/leads.php');
    }
    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $source = trim((string) ($_POST['source'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'new'));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        if ($name === '') {
            set_flash('error', 'Имя обязательно.');
        } else {
            if ($id > 0) {
                mb_db()->prepare('UPDATE leads SET name=?, phone=?, email=?, source=?, status=?, notes=?, updated_at=datetime(\'now\') WHERE id=?')
                    ->execute([$name, $phone, $email, $source, $status, $notes, $id]);
            } else {
                mb_exec('INSERT INTO leads (name, phone, email, source, status, notes) VALUES (?,?,?,?,?,?)', [$name, $phone, $email, $source, $status, $notes]);
            }
            set_flash('ok', 'Сохранено.');
        }
        redirect('/pages/leads.php');
    }
}

$list = [];
try {
    $list = mb_all('SELECT * FROM leads ORDER BY id DESC');
} catch (Throwable $e) {
    set_flash('error', 'Ошибка БД.');
}
$editId = get_int('id', 0);
$new = isset($_GET['new']) ? 1 : 0;
$edit = $editId > 0 ? mb_one('SELECT * FROM leads WHERE id = ?', [$editId]) : null;
if ($editId > 0 && $edit === null) {
    set_flash('error', 'Не найдено.');
    $editId = 0;
}
if ($new) {
    $edit = null;
    $editId = 0;
}

page_start(['title' => 'Лиды', 'nav' => 'leads']);
render_flash();
$b = h(base_path());
?>
<div class="toolbar">
  <a class="btn btn--primary" href="<?= $b ?>/pages/leads.php?new=1">Новый лид</a>
</div>
<?php if ($new || $editId > 0): ?>
<form class="form panel" method="post" action="<?= $b ?>/pages/leads.php">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
  <h2 class="form__h"><?= $new ? 'Создать' : 'Редактировать' ?></h2>
  <label class="form__row"><span>Имя *</span><input name="name" required value="<?= h($edit['name'] ?? '') ?>"></label>
  <label class="form__row"><span>Телефон</span><input name="phone" value="<?= h($edit['phone'] ?? '') ?>"></label>
  <label class="form__row"><span>Email</span><input name="email" value="<?= h($edit['email'] ?? '') ?>"></label>
  <label class="form__row"><span>Источник</span><input name="source" value="<?= h($edit['source'] ?? '') ?>"></label>
  <label class="form__row"><span>Статус</span><input name="status" value="<?= h($edit['status'] ?? 'new') ?>"></label>
  <label class="form__row form__row--area"><span>Заметки</span><textarea name="notes" rows="2"><?= h($edit['notes'] ?? '') ?></textarea></label>
  <div class="form__actions">
    <button class="btn btn--primary" type="submit">Сохранить</button>
    <a class="btn" href="<?= $b ?>/pages/leads.php">Отмена</a>
  </div>
</form>
<?php endif; ?>
<div class="tablewrap">
  <table class="table">
    <thead>
      <tr><th>ID</th><th>Имя</th><th>Контакты</th><th>Статус</th><th class="th-actions"></th></tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><a href="<?= $b ?>/pages/leads.php?id=<?= (int) $r['id'] ?>"><?= h($r['name'] ?? '') ?></a></td>
        <td><?= h(trim(($r['phone'] ?? '') . ' ' . ($r['email'] ?? ''))) ?></td>
        <td><?= h($r['status'] ?? '') ?></td>
        <td>
          <a class="btn btn--sm" href="<?= $b ?>/pages/leads.php?id=<?= (int) $r['id'] ?>">Изм.</a>
          <form class="form-inline" method="post" onsubmit="return confirm('Удалить?');">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn--sm btn--danger" type="submit">Удал.</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (count($list) === 0): ?><p class="empty">Лидов пока нет.</p><?php endif; ?>
</div>
<?php page_end(); ?>
