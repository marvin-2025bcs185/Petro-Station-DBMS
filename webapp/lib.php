<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(mixed $value): string
{
    return 'UGX ' . number_format((float) $value, 0);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function can_access(string $module): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    $role = $user['RoleName'];
    $matrix = [
        'Station Manager' => ['dashboard', 'sales', 'inventory', 'customers', 'procurement', 'employees', 'maintenance', 'reports', 'risks'],
        'Pump Attendant' => ['dashboard', 'sales', 'inventory'],
        'Cashier / Accounts Clerk' => ['dashboard', 'sales', 'customers', 'reports'],
        'Procurement Officer' => ['dashboard', 'inventory', 'procurement', 'reports'],
        'Owner / Director' => ['dashboard', 'reports', 'risks'],
    ];

    return in_array($module, $matrix[$role] ?? [], true);
}

function require_module(string $module): void
{
    require_login();
    if (!can_access($module)) {
        http_response_code(403);
        render_header('Access denied');
        echo '<section class="panel"><h1>Access denied</h1><p>Your role does not have access to this module.</p></section>';
        render_footer();
        exit;
    }
}

function nav_items(): array
{
    return [
        'dashboard' => 'Dashboard',
        'sales' => 'Sales',
        'inventory' => 'Inventory',
        'customers' => 'Customers',
        'procurement' => 'Procurement',
        'employees' => 'HR',
        'maintenance' => 'Maintenance',
        'reports' => 'Reports',
        'risks' => 'Risks',
    ];
}

function render_header(string $title): void
{
    $user = current_user();
    $page = $_GET['page'] ?? 'dashboard';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . ' | PetroStation DBMS</title>';
    echo '<link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<div class="app-shell">';
    echo '<aside class="sidebar"><div class="brand"><span>PS</span><div><strong>PetroStation DBMS</strong><small>Group 5 | Oil &amp; Gas</small></div></div>';
    if ($user) {
        echo '<nav>';
        foreach (nav_items() as $key => $label) {
            if (can_access($key)) {
                $active = $page === $key ? ' class="active"' : '';
                echo '<a' . $active . ' href="index.php?page=' . h($key) . '">' . h($label) . '</a>';
            }
        }
        echo '</nav>';
        echo '<div class="user-card"><strong>' . h($user['FullName']) . '</strong><small>' . h($user['RoleName']) . '</small><a href="index.php?page=logout">Log out</a></div>';
    }
    echo '</aside><main class="content">';
}

function render_footer(): void
{
    echo '</main></div></body></html>';
}

function count_table(string $table): int
{
    return (int) db()->query("SELECT COUNT(*) AS total FROM `$table`")->fetch()['total'];
}

function post_value(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function show_flash(): void
{
    if (!isset($_SESSION['flash'])) {
        return;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert ' . h($flash['type']) . '">' . h($flash['message']) . '</div>';
}

function fetch_options(string $sql): array
{
    return db()->query($sql)->fetchAll();
}
