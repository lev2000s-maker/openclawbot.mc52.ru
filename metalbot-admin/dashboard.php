<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/app/db.php';

$rows = [
    'suppliers'  => 0,
    'products'   => 0,
    'leads'      => 0,
    'tasks'      => 0,
    'price_loads' => 0,
];
try {
    $r = mb_one('SELECT (SELECT COUNT(*) FROM suppliers) AS s, (SELECT COUNT(*) FROM products) AS p, (SELECT COUNT(*) FROM leads) AS l, (SELECT COUNT(*) FROM tasks) AS t, (SELECT COUNT(*) FROM price_uploads) AS u');
    if ($r) {
        $rows = [
            'suppliers'   => (int) $r['s'],
            'products'    => (int) $r['p'],
            'leads'       => (int) $r['l'],
            'tasks'       => (int) $r['t'],
            'price_loads' => (int) $r['u'],
        ];
    }
} catch (Throwable $e) {
    log_line('dashboard stats: ' . $e->getMessage());
}

page_start(['title' => 'Обзор', 'nav' => 'dashboard']);
render_flash();
$user = h((string) ($_SESSION['login'] ?? ''));
$apiHealth = h(base_path() . '/api/health.php');
?>
<div class="grid">
  <div class="card"><h2 class="card__title">Поставщики</h2><p class="card__val"><?= (int) $rows['suppliers'] ?></p></div>
  <div class="card"><h2 class="card__title">Номенклатура</h2><p class="card__val"><?= (int) $rows['products'] ?></p></div>
  <div class="card"><h2 class="card__title">Лиды</h2><p class="card__val"><?= (int) $rows['leads'] ?></p></div>
  <div class="card"><h2 class="card__title">Задачи</h2><p class="card__val"><?= (int) $rows['tasks'] ?></p></div>
  <div class="card card--wide"><h2 class="card__title">Загружено прайсов</h2><p class="card__val"><?= (int) $rows['price_loads'] ?></p></div>
</div>
<div class="panel">
  <p class="text-muted">Сеанс: <strong><?= $user ?></strong></p>
  <p>JSON API: <a href="<?= $apiHealth ?>" target="_blank" rel="noopener">/api/health.php</a> (проверка без ключа) и <code>GET /api/*.php</code> с заголовком <code>X-Api-Key</code> или <code>Authorization: Bearer ...</code>.</p>
</div>
<?php
page_end();
