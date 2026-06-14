<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$pageId = (int) ($_GET['id'] ?? 0);

if ($pageId <= 0) {
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM landing_pages
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $pageId,
]);

$page = $stmt->fetch();

if (!$page) {
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

$reviews = [];

if (app_table_exists($pdo, 'landing_page_reviews')) {
    $reviewsStmt = $pdo->prepare("
        SELECT id, author_name, author_location, rating, quote_text, is_active, sort_order
        FROM landing_page_reviews
        WHERE landing_page_id = :landing_page_id
        ORDER BY sort_order ASC, id ASC
        LIMIT 6
    ");

    $reviewsStmt->execute([
        ':landing_page_id' => $pageId,
    ]);

    $reviews = $reviewsStmt->fetchAll();
}

while (count($reviews) < 3) {
    $reviews[] = [
        'id' => 0,
        'author_name' => '',
        'author_location' => '',
        'rating' => 5,
        'quote_text' => '',
        'is_active' => 1,
        'sort_order' => count($reviews),
    ];
}

$titleLength = mb_strlen((string) ($page['page_title'] ?? ''));
$metaLength = mb_strlen((string) ($page['meta_description'] ?? ''));
$slugLength = mb_strlen((string) ($page['slug'] ?? ''));
$seoContentLength = mb_strlen(strip_tags((string) ($page['seo_content_html'] ?? '')));
$faqContentLength = mb_strlen(strip_tags((string) ($page['faq_html'] ?? '')));

$titleStatus = ($titleLength >= 35 && $titleLength <= 65) ? 'good' : 'warning';
$metaStatus = ($metaLength >= 120 && $metaLength <= 165) ? 'good' : 'warning';
$slugStatus = ($slugLength >= 3 && preg_match('/^[a-z0-9-]+$/', (string) ($page['slug'] ?? ''))) ? 'good' : 'warning';
$seoStatus = ($seoContentLength >= 800) ? 'good' : 'warning';
$faqStatus = ($faqContentLength >= 250) ? 'good' : 'warning';

$clusterStatus = 'warning';

if (($page['area_type'] ?? '') === 'city' && ($page['page_template'] ?? '') === 'city') {
    $clusterStatus = 'good';
}

if (($page['area_type'] ?? '') !== 'city' && ($page['page_template'] ?? '') === 'default') {
    $clusterStatus = 'good';
}

$success = $_SESSION['seo_admin_success'] ?? null;
$error = $_SESSION['seo_admin_error'] ?? null;
unset($_SESSION['seo_admin_success'], $_SESSION['seo_admin_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?= e($page['city']) ?> bewerken - <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/admin.css">

    <style>
        :root {
            --blue: #0f2a44;
            --blue-dark: #071827;
            --orange: #ff8a1f;
            --bg: #f4f7fb;
            --white: #ffffff;
            --text: #132033;
            --muted: #6b7280;
            --border: #e5e7eb;
            --green: #16a34a;
            --red: #dc2626;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .topbar {
            width: 100%;
            background: var(--blue-dark);
            color: white;
        }

        .topbar-inner {
            max-width: 1240px;
            margin: 0 auto;
            padding: 18px 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
        }

        .logo {
            margin-right: auto;
            font-weight: 900;
            font-size: 20px;
            white-space: nowrap;
        }

        .topbar-right {
            margin-left: auto;
        }

        .topnav {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .topnav a {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,.12);
            padding: 9px 13px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
        }

        .topnav a.active {
            background: var(--orange);
            color: white;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .topbar-right .logout {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,.12);
            padding: 9px 13px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
        }

        .topbar-right .logout:hover {
            background: rgba(255,255,255,.18);
        }

        .page {
            max-width: 1480px;
            margin: 0 auto;
            padding: 28px 24px 40px;
        }

        .back {
            display: inline-flex;
            margin-bottom: 16px;
            color: var(--blue);
            font-weight: 800;
            text-decoration: none;
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0;
            font-size: 34px;
            color: var(--blue-dark);
        }

        .muted {
            color: var(--muted);
            margin-top: 6px;
        }

        .notice {
            padding: 13px 15px;
            border-radius: 14px;
            margin-bottom: 16px;
            font-weight: 800;
        }

        .notice.success {
            background: #ecfdf3;
            color: #166534;
        }

        .notice.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 22px;
            align-items: start;
        }

        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(15, 42, 68, .06);
            margin-bottom: 20px;
        }

        .card h2 {
            margin: 0 0 16px;
            color: var(--blue-dark);
            font-size: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: 900;
            color: var(--blue-dark);
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 13px 14px;
            font-size: 15px;
            outline: none;
            font-family: inherit;
            background: white;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        textarea.large {
            min-height: 330px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 14px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(255,138,31,.12);
        }

        .helper {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .btn {
            border: 0;
            background: var(--orange);
            color: white;
            font-weight: 800;
            border-radius: 13px;
            padding: 13px 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn.secondary {
            background: var(--blue);
        }

        .btn.light {
            background: #eef5ff;
            color: var(--blue);
        }

        .side-actions {
            display: grid;
            gap: 10px;
        }

        .side-actions .btn {
            width: 100%;
        }

        .seo-check {
            display: grid;
            gap: 12px;
        }

        .seo-check-item {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 14px;
        }

        .seo-check-item.good {
            background: #ecfdf3;
            border-color: #bbf7d0;
        }

        .seo-check-item.warning {
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .seo-check-item span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .seo-check-item strong {
            display: block;
            color: var(--blue-dark);
            font-size: 18px;
        }

        .seo-check-item small {
            display: block;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
            margin-top: 5px;
        }

        .topbar {
            background: var(--blue-dark);
            color: white;
            padding: 16px 24px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 18px;
        }

        .logo {
            margin: 0;
        }

        .logo a {
            color: white;
            text-decoration: none;
        }

        .topbar-right {
            margin: 0;
        }

        @media (max-width: 980px) {
            .topbar {
                grid-template-columns: 1fr;
                align-items: flex-start;
            }

            .topnav {
                justify-content: flex-start;
            }

            .topbar-right {
                flex-wrap: wrap;
                white-space: normal;
            }

            .layout {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .page-head {
                align-items: flex-start;
                flex-direction: column;
            }

            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../../includes/admin_header.php'; ?>

<main class="page">
    <a class="back" href="/admin/seo/landing-pages.php">← Terug naar SEO pagina’s</a>

    <div class="page-head">
        <div>
            <h1><?= e($page['city']) ?> bewerken</h1>
            <div class="muted">/<?= e($page['slug']) ?>/</div>
        </div>

        <a class="btn light" href="/<?= e($page['slug']) ?>/" target="_blank">Open pagina</a>
    </div>

    <?php if ($success): ?>
        <div class="notice success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <form action="/actions/update_landing_page.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">

        <div class="layout">
            <div>
                <section class="card">
                    <h2>Basis</h2>

                    <div class="form-grid">
                        <div class="field">
                            <label for="slug">Slug</label>
                            <input id="slug" name="slug" type="text" value="<?= e($page['slug'] ?? '') ?>" required>
                            <div class="helper">Bijv. den-haag, moerwijk, rotterdam.</div>
                        </div>

                        <div class="field">
                            <label for="city">City / paginanaam</label>
                            <input id="city" name="city" type="text" value="<?= e($page['city'] ?? '') ?>" required>
                        </div>

                        <div class="field">
                            <label for="municipality">Municipality / cluster</label>
                            <input id="municipality" name="municipality" type="text" value="<?= e($page['municipality'] ?? '') ?>" required>
                            <div class="helper">Bijv. Den Haag. Wijken worden hiermee gegroepeerd.</div>
                        </div>

                        <div class="field">
                            <label for="region">Regio</label>
                            <input id="region" name="region" type="text" value="<?= e($page['region'] ?? 'Zuid-Holland') ?>" required>
                        </div>

                        <div class="field">
                            <label for="area_type">Area type</label>
                            <select id="area_type" name="area_type" required>
                                <?php
                                $areaTypes = ['city', 'district', 'neighborhood', 'village', 'region'];
                                foreach ($areaTypes as $type):
                                ?>
                                    <option value="<?= e($type) ?>" <?= ($page['area_type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= e($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="page_template">Page template</label>
                            <select id="page_template" name="page_template" required>
                                <option value="default" <?= ($page['page_template'] ?? 'default') === 'default' ? 'selected' : '' ?>>default</option>
                                <option value="city" <?= ($page['page_template'] ?? '') === 'city' ? 'selected' : '' ?>>city</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="is_active">Status</label>
                            <select id="is_active" name="is_active" required>
                                <option value="1" <?= (int) ($page['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Actief</option>
                                <option value="0" <?= (int) ($page['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactief</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>SEO & hero</h2>

                    <div class="form-grid">
                        <div class="field full">
                            <label for="page_title">Page title</label>
                            <input id="page_title" name="page_title" type="text" value="<?= e($page['page_title'] ?? '') ?>" required>
                        </div>

                        <div class="field full">
                            <label for="meta_description">Meta description</label>
                            <textarea id="meta_description" name="meta_description" required><?= e($page['meta_description'] ?? '') ?></textarea>
                        </div>

                        <div class="field">
                            <label for="hero_eyebrow">Hero eyebrow</label>
                            <input id="hero_eyebrow" name="hero_eyebrow" type="text" value="<?= e($page['hero_eyebrow'] ?? '') ?>" required>
                        </div>

                        <div class="field">
                            <label for="hero_title">Hero title</label>
                            <input id="hero_title" name="hero_title" type="text" value="<?= e($page['hero_title'] ?? '') ?>" required>
                        </div>

                        <div class="field full">
                            <label for="hero_subtitle">Hero subtitle</label>
                            <textarea id="hero_subtitle" name="hero_subtitle" required><?= e($page['hero_subtitle'] ?? '') ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>Intro & regio tekst</h2>

                    <div class="form-grid">
                        <div class="field full">
                            <label for="intro_title">Intro title</label>
                            <input id="intro_title" name="intro_title" type="text" value="<?= e($page['intro_title'] ?? '') ?>" required>
                        </div>

                        <div class="field full">
                            <label for="intro_text">Intro text</label>
                            <textarea id="intro_text" name="intro_text" required><?= e($page['intro_text'] ?? '') ?></textarea>
                        </div>

                        <div class="field full">
                            <label for="region_text">Region text</label>
                            <textarea id="region_text" name="region_text" required><?= e($page['region_text'] ?? '') ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>Formulier & conversiecopy</h2>

                    <div class="form-grid">
                        <div class="field">
                            <label for="form_section_label">Formulier label</label>
                            <input id="form_section_label" name="form_section_label" type="text" value="<?= e($page['form_section_label'] ?? '') ?>" placeholder="Gratis check">
                        </div>

                        <div class="field">
                            <label for="check_aside_title">Aside titel</label>
                            <input id="check_aside_title" name="check_aside_title" type="text" value="<?= e($page['check_aside_title'] ?? '') ?>" placeholder="Ketelcheck">
                        </div>

                        <div class="field full">
                            <label for="form_section_title">Formulier titel</label>
                            <input id="form_section_title" name="form_section_title" type="text" value="<?= e($page['form_section_title'] ?? '') ?>" placeholder="Valt terug op Intro title als leeg">
                        </div>

                        <div class="field full">
                            <label for="form_section_intro">Formulier intro</label>
                            <textarea id="form_section_intro" name="form_section_intro" placeholder="Valt terug op Intro text als leeg"><?= e($page['form_section_intro'] ?? '') ?></textarea>
                        </div>

                        <div class="field full">
                            <label for="check_aside_text">Aside tekst naast formulier</label>
                            <textarea id="check_aside_text" name="check_aside_text"><?= e($page['check_aside_text'] ?? '') ?></textarea>
                        </div>

                        <div class="field">
                            <label for="conversion_label">Conversie label</label>
                            <input id="conversion_label" name="conversion_label" type="text" value="<?= e($page['conversion_label'] ?? '') ?>" placeholder="Offerte aanvragen">
                        </div>

                        <div class="field">
                            <label for="conversion_title">Conversie titel</label>
                            <input id="conversion_title" name="conversion_title" type="text" value="<?= e($page['conversion_title'] ?? '') ?>">
                        </div>

                        <div class="field full">
                            <label for="conversion_text">Conversie tekst</label>
                            <textarea id="conversion_text" name="conversion_text"><?= e($page['conversion_text'] ?? '') ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>Van probleem naar oplossing</h2>

                    <div class="form-grid">
                        <div class="field">
                            <label for="process_label">Blok label</label>
                            <input id="process_label" name="process_label" type="text" value="<?= e($page['process_label'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="process_title">Blok titel</label>
                            <input id="process_title" name="process_title" type="text" value="<?= e($page['process_title'] ?? '') ?>">
                        </div>

                        <div class="field full">
                            <label for="process_intro">Intro</label>
                            <textarea id="process_intro" name="process_intro"><?= e($page['process_intro'] ?? '') ?></textarea>
                        </div>

                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <div class="field">
                                <label for="process_step_<?= $i ?>_title">Stap <?= $i ?> titel</label>
                                <input id="process_step_<?= $i ?>_title" name="process_step_<?= $i ?>_title" type="text" value="<?= e($page['process_step_' . $i . '_title'] ?? '') ?>">
                            </div>

                            <div class="field">
                                <label for="process_step_<?= $i ?>_text">Stap <?= $i ?> tekst</label>
                                <textarea id="process_step_<?= $i ?>_text" name="process_step_<?= $i ?>_text"><?= e($page['process_step_' . $i . '_text'] ?? '') ?></textarea>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="helper">Laat velden leeg om de standaardtekst te gebruiken. Vul ze in wanneer deze pagina eigen tekst nodig heeft.</div>
                </section>

                <section class="card">
                    <h2>Trust, problemen & reviews intro</h2>

                    <div class="form-grid">
                        <div class="field">
                            <label for="trust_label">Trust label</label>
                            <input id="trust_label" name="trust_label" type="text" value="<?= e($page['trust_label'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="trust_title">Trust titel</label>
                            <input id="trust_title" name="trust_title" type="text" value="<?= e($page['trust_title'] ?? '') ?>">
                        </div>

                        <div class="field full">
                            <label for="trust_intro">Trust intro</label>
                            <textarea id="trust_intro" name="trust_intro"><?= e($page['trust_intro'] ?? '') ?></textarea>
                        </div>

                        <div class="field">
                            <label for="problems_label">Problemen label</label>
                            <input id="problems_label" name="problems_label" type="text" value="<?= e($page['problems_label'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="problems_title">Problemen titel</label>
                            <input id="problems_title" name="problems_title" type="text" value="<?= e($page['problems_title'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="reviews_label">Reviews label</label>
                            <input id="reviews_label" name="reviews_label" type="text" value="<?= e($page['reviews_label'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="reviews_title">Reviews titel</label>
                            <input id="reviews_title" name="reviews_title" type="text" value="<?= e($page['reviews_title'] ?? '') ?>">
                        </div>

                        <div class="field full">
                            <label for="reviews_intro">Reviews intro</label>
                            <textarea id="reviews_intro" name="reviews_intro"><?= e($page['reviews_intro'] ?? '') ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>Kaarten & badges</h2>

                    <div class="field">
                        <label for="hero_badges_json">Hero badges</label>
                        <textarea id="hero_badges_json" name="hero_badges_json" class="large" placeholder='["Storing","Lekkage","Geen warm water"]'><?= e($page['hero_badges_json'] ?? '') ?></textarea>
                        <div class="helper">Korte badges voor bovenaan de pagina. Laat leeg voor de standaard badges.</div>
                    </div>

                    <div class="field" style="margin-top: 18px;">
                        <label for="trust_cards_json">Trust kaarten</label>
                        <textarea id="trust_cards_json" name="trust_cards_json" class="large" placeholder='[{"icon":"24","title":"Binnen 24 uur duidelijkheid","text":"..."}]'><?= e($page['trust_cards_json'] ?? '') ?></textarea>
                        <div class="helper">Kaarten met icoon, titel en tekst voor het vertrouwensblok.</div>
                    </div>

                    <div class="field" style="margin-top: 18px;">
                        <label for="problem_cards_json">Probleem kaarten</label>
                        <textarea id="problem_cards_json" name="problem_cards_json" class="large" placeholder='[{"icon":"⚠","title":"CV-ketel storing","text":"..."}]'><?= e($page['problem_cards_json'] ?? '') ?></textarea>
                        <div class="helper">Kaarten met icoon, titel en tekst. Laat leeg voor de standaard probleemkaarten.</div>
                    </div>
                </section>

                <section class="card">
                    <h2>Reviews</h2>

                    <?php foreach ($reviews as $index => $review): ?>
                        <input type="hidden" name="review_id[]" value="<?= (int) ($review['id'] ?? 0) ?>">
                        <input type="hidden" name="review_sort_order[]" value="<?= $index ?>">

                        <div class="form-grid" style="margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid var(--border);">
                            <div class="field">
                                <label>Naam</label>
                                <input name="review_author_name[]" type="text" value="<?= e($review['author_name'] ?? '') ?>">
                            </div>

                            <div class="field">
                                <label>Locatie</label>
                                <input name="review_author_location[]" type="text" value="<?= e($review['author_location'] ?? '') ?>">
                            </div>

                            <div class="field">
                                <label>Rating</label>
                                <select name="review_rating[]">
                                    <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                        <option value="<?= $rating ?>" <?= (int) ($review['rating'] ?? 5) === $rating ? 'selected' : '' ?>><?= $rating ?> sterren</option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label>Status</label>
                                <select name="review_is_active[]">
                                    <option value="1" <?= (int) ($review['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Actief</option>
                                    <option value="0" <?= (int) ($review['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactief</option>
                                </select>
                            </div>

                            <div class="field full">
                                <label>Review tekst</label>
                                <textarea name="review_quote_text[]"><?= e($review['quote_text'] ?? '') ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="helper">Lege reviewregels worden overgeslagen. Actieve reviews worden op de pagina getoond.</div>
                </section>

                <section class="card">
                    <h2>Uitgebreide SEO-content</h2>

                    <div class="field">
                        <label for="seo_content_html">seo_content_html</label>
                        <textarea id="seo_content_html" name="seo_content_html" class="large"><?= e($page['seo_content_html'] ?? '') ?></textarea>
                        <div class="helper">HTML toegestaan: h2, h3, p, ul, li, strong.</div>
                    </div>
                </section>

                <section class="card">
                    <h2>FAQ</h2>

                    <div class="field">
                        <label for="faq_html">faq_html</label>
                        <textarea id="faq_html" name="faq_html" class="large"><?= e($page['faq_html'] ?? '') ?></textarea>
                        <div class="helper">HTML toegestaan: h2, h3, p.</div>
                    </div>
                </section>
            </div>

            <aside>
                <section class="card">
                    <h2>Opslaan</h2>

                    <div class="side-actions">
                        <button class="btn" type="submit">Wijzigingen opslaan</button>
                        <a class="btn light" href="/<?= e($page['slug']) ?>/" target="_blank">Preview openen</a>
                        <a class="btn secondary" href="/admin/seo/landing-pages.php">Terug naar overzicht</a>
                    </div>
                </section>

                <section class="card">
                    <h2>SEO check</h2>

                    <div class="seo-check">
                        <div class="seo-check-item <?= e($titleStatus) ?>">
                            <span>Title lengte</span>
                            <strong><?= $titleLength ?> tekens</strong>
                            <small>Richtlijn: 35–65 tekens.</small>
                        </div>

                        <div class="seo-check-item <?= e($metaStatus) ?>">
                            <span>Meta description</span>
                            <strong><?= $metaLength ?> tekens</strong>
                            <small>Richtlijn: 120–165 tekens.</small>
                        </div>

                        <div class="seo-check-item <?= e($slugStatus) ?>">
                            <span>Slug</span>
                            <strong>/<?= e($page['slug'] ?? '') ?>/</strong>
                            <small>Gebruik alleen kleine letters, cijfers en streepjes.</small>
                        </div>

                        <div class="seo-check-item <?= e($seoStatus) ?>">
                            <span>SEO-content</span>
                            <strong><?= $seoContentLength ?> tekens</strong>
                            <small>Voor city-pagina’s liefst uitgebreid gevuld.</small>
                        </div>

                        <div class="seo-check-item <?= e($faqStatus) ?>">
                            <span>FAQ-content</span>
                            <strong><?= $faqContentLength ?> tekens</strong>
                            <small>Voor hoofdclusterpagina’s sterk aanbevolen.</small>
                        </div>

                        <div class="seo-check-item <?= e($clusterStatus) ?>">
                            <span>Cluster instelling</span>
                            <strong><?= e(($page['area_type'] ?? '-') . ' / ' . ($page['page_template'] ?? '-')) ?></strong>
                            <small>City hoort bij template city. Wijk hoort meestal bij default.</small>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
</main>

</body>
</html>
