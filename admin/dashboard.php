<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function get_count(PDO $pdo, string $query, array $params = []): int
{
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function table_exists(PDO $pdo, string $tableName): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute([
            ':table_name' => $tableName,
        ]);

        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$totalLeads = get_count($pdo, "SELECT COUNT(*) FROM leads");
$newLeads = get_count($pdo, "SELECT COUNT(*) FROM leads WHERE status = 'new'");
$sentToCompanyLeads = get_count($pdo, "SELECT COUNT(*) FROM leads WHERE status = 'sent_to_company'");
$quoteMadeLeads = get_count($pdo, "SELECT COUNT(*) FROM leads WHERE status = 'quote_made'");
$wonLeads = get_count($pdo, "SELECT COUNT(*) FROM leads WHERE status = 'won'");

$totalCompanies = get_count($pdo, "SELECT COUNT(*) FROM companies");
$activeCompanies = get_count($pdo, "SELECT COUNT(*) FROM companies WHERE status = 'active'");

$totalLandingPages = get_count($pdo, "SELECT COUNT(*) FROM landing_pages");
$activeLandingPages = get_count($pdo, "SELECT COUNT(*) FROM landing_pages WHERE is_active = 1");
$cityPages = get_count($pdo, "SELECT COUNT(*) FROM landing_pages WHERE is_active = 1 AND area_type = 'city'");
$districtPages = get_count($pdo, "SELECT COUNT(*) FROM landing_pages WHERE is_active = 1 AND area_type != 'city'");

$knowledgePagesAvailable = table_exists($pdo, 'knowledge_pages');
$totalKnowledgePages = $knowledgePagesAvailable ? get_count($pdo, "SELECT COUNT(*) FROM knowledge_pages") : 0;
$activeKnowledgePages = $knowledgePagesAvailable ? get_count($pdo, "SELECT COUNT(*) FROM knowledge_pages WHERE is_active = 1") : 0;

$recentLeadsStmt = $pdo->prepare("
    SELECT id, public_id, name, city, status, created_at
    FROM leads
    ORDER BY created_at DESC
    LIMIT 5
");

$recentLeadsStmt->execute();
$recentLeads = $recentLeadsStmt->fetchAll();

$statusLabels = [
    'new' => 'Nieuw',
    'contacted' => 'Gebeld',
    'waiting_info' => 'Wacht op info',
    'quote_made' => 'Offerte gemaakt',
    'sent_to_company' => 'Doorgestuurd',
    'won' => 'Gewonnen',
    'lost' => 'Verloren',
];

$seoPagesStmt = $pdo->prepare("
    SELECT id, slug, city, municipality, area_type, page_template, is_active
    FROM landing_pages
    ORDER BY updated_at DESC, created_at DESC
    LIMIT 6
");

$seoPagesStmt->execute();
$recentSeoPages = $seoPagesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
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
            font-weight: 900;
            font-size: 20px;
            white-space: nowrap;
        }

        .logo a {
            color: white;
            text-decoration: none;
        }

        .topnav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .topnav a,
        .logout {
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

        .topnav a:hover,
        .logout:hover {
            background: rgba(255,255,255,.18);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            white-space: nowrap;
        }

        .topbar-right span {
            color: rgba(255,255,255,.78);
        }

        .page {
            max-width: 1240px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        .hero-card {
            background:
                radial-gradient(circle at 92% 12%, rgba(255, 138, 31, .18), transparent 28%),
                linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: clamp(24px, 4vw, 34px);
            box-shadow: 0 14px 40px rgba(15, 42, 68, .08);
            margin-bottom: 22px;
        }

        .eyebrow {
            display: inline-flex;
            padding: 7px 12px;
            border-radius: 999px;
            background: #fff3e8;
            color: #9a4b00;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0;
            font-size: clamp(30px, 4vw, 44px);
            line-height: 1.05;
            color: var(--blue-dark);
            letter-spacing: -0.04em;
        }

        .hero-card p {
            max-width: 760px;
            color: var(--muted);
            line-height: 1.65;
            margin: 12px 0 0;
            font-size: 16px;
        }

        .quick-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn {
            border: 0;
            background: var(--orange);
            color: white;
            font-weight: 850;
            border-radius: 14px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 42, 68, .06);
        }

        .stat-card span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-card strong {
            display: block;
            color: var(--blue-dark);
            font-size: 32px;
            line-height: 1;
            font-weight: 950;
        }

        .stat-card small {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 20px;
            align-items: start;
        }

        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(15, 42, 68, .06);
            margin-bottom: 20px;
        }

        .card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 16px;
        }

        .card h2 {
            margin: 0;
            color: var(--blue-dark);
            font-size: 22px;
            letter-spacing: -0.03em;
        }

        .card p {
            color: var(--muted);
            line-height: 1.6;
            margin: 6px 0 0;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .nav-card {
            display: block;
            text-decoration: none;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px;
            transition: transform .15s ease, border-color .15s ease, background .15s ease;
        }

        .nav-card:hover {
            transform: translateY(-1px);
            border-color: rgba(255, 138, 31, .45);
            background: #fff7ed;
        }

        .nav-card strong {
            display: block;
            color: var(--blue-dark);
            font-size: 16px;
            margin-bottom: 5px;
        }

        .nav-card span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .table-list {
            display: grid;
            gap: 10px;
        }

        .list-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 13px;
        }

        .list-row strong {
            display: block;
            color: var(--blue-dark);
            font-size: 14px;
            margin-bottom: 3px;
        }

        .list-row span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .badge {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 999px;
            background: #eef5ff;
            color: var(--blue);
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .badge.new {
            background: #fff7ed;
            color: #9a4b00;
        }

        .badge.won {
            background: #ecfdf3;
            color: #166534;
        }

        .badge.lost {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.city {
            background: #fff7ed;
            color: #9a4b00;
        }

        .mini-metrics {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .mini-metric {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px;
        }

        .mini-metric strong {
            display: block;
            color: var(--blue-dark);
            font-size: 24px;
            line-height: 1;
        }

        .mini-metric span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 6px;
            font-weight: 750;
        }

        .empty {
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 16px;
            color: var(--muted);
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

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .stats-grid,
            .nav-grid,
            .mini-metrics {
                grid-template-columns: 1fr;
            }

            .quick-actions .btn {
                width: 100%;
            }

            .card-head {
                flex-direction: column;
            }

            .list-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>

<main class="page">
    <section class="hero-card">
        <div class="eyebrow">Admin dashboard</div>
        <h1>Welkom terug.</h1>
        <p>
            Beheer aanvragen, bedrijven, offertes en SEO-pagina’s vanuit één overzicht.
            Gebruik dit dashboard als snelle startpagina voor KetelOfferte24.nl.
        </p>

        <div class="quick-actions">
            <a class="btn" href="/admin/leads.php">Nieuwe aanvragen bekijken</a>
            <a class="btn secondary" href="/admin/seo/landing-pages.php">SEO pagina’s beheren</a>
            <a class="btn light" href="/admin/seo/landing-page-create.php">Nieuwe landing page</a>
            <a class="btn light" href="/" target="_blank">Website openen</a>
        </div>
    </section>

    <section class="stats-grid" aria-label="Dashboard statistieken">
        <article class="stat-card">
            <span>Nieuwe aanvragen</span>
            <strong><?= $newLeads ?></strong>
            <small>Totaal aantal nieuwe leads dat nog actie nodig heeft.</small>
        </article>

        <article class="stat-card">
            <span>Doorgestuurd naar bedrijf</span>
            <strong><?= $sentToCompanyLeads ?></strong>
            <small>Leads die al naar een bedrijf zijn doorgestuurd.</small>
        </article>

        <article class="stat-card">
            <span>Actieve bedrijven</span>
            <strong><?= $activeCompanies ?></strong>
            <small><?= $totalCompanies ?> bedrijven totaal in het systeem.</small>
        </article>

        <article class="stat-card">
            <span>Actieve SEO pagina’s</span>
            <strong><?= $activeLandingPages ?></strong>
            <small><?= $cityPages ?> hoofdclusters en <?= $districtPages ?> wijk/regiopagina’s.</small>
        </article>
    </section>

    <div class="dashboard-grid">
        <div>
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2>Snel navigeren</h2>
                        <p>Ga direct naar de belangrijkste beheerpagina’s.</p>
                    </div>
                </div>

                <div class="nav-grid">
                    <a class="nav-card" href="/admin/leads.php">
                        <strong>Leads beheren</strong>
                        <span>Bekijk aanvragen, status, foto’s, notities en offerteverzoeken.</span>
                    </a>

                    <a class="nav-card" href="/admin/companies.php">
                        <strong>Bedrijven beheren</strong>
                        <span>Beheer aangesloten bedrijven en hun contactgegevens.</span>
                    </a>

                    <a class="nav-card" href="/admin/seo/landing-pages.php">
                        <strong>SEO landing pages</strong>
                        <span>Beheer steden, wijken, clusters, teksten en FAQ’s.</span>
                    </a>

                    <a class="nav-card" href="/admin/seo/landing-page-create.php">
                        <strong>Nieuwe landing page</strong>
                        <span>Maak snel een nieuwe stad-, wijk- of regiopagina aan.</span>
                    </a>

                    <a class="nav-card" href="/regios/" target="_blank">
                        <strong>Regio-overzicht</strong>
                        <span>Bekijk de publieke regio-index zoals bezoekers die zien.</span>
                    </a>

                    <a class="nav-card" href="/sitemap.xml" target="_blank">
                        <strong>Sitemap</strong>
                        <span>Controleer of actieve pagina’s in de sitemap staan.</span>
                    </a>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2>Recente leads</h2>
                        <p>Laatste aanvragen die binnenkwamen via de ketelcheck.</p>
                    </div>

                    <a class="btn light" href="/admin/leads.php">Alle leads</a>
                </div>

                <?php if (!$recentLeads): ?>
                    <div class="empty">Nog geen leads gevonden.</div>
                <?php else: ?>
                    <div class="table-list">
                        <?php foreach ($recentLeads as $lead): ?>
                            <div class="list-row">
                                <div>
                                    <strong><?= e($lead['public_id']) ?> · <?= e($lead['name']) ?></strong>
                                    <span>
                                        <?= e($lead['city'] ?? '-') ?>
                                        · <?= e(date('d-m-Y H:i', strtotime((string) $lead['created_at']))) ?>
                                    </span>
                                </div>

                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <span class="badge <?= e($lead['status']) ?>">
                                        <?= e($statusLabels[$lead['status']] ?? $lead['status']) ?>
                                    </span>
                                    <a class="btn light" href="/admin/lead.php?id=<?= (int) $lead['id'] ?>">Open</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside>
            <section class="card">
                <h2>Lead status</h2>
                <p>Korte stand van je aanvraagflow.</p>

                <br>

                <div class="mini-metrics">
                    <div class="mini-metric">
                        <strong><?= $totalLeads ?></strong>
                        <span>Totaal leads</span>
                    </div>

                    <div class="mini-metric">
                        <strong><?= $quoteMadeLeads ?></strong>
                        <span>Offerte gemaakt</span>
                    </div>

                    <div class="mini-metric">
                        <strong><?= $wonLeads ?></strong>
                        <span>Gewonnen</span>
                    </div>

                    <div class="mini-metric">
                        <strong><?= $sentToCompanyLeads ?></strong>
                        <span>Doorgestuurd</span>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>SEO structuur</h2>
                <p>Controleer snel of je organische structuur groeit.</p>

                <br>

                <div class="mini-metrics">
                    <div class="mini-metric">
                        <strong><?= $activeLandingPages ?></strong>
                        <span>Actieve landingspagina’s</span>
                    </div>

                    <div class="mini-metric">
                        <strong><?= $cityPages ?></strong>
                        <span>Hoofdclusters</span>
                    </div>

                    <div class="mini-metric">
                        <strong><?= $districtPages ?></strong>
                        <span>Wijk/regiopagina’s</span>
                    </div>

                    <div class="mini-metric">
                        <strong><?= $activeKnowledgePages ?></strong>
                        <span>Kennisbank actief</span>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2>Recent aangepaste SEO-pagina’s</h2>
                        <p>Laatste pagina’s uit `landing_pages`.</p>
                    </div>
                </div>

                <?php if (!$recentSeoPages): ?>
                    <div class="empty">Nog geen SEO-pagina’s gevonden.</div>
                <?php else: ?>
                    <div class="table-list">
                        <?php foreach ($recentSeoPages as $page): ?>
                            <div class="list-row">
                                <div>
                                    <strong><?= e($page['city']) ?></strong>
                                    <span>
                                        /<?= e($page['slug']) ?>/
                                        · <?= e($page['municipality'] ?? '-') ?>
                                    </span>
                                </div>

                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <span class="badge <?= ($page['area_type'] ?? '') === 'city' ? 'city' : '' ?>">
                                        <?= e($page['area_type'] ?? '-') ?>
                                    </span>
                                    <a class="btn light" href="/admin/seo/landing-page-edit.php?id=<?= (int) $page['id'] ?>">Bewerk</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</main>

</body>
</html>
