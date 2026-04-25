<?php
declare(strict_types=1);

function layout_nav_items(): array
{
    $b = base_path();
    return [
        ['href' => $b . '/dashboard.php', 'id' => 'dashboard', 'label' => 'Обзор'],
        ['href' => $b . '/pages/suppliers.php', 'id' => 'suppliers', 'label' => 'Поставщики'],
        ['href' => $b . '/pages/products.php', 'id' => 'products', 'label' => 'Номенклатура'],
        ['href' => $b . '/pages/price_uploads.php', 'id' => 'price_uploads', 'label' => 'Прайсы'],
        ['href' => $b . '/pages/leads.php', 'id' => 'leads', 'label' => 'Лиды'],
        ['href' => $b . '/pages/tasks.php', 'id' => 'tasks', 'label' => 'Задачи'],
        ['href' => $b . '/pages/scenarios.php', 'id' => 'scenarios', 'label' => 'Сценарии'],
        ['href' => $b . '/pages/prompts.php', 'id' => 'prompts', 'label' => 'Промты'],
        ['href' => $b . '/pages/settings.php', 'id' => 'settings', 'label' => 'Настройки'],
    ];
}

function layout_header(string $title, string $activeNav = ''): void
{
    $app = h((string) (mb_config()['app_name'] ?? 'Admin'));
    $t = h($title);
    $css = h(base_path() . '/assets/css/admin.css');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $t . ' — ' . $app . '</title>';
    echo '<link rel="stylesheet" href="' . $css . '">';
    echo '</head><body class="admin"><div class="layout"><aside class="sidebar"><div class="brand">' . $app . '</div><nav class="nav">';
    foreach (layout_nav_items() as $item) {
        $cls = $item['id'] === $activeNav ? 'nav__a nav__a--active' : 'nav__a';
        echo '<a class="' . h($cls) . '" href="' . h($item['href']) . '">' . h($item['label']) . '</a>';
    }
    echo '</nav><div class="sidebar__foot"><a class="nav__a" href="' . h(base_path() . '/logout.php') . '">Выход</a></div></aside>';
    echo '<main class="main"><header class="main__head"><h1 class="main__title">' . $t . '</h1></header><div class="main__body">';
}

function layout_footer(): void
{
    $js = h(base_path() . '/assets/js/admin.js');
    echo '</div></main></div>';
    echo '<script src="' . $js . '" defer></script></body></html>';
}
