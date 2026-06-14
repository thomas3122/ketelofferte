<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function get_deadline_info(string $createdAt, string $status): array
{
    $doneStatuses = [
        'quote_made',
        'won',
        'lost',
    ];

    if (in_array($status, $doneStatuses, true)) {
        return [
            'label' => 'Afgehandeld',
            'class' => 'done',
            'sub' => '24-uurs actie niet meer actief',
        ];
    }

    $createdTs = strtotime($createdAt);

    if ($createdTs === false) {
        return [
            'label' => 'Onbekend',
            'class' => 'warning',
            'sub' => 'Geen geldige datum',
        ];
    }

    $deadlineTs = $createdTs + (24 * 60 * 60);
    $now = time();
    $diff = $deadlineTs - $now;

    if ($diff <= 0) {
        $overdueSeconds = abs($diff);
        $hours = floor($overdueSeconds / 3600);
        $minutes = floor(($overdueSeconds % 3600) / 60);

        return [
            'label' => 'Te laat',
            'class' => 'danger',
            'sub' => $hours . 'u ' . $minutes . 'm over deadline',
        ];
    }

    $hoursLeft = floor($diff / 3600);
    $minutesLeft = floor(($diff % 3600) / 60);

    if ($diff <= 6 * 60 * 60) {
        return [
            'label' => 'Bijna deadline',
            'class' => 'warning',
            'sub' => 'Nog ' . $hoursLeft . 'u ' . $minutesLeft . 'm',
        ];
    }

    return [
        'label' => 'Op schema',
        'class' => '',
        'sub' => 'Nog ' . $hoursLeft . 'u ' . $minutesLeft . 'm',
    ];
}

$statusLabels = [
    'new' => 'Nieuw',
    'contacted' => 'Gebeld',
    'waiting_info' => 'Wacht op info',
    'quote_made' => 'Offerte gemaakt',
    'sent_to_company' => 'Doorgestuurd',
    'won' => 'Gewonnen',
    'lost' => 'Verloren',
];

$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($status !== '' && array_key_exists($status, $statusLabels)) {
    $where[] = 'l.status = :status';
    $params[':status'] = $status;
}

if ($search !== '') {
    $where[] = '(l.name LIKE :search OR l.phone LIKE :search OR l.email LIKE :search OR l.postcode LIKE :search OR l.city LIKE :search OR l.public_id LIKE :search OR l.landing_city LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.public_id,
        l.name,
        l.phone,
        l.email,
        l.postcode,
        l.city,
        l.status,
        l.source,
        l.created_at,
        l.landing_slug,
        l.landing_city,
        l.landing_region,
        COUNT(DISTINCT lcr.id) AS request_count,
        COUNT(DISTINCT cq.id) AS quote_count
    FROM leads l
    LEFT JOIN lead_company_requests lcr ON lcr.lead_id = l.id
    LEFT JOIN company_quotes cq ON cq.lead_id = l.id
    {$whereSql}
    GROUP BY
        l.id,
        l.public_id,
        l.name,
        l.phone,
        l.email,
        l.postcode,
        l.city,
        l.status,
        l.source,
        l.created_at,
        l.landing_slug,
        l.landing_city,
        l.landing_region
    ORDER BY l.created_at DESC
    LIMIT 200
");

$stmt->execute($params);
$leads = $stmt->fetchAll();

$countStmt = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM leads
    GROUP BY status
");

$statusCounts = [];

foreach ($countStmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

$urgentCount = 0;
$lateCount = 0;

foreach ($leads as $lead) {
    $deadlineInfo = get_deadline_info((string) $lead['created_at'], (string) $lead['status']);

    if ($deadlineInfo['class'] === 'warning') {
        $urgentCount++;
    }

    if ($deadlineInfo['class'] === 'danger') {
        $lateCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Leads - <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<header class="topbar">
    <div class="logo"><?= e(APP_NAME) ?></div>

    <nav class="topnav">
        <a href="/admin/dashboard.php">Dashboard</a>
        <a href="/admin/leads.php">Leads</a>
        <a href="/admin/companies.php">Bedrijven</a>
        <a href="/admin/logout.php">Uitloggen</a>
    </nav>
</header>

<main class="page">
    <div class="page-head">
        <div>
            <h1>Leads</h1>
            <div class="muted">Bekijk en beheer alle ketelcheck-aanvragen binnen de 24-uurs belofte.</div>
        </div>
    </div>

    <section class="cards">
        <div class="stat">
            <strong><?= array_sum($statusCounts) ?></strong>
            <span>Totaal</span>
        </div>

        <div class="stat">
            <strong><?= $statusCounts['new'] ?? 0 ?></strong>
            <span>Nieuw</span>
        </div>

        <div class="stat">
            <strong><?= $urgentCount ?></strong>
            <span>Bijna deadline</span>
        </div>

        <div class="stat">
            <strong><?= $lateCount ?></strong>
            <span>Over deadline</span>
        </div>
    </section>

    <form class="filters" method="get" action="/admin/leads.php">
        <input
            type="search"
            name="search"
            placeholder="Zoek op naam, telefoon, plaats, postcode, regio of aanvraagnummer"
            value="<?= e($search) ?>"
        >

        <select name="status">
            <option value="">Alle statussen</option>
            <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Filter</button>
    </form>

    <section class="table-wrap">
        <?php if (!$leads): ?>
            <div class="empty">Nog geen leads gevonden.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Aanvraag</th>
                        <th>Klant</th>
                        <th>Contact</th>
                        <th>Locatie/regio</th>
                        <th>Status</th>
                        <th>24 uur</th>
                        <th>Offertes</th>
                        <th>Bron</th>
                        <th>Datum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <?php $deadlineInfo = get_deadline_info((string) $lead['created_at'], (string) $lead['status']); ?>

                        <tr>
                            <td>
                                <strong><?= e($lead['public_id']) ?></strong>
                                <span class="small">ID <?= (int) $lead['id'] ?></span>
                            </td>

                            <td>
                                <span class="lead-name"><?= e($lead['name']) ?></span>
                            </td>

                            <td>
                                <?= e($lead['phone']) ?>
                                <?php if (!empty($lead['email'])): ?>
                                    <span class="small"><?= e($lead['email']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($lead['city'] ?? '') ?>
                                <?php if (!empty($lead['postcode'])): ?>
                                    <span class="small"><?= e($lead['postcode']) ?></span>
                                <?php endif; ?>

                                <?php if (!empty($lead['landing_city'])): ?>
                                    <span class="small">Regio: <?= e($lead['landing_city']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status <?= e($lead['status']) ?>">
                                    <?= e($statusLabels[$lead['status']] ?? $lead['status']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="deadline-pill <?= e($deadlineInfo['class']) ?>">
                                    <?= e($deadlineInfo['label']) ?>
                                </span>
                                <span class="deadline-sub"><?= e($deadlineInfo['sub']) ?></span>
                            </td>

                            <td>
                                <strong><?= (int) $lead['quote_count'] ?></strong>
                                <span class="small"><?= (int) $lead['request_count'] ?> verzoek(en)</span>
                            </td>

                            <td>
                                <?= e($lead['source'] ?? 'website') ?>
                            </td>

                            <td>
                                <?= e(date('d-m-Y H:i', strtotime($lead['created_at']))) ?>
                            </td>

                            <td>
                                <a class="btn secondary" href="/admin/lead.php?id=<?= (int) $lead['id'] ?>">Open</a>
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
