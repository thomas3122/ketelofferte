<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

function clean_string(?string $value): string
{
    return trim((string) $value);
}

$requestPublicId = clean_string($_POST['request_public_id'] ?? '');
$quoteAmountRaw = clean_string($_POST['quote_amount'] ?? '');
$quoteDescription = clean_string($_POST['quote_description'] ?? '');
$installationDate = clean_string($_POST['installation_possible_date'] ?? '');
$warrantyText = clean_string($_POST['warranty_text'] ?? '');
$extraConditions = clean_string($_POST['extra_conditions'] ?? '');
$companyNote = clean_string($_POST['company_note'] ?? '');

if ($requestPublicId === '' || !preg_match('/^REQ-[A-F0-9]{10,32}$/i', $requestPublicId)) {
    header('Location: /');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
    exit;
}

if ($quoteDescription === '') {
    header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
    exit;
}

$quoteAmount = null;

if ($quoteAmountRaw !== '') {
    $quoteAmountNormalized = str_replace(',', '.', $quoteAmountRaw);

    if (!is_numeric($quoteAmountNormalized) || (float) $quoteAmountNormalized < 0) {
        header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
        exit;
    }

    $quoteAmount = number_format((float) $quoteAmountNormalized, 2, '.', '');
}

if ($installationDate !== '') {
    $dateCheck = DateTime::createFromFormat('Y-m-d', $installationDate);

    if (!$dateCheck || $dateCheck->format('Y-m-d') !== $installationDate) {
        header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
        exit;
    }
} else {
    $installationDate = null;
}

try {
    $requestStmt = $pdo->prepare("
        SELECT
            lcr.*,
            l.public_id AS lead_public_id,
            l.name AS lead_name,
            l.email AS lead_email,
            l.phone AS lead_phone,
            l.postcode,
            l.city AS lead_city,
            c.company_name,
            c.contact_name,
            c.email AS company_email
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
        header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
        exit;
    }

    if (in_array($request['status'], ['quoted', 'declined', 'expired', 'cancelled'], true)) {
        header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
        exit;
    }

    $deadlineTs = strtotime((string) $request['deadline_at']);

    if ($deadlineTs !== false && $deadlineTs < time()) {
        $expireStmt = $pdo->prepare("
            UPDATE lead_company_requests
            SET status = 'expired'
            WHERE id = :id
              AND status IN ('sent', 'opened')
        ");

        $expireStmt->execute([
            ':id' => (int) $request['id'],
        ]);

        header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
        exit;
    }

    $existingQuoteStmt = $pdo->prepare("
        SELECT id
        FROM company_quotes
        WHERE request_id = :request_id
        LIMIT 1
    ");

    $existingQuoteStmt->execute([
        ':request_id' => (int) $request['id'],
    ]);

    if ($existingQuoteStmt->fetch()) {
        header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
        exit;
    }

    $pdo->beginTransaction();

    $quoteStmt = $pdo->prepare("
        INSERT INTO company_quotes (
            request_id,
            lead_id,
            company_id,
            quote_amount,
            quote_description,
            installation_possible_date,
            warranty_text,
            extra_conditions,
            status
        ) VALUES (
            :request_id,
            :lead_id,
            :company_id,
            :quote_amount,
            :quote_description,
            :installation_possible_date,
            :warranty_text,
            :extra_conditions,
            'submitted'
        )
    ");

    $quoteStmt->execute([
        ':request_id' => (int) $request['id'],
        ':lead_id' => (int) $request['lead_id'],
        ':company_id' => (int) $request['company_id'],
        ':quote_amount' => $quoteAmount,
        ':quote_description' => $quoteDescription,
        ':installation_possible_date' => $installationDate,
        ':warranty_text' => $warrantyText ?: null,
        ':extra_conditions' => $extraConditions ?: null,
    ]);

    $updateRequestStmt = $pdo->prepare("
        UPDATE lead_company_requests
        SET status = 'quoted',
            responded_at = NOW(),
            company_note = :company_note
        WHERE id = :id
    ");

    $updateRequestStmt->execute([
        ':id' => (int) $request['id'],
        ':company_note' => $companyNote ?: null,
    ]);

    $updateLeadStmt = $pdo->prepare("
        UPDATE leads
        SET status = 'quote_made'
        WHERE id = :id
    ");

    $updateLeadStmt->execute([
        ':id' => (int) $request['lead_id'],
    ]);

    $noteText = 'Offerte ontvangen van ' . $request['company_name'] . '.';

    if ($quoteAmount !== null) {
        $noteText .= "\nBedrag: € " . number_format((float) $quoteAmount, 2, ',', '.');
    }

    if ($installationDate !== null) {
        $noteText .= "\nMogelijke installatiedatum: " . date('d-m-Y', strtotime($installationDate));
    }

    if ($companyNote !== '') {
        $noteText .= "\n\nToelichting bedrijf:\n" . $companyNote;
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
        ':lead_id' => (int) $request['lead_id'],
        ':note' => $noteText,
    ]);

    $pdo->commit();

    if (defined('ADMIN_NOTIFICATION_EMAIL')) {
        $adminSubject = 'Offerte ontvangen: ' . ($request['lead_public_id'] ?? '');

        $adminBody = "Er is een offerte ingediend door " . $request['company_name'] . ".\n\n";
        $adminBody .= "Aanvraagnummer: " . ($request['lead_public_id'] ?? '-') . "\n";
        $adminBody .= "Klant: " . ($request['lead_name'] ?? '-') . "\n";
        $adminBody .= "Locatie: " . trim(($request['postcode'] ?? '') . ' ' . ($request['lead_city'] ?? '')) . "\n";

        if ($quoteAmount !== null) {
            $adminBody .= "Bedrag: € " . number_format((float) $quoteAmount, 2, ',', '.') . "\n";
        }

        if ($installationDate !== null) {
            $adminBody .= "Mogelijke installatiedatum: " . date('d-m-Y', strtotime($installationDate)) . "\n";
        }

        $adminBody .= "\nOmschrijving offerte:\n";
        $adminBody .= $quoteDescription . "\n\n";

        if ($warrantyText !== '') {
            $adminBody .= "Garantie:\n" . $warrantyText . "\n\n";
        }

        if ($extraConditions !== '') {
            $adminBody .= "Extra voorwaarden:\n" . $extraConditions . "\n\n";
        }

        if ($companyNote !== '') {
            $adminBody .= "Interne toelichting bedrijf:\n" . $companyNote . "\n\n";
        }

        $adminBody .= "Bekijk lead:\n";
        $adminBody .= APP_URL . "/admin/lead.php?id=" . (int) $request['lead_id'] . "\n";

        send_app_mail(
            ADMIN_NOTIFICATION_EMAIL,
            $adminSubject,
            $adminBody,
            $request['company_email'] ?? null
        );
    }

    header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: /company/quote.php?request=' . urlencode($requestPublicId));
    exit;
}
