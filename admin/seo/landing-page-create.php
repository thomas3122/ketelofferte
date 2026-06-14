<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$error = $_SESSION['seo_admin_error'] ?? null;
unset($_SESSION['seo_admin_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Nieuwe landing page - <?= e(APP_NAME) ?></title>
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
            background: var(--blue-dark);
            color: white;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .logo {
            font-weight: 900;
            font-size: 20px;
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

        .tips {
            display: grid;
            gap: 12px;
        }

        .tip {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 14px;
        }

        .tip strong {
            display: block;
            color: var(--blue-dark);
            margin-bottom: 5px;
        }

        .tip span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
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
            <h1>Nieuwe landing page</h1>
            <div class="muted">Maak een nieuwe stad-, wijk- of regiopagina aan.</div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <form action="/actions/create_landing_page.php" method="post">
        <?= csrf_field() ?>

        <div class="layout">
            <div>
                <section class="card">
                    <h2>Basis</h2>

                    <div class="form-grid">
                        <div class="field">
                            <label for="slug">Slug</label>
                            <input id="slug" name="slug" type="text" placeholder="bijv. gouda" required>
                            <div class="helper">Alleen kleine letters, cijfers en streepjes. Geen slash.</div>
                        </div>

                        <div class="field">
                            <label for="city">City / paginanaam</label>
                            <input id="city" name="city" type="text" placeholder="Bijv. Gouda" required>
                        </div>

                        <div class="field">
                            <label for="municipality">Municipality / cluster</label>
                            <input id="municipality" name="municipality" type="text" placeholder="Bijv. Gouda of Den Haag" required>
                            <div class="helper">Gebruik bij wijkpagina’s de hoofdgemeente.</div>
                        </div>

                        <div class="field">
                            <label for="region">Regio</label>
                            <input id="region" name="region" type="text" value="Zuid-Holland" required>
                        </div>

                        <div class="field">
                            <label for="area_type">Area type</label>
                            <select id="area_type" name="area_type" required>
                                <option value="district">district</option>
                                <option value="city">city</option>
                                <option value="neighborhood">neighborhood</option>
                                <option value="village">village</option>
                                <option value="region">region</option>
                            </select>
                            <div class="helper">Hoofdpagina zoals Rotterdam = city. Wijk zoals Moerwijk = district.</div>
                        </div>

                        <div class="field">
                            <label for="page_template">Page template</label>
                            <select id="page_template" name="page_template" required>
                                <option value="default">default</option>
                                <option value="city">city</option>
                            </select>
                            <div class="helper">City-pagina’s krijgen extra blokken en clusterlinks.</div>
                        </div>

                        <div class="field">
                            <label for="is_active">Status</label>
                            <select id="is_active" name="is_active" required>
                                <option value="1">Actief</option>
                                <option value="0">Inactief</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>SEO & hero</h2>

                    <div class="form-grid">
                        <div class="field full">
                            <label for="page_title">Page title</label>
                            <input id="page_title" name="page_title" type="text" placeholder="CV-ketel problemen in Gouda? | KetelOfferte24.nl" required>
                        </div>

                        <div class="field full">
                            <label for="meta_description">Meta description</label>
                            <textarea id="meta_description" name="meta_description" placeholder="Problemen met uw cv-ketel in Gouda? Doe de snelle ketelcheck en ontvang binnen 24 uur duidelijkheid, advies of een offerte." required></textarea>
                        </div>

                        <div class="field">
                            <label for="hero_eyebrow">Hero eyebrow</label>
                            <input id="hero_eyebrow" name="hero_eyebrow" type="text" placeholder="Snelle ketelcheck voor Gouda" required>
                        </div>

                        <div class="field">
                            <label for="hero_title">Hero title</label>
                            <input id="hero_title" name="hero_title" type="text" placeholder="CV-ketel problemen in Gouda?" required>
                        </div>

                        <div class="field full">
                            <label for="hero_subtitle">Hero subtitle</label>
                            <textarea id="hero_subtitle" name="hero_subtitle" required>Doe de snelle ketelcheck en ontvang binnen 24 uur duidelijkheid, advies of een passende offerte.</textarea>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>Intro & regio tekst</h2>

                    <div class="form-grid">
                        <div class="field full">
                            <label for="intro_title">Intro title</label>
                            <input id="intro_title" name="intro_title" type="text" placeholder="Snel duidelijkheid bij cv-ketelproblemen" required>
                        </div>

                        <div class="field full">
                            <label for="intro_text">Intro text</label>
                            <textarea id="intro_text" name="intro_text" required></textarea>
                        </div>

                        <div class="field full">
                            <label for="region_text">Region text</label>
                            <textarea id="region_text" name="region_text" required></textarea>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>Uitgebreide SEO-content</h2>

                    <div class="field">
                        <label for="seo_content_html">seo_content_html</label>
                        <textarea id="seo_content_html" name="seo_content_html" class="large"></textarea>
                        <div class="helper">Voor gewone wijkpagina’s mag dit leeg blijven. Voor city-pagina’s liefst uitgebreid vullen.</div>
                    </div>
                </section>

                <section class="card">
                    <h2>FAQ</h2>

                    <div class="field">
                        <label for="faq_html">faq_html</label>
                        <textarea id="faq_html" name="faq_html" class="large"></textarea>
                        <div class="helper">Voor city-pagina’s sterk aanbevolen. Voor wijkpagina’s optioneel.</div>
                    </div>
                </section>
            </div>

            <aside>
                <section class="card">
                    <h2>Aanmaken</h2>

                    <div class="side-actions">
                        <button class="btn" type="submit">Landing page aanmaken</button>
                        <a class="btn secondary" href="/admin/seo/landing-pages.php">Annuleren</a>
                    </div>
                </section>

                <section class="card">
                    <h2>Snelle richtlijn</h2>

                    <div class="tips">
                        <div class="tip">
                            <strong>Hoofdpagina</strong>
                            <span>Gebruik area_type city en page_template city. Bijvoorbeeld /gouda/.</span>
                        </div>

                        <div class="tip">
                            <strong>Wijkpagina</strong>
                            <span>Gebruik area_type district en page_template default. Municipality is dan de hoofdgemeente.</span>
                        </div>

                        <div class="tip">
                            <strong>Slug</strong>
                            <span>Gebruik korte nette slugs zoals /gouda/ of /leiden-noord/.</span>
                        </div>

                        <div class="tip">
                            <strong>SEO-content</strong>
                            <span>Voor clusterpagina’s veel tekst en FAQ. Voor wijkpagina’s korter en lokaler.</span>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
</main>

</body>
</html>
