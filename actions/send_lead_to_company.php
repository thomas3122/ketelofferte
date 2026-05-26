<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/leads.php');
    exit;
}

require_valid_csrf_token('/admin/leads.php', 'lead_admin_error');

function clean_string(?string $value): string
{
    return trim((string) $value);
}

function generate_request_public_id(): string
{
    return 'REQ-' . strtoupper(bin2hex(random_bytes(16)));
}

$leadId = (int) ($_POST['lead_id'] ?? 0);
$companyId = (int) ($_POST['company_id'] ?? 0);
$adminNote = clean_string($_POST['admin_note'] ?? '');

if ($leadId <= 0 || $companyId <= 0) {
    $_SESSION['lead_admin_error'] = 'Lead of bedrijf ontbreekt.';
    header('Location: /admin/leads.php');
    exit;
}

try {
    $leadStmt = $pdo->prepare("
        SELECT *
        FROM leads
        WHERE id = :id
        LIMIT 1
    ");

    $leadStmt->execute([
        ':id' => $leadId,
    ]);

    $lead = $leadStmt->fetch();

    if (!$lead) {
        $_SESSION['lead_admin_error'] = 'Lead niet gevonden.';
        header('Location: /admin/leads.php');
        exit;
    }

    $companyStmt = $pdo->prepare("
        SELECT *
        FROM companies
        WHERE id = :id
          AND status = 'active'
        LIMIT 1
    ");

    $companyStmt->execute([
        ':id' => $companyId,
    ]);

    $company = $companyStmt->fetch();

    if (!$company) {
        $_SESSION['lead_admin_error'] = 'Actief bedrijf niet gevonden.';
        header('Location: /admin/lead.php?id=' . $leadId);
        exit;
    }

    if (empty($company['email']) || !filter_var($company['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['lead_admin_error'] = 'Dit bedrijf heeft geen geldig e-mailadres.';
        header('Location: /admin/lead.php?id=' . $leadId);
        exit;
    }

    $existingStmt = $pdo->prepare("
        SELECT id
        FROM lead_company_requests
        WHERE lead_id = :lead_id
          AND company_id = :company_id
          AND status IN ('sent', 'opened', 'quoted')
        LIMIT 1
    ");

    $existingStmt->execute([
        ':lead_id' => $leadId,
        ':company_id' => $companyId,
    ]);

    if ($existingStmt->fetch()) {
        $_SESSION['lead_admin_error'] = 'Deze lead is al naar dit bedrijf doorgestuurd.';
        header('Location: /admin/lead.php?id=' . $leadId);
        exit;
    }

    $answersStmt = $pdo->prepare("
        SELECT question_label, answer_label, answer_value
        FROM lead_answers
        WHERE lead_id = :lead_id
        ORDER BY id ASC
    ");

    $answersStmt->execute([
        ':lead_id' => $leadId,
    ]);

    $answers = $answersStmt->fetchAll();

    $filesStmt = $pdo->prepare("
        SELECT file_path
        FROM lead_files
        WHERE lead_id = :lead_id
        ORDER BY id ASC
    ");

    $filesStmt->execute([
        ':lead_id' => $leadId,
    ]);

    $files = $filesStmt->fetchAll();

    $pdo->beginTransaction();

    $requestPublicId = generate_request_public_id();
    $deadlineAt = (new DateTime('+24 hours'))->format('Y-m-d H:i:s');

    $insertStmt = $pdo->prepare("
        INSERT INTO lead_company_requests (
            lead_id,
            company_id,
            public_id,
            status,
            deadline_at,
            admin_note
        ) VALUES (
            :lead_id,
            :company_id,
            :public_id,
            'sent',
            :deadline_at,
            :admin_note
        )
    ");

    $insertStmt->execute([
        ':lead_id' => $leadId,
        ':company_id' => $companyId,
        ':public_id' => $requestPublicId,
        ':deadline_at' => $deadlineAt,
        ':admin_note' => $adminNote ?: null,
    ]);

    $updateLeadStmt = $pdo->prepare("
        UPDATE leads
        SET status = 'sent_to_company'
        WHERE id = :id
    ");

    $updateLeadStmt->execute([
        ':id' => $leadId,
    ]);

    $noteText = 'Offerteverzoek doorgestuurd naar ' . $company['company_name'] . '. Deadline: ' . date('d-m-Y H:i', strtotime($deadlineAt)) . '.';

    if ($adminNote !== '') {
        $noteText .= "\n\nToelichting:\n" . $adminNote;
    }

    $noteStmt = $pdo->prepare("
        INSERT INTO lead_notes (
            lead_id,
            note
        ) VALUES (
            :lead_id,
            :note
        )
    ");

    $noteStmt->execute([
        ':lead_id' => $leadId,
        ':note' => $noteText,
    ]);

    $pdo->commit();

    $quoteLink = APP_URL . '/company/quote.php?request=' . urlencode($requestPublicId);

    $subject = 'Offerteverzoek cv-ketel - reactie binnen 24 uur gevraagd';

    $body = "Beste " . ($company['contact_name'] ?: $company['company_name']) . ",\n\n";
    $body .= "Er staat een nieuw offerteverzoek klaar via KetelOfferte24.nl.\n\n";
    $body .= "Belangrijk: wij werken met een 24-uurs belofte richting de klant. ";
    $body .= "Dien daarom uiterlijk vóór " . date('d-m-Y H:i', strtotime($deadlineAt)) . " een reactie/offerte in.\n\n";

    $body .= "Aanvraagnummer: " . ($lead['public_id'] ?? '-') . "\n";
    $body .= "Locatie: " . trim(($lead['postcode'] ?? '') . ' ' . ($lead['city'] ?? '')) . "\n";
    $body .= "Naam klant: " . ($lead['name'] ?? '-') . "\n";
    $body .= "Telefoon klant: " . ($lead['phone'] ?? '-') . "\n";
    $body .= "E-mail klant: " . ($lead['email'] ?? '-') . "\n\n";

    if (!empty($lead['message'])) {
        $body .= "Extra toelichting klant:\n";
        $body .= $lead['message'] . "\n\n";
    }

    if ($answers) {
        $body .= "Ketelcheck antwoorden:\n";

        foreach ($answers as $answer) {
            $answerLabel = $answer['answer_label'] ?: $answer['answer_value'];
            $body .= "- " . $answer['question_label'] . ': ' . $answerLabel . "\n";
        }

        $body .= "\n";
    }

    if ($files) {
        $body .= "Er zijn " . count($files) . " foto('s) toegevoegd aan deze aanvraag.\n";
        $body .= "Deze zijn later zichtbaar via de offertepagina.\n\n";
    }

    if ($adminNote !== '') {
        $body .= "Toelichting vanuit KetelOfferte24.nl:\n";
        $body .= $adminNote . "\n\n";
    }

    $body .= "Offerte indienen:\n";
    $body .= $quoteLink . "\n\n";

    $body .= "Met vriendelijke groet,\n";
    $body .= "KetelOfferte24.nl\n";

    send_app_mail(
        $company['email'],
        $subject,
        $body,
        defined('ADMIN_NOTIFICATION_EMAIL') ? ADMIN_NOTIFICATION_EMAIL : null
    );

    $_SESSION['lead_admin_success'] = 'Offerteverzoek is verstuurd naar ' . $company['company_name'] . '.';

    header('Location: /admin/lead.php?id=' . $leadId);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $_SESSION['lead_admin_error'] = 'Fout bij doorsturen: ' . $e->getMessage();
    } else {
        $_SESSION['lead_admin_error'] = 'Er ging iets mis bij het doorsturen naar het bedrijf.';
    }

    header('Location: /admin/lead.php?id=' . $leadId);
    exit;
}
