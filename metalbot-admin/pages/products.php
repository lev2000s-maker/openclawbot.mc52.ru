<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/app/db.php';
require_once dirname(__DIR__) . '/app/csrf.php';

$suppliers = [];
try {
    $suppliers = mb_all('SELECT id, name FROM suppliers ORDER BY name COLLATE NOCASE');
} catch (Throwable $e) {
    log_line('products suppliers: ' . $e->getMessage());
}

if (is_post()) {
    if (!csrf_verify()) {
        set_flash('error', 'CSRF: обновите страницу и повторите.');
        redirect('/pages/products.php');
    }
    $act = (string) ($_POST['action'] ?? '');
    if ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            mb_db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            set_flash('ok', 'Позиция удалена.');
        }
        redirect('/pages/products.php');
    }
    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $priceRaw = trim((string) ($_POST['price'] ?? ''));
        $unit = trim((string) ($_POST['unit'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $price = $priceRaw === '' ? null : (float) str_replace(',', '.', $priceRaw);
        $sid = $supplierId > 0 ? $supplierId : null;
        if ($name === '') {
            set_flash('error', 'Наименование обязательно.');
        } else {
            if ($id > 0) {
                mb_db()->prepare('UPDATE products SET supplier_id = ?, sku = ?, name = ?, price = ?, unit = ?, notes = ?, updated_at = datetime(\'now\') WHERE id = ?')
                    ->execute([$sid, $sku, $name, $price, $unit, $notes, $id]);
                set_flash('ok', 'Сохранено.');
            } else {
                mb_exec('INSERT INTO products (supplier_id, sku, name, price, unit, notes) VALUES (?,?,?,?,?,?)', [$sid, $sku, $name, $price, $unit, $notes]);
                set_flash('ok', 'Создано.');
            }
        }
        redirect('/pages/products.php');
    }
}

$list = [];
try {
    $list = mb_all(
        'SELECT p.*, s.name AS supplier_name FROM products p
         LEFT JOIN suppliers s ON s.id = p.supplier_id
         ORDER BY p.name COLLATE NOCASE'
    );
} catch (Throwable $e) {
    log_line('products list: ' . $e->getMessage());
    set_flash('error', 'Ошибка чтения базы.');
}

$editId = get_int('id', 0);
$new = isset($_GET['new']) ? 1 : 0;
$edit = null;
if ($editId > 0) {
    $edit = mb_one('SELECT * FROM products WHERE id = ?', [$editId]);
    if ($edit === null) {
        set_flash('error', 'Запись не найдена.');
        $editId = 0;
    }
}
if ($new) {
    $editId = 0;
    $edit = null;
}

page_start(['title' => 'Номенклатура', 'nav' => 'products']);
render_flash();
$b = h(base_path());
?>
<div class="toolbar">
  <a class="btn btn--primary" href="<?= $b ?>/pages/products.php?new=1">Новая позиция</a>
  <a class="btn" href="<?= $b ?>/pages/suppliers.php">Поставщики</a>
</div>

<?php if ($new || $editId > 0): ?>
<form class="form panel" method="post" action="<?= $b ?>/pages/products.php">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
  <h2 class="form__h"><?= $new ? 'Создать' : 'Редактировать' ?></h2>
  <label class="form__row">
    <span>Поставщик</span>
    <select name="supplier_id">
      <option value="0">—</option>
      <?php foreach ($suppliers as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (isset($edit['supplier_id']) && (int) $edit['supplier_id'] === (int) $s['id']) ? 'selected' : '' ?>>
          <?= h($s['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="form__row"><span>Артикул / код</span><input type="text" name="sku" value="<?= h($edit['sku'] ?? '') ?>"></label>
  <label class="form__row"><span>Наименование *</span><input type="text" name="name" required value="<?= h($edit['name'] ?? '') ?>"></label>
  <label class="form__row"><span>Цена</span><input type="text" name="price" inputmode="decimal" placeholder="0.00" value="<?= $edit && isset($edit['price']) && $edit['price'] !== null && $edit['price'] !== '' ? h((string) $edit['price']) : '' ?>"></label>
  <label class="form__row"><span>Ед. изм.</span><input type="text" name="unit" value="<?= h($edit['unit'] ?? '') ?>"></label>
  <label class="form__row form__row--area"><span>Заметки</span><textarea name="notes" rows="2"><?= h($edit['notes'] ?? '') ?></textarea></label>
  <div class="form__actions">
    <button type="submit" class="btn btn--primary">Сохранить</button>
    <a class="btn" href="<?= $b ?>/pages/products.php">Отмена</a>
  </div>
</form>
<?php endif; ?>

<div class="tablewrap">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Код</th>
        <th>Наименование</th>
        <th>Поставщик</th>
        <th>Цена</th>
        <th class="th-actions"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><?= h(nvl($r['sku'] ?? null)) ?></td>
        <td><a href="<?= $b ?>/pages/products.php?id=<?= (int) $r['id'] ?>"><?= h($r['name'] ?? '') ?></a></td>
        <td><?= h(nvl($r['supplier_name'] ?? null)) ?></td>
        <td><?= $r['price'] === null || $r['price'] === '' ? '—' : h((string) $r['price']) ?></td>
        <td>
          <a class="btn btn--sm" href="<?= $b ?>/pages/products.php?id=<?= (int) $r['id'] ?>">Изм.</a>
          <form method="post" class="form-inline" action="<?= $b ?>/pages/products.php" onsubmit="return confirm('Удалить?');">
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
  <?php if (count($list) === 0): ?><p class="empty">Пока нет позиций.</p><?php endif; ?>
</div>
<?php page_end(); ?>
