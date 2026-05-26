<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$search = trim((string) ($_GET['search'] ?? ''));
$municipality = trim((string) ($_GET['municipality'] ?? ''));
$areaType = trim((string) ($_GET['area_type'] ?? ''));
$isActive = trim((string) ($_GET['is_active'] ?? ''));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        slug LIKE :search
        OR city LIKE :search
        OR municipality LIKE :search
        OR page_title LIKE :search
        OR meta_description LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
}

if ($municipality !== '') {
    $where[] = "municipality = :municipality";
    $params[':municipality'] = $municipality;
}

if ($areaType !== '') {
    $where[] = "area_type = :area_type";
    $params[':area_type'] = $areaType;
}

if ($isActive !== '' && in_array($isActive, ['0', '1'], true)) {
    $where[] = "is_active = :is_active";
    $params[':is_active'] = (int) $isActive;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT
        id,
        slug,
        city,
        municipality,
        area_type,
        page_template,
        region,
        page_title,
        is_active,
        updated_at,
        created_at
    FROM landing_pages
    {$whereSql}
    ORDER BY
        municipality ASC,
        CASE
            WHEN area_type = 'city' THEN 0
            ELSE 1
        END,
        city ASC
");

$stmt->execute($params);
$pages = $stmt->fetchAll();

$municipalityStmt = $pdo->query("
    SELECT DISTINCT municipality
    FROM landing_pages
    WHERE municipality IS NOT NULL
      AND municipality != ''
    ORDER BY municipality ASC
");

$municipalities = $municipalityStmt->fetchAll();

$success = $_SESSION['seo_admin_success'] ?? null;
$error = $_SESSION['seo_admin_error'] ?? null;
unset($_SESSION['seo_admin_success'], $_SESSION['seo_admin_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Landing pages beheren - <?= e(APP_NAME) ?></title>
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
            width: 100%;
            padding: 32px 20px;
            display: flex;
            justify-content: center;
        }

        .page-inner {
            width: 100%;
            max-width: 1440px;
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

        .filters {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px;
            display: grid;
            grid-template-columns: 1fr 190px 160px 140px auto;
            gap: 12px;
            margin-bottom: 18px;
            box-shadow: 0 10px 30px rgba(15, 42, 68, .06);
        }

        input,
        select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 13px 14px;
            font-size: 15px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(255,138,31,.12);
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

        .table-wrap {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 42, 68, .06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            background: #f8fafc;
            color: var(--muted);
            font-size: 13px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .page-title {
            font-weight: 900;
            color: var(--blue-dark);
        }

        .small {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
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

        .badge.city {
            background: #fff7ed;
            color: #9a4b00;
        }

        .badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .empty {
            padding: 34px;
            text-align: center;
            color: var(--muted);
        }

        .csv-panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
            box-shadow: 0 10px 30px rgba(15, 42, 68, .06);
        }

        .csv-panel h2 {
            margin: 0 0 6px;
            color: var(--blue-dark);
            font-size: 18px;
        }

        .csv-panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
            font-size: 14px;
        }

        .csv-form {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto;
            gap: 10px;
            align-items: center;
            min-width: 460px;
        }

        .csv-check {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .csv-check input {
            width: auto;
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

        @media (max-width: 1000px) {
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

            .filters {
                grid-template-columns: 1fr 1fr;
            }

            .csv-panel,
            .csv-form {
                grid-template-columns: 1fr;
                min-width: 0;
            }

            .table-wrap {
                overflow-x: auto;
            }

            table {
                min-width: 1050px;
            }
        }

        @media (max-width: 560px) {
            .page-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .filters {
                grid-template-columns: 1fr;
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
    <div class="page-inner">
        <div class="page-head">
            <div>
                <h1>Landing pages beheren</h1>
                <div class="muted">Beheer steden, wijken, clusters, SEO-tekst en FAQ’s.</div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn" href="/admin/seo/landing-page-create.php">Nieuwe landing page</a>
                <a class="btn secondary" href="/actions/export_landing_pages_csv.php">Export CSV</a>
                <a class="btn light" href="/regios/" target="_blank">Open regio-overzicht</a>
            </div>
        </div>

    <?php if ($success): ?>
        <div class="notice success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="csv-panel">
        <div>
            <h2>CSV import/export</h2>
            <p>Gebruik de export als basis voor bulkbeheer. Bij import worden bestaande slugs beschermd; nieuwe pagina’s worden alleen aangemaakt als u dat aanvinkt.</p>
        </div>

        <form class="csv-form" action="/actions/import_landing_pages_csv.php" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="file" name="landing_pages_csv" accept=".csv,text/csv" required>
            <button class="btn" type="submit">Import CSV</button>
            <label class="csv-check">
                <input type="checkbox" name="allow_create" value="1">
                Nieuwe slugs uit CSV als nieuwe pagina aanmaken
            </label>
        </form>
    </section>

    <form class="filters" method="get" action="/admin/seo/landing-pages.php">
        <input
            type="search"
            name="search"
            placeholder="Zoek op slug, stad, gemeente of titel"
            value="<?= e($search) ?>"
        >

        <select name="municipality">
            <option value="">Alle gemeenten</option>
            <?php foreach ($municipalities as $row): ?>
                <option value="<?= e($row['municipality']) ?>" <?= $municipality === $row['municipality'] ? 'selected' : '' ?>>
                    <?= e($row['municipality']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="area_type">
            <option value="">Alle types</option>
            <option value="city" <?= $areaType === 'city' ? 'selected' : '' ?>>City</option>
            <option value="district" <?= $areaType === 'district' ? 'selected' : '' ?>>District</option>
            <option value="neighborhood" <?= $areaType === 'neighborhood' ? 'selected' : '' ?>>Neighborhood</option>
            <option value="village" <?= $areaType === 'village' ? 'selected' : '' ?>>Village</option>
            <option value="region" <?= $areaType === 'region' ? 'selected' : '' ?>>Region</option>
        </select>

        <select name="is_active">
            <option value="">Alles</option>
            <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>Actief</option>
            <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>Inactief</option>
        </select>

        <button class="btn" type="submit">Filter</button>
    </form>

    <section class="table-wrap">
        <?php if (!$pages): ?>
            <div class="empty">Geen landing pages gevonden.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Pagina</th>
                        <th>Gemeente</th>
                        <th>Type</th>
                        <th>Template</th>
                        <th>Status</th>
                        <th>Bijgewerkt</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td>
                                <span class="page-title"><?= e($page['city']) ?></span>
                                <span class="small">/<?= e($page['slug']) ?>/</span>
                                <span class="small"><?= e($page['page_title'] ?? '') ?></span>
                            </td>

                            <td>
                                <?= e($page['municipality'] ?? '-') ?>
                                <span class="small"><?= e($page['region'] ?? '-') ?></span>
                            </td>

                            <td>
                                <span class="badge <?= ($page['area_type'] ?? '') === 'city' ? 'city' : '' ?>">
                                    <?= e($page['area_type'] ?? '-') ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?= ($page['page_template'] ?? '') === 'city' ? 'city' : '' ?>">
                                    <?= e($page['page_template'] ?? 'default') ?>
                                </span>
                            </td>

                            <td>
                                <?php if ((int) $page['is_active'] === 1): ?>
                                    <span class="badge">Actief</span>
                                <?php else: ?>
                                    <span class="badge inactive">Inactief</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= !empty($page['updated_at']) ? e(date('d-m-Y H:i', strtotime($page['updated_at']))) : '-' ?>
                                <span class="small">
                                    Aangemaakt: <?= !empty($page['created_at']) ? e(date('d-m-Y', strtotime($page['created_at']))) : '-' ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions">
                                    <a class="btn secondary" href="/admin/seo/landing-page-edit.php?id=<?= (int) $page['id'] ?>">Bewerk</a>
                                    <a class="btn light" href="/<?= e($page['slug']) ?>/" target="_blank">Open</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    </div>
</main>

</body>
</html>
