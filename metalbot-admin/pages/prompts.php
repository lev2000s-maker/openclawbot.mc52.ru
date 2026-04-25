<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

if (is_post()) {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF.');
        redirect('/pages/prompts.php');
    }
    $act = (string) ($_POST['action'] ?? '');
    if ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            mb_db()->prepare('DELETE FROM prompts WHERE id = ?')->execute([$id]);
            set_flash('ok', 'Удалено.');
        }
        redirect('/pages/prompts.php');
    }
    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $key = trim((string) ($_POST['prompt_key'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = (string) ($_POST['content'] ?? '');
        if ($key === '' || $title === '') {
            set_flash('error', 'Ключ и заголовок обязательны.');
        } else {
            try {
                if ($id > 0) {
                    mb_db()->prepare('UPDATE prompts SET title=?, content=?, updated_at=datetime(\'now\') WHERE id=?')
                        ->execute([$title, $content, $id]);
                } else {
                    mb_db()->prepare('INSERT INTO prompts (prompt_key, title, content) VALUES (?,?,?)')
                        ->execute([$key, $title, $content]);
                }
                set_flash('ok', 'Сохранено.');
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'UNIQUE')) {
                    set_flash('error', 'Такой ключ уже занят.');
                } else {
                    set_flash('error', 'Ошибка сохранения.');
                }
            }
        }
        redirect('/pages/prompts.php');
    }
}

$list = mb_all('SELECT * FROM prompts ORDER BY prompt_key COLLATE NOCASE');
$editId = get_int('id', 0);
$new = isset($_GET['new']) ? 1 : 0;
$edit = $editId > 0 ? mb_one('SELECT * FROM prompts WHERE id = ?', [$editId]) : null;
if ($editId > 0 && $edit === null) {
    $editId = 0;
}
if ($new) {
    $edit = null;
    $editId = 0;
}

page_start(['title' => 'Промты', 'nav' => 'prompts']);
render_flash();
$b = h(base_path());
?>
<div class="toolbar"><a class="btn btn--primary" href="<?= $b ?>/pages/prompts.php?new=1">Новый промт</a></div>
<?php if ($new || $editId > 0): ?>
<form class="form panel" method="post" action="<?= $b ?>/pages/prompts.php">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
  <h2 class="form__h"><?= $new ? 'Создать' : 'Редактировать' ?></h2>
  <label class="form__row"><span>Ключ (латиница) *</span>
    <input name="prompt_key" required value="<?= h($edit['prompt_key'] ?? '') ?>" <?= $editId > 0 ? 'readonly' : '' ?>></label>
  <label class="form__row"><span>Название *</span><input name="title" required value="<?= h($edit['title'] ?? '') ?>"></label>
  <label class="form__row form__row--area"><span>Текст</span><textarea name="content" rows="12" class="code"><?= h($edit['content'] ?? '') ?></textarea></label>
  <div class="form__actions">
    <button class="btn btn--primary" type="submit">Сохранить</button>
    <a class="btn" href="<?= $b ?>/pages/prompts.php">Отмена</a>
  </div>
</form>
<?php endif; ?>
<div class="tablewrap">
  <table class="table">
    <thead>
      <tr><th>ID</th><th>Ключ</th><th>Название</th><th>Обновлён</th><th class="th-actions"></th></tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><code><?= h($r['prompt_key'] ?? '') ?></code></td>
        <td><a href="<?= $b ?>/pages/prompts.php?id=<?= (int) $r['id'] ?>"><?= h($r['title'] ?? '') ?></a></td>
        <td class="text-muted"><?= h(nvl($r['updated_at'] ?? null)) ?></td>
        <td>
          <a class="btn btn--sm" href="<?= $b ?>/pages/prompts.php?id=<?= (int) $r['id'] ?>">Изм.</a>
          <form class="form-inline" method="post" onsubmit="return confirm('Удалить?');">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn--sm btn--danger" type="submit">Удал.</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (count($list) === 0): ?><p class="empty">Промтов нет. Добавьте, например, ключ <code>greeting</code> для приветствия бота.</p><?php endif; ?>
</div>
<?php page_end(); ?>
