<?php
declare(strict_types=1);

$currentPath = $_SERVER['REQUEST_URI'] ?? '';

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

function admin_nav_active(string $path, string $currentPath): string
{
    if ($path === '/admin/companies' && str_starts_with($currentPath, '/admin/company')) {
        return 'active';
    }

    return str_starts_with($currentPath, $path) ? 'active' : '';
}

$adminName = function_exists('current_admin_name') ? current_admin_name() : 'admin';
?>

<header class="topbar">
    <div class="logo">
        <a href="/admin/dashboard.php"><?= e(APP_NAME) ?></a>
    </div>

    <nav class="topnav" aria-label="Admin navigatie">
        <a class="<?= admin_nav_active('/admin/dashboard', $currentPath) ?>" href="/admin/dashboard.php">Dashboard</a>
        <a class="<?= admin_nav_active('/admin/leads', $currentPath) ?>" href="/admin/leads.php">Leads</a>
        <a class="<?= admin_nav_active('/admin/companies', $currentPath) ?>" href="/admin/companies.php">Bedrijven</a>
        <a class="<?= admin_nav_active('/admin/seo', $currentPath) ?>" href="/admin/seo/landing-pages.php">SEO pagina’s</a>
        <a href="/regios/" target="_blank">Regio’s</a>
    </nav>

    <div class="topbar-right">
        <span>Ingelogd als <?= e($adminName) ?></span>
        <a class="logout" href="/admin/logout.php">Uitloggen</a>
    </div>
</header>
