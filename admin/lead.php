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

$requestStatusLabels = [
    'sent' => 'Verstuurd',
    'opened' => 'Geopend',
    'quoted' => 'Offerte ontvangen',
    'declined' => 'Afgewezen',
    'expired' => 'Verlopen',
    'cancelled' => 'Geannuleerd',
];

$leadId = (int) ($_GET['id'] ?? 0);

if ($leadId <= 0) {
    header('Location: /admin/leads.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM leads
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $leadId
]);

$lead = $stmt->fetch();

if (!$lead) {
    header('Location: /admin/leads.php');
    exit;
}

$answersStmt = $pdo->prepare("
    SELECT question_label, answer_label, question_key, answer_value
    FROM lead_answers
    WHERE lead_id = :lead_id
    ORDER BY id ASC
");

$answersStmt->execute([
    ':lead_id' => $leadId
]);

$answers = $answersStmt->fetchAll();

$filesStmt = $pdo->prepare("
    SELECT
        id,
        original_name,
        stored_name,
        file_path,
        mime_type,
        file_size,
        created_at
    FROM lead_files
    WHERE lead_id = :lead_id
    ORDER BY created_at ASC
");

$filesStmt->execute([
    ':lead_id' => $leadId
]);

$files = $filesStmt->fetchAll();

$notesStmt = $pdo->prepare("
    SELECT note, created_at
    FROM lead_notes
    WHERE lead_id = :lead_id
    ORDER BY created_at DESC
");

$notesStmt->execute([
    ':lead_id' => $leadId
]);

$notes = $notesStmt->fetchAll();

$companiesStmt = $pdo->prepare("
    SELECT id, company_name, contact_name, email, city, service_area, status
    FROM companies
    WHERE status = 'active'
    ORDER BY company_name ASC
");

$companiesStmt->execute();

$companies = $companiesStmt->fetchAll();

$requestsStmt = $pdo->prepare("
    SELECT
        lcr.id,
        lcr.public_id,
        lcr.status,
        lcr.requested_at,
        lcr.deadline_at,
        lcr.opened_at,
        lcr.responded_at,
        lcr.admin_note,
        lcr.company_note,
        c.company_name,
        c.email
    FROM lead_company_requests lcr
    INNER JOIN companies c ON c.id = lcr.company_id
    WHERE lcr.lead_id = :lead_id
    ORDER BY lcr.requested_at DESC
");

$requestsStmt->execute([
    ':lead_id' => $leadId
]);

$companyRequests = $requestsStmt->fetchAll();

$quotesStmt = $pdo->prepare("
    SELECT
        cq.*,
        c.company_name,
        c.email AS company_email,
        c.phone AS company_phone,
        lcr.public_id AS request_public_id
    FROM company_quotes cq
    INNER JOIN companies c ON c.id = cq.company_id
    INNER JOIN lead_company_requests lcr ON lcr.id = cq.request_id
    WHERE cq.lead_id = :lead_id
    ORDER BY cq.submitted_at DESC
");

$quotesStmt->execute([
    ':lead_id' => $leadId
]);

$quotes = $quotesStmt->fetchAll();

$activities = [];

foreach ($notes as $note) {
    $activities[] = [
        'type' => 'Notitie',
        'time' => $note['created_at'],
        'text' => $note['note'],
    ];
}

foreach ($companyRequests as $request) {
    $text = 'Offerteverzoek naar ' . $request['company_name'] . '.';
    $text .= "\nStatus: " . ($requestStatusLabels[$request['status']] ?? $request['status']);
    $text .= "\nDeadline: " . date('d-m-Y H:i', strtotime($request['deadline_at']));

    if (!empty($request['admin_note'])) {
        $text .= "\n\nAdmin toelichting:\n" . $request['admin_note'];
    }

    if (!empty($request['company_note'])) {
        $text .= "\n\nReactie bedrijf:\n" . $request['company_note'];
    }

    $activities[] = [
        'type' => 'Offerteverzoek',
        'time' => $request['requested_at'],
        'text' => $text,
    ];
}

foreach ($quotes as $quote) {
    $text = 'Offerte ontvangen van ' . $quote['company_name'] . '.';

    if ($quote['quote_amount'] !== null) {
        $text .= "\nBedrag: € " . number_format((float) $quote['quote_amount'], 2, ',', '.');
    }

    $activities[] = [
        'type' => 'Offerte',
        'time' => $quote['submitted_at'],
        'text' => $text,
    ];
}

usort($activities, function (array $a, array $b): int {
    return strtotime((string) $b['time']) <=> strtotime((string) $a['time']);
});

$success = $_SESSION['lead_admin_success'] ?? null;
$error = $_SESSION['lead_admin_error'] ?? null;
unset($_SESSION['lead_admin_success'], $_SESSION['lead_admin_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?= e($lead['public_id']) ?> - <?= e(APP_NAME) ?></title>
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
    <a class="back" href="/admin/leads.php">← Terug naar leads</a>

    <?php if ($success): ?>
        <div class="notice success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="card">
        <h1><?= e($lead['public_id']) ?></h1>
        <div class="muted">
            Binnengekomen op <?= e(date('d-m-Y H:i', strtotime($lead['created_at']))) ?>
        </div>
        <br>
        <span class="status <?= e($lead['status']) ?>">
            <?= e($statusLabels[$lead['status']] ?? $lead['status']) ?>
        </span>
    </section>

    <div class="layout">
        <div>
            <section class="card">
                <h2>Klantgegevens</h2>

                <div class="info-grid">
                    <div class="info">
                        <span>Naam</span>
                        <strong><?= e($lead['name']) ?></strong>
                    </div>

                    <div class="info">
                        <span>Telefoon</span>
                        <strong><?= e($lead['phone']) ?></strong>
                    </div>

                    <div class="info">
                        <span>E-mail</span>
                        <strong><?= e($lead['email'] ?? '-') ?></strong>
                    </div>

                    <div class="info">
                        <span>Locatie</span>
                        <strong><?= e(trim(($lead['postcode'] ?? '') . ' ' . ($lead['city'] ?? ''))) ?></strong>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>Activiteiten</h2>

                <?php if (!$activities): ?>
                    <p class="muted">Nog geen activiteiten.</p>
                <?php else: ?>
                    <div class="activity-scroll">
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-top">
                                    <div class="activity-type"><?= e($activity['type']) ?></div>
                                    <div class="activity-time"><?= e(date('d-m-Y H:i', strtotime((string) $activity['time']))) ?></div>
                                </div>
                                <div class="activity-text"><?= e($activity['text']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Ontvangen offertes</h2>

                <?php if (!$quotes): ?>
                    <p class="muted">Nog geen offertes ontvangen.</p>
                <?php else: ?>
                    <div class="quote-list">
                        <?php foreach ($quotes as $quote): ?>
                            <div class="quote-card">
                                <div class="quote-head">
                                    <div>
                                        <div class="quote-company"><?= e($quote['company_name']) ?></div>
                                        <div class="quote-meta">
                                            Ingediend op <?= e(date('d-m-Y H:i', strtotime($quote['submitted_at']))) ?>
                                            · status <?= e($quote['status']) ?>
                                        </div>
                                    </div>

                                    <div class="quote-amount">
                                        <?= $quote['quote_amount'] !== null ? '€ ' . number_format((float) $quote['quote_amount'], 2, ',', '.') : 'Geen bedrag' ?>
                                    </div>
                                </div>

                                <div class="quote-actions">
                                    <button
                                        type="button"
                                        class="btn light quote-open-btn"
                                        data-modal-id="quoteModal<?= (int) $quote['id'] ?>"
                                    >
                                        Bekijk offerte
                                    </button>

                                    <button
                                        type="button"
                                        class="btn secondary quote-print-direct"
                                        data-modal-id="quoteModal<?= (int) $quote['id'] ?>"
                                    >
                                        Download/print
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Extra toelichting</h2>

                <?php if (empty($lead['message'])): ?>
                    <p class="muted">Geen extra toelichting ingevuld.</p>
                <?php else: ?>
                    <div class="message-box">
                        <div class="message-text"><?= e($lead['message']) ?></div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Ketelcheck antwoorden</h2>

                <?php if (!$answers): ?>
                    <p class="muted">Geen antwoorden gevonden.</p>
                <?php else: ?>
                    <?php foreach ($answers as $answer): ?>
                        <div class="answer">
                            <span><?= e($answer['question_label']) ?></span>
                            <strong><?= e($answer['answer_label'] ?? $answer['answer_value']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Foto’s</h2>

                <?php if (!$files): ?>
                    <p class="muted">Geen foto’s toegevoegd.</p>
                <?php else: ?>
                    <div class="photo-grid">
                        <?php foreach ($files as $file): ?>
                          <a href="/admin/file.php?id=<?= (int) $file['id'] ?>" target="_blank" class="photo-card">
                              <img src="/admin/file.php?id=<?= (int) $file['id'] ?>" alt="<?= e($file['original_name']) ?>">

                                <div class="photo-card-footer">
                                    <strong><?= e($file['original_name']) ?></strong>
                                    <span>
                                        <?= e(strtoupper(str_replace('image/', '', $file['mime_type'] ?? 'image'))) ?>
                                        <?php if (!empty($file['file_size'])): ?>
                                            · <?= number_format((int) $file['file_size'] / 1024, 0, ',', '.') ?> KB
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Bron & tracking</h2>

                <div class="info-grid">
                    <div class="info">
                        <span>Bron</span>
                        <strong><?= e($lead['source'] ?? '-') ?></strong>
                    </div>

                    <div class="info">
                        <span>Landing page</span>
                        <strong><?= e($lead['landing_page'] ?? '-') ?></strong>
                    </div>

                    <div class="info">
                        <span>Regio</span>
                        <strong>
                            <?= e(trim(($lead['landing_city'] ?? '') . ' ' . ($lead['landing_region'] ?? ''))) ?: '-' ?>
                        </strong>
                    </div>

                    <div class="info">
                        <span>Host</span>
                        <strong><?= e($lead['source_host'] ?? '-') ?></strong>
                    </div>

                    <div class="info">
                        <span>UTM source</span>
                        <strong><?= e($lead['utm_source'] ?? '-') ?></strong>
                    </div>

                    <div class="info">
                        <span>UTM medium</span>
                        <strong><?= e($lead['utm_medium'] ?? '-') ?></strong>
                    </div>

                    <div class="info">
                        <span>UTM campaign</span>
                        <strong><?= e($lead['utm_campaign'] ?? '-') ?></strong>
                    </div>

                    <div class="info">
                        <span>UTM term</span>
                        <strong><?= e($lead['utm_term'] ?? '-') ?></strong>
                    </div>
                </div>
            </section>
        </div>

        <aside>
            <section class="card">
                <h2>Offerteverzoek sturen</h2>

                <?php if (!$companies): ?>
                    <p class="muted">Geen actieve bedrijven gevonden.</p>
                <?php else: ?>
                    <form action="/actions/send_lead_to_company.php" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">

                        <select name="company_id" required>
                            <option value="">Kies bedrijf</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= (int) $company['id'] ?>">
                                    <?= e($company['company_name']) ?>
                                    <?= !empty($company['city']) ? ' - ' . e($company['city']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <textarea name="admin_note" placeholder="Optionele toelichting voor het bedrijf"></textarea>

                        <button class="btn" type="submit">Stuur offerteverzoek</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Verstuurde offerteverzoeken</h2>

                <?php if (!$companyRequests): ?>
                    <p class="muted">Nog geen offerteverzoeken verstuurd.</p>
                <?php else: ?>
                    <?php foreach ($companyRequests as $request): ?>
                        <div class="note">
                            <div class="note-time">
                                <?= e(date('d-m-Y H:i', strtotime($request['requested_at']))) ?>
                                · deadline <?= e(date('d-m-Y H:i', strtotime($request['deadline_at']))) ?>
                            </div>

                            <div class="note-text request-note-text">
    <strong><?= e($request['company_name']) ?></strong><br>
    <span>E-mail: <?= e($request['email']) ?></span><br>
    <span>Status:</span>
    <span class="request-status <?= e($request['status']) ?>">
        <?= e($requestStatusLabels[$request['status']] ?? $request['status']) ?>
    </span>
</div>

                            <?php if (strtotime($request['deadline_at']) < time() && !in_array($request['status'], ['quoted', 'declined', 'cancelled'], true)): ?>
                                <div class="deadline-warning">Deadline is verlopen.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Status wijzigen</h2>

                <form action="/actions/update_lead_status.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">

                    <select name="status" required>
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $lead['status'] === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn" type="submit">Status opslaan</button>
                </form>
            </section>

            <section class="card">
                <h2>Notitie toevoegen</h2>

                <form action="/actions/add_lead_note.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">

                    <textarea name="note" placeholder="Bijv. klant gebeld, offerte doorgestuurd, wacht op foto..." required></textarea>

                    <button class="btn" type="submit">Notitie opslaan</button>
                </form>
            </section>
        </aside>
    </div>
</main>

<?php foreach ($quotes as $quote): ?>
    <div class="modal-backdrop" id="quoteModal<?= (int) $quote['id'] ?>" aria-hidden="true">
        <div class="admin-modal" role="dialog" aria-modal="true">
            <div class="modal-header">
                <div>
                    <h2>Offerte van <?= e($quote['company_name']) ?></h2>
                    <div class="muted">
                        Ingediend op <?= e(date('d-m-Y H:i', strtotime($quote['submitted_at']))) ?>
                    </div>
                </div>

                <button type="button" class="modal-close" data-close-modal>×</button>
            </div>

            <div class="quote-detail-grid">
                <div class="quote-detail-box">
                    <span>Bedrijf</span>
                    <strong><?= e($quote['company_name']) ?></strong>
                </div>

                <div class="quote-detail-box">
                    <span>Bedrag</span>
                    <strong>
                        <?= $quote['quote_amount'] !== null ? '€ ' . number_format((float) $quote['quote_amount'], 2, ',', '.') : 'Geen bedrag opgegeven' ?>
                    </strong>
                </div>

                <div class="quote-detail-box">
                    <span>Mogelijke installatiedatum</span>
                    <strong>
                        <?= !empty($quote['installation_possible_date']) ? e(date('d-m-Y', strtotime($quote['installation_possible_date']))) : 'Niet opgegeven' ?>
                    </strong>
                </div>

                <div class="quote-detail-box">
                    <span>Status</span>
                    <strong><?= e($quote['status']) ?></strong>
                </div>
            </div>

            <div class="quote-section">
                <h3>Omschrijving offerte</h3>
                <p><?= e($quote['quote_description']) ?></p>
            </div>

            <?php if (!empty($quote['warranty_text'])): ?>
                <div class="quote-section">
                    <h3>Garantie</h3>
                    <p><?= e($quote['warranty_text']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($quote['extra_conditions'])): ?>
                <div class="quote-section">
                    <h3>Extra voorwaarden</h3>
                    <p><?= e($quote['extra_conditions']) ?></p>
                </div>
            <?php endif; ?>

            <div class="modal-actions">
                <button type="button" class="btn" data-print-modal>Download/print offerte</button>
                <button type="button" class="btn secondary" data-close-modal>Sluiten</button>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.quote-open-btn').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.modalId);

        if (!modal) {
            return;
        }

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    });
});

document.querySelectorAll('.quote-print-direct').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.modalId);

        if (!modal) {
            return;
        }

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');

        setTimeout(() => {
            window.print();
        }, 150);
    });
});

document.querySelectorAll('[data-close-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = button.closest('.modal-backdrop');

        if (!modal) {
            return;
        }

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    });
});

document.querySelectorAll('[data-print-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        window.print();
    });
});

document.querySelectorAll('.modal-backdrop').forEach((modal) => {
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.modal-backdrop.active').forEach((modal) => {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    });
});
</script>

</body>
</html>
