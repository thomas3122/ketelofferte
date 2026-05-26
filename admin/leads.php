<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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

$status = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));

$where = [];
$params = [];

if ($status !== '' && array_key_exists($status, $statusLabels)) {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}

if ($search !== '') {
    $where[] = '(public_id LIKE :search OR name LIKE :search OR email LIKE :search OR phone LIKE :search OR city LIKE :search OR postcode LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT id, public_id, name, email, phone, city, postcode, status, landing_page, created_at
    FROM leads
    {$whereSql}
    ORDER BY created_at DESC
    LIMIT 250
");

$stmt->execute($params);
$leads = $stmt->fetchAll();

$counts = [];
foreach ($statusLabels as $key => $label) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE status = :status");
    $countStmt->execute([':status' => $key]);
    $counts[$key] = (int) $countStmt->fetchColumn();
}

$totalStmt = $pdo->query('SELECT COUNT(*) FROM leads');
$totalLeads = (int) $totalStmt->fetchColumn();

$success = $_SESSION['lead_admin_success'] ?? null;
$error = $_SESSION['lead_admin_error'] ?? null;
unset($_SESSION['lead_admin_success'], $_SESSION['lead_admin_error']);
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

<?php require __DIR__ . '/../includes/admin_header.php'; ?>

<main class="page">
    <section class="hero-card">
        <span class="eyebrow">Aanvragen</span>
        <h1>Leads</h1>
        <p>Bekijk, filter en open binnengekomen offerteaanvragen.</p>
    </section>

    <?php if ($success): ?>
        <div class="notice success"><?= e((string) $success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <section class="card">
        <form class="filters" method="get" action="/admin/leads.php">
            <label>
                Zoek
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Naam, mail, telefoon, plaats">
            </label>

            <label>
                Status
                <select name="status">
                    <option value="">Alle statussen</option>
                    <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button class="btn" type="submit">Filteren</button>
            <a class="btn light" href="/admin/leads.php">Reset</a>
        </form>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span>Totaal</span>
            <strong><?= e((string) $totalLeads) ?></strong>
        </article>
        <?php foreach ($statusLabels as $key => $label): ?>
            <a class="stat-card" href="/admin/leads.php?status=<?= e($key) ?>">
                <span><?= e($label) ?></span>
                <strong><?= e((string) ($counts[$key] ?? 0)) ?></strong>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <div class="section-head">
            <div>
                <h2>Laatste leads</h2>
                <p><?= count($leads) ?> aanvraag<?= count($leads) === 1 ? '' : 'en' ?> getoond.</p>
            </div>
        </div>

        <?php if (!$leads): ?>
            <p class="muted">Geen leads gevonden.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Klant</th>
                        <th>Locatie</th>
                        <th>Status</th>
                        <th>Pagina</th>
                        <th>Datum</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><strong><?= e((string) $lead['public_id']) ?></strong></td>
                            <td>
                                <strong><?= e((string) $lead['name']) ?></strong><br>
                                <span class="muted"><?= e((string) $lead['email']) ?></span><br>
                                <span class="muted"><?= e((string) $lead['phone']) ?></span>
                            </td>
                            <td><?= e(trim((string) $lead['postcode'] . ' ' . (string) $lead['city'])) ?></td>
                            <td>
                                <span class="status <?= e((string) $lead['status']) ?>">
                                    <?= e($statusLabels[$lead['status']] ?? (string) $lead['status']) ?>
                                </span>
                            </td>
                            <td class="muted"><?= e((string) ($lead['landing_page'] ?? '-')) ?></td>
                            <td><?= e(date('d-m-Y H:i', strtotime((string) $lead['created_at']))) ?></td>
                            <td><a class="btn small" href="/admin/lead.php?id=<?= e((string) $lead['id']) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
