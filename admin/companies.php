<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$allowedStatuses = ['active', 'inactive'];
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        c.company_name LIKE :search
        OR c.contact_name LIKE :search
        OR c.email LIKE :search
        OR c.phone LIKE :search
        OR c.city LIKE :search
        OR c.service_area LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
}

if ($status !== '' && in_array($status, $allowedStatuses, true)) {
    $where[] = 'c.status = :status';
    $params[':status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.company_name,
        c.contact_name,
        c.email,
        c.phone,
        c.city,
        c.service_area,
        c.status,
        COUNT(DISTINCT lcr.id) AS request_count,
        COUNT(DISTINCT cq.id) AS quote_count,
        MAX(lcr.requested_at) AS last_request_at,
        MAX(cq.submitted_at) AS last_quote_at
    FROM companies c
    LEFT JOIN lead_company_requests lcr ON lcr.company_id = c.id
    LEFT JOIN company_quotes cq ON cq.company_id = c.id
    {$whereSql}
    GROUP BY
        c.id,
        c.company_name,
        c.contact_name,
        c.email,
        c.phone,
        c.city,
        c.service_area,
        c.status
    ORDER BY
        CASE WHEN c.status = 'active' THEN 0 ELSE 1 END,
        c.company_name ASC
");

$stmt->execute($params);
$companies = $stmt->fetchAll();

$countsStmt = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM companies
    GROUP BY status
");

$statusCounts = [];

foreach ($countsStmt->fetchAll() as $row) {
    $statusCounts[(string) $row['status']] = (int) $row['total'];
}

$totalCompanies = array_sum($statusCounts);
$totalRequests = 0;
$totalQuotes = 0;

foreach ($companies as $company) {
    $totalRequests += (int) $company['request_count'];
    $totalQuotes += (int) $company['quote_count'];
}

$success = $_SESSION['company_admin_success'] ?? null;
$error = $_SESSION['company_admin_error'] ?? null;
unset($_SESSION['company_admin_success'], $_SESSION['company_admin_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bedrijven - <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<main class="page">
    <div class="page-head">
        <div>
            <h1>Bedrijven</h1>
            <div class="muted">Beheer partners, contactgegevens, werkgebieden en offerte-activiteit.</div>
        </div>

        <div class="quick-actions">
            <a class="btn" href="/admin/company.php">Nieuw bedrijf</a>
            <a class="btn light" href="/admin/leads.php">Open leads</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="notice success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="cards">
        <div class="stat">
            <strong><?= $totalCompanies ?></strong>
            <span>Totaal bedrijven</span>
        </div>

        <div class="stat">
            <strong><?= $statusCounts['active'] ?? 0 ?></strong>
            <span>Actief</span>
        </div>

        <div class="stat">
            <strong><?= $totalRequests ?></strong>
            <span>Verzoeken</span>
        </div>

        <div class="stat">
            <strong><?= $totalQuotes ?></strong>
            <span>Offertes</span>
        </div>
    </section>

    <form class="filters" method="get" action="/admin/companies.php">
        <input
            type="search"
            name="search"
            placeholder="Zoek op bedrijf, contactpersoon, e-mail, telefoon, plaats of werkgebied"
            value="<?= e($search) ?>"
        >

        <select name="status">
            <option value="">Alle statussen</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actief</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactief</option>
        </select>

        <button class="btn" type="submit">Filter</button>
    </form>

    <section class="table-wrap">
        <?php if (!$companies): ?>
            <div class="empty">Geen bedrijven gevonden.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Bedrijf</th>
                        <th>Contact</th>
                        <th>Werkgebied</th>
                        <th>Status</th>
                        <th>Verzoeken</th>
                        <th>Offertes</th>
                        <th>Laatste activiteit</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td>
                                <span class="company-name"><?= e($company['company_name']) ?></span>
                                <span class="company-meta"><?= e($company['city'] ?: '-') ?></span>
                            </td>
                            <td>
                                <?= e($company['contact_name'] ?: '-') ?>
                                <span class="company-meta"><?= e($company['email'] ?: '-') ?></span>
                                <span class="company-meta"><?= e($company['phone'] ?: '-') ?></span>
                            </td>
                            <td>
                                <span class="company-meta"><?= e($company['service_area'] ?: '-') ?></span>
                            </td>
                            <td>
                                <span class="badge <?= e($company['status']) ?>">
                                    <?= $company['status'] === 'active' ? 'Actief' : 'Inactief' ?>
                                </span>
                            </td>
                            <td class="metric-cell">
                                <strong><?= (int) $company['request_count'] ?></strong>
                                <span class="small">verzonden</span>
                            </td>
                            <td class="metric-cell">
                                <strong><?= (int) $company['quote_count'] ?></strong>
                                <span class="small">ontvangen</span>
                            </td>
                            <td class="activity-cell">
                                <?php if (!empty($company['last_quote_at'])): ?>
                                    <span class="activity-label">Offerte</span>
                                    <span class="small"><?= e(date('d-m-Y H:i', strtotime((string) $company['last_quote_at']))) ?></span>
                                <?php elseif (!empty($company['last_request_at'])): ?>
                                    <span class="activity-label">Verzoek</span>
                                    <span class="small"><?= e(date('d-m-Y H:i', strtotime((string) $company['last_request_at']))) ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary" href="/admin/company.php?id=<?= (int) $company['id'] ?>">Bekijk</a>
                                    <?php if (!empty($company['email'])): ?>
                                        <a class="btn light" href="mailto:<?= e($company['email']) ?>">Mail</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
