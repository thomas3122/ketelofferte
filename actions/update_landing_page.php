<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$editUrl = $id > 0 ? '/admin/seo/landing-page-edit.php?id=' . $id : '/admin/seo/landing-pages.php';

require_valid_csrf_token($editUrl, 'seo_admin_error');

function clean_string(?string $value): string
{
    return trim((string) $value);
}

function clean_nullable(?string $value): ?string
{
    $cleaned = trim((string) $value);
    return $cleaned === '' ? null : $cleaned;
}

if ($id <= 0) {
    $_SESSION['seo_admin_error'] = 'Ongeldige pagina.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
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

$optionalLandingFields = [
    'form_section_label',
    'form_section_title',
    'form_section_intro',
    'check_aside_title',
    'check_aside_text',
    'process_label',
    'process_title',
    'process_intro',
    'process_step_1_title',
    'process_step_1_text',
    'process_step_2_title',
    'process_step_2_text',
    'process_step_3_title',
    'process_step_3_text',
    'conversion_label',
    'conversion_title',
    'conversion_text',
    'trust_label',
    'trust_title',
    'trust_intro',
    'problems_label',
    'problems_title',
    'reviews_label',
    'reviews_title',
    'reviews_intro',
    'hero_badges_json',
    'trust_cards_json',
    'problem_cards_json',
];

$optionalValues = [];

foreach ($optionalLandingFields as $field) {
    $optionalValues[$field] = clean_nullable($_POST[$field] ?? '');
}

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
    header('Location: ' . $editUrl);
    exit;
}

if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    $_SESSION['seo_admin_error'] = 'Slug mag alleen kleine letters, cijfers en streepjes bevatten.';
    header('Location: ' . $editUrl);
    exit;
}

if (!in_array($areaType, $allowedAreaTypes, true)) {
    $_SESSION['seo_admin_error'] = 'Ongeldig area type.';
    header('Location: ' . $editUrl);
    exit;
}

if (!in_array($pageTemplate, $allowedTemplates, true)) {
    $_SESSION['seo_admin_error'] = 'Ongeldig page template.';
    header('Location: ' . $editUrl);
    exit;
}

foreach (['hero_badges_json', 'trust_cards_json', 'problem_cards_json'] as $jsonField) {
    if (empty($optionalValues[$jsonField])) {
        continue;
    }

    json_decode((string) $optionalValues[$jsonField], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['seo_admin_error'] = $jsonField . ' bevat geen geldige JSON.';
        header('Location: ' . $editUrl);
        exit;
    }
}

try {
    $checkStmt = $pdo->prepare("
        SELECT id
        FROM landing_pages
        WHERE slug = :slug
          AND id != :id
        LIMIT 1
    ");

    $checkStmt->execute([
        ':slug' => $slug,
        ':id' => $id,
    ]);

    if ($checkStmt->fetch()) {
        $_SESSION['seo_admin_error'] = 'Deze slug bestaat al bij een andere pagina.';
        header('Location: ' . $editUrl);
        exit;
    }

    $setParts = [
        'slug = :slug',
        'city = :city',
        'municipality = :municipality',
        'area_type = :area_type',
        'page_template = :page_template',
        'region = :region',
        'page_title = :page_title',
        'meta_description = :meta_description',
        'hero_eyebrow = :hero_eyebrow',
        'hero_title = :hero_title',
        'hero_subtitle = :hero_subtitle',
        'intro_title = :intro_title',
        'intro_text = :intro_text',
        'region_text = :region_text',
        'seo_content_html = :seo_content_html',
        'faq_html = :faq_html',
        'is_active = :is_active',
    ];

    $executeParams = [
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
        ':id' => $id,
    ];

    foreach ($optionalValues as $field => $value) {
        if (!app_column_exists($pdo, 'landing_pages', $field)) {
            continue;
        }

        $setParts[] = $field . ' = :' . $field;
        $executeParams[':' . $field] = $value;
    }

    $stmt = $pdo->prepare("
        UPDATE landing_pages
        SET " . implode(",\n            ", $setParts) . "
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute($executeParams);

    if (app_table_exists($pdo, 'landing_page_reviews')) {
        $reviewIds = $_POST['review_id'] ?? [];
        $reviewAuthorNames = $_POST['review_author_name'] ?? [];
        $reviewAuthorLocations = $_POST['review_author_location'] ?? [];
        $reviewRatings = $_POST['review_rating'] ?? [];
        $reviewQuoteTexts = $_POST['review_quote_text'] ?? [];
        $reviewIsActive = $_POST['review_is_active'] ?? [];
        $reviewSortOrders = $_POST['review_sort_order'] ?? [];

        $seenReviewIds = [];

        foreach ($reviewAuthorNames as $index => $authorName) {
            $reviewId = (int) ($reviewIds[$index] ?? 0);
            $authorName = clean_string($authorName);
            $authorLocation = clean_nullable($reviewAuthorLocations[$index] ?? '');
            $rating = max(1, min(5, (int) ($reviewRatings[$index] ?? 5)));
            $quoteText = clean_string($reviewQuoteTexts[$index] ?? '');
            $isReviewActive = ((int) ($reviewIsActive[$index] ?? 1)) === 1 ? 1 : 0;
            $sortOrder = (int) ($reviewSortOrders[$index] ?? $index);

            if ($authorName === '' && $quoteText === '') {
                if ($reviewId > 0) {
                    $deleteStmt = $pdo->prepare("
                        DELETE FROM landing_page_reviews
                        WHERE id = :id
                          AND landing_page_id = :landing_page_id
                        LIMIT 1
                    ");

                    $deleteStmt->execute([
                        ':id' => $reviewId,
                        ':landing_page_id' => $id,
                    ]);
                }

                continue;
            }

            if ($authorName === '' || $quoteText === '') {
                continue;
            }

            if ($reviewId > 0) {
                $reviewStmt = $pdo->prepare("
                    UPDATE landing_page_reviews
                    SET
                        author_name = :author_name,
                        author_location = :author_location,
                        rating = :rating,
                        quote_text = :quote_text,
                        is_active = :is_active,
                        sort_order = :sort_order
                    WHERE id = :id
                      AND landing_page_id = :landing_page_id
                    LIMIT 1
                ");

                $reviewStmt->execute([
                    ':author_name' => $authorName,
                    ':author_location' => $authorLocation,
                    ':rating' => $rating,
                    ':quote_text' => $quoteText,
                    ':is_active' => $isReviewActive,
                    ':sort_order' => $sortOrder,
                    ':id' => $reviewId,
                    ':landing_page_id' => $id,
                ]);

                $seenReviewIds[] = $reviewId;
                continue;
            }

            $reviewStmt = $pdo->prepare("
                INSERT INTO landing_page_reviews (
                    landing_page_id,
                    author_name,
                    author_location,
                    rating,
                    quote_text,
                    is_active,
                    sort_order
                ) VALUES (
                    :landing_page_id,
                    :author_name,
                    :author_location,
                    :rating,
                    :quote_text,
                    :is_active,
                    :sort_order
                )
            ");

            $reviewStmt->execute([
                ':landing_page_id' => $id,
                ':author_name' => $authorName,
                ':author_location' => $authorLocation,
                ':rating' => $rating,
                ':quote_text' => $quoteText,
                ':is_active' => $isReviewActive,
                ':sort_order' => $sortOrder,
            ]);
        }
    }

    $_SESSION['seo_admin_success'] = 'Landing page opgeslagen.';
    header('Location: ' . $editUrl);
    exit;
} catch (Throwable $e) {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $_SESSION['seo_admin_error'] = 'Fout bij opslaan: ' . $e->getMessage();
    } else {
        $_SESSION['seo_admin_error'] = 'Er ging iets mis bij het opslaan.';
    }

    header('Location: ' . $editUrl);
    exit;
}
