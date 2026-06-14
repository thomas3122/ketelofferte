<?php
declare(strict_types=1);

// Router voor de PHP ingebouwde server (php -S). Bootst de rewrite-regels uit
// public/.htaccess na, zodat lokaal testen overeenkomt met de live Apache/LiteSpeed.
// ALLEEN voor lokaal gebruik.

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$docroot = __DIR__;
$path = $docroot . $uri;

// Bestaand bestand of asset: laat de ingebouwde server het direct serveren.
if ($uri !== '/' && is_file($path)) {
    return false;
}

// Vaste rewrites (zie public/.htaccess)
$map = [
    '#^/sitemap\.xml$#'            => '/sitemap.php',
    '#^/privacyverklaring/?$#'     => '/privacyverklaring.php',
    '#^/cookieverklaring/?$#'      => '/cookieverklaring.php',
    '#^/voorwaarden/?$#'           => '/voorwaarden.php',
];

foreach ($map as $pattern => $target) {
    if (preg_match($pattern, $uri)) {
        require $docroot . $target;
        return true;
    }
}

// Kennisbank: /kennisbank/<slug>/ -> kennisbank/article.php?slug=<slug>
if (preg_match('#^/kennisbank/([a-z0-9-]+)/?$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require $docroot . '/kennisbank/article.php';
    return true;
}

// Directory met index.php (admin/, company/, kennisbank/, regios/)
if (is_dir($path)) {
    $index = rtrim($path, '/') . '/index.php';
    if (is_file($index)) {
        require $index;
        return true;
    }
}

// Resterende enkelvoudige slugs -> landingspagina via index.php
if (preg_match('#^/[a-z0-9-]+/?$#', $uri)) {
    require $docroot . '/index.php';
    return true;
}

// Root en fallback
require $docroot . '/index.php';
return true;
