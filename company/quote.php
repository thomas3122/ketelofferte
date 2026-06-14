<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

$requestPublicId = trim((string) ($_GET['request'] ?? ''));

$request = null;
$lead = null;
$company = null;
$answers = [];
$files = [];
$quote = null;
$errorMessage = null;

if ($requestPublicId === '') {
    $errorMessage = 'Geen geldig offerteverzoek gevonden.';
} else {
    $requestStmt = $pdo->prepare("
        SELECT
            lcr.*,
            l.public_id AS lead_public_id,
            l.name AS lead_name,
            l.phone AS lead_phone,
            l.email AS lead_email,
            l.postcode,
            l.city AS lead_city,
            l.message AS lead_message,
            l.created_at AS lead_created_at,
            c.company_name,
            c.contact_name,
            c.email AS company_email,
            c.phone AS company_phone
        FROM lead_company_requests lcr
        INNER JOIN leads l ON l.id = lcr.lead_id
        INNER JOIN companies c ON c.id = lcr.company_id
        WHERE lcr.public_id = :public_id
        LIMIT 1
    ");

    $requestStmt->execute([
        ':public_id' => $requestPublicId,
    ]);

    $request = $requestStmt->fetch();

    if (!$request) {
        $errorMessage = 'Dit offerteverzoek bestaat niet of is niet meer beschikbaar.';
    } else {
        $deadlineTs = strtotime((string) $request['deadline_at']);
        $isExpired = $deadlineTs !== false && $deadlineTs < time();
        $isFinalStatus = in_array($request['status'], ['quoted', 'declined', 'cancelled', 'expired'], true);

        if ($isExpired && !$isFinalStatus) {
            $expireStmt = $pdo->prepare("
                UPDATE lead_company_requests
                SET status = 'expired'
                WHERE id = :id
                  AND status IN ('sent', 'opened')
            ");

            $expireStmt->execute([
                ':id' => (int) $request['id'],
            ]);

            $request['status'] = 'expired';
        }

        if ($request['status'] === 'sent') {
            $openStmt = $pdo->prepare("
                UPDATE lead_company_requests
                SET status = 'opened',
                    opened_at = COALESCE(opened_at, NOW())
                WHERE id = :id
                  AND status = 'sent'
            ");

            $openStmt->execute([
                ':id' => (int) $request['id'],
            ]);

            $request['status'] = 'opened';
            $request['opened_at'] = date('Y-m-d H:i:s');
        }

        $answersStmt = $pdo->prepare("
            SELECT question_label, answer_label, answer_value
            FROM lead_answers
            WHERE lead_id = :lead_id
            ORDER BY id ASC
        ");

        $answersStmt->execute([
            ':lead_id' => (int) $request['lead_id'],
        ]);

        $answers = $answersStmt->fetchAll();

        $filesStmt = $pdo->prepare("
            SELECT id, original_name, file_path
            FROM lead_files
            WHERE lead_id = :lead_id
            ORDER BY id ASC
        ");

        $filesStmt->execute([
            ':lead_id' => (int) $request['lead_id'],
        ]);

        $files = $filesStmt->fetchAll();

        $quoteStmt = $pdo->prepare("
            SELECT *
            FROM company_quotes
            WHERE request_id = :request_id
            LIMIT 1
        ");

        $quoteStmt->execute([
            ':request_id' => (int) $request['id'],
        ]);

        $quote = $quoteStmt->fetch();
    }
}

$status = $request['status'] ?? null;
$deadlineAt = $request['deadline_at'] ?? null;
$deadlineTs = $deadlineAt ? strtotime((string) $deadlineAt) : false;
$isExpired = $deadlineTs !== false && $deadlineTs < time();
$canSubmit = $request && !$quote && !in_array((string) $status, ['quoted', 'declined', 'cancelled', 'expired'], true);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Offerte indienen | KetelOfferte24.nl</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="company-page">

<header class="company-header">
    <div class="container company-header-inner">
        <a href="/" class="brand" aria-label="KetelOfferte24.nl home">
            <img src="/assets/img/logo.png" alt="KetelOfferte24.nl" class="brand-logo">
            <span class="brand-text">
                KetelOfferte<span>24</span><small>.nl</small>
            </span>
        </a>
    </div>
</header>

<main class="company-shell">
    <?php if ($errorMessage): ?>
        <section class="company-card">
            <div class="company-error"><?= e($errorMessage) ?></div>
        </section>
    <?php else: ?>
        <section class="company-hero">
            <div class="company-kicker">Offerteverzoek</div>
            <h1>Offerte indienen voor cv-ketel aanvraag</h1>
            <p>
                U bent gevraagd om binnen 24 uur een reactie of offerte in te dienen.
                KetelOfferte24.nl controleert de offerte en stuurt deze daarna officieel door naar de klant.
            </p>
        </section>

        <div class="company-layout">
            <div>
                <section class="company-card">
                    <h2>Aanvraaggegevens</h2>

                    <div class="company-info-grid">
                        <div class="company-info">
                            <span>Aanvraagnummer</span>
                            <strong><?= e($request['lead_public_id'] ?? '-') ?></strong>
                        </div>

                        <div class="company-info">
                            <span>Binnengekomen</span>
                            <strong><?= e(date('d-m-Y H:i', strtotime((string) $request['lead_created_at']))) ?></strong>
                        </div>

                        <div class="company-info">
                            <span>Naam klant</span>
                            <strong><?= e($request['lead_name'] ?? '-') ?></strong>
                        </div>

                        <div class="company-info">
                            <span>Telefoon</span>
                            <strong><?= e($request['lead_phone'] ?? '-') ?></strong>
                        </div>

                        <div class="company-info">
                            <span>E-mail</span>
                            <strong><?= e($request['lead_email'] ?? '-') ?></strong>
                        </div>

                        <div class="company-info">
                            <span>Locatie</span>
                            <strong><?= e(trim(($request['postcode'] ?? '') . ' ' . ($request['lead_city'] ?? ''))) ?></strong>
                        </div>
                    </div>
                </section>

                <section class="company-card">
                    <h2>Ketelcheck antwoorden</h2>

                    <?php if (!$answers): ?>
                        <p class="company-muted">Geen antwoorden gevonden.</p>
                    <?php else: ?>
                        <?php foreach ($answers as $answer): ?>
                            <div class="company-answer">
                                <span><?= e($answer['question_label']) ?></span>
                                <strong><?= e($answer['answer_label'] ?: $answer['answer_value']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <section class="company-card">
                    <h2>Extra toelichting klant</h2>

                    <?php if (empty($request['lead_message'])): ?>
                        <p class="company-muted">Geen extra toelichting ingevuld.</p>
                    <?php else: ?>
                        <div class="company-message"><?= e($request['lead_message']) ?></div>
                    <?php endif; ?>
                </section>

                <section class="company-card">
                    <h2>Foto’s</h2>

                    <?php if (!$files): ?>
                        <p class="company-muted">Geen foto’s toegevoegd.</p>
                    <?php else: ?>
                        <div class="company-photo-grid">
                            <?php foreach ($files as $file): ?>
                              <a href="/company/file.php?request=<?= e($requestPublicId) ?>&file=<?= (int) $file['id'] ?>" target="_blank" class="company-photo">
                                <img src="/company/file.php?request=<?= e($requestPublicId) ?>&file=<?= (int) $file['id'] ?>" alt="<?= e($file['original_name']) ?>">
                              </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <aside>
                <section class="company-card">
                    <h2>Offerte indienen</h2>

                    <?php if ($status === 'expired'): ?>
                        <div class="company-deadline bad">
                            De deadline voor dit offerteverzoek is verlopen.
                        </div>
                    <?php elseif ($quote): ?>
                        <div class="company-deadline good">
                            Er is al een offerte ingediend voor dit verzoek.
                        </div>
                    <?php elseif ($deadlineAt): ?>
                        <div class="company-deadline">
                            Deadline: <?= e(date('d-m-Y H:i', strtotime((string) $deadlineAt))) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($request['admin_note'])): ?>
                        <div class="company-message" style="margin-bottom: 16px;">
                            <?= e($request['admin_note']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($canSubmit): ?>
                        <form class="company-form" action="/actions/submit_company_quote.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_public_id" value="<?= e($requestPublicId) ?>">

                            <div class="company-field">
                                <label for="quote_amount">Offertebedrag</label>
                                <input
                                    id="quote_amount"
                                    name="quote_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="Bijv. 2450.00"
                                >
                            </div>

                            <div class="company-field">
                                <label for="quote_description">Omschrijving offerte *</label>
                                <textarea
                                    id="quote_description"
                                    name="quote_description"
                                    placeholder="Beschrijf wat er geleverd/geplaatst wordt, inclusief eventuele werkzaamheden."
                                    required
                                ></textarea>
                            </div>

                            <div class="company-field">
                                <label for="installation_possible_date">Mogelijke installatiedatum</label>
                                <input
                                    id="installation_possible_date"
                                    name="installation_possible_date"
                                    type="date"
                                >
                            </div>

                            <div class="company-field">
                                <label for="warranty_text">Garantie</label>
                                <textarea
                                    id="warranty_text"
                                    name="warranty_text"
                                    placeholder="Bijv. fabrieksgarantie, installatiegarantie, onderhoudsvoorwaarden..."
                                ></textarea>
                            </div>

                            <div class="company-field">
                                <label for="extra_conditions">Extra voorwaarden</label>
                                <textarea
                                    id="extra_conditions"
                                    name="extra_conditions"
                                    placeholder="Bijv. onder voorbehoud van inspectie, bereikbaarheid, meerwerk..."
                                ></textarea>
                            </div>

                            <div class="company-field">
                                <label for="company_note">Interne toelichting aan KetelOfferte24.nl</label>
                                <textarea
                                    id="company_note"
                                    name="company_note"
                                    placeholder="Niet voor de klant, alleen voor KetelOfferte24.nl."
                                ></textarea>
                            </div>

                            <div class="company-actions">
                                <button class="btn btn-primary" type="submit">Offerte indienen</button>
                            </div>

                            <p class="company-footer-note">
                                De offerte wordt niet direct naar de klant gestuurd. KetelOfferte24.nl controleert deze eerst.
                            </p>
                        </form>
                    <?php else: ?>
                        <p class="company-muted">
                            U kunt via deze link geen nieuwe offerte meer indienen.
                        </p>
                    <?php endif; ?>
                </section>
            </aside>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
