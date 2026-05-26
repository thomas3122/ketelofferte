<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

load_env(dirname(__DIR__) . '/.env');

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443')
);

if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.googleadservices.com https://googleads.g.doubleclick.net; connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://www.googleadservices.com https://googleads.g.doubleclick.net https://stats.g.doubleclick.net; img-src 'self' data: https://www.google.nl https://www.google.com https://www.google-analytics.com https://www.googleadservices.com https://googleads.g.doubleclick.net https://stats.g.doubleclick.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; frame-src https://www.googletagmanager.com https://www.google.com https://googleads.g.doubleclick.net; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; upgrade-insecure-requests");

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (!headers_sent() && preg_match('~/(admin|actions|company)(/|$)|^/bedankt\.php$~', $requestPath)) {
    header('X-Robots-Tag: noindex, nofollow');
}
$needsSession = (
    isset($_COOKIE[session_name()])
    || preg_match('~/(admin|actions|company)(/|$)~', $requestPath)
);

if ($needsSession && session_status() !== PHP_SESSION_ACTIVE) {
    session_cache_limiter('');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/legal.php';
require_once __DIR__ . '/mailer.php';
