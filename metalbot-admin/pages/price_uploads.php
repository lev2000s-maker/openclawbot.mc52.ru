<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

$cfg = mb_config();
$uploadBase = (string) ($cfg['upload_dir'] ?? (dirname(__DIR__) . '/uploads/prices'));
if (!is_dir($uploadBase) && !@mkdir($uploadBase, 0755, true) && !is_dir($uploadBase)) {
    set_flash('error', 'Не удалось создать папку uploads/prices. Проверьте права на хосте.');
}

if (is_post() && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF: обновите страницу.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $row = mb_one('SELECT stored_path FROM price_uploads WHERE id = ?', [$id]);
            if ($row) {
                $p = (string) $row['stored_path'];
                if ($p !== '' && is_file($p)) {
                    @unlink($p);
                }
                mb_db()->prepare('DELETE FROM price_uploads WHERE id = ?')->execute([$id]);
                set_flash('ok', 'Запись и файл удалены.');
            }
        }
    }
    redirect('/pages/price_uploads.php');
}

if (is_post() && !empty($_FILES['pricefile']) && (int) ($_FILES['pricefile']['error'] ?? 0) === UPLOAD_ERR_OK) {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF: обновите страницу.');
    } else {
        $f = $_FILES['pricefile'];
        $oname = (string) ($f['name'] ?? 'file');
        $size = (int) ($f['size'] ?? 0);
        $tmp = (string) ($f['tmp_name'] ?? '');
        $mime = (string) (mime_content_type($tmp) ?: 'application/octet-stream');
        $sub = date('Y-m');
        $dir = rtrim($uploadBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            set_flash('error', 'Нет папки для загрузки.');
        } else {
            $ext = pathinfo($oname, PATHINFO_EXTENSION);
            $safe = preg_replace('#[^a-zA-Z0-9._-]#', '_', pathinfo($oname, PATHINFO_FILENAME));
            $name = $safe . '_' . bin2hex(random_bytes(4)) . ($ext !== '' ? '.' . $ext : '');
            $dest = $dir . DIRECTORY_SEPARATOR . $name;
            if (!move_uploaded_file($tmp, $dest)) {
                set_flash('error', 'Не удалось сохранить файл (move_uploaded_file).');
            } else {
                $sid = (int) post('supplier_id', '0');
                $sIdVal = $sid > 0 ? $sid : null;
                mb_exec(
                    'INSERT INTO price_uploads (original_name, stored_path, file_size, mime_type, supplier_id) VALUES (?,?,?,?,?)',
                    [$oname, $dest, $size, $mime, $sIdVal]
                );
                set_flash('ok', 'Прайс загружен: ' . $oname);
            }
        }
    }
    redirect('/pages/price_uploads.php');
}

$suppliers = [];
try {
    $suppliers = mb_all('SELECT id, name FROM suppliers ORDER BY name COLLATE NOCASE');
} catch (Throwable $e) {
    log_line('price_uploads suppliers: ' . $e->getMessage());
}

$list = [];
try {
    $list = mb_all(
        'SELECT u.*, s.name AS supplier_name FROM price_uploads u
         LEFT JOIN suppliers s ON s.id = u.supplier_id
         ORDER BY u.id DESC'
    );
} catch (Throwable $e) {
    set_flash('error', 'Ошибка чтения: ' . $e->getMessage());
}

page_start(['title' => 'Загрузка прайсов', 'nav' => 'price_uploads']);
render_flash();
$b = h(base_path());
?>
<form class="form panel" method="post" action="<?= $b ?>/pages/price_uploads.php" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <h2 class="form__h">Новая загрузка</h2>
  <label class="form__row">
    <span>Поставщик (опц.)</span>
    <select name="supplier_id">
      <option value="0">—</option>
      <?php foreach ($suppliers as $s): ?>
        <option value="<?= (int) $s['id'] ?>"><?= h($s['name'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="form__row">
    <span>Файл</span>
    <input type="file" name="pricefile" required>
  </label>
  <div class="form__actions">
    <button type="submit" class="btn btn--primary">Загрузить</button>
  </div>
</form>

<div class="tablewrap">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Исходное имя</th>
        <th>Поставщик</th>
        <th>Размер</th>
        <th>Дата</th>
        <th class="th-actions"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><?= h($r['original_name'] ?? '') ?></td>
        <td><?= h(nvl($r['supplier_name'] ?? null)) ?></td>
        <td><?= (int) ($r['file_size'] ?? 0) ?></td>
        <td class="text-muted"><?= h(nvl($r['created_at'] ?? null)) ?></td>
        <td>
          <form method="post" class="form-inline" action="<?= $b ?>/pages/price_uploads.php" onsubmit="return confirm('Удалить файл?');">
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
  <?php if (count($list) === 0): ?><p class="empty">Файлы ещё не загружались.</p><?php endif; ?>
</div>
<?php page_end(); ?>
