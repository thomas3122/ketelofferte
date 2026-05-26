<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

require_valid_csrf_token('/admin/seo/landing-page-create.php', 'seo_admin_error');

function clean_string(?string $value): string
{
    return trim((string) $value);
}

function clean_nullable(?string $value): ?string
{
    $cleaned = trim((string) $value);
    return $cleaned === '' ? null : $cleaned;
}

$slug = strtolower(clean_string($_POST['slug'] ?? ''));
$city = clean_string($_POST['city'] ?? '');
$municipality = clean_string($_POST['municipality'] ?? '');
$areaType = clean_string($_POST['area_type'] ?? '');
$pageTemplate = clean_string($_POST['page_template'] ?? '');
$region = clean_string($_POST['region'] ?? '');

$pageTitle = clean_string($_POST['page_title'] ?? '');
$metaDescription = clean_string($_POST['meta_description'] ?? '');
$heroEyebrow = clean_string($_POST['hero_eyebrow'] ?? '');
$heroTitle = clean_string($_POST['hero_title'] ?? '');
$heroSubtitle = clean_string($_POST['hero_subtitle'] ?? '');

$introTitle = clean_string($_POST['intro_title'] ?? '');
$introText = clean_string($_POST['intro_text'] ?? '');
$regionText = clean_string($_POST['region_text'] ?? '');

$seoContentHtml = clean_nullable($_POST['seo_content_html'] ?? '');
$faqHtml = clean_nullable($_POST['faq_html'] ?? '');

$isActive = (int) ($_POST['is_active'] ?? 1);
$isActive = $isActive === 1 ? 1 : 0;

$allowedAreaTypes = ['city', 'district', 'neighborhood', 'village', 'region'];
$allowedTemplates = ['default', 'city'];

if (
    $slug === ''
    || $city === ''
    || $municipality === ''
    || $areaType === ''
    || $pageTemplate === ''
    || $region === ''
    || $pageTitle === ''
    || $metaDescription === ''
    || $heroEyebrow === ''
    || $heroTitle === ''
    || $heroSubtitle === ''
    || $introTitle === ''
    || $introText === ''
    || $regionText === ''
) {
    $_SESSION['seo_admin_error'] = 'Niet alle verplichte velden zijn ingevuld.';
    header('Location: /admin/seo/landing-page-create.php');
    exit;
}

if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    $_SESSION['seo_admin_error'] = 'Slug mag alleen kleine letters, cijfers en streepjes bevatten.';
    header('Location: /admin/seo/landing-page-create.php');
    exit;
}

if (!in_array($areaType, $allowedAreaTypes, true)) {
    $_SESSION['seo_admin_error'] = 'Ongeldig area type.';
    header('Location: /admin/seo/landing-page-create.php');
    exit;
}

if (!in_array($pageTemplate, $allowedTemplates, true)) {
    $_SESSION['seo_admin_error'] = 'Ongeldig page template.';
    header('Location: /admin/seo/landing-page-create.php');
    exit;
}

try {
    $checkStmt = $pdo->prepare("
        SELECT id
        FROM landing_pages
        WHERE slug = :slug
        LIMIT 1
    ");

    $checkStmt->execute([
        ':slug' => $slug,
    ]);

    if ($checkStmt->fetch()) {
        $_SESSION['seo_admin_error'] = 'Deze slug bestaat al.';
        header('Location: /admin/seo/landing-page-create.php');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO landing_pages (
            slug,
            city,
            municipality,
            area_type,
            page_template,
            region,
            page_title,
            meta_description,
            hero_eyebrow,
            hero_title,
            hero_subtitle,
            intro_title,
            intro_text,
            region_text,
            seo_content_html,
            faq_html,
            is_active
        ) VALUES (
            :slug,
            :city,
            :municipality,
            :area_type,
            :page_template,
            :region,
            :page_title,
            :meta_description,
            :hero_eyebrow,
            :hero_title,
            :hero_subtitle,
            :intro_title,
            :intro_text,
            :region_text,
            :seo_content_html,
            :faq_html,
            :is_active
        )
    ");

    $stmt->execute([
        ':slug' => $slug,
        ':city' => $city,
        ':municipality' => $municipality,
        ':area_type' => $areaType,
        ':page_template' => $pageTemplate,
        ':region' => $region,
        ':page_title' => $pageTitle,
        ':meta_description' => $metaDescription,
        ':hero_eyebrow' => $heroEyebrow,
        ':hero_title' => $heroTitle,
        ':hero_subtitle' => $heroSubtitle,
        ':intro_title' => $introTitle,
        ':intro_text' => $introText,
        ':region_text' => $regionText,
        ':seo_content_html' => $seoContentHtml,
        ':faq_html' => $faqHtml,
        ':is_active' => $isActive,
    ]);

    $newId = (int) $pdo->lastInsertId();

    $_SESSION['seo_admin_success'] = 'Nieuwe landing page aangemaakt.';
    header('Location: /admin/seo/landing-page-edit.php?id=' . $newId);
    exit;
} catch (Throwable $e) {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $_SESSION['seo_admin_error'] = 'Fout bij aanmaken: ' . $e->getMessage();
    } else {
        $_SESSION['seo_admin_error'] = 'Er ging iets mis bij het aanmaken.';
    }

    header('Location: /admin/seo/landing-page-create.php');
    exit;
}
