<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$companyId = (int) ($_GET['id'] ?? 0);
$isNew = $companyId <= 0;

$company = [
    'id' => null,
    'company_name' => '',
    'contact_name' => '',
    'email' => '',
    'phone' => '',
    'city' => '',
    'service_area' => '',
    'status' => 'active',
];

if (!$isNew) {
    $stmt = $pdo->prepare("
        SELECT id, company_name, contact_name, email, phone, city, service_area, status
        FROM companies
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $companyId,
    ]);

    $foundCompany = $stmt->fetch();

    if (!$foundCompany) {
        $_SESSION['company_admin_error'] = 'Bedrijf niet gevonden.';
        header('Location: /admin/companies.php');
        exit;
    }

    $company = $foundCompany;
}

$stats = [
    'request_count' => 0,
    'quote_count' => 0,
    'opened_count' => 0,
    'expired_count' => 0,
];

$requests = [];
$quotes = [];

if (!$isNew) {
    $statsStmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT lcr.id) AS request_count,
            COUNT(DISTINCT cq.id) AS quote_count,
            SUM(CASE WHEN lcr.status IN ('opened', 'quoted') THEN 1 ELSE 0 END) AS opened_count,
            SUM(CASE WHEN lcr.status = 'expired' THEN 1 ELSE 0 END) AS expired_count
        FROM companies c
        LEFT JOIN lead_company_requests lcr ON lcr.company_id = c.id
        LEFT JOIN company_quotes cq ON cq.company_id = c.id
        WHERE c.id = :id
    ");

    $statsStmt->execute([
        ':id' => $companyId,
    ]);

    $stats = array_merge($stats, $statsStmt->fetch() ?: []);

    $requestsStmt = $pdo->prepare("
        SELECT
            lcr.id,
            lcr.public_id,
            lcr.status,
            lcr.requested_at,
            lcr.deadline_at,
            lcr.opened_at,
            lcr.responded_at,
            l.public_id AS lead_public_id,
            l.name AS lead_name,
            l.city AS lead_city,
            l.postcode
        FROM lead_company_requests lcr
        INNER JOIN leads l ON l.id = lcr.lead_id
        WHERE lcr.company_id = :company_id
        ORDER BY lcr.requested_at DESC
        LIMIT 20
    ");

    $requestsStmt->execute([
        ':company_id' => $companyId,
    ]);

    $requests = $requestsStmt->fetchAll();

    $quotesStmt = $pdo->prepare("
        SELECT
            cq.id,
            cq.quote_amount,
            cq.status,
            cq.submitted_at,
            cq.installation_possible_date,
            l.id AS lead_id,
            l.public_id AS lead_public_id,
            l.name AS lead_name,
            l.city AS lead_city
        FROM company_quotes cq
        INNER JOIN leads l ON l.id = cq.lead_id
        WHERE cq.company_id = :company_id
        ORDER BY cq.submitted_at DESC
        LIMIT 20
    ");

    $quotesStmt->execute([
        ':company_id' => $companyId,
    ]);

    $quotes = $quotesStmt->fetchAll();
}

$statusLabels = [
    'sent' => 'Verstuurd',
    'opened' => 'Geopend',
    'quoted' => 'Offerte ontvangen',
    'expired' => 'Verlopen',
    'declined' => 'Afgewezen',
    'cancelled' => 'Geannuleerd',
];

$success = $_SESSION['company_admin_success'] ?? null;
$error = $_SESSION['company_admin_error'] ?? null;
unset($_SESSION['company_admin_success'], $_SESSION['company_admin_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?= $isNew ? 'Nieuw bedrijf' : e($company['company_name']) ?> - <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/admin.css">

    <style>
        .company-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 20px;
            align-items: start;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
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

        textarea {
            min-height: 118px;
            resize: vertical;
        }

        .badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 7px 10px;
            background: #eef5ff;
            color: var(--blue);
            font-weight: 900;
            font-size: 12px;
            white-space: nowrap;
        }

        .badge.expired,
        .badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.quoted {
            background: #ecfdf3;
            color: #166534;
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

        .meta-list {
            display: grid;
            gap: 10px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .meta-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .meta-row span {
            color: var(--muted);
            font-size: 13px;
        }

        .meta-row strong {
            color: var(--blue-dark);
            text-align: right;
        }

        .actions-stack {
            display: grid;
            gap: 10px;
        }

        .actions-stack .btn {
            width: 100%;
        }

        @media (max-width: 1000px) {
            .company-layout,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-wrap {
                overflow-x: auto;
            }

            table {
                min-width: 840px;
            }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../includes/admin_header.php'; ?>

<main class="page">
    <a class="btn light" href="/admin/companies.php" style="margin-bottom: 16px;">Terug naar bedrijven</a>

    <section class="hero-card">
        <div class="eyebrow"><?= $isNew ? 'Nieuw bedrijf' : 'Bedrijf' ?></div>
        <h1><?= $isNew ? 'Nieuw bedrijf toevoegen' : e($company['company_name']) ?></h1>
        <p>
            Beheer contactgegevens, status en werkgebied. Alleen actieve bedrijven zijn selecteerbaar bij het doorsturen van leads.
        </p>
    </section>

    <?php if ($success): ?>
        <div class="notice success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="company-layout">
        <div>
            <section class="card">
                <h2>Bedrijfsgegevens</h2>

                <form action="/actions/save_company.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) ($company['id'] ?? 0) ?>">

                    <div class="form-grid">
                        <div class="field full">
                            <label for="company_name">Bedrijfsnaam</label>
                            <input id="company_name" name="company_name" type="text" value="<?= e($company['company_name'] ?? '') ?>" required>
                        </div>

                        <div class="field">
                            <label for="contact_name">Contactpersoon</label>
                            <input id="contact_name" name="contact_name" type="text" value="<?= e($company['contact_name'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?= ($company['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Actief</option>
                                <option value="inactive" <?= ($company['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactief</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="email">E-mail</label>
                            <input id="email" name="email" type="email" value="<?= e($company['email'] ?? '') ?>" required>
                        </div>

                        <div class="field">
                            <label for="phone">Telefoon</label>
                            <input id="phone" name="phone" type="text" value="<?= e($company['phone'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="city">Plaats</label>
                            <input id="city" name="city" type="text" value="<?= e($company['city'] ?? '') ?>">
                        </div>

                        <div class="field full">
                            <label for="service_area">Werkgebied</label>
                            <textarea id="service_area" name="service_area" placeholder="Bijv. Den Haag, Rijswijk, Delft, Westland"><?= e($company['service_area'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <button class="btn" type="submit"><?= $isNew ? 'Bedrijf aanmaken' : 'Wijzigingen opslaan' ?></button>
                        <a class="btn light" href="/admin/companies.php">Annuleren</a>
                    </div>
                </form>
            </section>

            <?php if (!$isNew): ?>
                <section class="card">
                    <h2>Laatste offerteverzoeken</h2>

                    <?php if (!$requests): ?>
                        <div class="empty">Nog geen offerteverzoeken verstuurd naar dit bedrijf.</div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Aanvraag</th>
                                        <th>Klant</th>
                                        <th>Status</th>
                                        <th>Verstuurd</th>
                                        <th>Deadline</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?= e($request['lead_public_id'] ?? '-') ?></td>
                                            <td>
                                                <?= e($request['lead_name'] ?? '-') ?>
                                                <span class="small"><?= e(trim(($request['postcode'] ?? '') . ' ' . ($request['lead_city'] ?? ''))) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= e($request['status']) ?>">
                                                    <?= e($statusLabels[$request['status']] ?? $request['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="activity-label"><?= e(date('d-m-Y', strtotime((string) $request['requested_at']))) ?></span>
                                                <span class="small"><?= e(date('H:i', strtotime((string) $request['requested_at']))) ?></span>
                                            </td>
                                            <td>
                                                <span class="activity-label"><?= e(date('d-m-Y', strtotime((string) $request['deadline_at']))) ?></span>
                                                <span class="small"><?= e(date('H:i', strtotime((string) $request['deadline_at']))) ?></span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a class="btn light" href="/company/quote.php?request=<?= e($request['public_id']) ?>" target="_blank">Link</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="card">
                    <h2>Laatste offertes</h2>

                    <?php if (!$quotes): ?>
                        <div class="empty">Nog geen offertes ontvangen van dit bedrijf.</div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Aanvraag</th>
                                        <th>Klant</th>
                                        <th>Bedrag</th>
                                        <th>Installatiedatum</th>
                                        <th>Ingediend</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quotes as $quote): ?>
                                        <tr>
                                            <td><?= e($quote['lead_public_id'] ?? '-') ?></td>
                                            <td>
                                                <?= e($quote['lead_name'] ?? '-') ?>
                                                <span class="small"><?= e($quote['lead_city'] ?? '-') ?></span>
                                            </td>
                                            <td class="metric-cell">
                                                <?= $quote['quote_amount'] !== null ? '€ ' . e(number_format((float) $quote['quote_amount'], 2, ',', '.')) : '-' ?>
                                            </td>
                                            <td>
                                                <?= !empty($quote['installation_possible_date']) ? e(date('d-m-Y', strtotime((string) $quote['installation_possible_date']))) : '-' ?>
                                            </td>
                                            <td>
                                                <span class="activity-label"><?= e(date('d-m-Y', strtotime((string) $quote['submitted_at']))) ?></span>
                                                <span class="small"><?= e(date('H:i', strtotime((string) $quote['submitted_at']))) ?></span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a class="btn light" href="/admin/lead.php?id=<?= (int) $quote['lead_id'] ?>">Lead</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <aside>
            <section class="card">
                <h2>Overzicht</h2>

                <div class="meta-list">
                    <div class="meta-row">
                        <span>Status</span>
                        <strong><?= ($company['status'] ?? 'active') === 'active' ? 'Actief' : 'Inactief' ?></strong>
                    </div>
                    <div class="meta-row">
                        <span>Verzoeken</span>
                        <strong><?= (int) ($stats['request_count'] ?? 0) ?></strong>
                    </div>
                    <div class="meta-row">
                        <span>Geopend/geoffreerd</span>
                        <strong><?= (int) ($stats['opened_count'] ?? 0) ?></strong>
                    </div>
                    <div class="meta-row">
                        <span>Offertes</span>
                        <strong><?= (int) ($stats['quote_count'] ?? 0) ?></strong>
                    </div>
                    <div class="meta-row">
                        <span>Verlopen</span>
                        <strong><?= (int) ($stats['expired_count'] ?? 0) ?></strong>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>Opties</h2>

                <div class="actions-stack">
                    <?php if (!$isNew && !empty($company['email'])): ?>
                        <a class="btn" href="mailto:<?= e($company['email']) ?>">E-mail sturen</a>
                    <?php endif; ?>

                    <?php if (!$isNew && !empty($company['phone'])): ?>
                        <a class="btn secondary" href="tel:<?= e($company['phone']) ?>">Bellen</a>
                    <?php endif; ?>

                    <a class="btn light" href="/admin/leads.php">Lead kiezen</a>
                </div>
            </section>
        </aside>
    </div>
</main>

</body>
</html>
