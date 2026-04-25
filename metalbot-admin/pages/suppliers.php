<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

if (is_post()) {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF: обновите страницу и повторите.');
        redirect('/pages/suppliers.php');
    }
    $act = (string) ($_POST['action'] ?? '');
    if ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                mb_db()->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
                set_flash('ok', 'Поставщик удалён.');
            } catch (Throwable $e) {
                log_line('suppliers delete: ' . $e->getMessage());
                set_flash('error', 'Нельзя удалить: проверьте связанные записи.');
            }
        }
        redirect('/pages/suppliers.php');
    }
    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $contact = trim((string) ($_POST['contact'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        if ($name === '') {
            set_flash('error', 'Название обязательно.');
        } else {
            if ($id > 0) {
                mb_db()->prepare('UPDATE suppliers SET name = ?, contact = ?, notes = ?, updated_at = datetime(\'now\') WHERE id = ?')
                    ->execute([$name, $contact, $notes, $id]);
                set_flash('ok', 'Сохранено.');
            } else {
                mb_exec('INSERT INTO suppliers (name, contact, notes) VALUES (?,?,?)', [$name, $contact, $notes]);
                set_flash('ok', 'Создано.');
            }
        }
        redirect('/pages/suppliers.php');
    }
}

$list = [];
try {
    $list = mb_all('SELECT id, name, contact, updated_at FROM suppliers ORDER BY name COLLATE NOCASE');
} catch (Throwable $e) {
    log_line('suppliers list: ' . $e->getMessage());
    set_flash('error', 'Ошибка чтения базы. Запустите: php db/seed.php');
}

$editId = get_int('id', 0);
$new = isset($_GET['new']) ? 1 : 0;
$edit = null;
if ($editId > 0) {
    $edit = mb_one('SELECT * FROM suppliers WHERE id = ?', [$editId]);
    if ($edit === null) {
        set_flash('error', 'Запись не найдена.');
        $editId = 0;
    }
}
if ($new) {
    $editId = 0;
    $edit = null;
}

page_start(['title' => 'Поставщики', 'nav' => 'suppliers']);
render_flash();
$b = h(base_path());
?>
<div class="toolbar">
  <a class="btn btn--primary" href="<?= $b ?>/pages/suppliers.php?new=1">Новый поставщик</a>
  <a class="btn" href="<?= $b ?>/dashboard.php">К обзору</a>
</div>

<?php if ($new || $editId > 0): ?>
<form class="form panel" method="post" action="<?= $b ?>/pages/suppliers.php">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
  <h2 class="form__h"><?= $new ? 'Создать' : 'Редактировать' ?></h2>
  <label class="form__row"><span>Название *</span><input type="text" name="name" required value="<?= h($edit['name'] ?? '') ?>"></label>
  <label class="form__row"><span>Контакты</span><input type="text" name="contact" value="<?= h($edit['contact'] ?? '') ?>"></label>
  <label class="form__row form__row--area"><span>Заметки</span><textarea name="notes" rows="3"><?= h($edit['notes'] ?? '') ?></textarea></label>
  <div class="form__actions">
    <button type="submit" class="btn btn--primary">Сохранить</button>
    <a class="btn" href="<?= $b ?>/pages/suppliers.php">Отмена</a>
  </div>
</form>
<?php endif; ?>

<div class="tablewrap">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Контакты</th>
        <th>Обновлён</th>
        <th class="th-actions"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><a href="<?= $b ?>/pages/suppliers.php?id=<?= (int) $r['id'] ?>"><?= h($r['name'] ?? '') ?></a></td>
        <td><?= h(nvl($r['contact'] ?? null)) ?></td>
        <td class="text-muted"><?= h(nvl($r['updated_at'] ?? null)) ?></td>
        <td>
          <a class="btn btn--sm" href="<?= $b ?>/pages/suppliers.php?id=<?= (int) $r['id'] ?>">Изм.</a>
          <form method="post" action="<?= $b ?>/pages/suppliers.php" class="form-inline" onsubmit="return confirm('Удалить?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn--sm btn--danger">Удал.</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (count($list) === 0): ?>
  <p class="empty">Пока нет записей.</p>
  <?php endif; ?>
</div>
<?php page_end(); ?>
