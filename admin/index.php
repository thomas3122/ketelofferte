<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (function_exists('is_admin_logged_in') && is_admin_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

header('Location: /admin/login.php');
exit;
