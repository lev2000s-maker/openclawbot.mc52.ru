<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

if (is_post()) {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF.');
        redirect('/pages/tasks.php');
    }
    $act = (string) ($_POST['action'] ?? '');
    if ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            mb_db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
            set_flash('ok', 'Удалено.');
        }
        redirect('/pages/tasks.php');
    }
    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'open'));
        $due = trim((string) ($_POST['due_at'] ?? ''));
        $dueAt = $due === '' ? null : $due;
        if ($title === '') {
            set_flash('error', 'Заголовок обязателен.');
        } else {
            if ($id > 0) {
                mb_db()->prepare('UPDATE tasks SET title=?, description=?, status=?, due_at=?, updated_at=datetime(\'now\') WHERE id=?')
                    ->execute([$title, $description, $status, $dueAt, $id]);
            } else {
                mb_exec('INSERT INTO tasks (title, description, status, due_at) VALUES (?,?,?,?)', [$title, $description, $status, $dueAt]);
            }
            set_flash('ok', 'Сохранено.');
        }
        redirect('/pages/tasks.php');
    }
}

$list = mb_all('SELECT * FROM tasks ORDER BY id DESC');
$editId = get_int('id', 0);
$new = isset($_GET['new']) ? 1 : 0;
$edit = $editId > 0 ? mb_one('SELECT * FROM tasks WHERE id = ?', [$editId]) : null;
if ($editId > 0 && $edit === null) {
    $editId = 0;
}
if ($new) {
    $edit = null;
    $editId = 0;
}

page_start(['title' => 'Задачи', 'nav' => 'tasks']);
render_flash();
$b = h(base_path());
?>
<div class="toolbar"><a class="btn btn--primary" href="<?= $b ?>/pages/tasks.php?new=1">Новая задача</a></div>
<?php if ($new || $editId > 0): ?>
<form class="form panel" method="post" action="<?= $b ?>/pages/tasks.php">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
  <h2 class="form__h"><?= $new ? 'Создать' : 'Редактировать' ?></h2>
  <label class="form__row"><span>Заголовок *</span><input name="title" required value="<?= h($edit['title'] ?? '') ?>"></label>
  <label class="form__row form__row--area"><span>Описание</span><textarea name="description" rows="2"><?= h($edit['description'] ?? '') ?></textarea></label>
  <label class="form__row"><span>Статус</span><input name="status" value="<?= h($edit['status'] ?? 'open') ?>"></label>
  <label class="form__row"><span>Срок (YYYY-MM-DD HH:MM или пусто)</span><input name="due_at" placeholder="2026-04-25 12:00" value="<?= h($edit['due_at'] ?? '') ?>"></label>
  <div class="form__actions">
    <button class="btn btn--primary" type="submit">Сохранить</button>
    <a class="btn" href="<?= $b ?>/pages/tasks.php">Отмена</a>
  </div>
</form>
<?php endif; ?>
<div class="tablewrap">
  <table class="table">
    <thead>
      <tr><th>ID</th><th>Заголовок</th><th>Статус</th><th>Срок</th><th class="th-actions"></th></tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><a href="<?= $b ?>/pages/tasks.php?id=<?= (int) $r['id'] ?>"><?= h($r['title'] ?? '') ?></a></td>
        <td><?= h($r['status'] ?? '') ?></td>
        <td class="text-muted"><?= h(nvl($r['due_at'] ?? null)) ?></td>
        <td>
          <a class="btn btn--sm" href="<?= $b ?>/pages/tasks.php?id=<?= (int) $r['id'] ?>">Изм.</a>
          <form class="form-inline" method="post" onsubmit="return confirm('Удалить?');">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn--sm btn--danger" type="submit">Удал.</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (count($list) === 0): ?><p class="empty">Нет задач.</p><?php endif; ?>
</div>
<?php page_end(); ?>
