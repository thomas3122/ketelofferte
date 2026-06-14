<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$questions = require __DIR__ . '/../config/questions.php';

function clean_string(?string $value): string
{
    return trim((string) $value);
}

function generate_public_id(): string
{
    return 'LEAD-' . strtoupper(bin2hex(random_bytes(4)));
}

function find_question_by_key(array $questions, string $key): ?array
{
    foreach ($questions as $question) {
        if (($question['key'] ?? '') === $key) {
            return $question;
        }
    }

    return null;
}

function find_option_label(array $question, string $value): ?string
{
    foreach (($question['options'] ?? []) as $option) {
        if (($option['value'] ?? '') === $value) {
            return $option['label'] ?? null;
        }
    }

    return null;
}

function get_landing_page(): string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    if ($referer !== '') {
        return substr($referer, 0, 255);
    }

    return '/';
}

function get_error_redirect(): string
{
    $slug = trim((string) ($_POST['landing_slug'] ?? ''));

    if ($slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug)) {
        return '/' . $slug . '/#ketelcheck';
    }

    return '/#ketelcheck';
}

function resolve_posted_landing_page(PDO $pdo): ?array
{
    $landingPageId = (int) ($_POST['landing_page_id'] ?? 0);
    $landingSlug = clean_string($_POST['landing_slug'] ?? '');

    if ($landingPageId > 0) {
        $stmt = $pdo->prepare("
            SELECT id, slug, city, region
            FROM landing_pages
            WHERE id = :id
              AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $landingPageId,
        ]);

        $page = $stmt->fetch();

        if ($page) {
            return $page;
        }
    }

    if ($landingSlug !== '') {
        $stmt = $pdo->prepare("
            SELECT id, slug, city, region
            FROM landing_pages
            WHERE slug = :slug
              AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([
            ':slug' => $landingSlug,
        ]);

        $page = $stmt->fetch();

        if ($page) {
            return $page;
        }
    }

    return null;
}

function normalize_files_array(array $files): array
{
    $normalized = [];

    if (!isset($files['name'])) {
        return $normalized;
    }

    if (is_array($files['name'])) {
        foreach ($files['name'] as $index => $name) {
            $normalized[] = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }

    return [
        [
            'name' => $files['name'] ?? '',
            'type' => $files['type'] ?? '',
            'tmp_name' => $files['tmp_name'] ?? '',
            'error' => $files['error'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'] ?? 0,
        ]
    ];
}

function get_extension_from_mime(string $mimeType): ?string
{
    return match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => null,
    };
}

$errorRedirect = get_error_redirect();

if (!verify_csrf_token($_POST['csrf_token'] ?? null) || clean_string($_POST['website'] ?? '') !== '') {
    $_SESSION['lead_error'] = 'De aanvraag kon niet veilig worden verwerkt. Probeer het opnieuw.';
    header('Location: ' . $errorRedirect);
    exit;
}

// Rate limiting tegen geautomatiseerde spam-leads: max 10 inzendingen per IP per uur.
$leadRateKey = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$leadRateWindow = 3600;

if (!rate_limit_allow('lead_attempts', $leadRateKey, 10, $leadRateWindow)) {
    $_SESSION['lead_error'] = 'U heeft te veel aanvragen verstuurd. Probeer het later opnieuw of bel ons.';
    header('Location: ' . $errorRedirect);
    exit;
}

rate_limit_hit('lead_attempts', $leadRateKey, $leadRateWindow);

$naam = clean_string($_POST['naam'] ?? '');
$telefoon = clean_string($_POST['telefoon'] ?? '');
$email = clean_string($_POST['email'] ?? '');
$postcode = clean_string($_POST['postcode'] ?? '');
$plaats = clean_string($_POST['plaats'] ?? '');
$message = clean_string($_POST['message'] ?? '');

$requiredFields = [
    'naam' => $naam,
    'telefoon' => $telefoon,
    'email' => $email,
    'postcode' => $postcode,
    'plaats' => $plaats,
];

foreach ($requiredFields as $fieldValue) {
    if ($fieldValue === '') {
        $_SESSION['lead_error'] = 'Niet alle verplichte velden zijn ingevuld.';
        header('Location: ' . $errorRedirect);
        exit;
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['lead_error'] = 'Vul een geldig e-mailadres in.';
    header('Location: ' . $errorRedirect);
    exit;
}

$answersToSave = [];

foreach ($questions as $question) {
    if (($question['type'] ?? '') !== 'options') {
        continue;
    }

    if (!($question['required'] ?? false)) {
        continue;
    }

    $key = (string) ($question['key'] ?? '');
    $value = clean_string($_POST[$key] ?? '');

    if ($value === '') {
        $_SESSION['lead_error'] = 'Niet alle vragen zijn beantwoord.';
        header('Location: ' . $errorRedirect);
        exit;
    }

    $label = find_option_label($question, $value);

    if ($label === null) {
        $_SESSION['lead_error'] = 'Ongeldige keuze ontvangen.';
        header('Location: ' . $errorRedirect);
        exit;
    }

    $answersToSave[] = [
        'question_key' => $key,
        'question_label' => $question['label'],
        'answer_value' => $value,
        'answer_label' => $label,
    ];
}

$validUploads = [];
$uploadedFiles = normalize_files_array($_FILES['photos'] ?? []);

$maxFiles = 5;
$maxFileSize = 5 * 1024 * 1024;

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
];

$actualUploadedFiles = array_filter($uploadedFiles, function (array $file): bool {
    return ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
});

if (count($actualUploadedFiles) > $maxFiles) {
    $_SESSION['lead_error'] = 'U kunt maximaal 5 foto’s uploaden.';
    header('Location: ' . $errorRedirect);
    exit;
}

if ($actualUploadedFiles) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($actualUploadedFiles as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['lead_error'] = 'Er ging iets mis bij het uploaden van een foto.';
            header('Location: ' . $errorRedirect);
            exit;
        }

        if ((int) $file['size'] > $maxFileSize) {
            $_SESSION['lead_error'] = 'Een foto mag maximaal 5 MB zijn.';
            header('Location: ' . $errorRedirect);
            exit;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            $_SESSION['lead_error'] = 'Ongeldige upload ontvangen.';
            header('Location: ' . $errorRedirect);
            exit;
        }

        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $_SESSION['lead_error'] = 'Alleen JPG, PNG en WEBP foto’s zijn toegestaan.';
            header('Location: ' . $errorRedirect);
            exit;
        }

        $extension = get_extension_from_mime($mimeType);

        if ($extension === null) {
            $_SESSION['lead_error'] = 'Bestandstype niet toegestaan.';
            header('Location: ' . $errorRedirect);
            exit;
        }

        $validUploads[] = [
            'original_name' => basename((string) $file['name']),
            'tmp_name' => $file['tmp_name'],
            'mime_type' => $mimeType,
            'size' => (int) $file['size'],
            'extension' => $extension,
        ];
    }
}

$utmSource = clean_string($_POST['utm_source'] ?? $_GET['utm_source'] ?? '');
$utmMedium = clean_string($_POST['utm_medium'] ?? $_GET['utm_medium'] ?? '');
$utmCampaign = clean_string($_POST['utm_campaign'] ?? $_GET['utm_campaign'] ?? '');
$utmTerm = clean_string($_POST['utm_term'] ?? $_GET['utm_term'] ?? '');
$utmContent = clean_string($_POST['utm_content'] ?? $_GET['utm_content'] ?? '');

$source = $utmSource !== '' ? $utmSource : 'website';
$landingPage = get_landing_page();

$resolvedLandingPage = resolve_posted_landing_page($pdo);

$landingPageId = $resolvedLandingPage ? (int) $resolvedLandingPage['id'] : null;
$landingSlug = $resolvedLandingPage ? (string) $resolvedLandingPage['slug'] : null;
$landingCity = $resolvedLandingPage ? (string) $resolvedLandingPage['city'] : null;
$landingRegion = $resolvedLandingPage ? (string) $resolvedLandingPage['region'] : null;

$sourceHost = clean_string($_POST['source_host'] ?? $_SERVER['HTTP_HOST'] ?? '');
$sourceHost = substr($sourceHost, 0, 255);

$uploadBaseDir = __DIR__ . '/../storage/uploads/leads';

if (!is_dir($uploadBaseDir)) {
    mkdir($uploadBaseDir, 0755, true);
}

try {
    $pdo->beginTransaction();

    $publicId = generate_public_id();

    $leadStmt = $pdo->prepare("
        INSERT INTO leads (
            landing_page_id,
            landing_slug,
            landing_city,
            landing_region,
            source_host,
            public_id,
            name,
            phone,
            email,
            postcode,
            city,
            message,
            source,
            landing_page,
            utm_source,
            utm_medium,
            utm_campaign,
            utm_term,
            utm_content,
            status
        ) VALUES (
            :landing_page_id,
            :landing_slug,
            :landing_city,
            :landing_region,
            :source_host,
            :public_id,
            :name,
            :phone,
            :email,
            :postcode,
            :city,
            :message,
            :source,
            :landing_page,
            :utm_source,
            :utm_medium,
            :utm_campaign,
            :utm_term,
            :utm_content,
            'new'
        )
    ");

    $leadStmt->execute([
        ':landing_page_id' => $landingPageId,
        ':landing_slug' => $landingSlug,
        ':landing_city' => $landingCity,
        ':landing_region' => $landingRegion,
        ':source_host' => $sourceHost ?: null,
        ':public_id' => $publicId,
        ':name' => $naam,
        ':phone' => $telefoon,
        ':email' => $email,
        ':postcode' => $postcode,
        ':city' => $plaats,
        ':message' => $message ?: null,
        ':source' => $source,
        ':landing_page' => $landingPage,
        ':utm_source' => $utmSource ?: null,
        ':utm_medium' => $utmMedium ?: null,
        ':utm_campaign' => $utmCampaign ?: null,
        ':utm_term' => $utmTerm ?: null,
        ':utm_content' => $utmContent ?: null,
    ]);

    $leadId = (int) $pdo->lastInsertId();

    $answerStmt = $pdo->prepare("
        INSERT INTO lead_answers (
            lead_id,
            question_key,
            question_label,
            answer_value,
            answer_label
        ) VALUES (
            :lead_id,
            :question_key,
            :question_label,
            :answer_value,
            :answer_label
        )
    ");

    foreach ($answersToSave as $answer) {
        $answerStmt->execute([
            ':lead_id' => $leadId,
            ':question_key' => $answer['question_key'],
            ':question_label' => $answer['question_label'],
            ':answer_value' => $answer['answer_value'],
            ':answer_label' => $answer['answer_label'],
        ]);
    }

    if ($validUploads) {
        $fileStmt = $pdo->prepare("
            INSERT INTO lead_files (
                lead_id,
                original_name,
                stored_name,
                file_path,
                mime_type,
                file_size
            ) VALUES (
                :lead_id,
                :original_name,
                :stored_name,
                :file_path,
                :mime_type,
                :file_size
            )
        ");

        foreach ($validUploads as $upload) {
            $storedName = $publicId . '-' . bin2hex(random_bytes(8)) . '.' . $upload['extension'];
            $absoluteTargetPath = $uploadBaseDir . '/' . $storedName;
            $relativePath = '/storage/uploads/leads/' . $storedName;

            if (!move_uploaded_file($upload['tmp_name'], $absoluteTargetPath)) {
                throw new RuntimeException('Upload kon niet worden opgeslagen.');
            }

            $fileStmt->execute([
                ':lead_id' => $leadId,
                ':original_name' => $upload['original_name'],
                ':stored_name' => $storedName,
                ':file_path' => $relativePath,
                ':mime_type' => $upload['mime_type'],
                ':file_size' => $upload['size'],
            ]);
        }
    }

    $pdo->commit();

    $adminSubject = 'Nieuwe ketelcheck aanvraag: ' . $publicId;

    $adminBody = "Er is een nieuwe aanvraag binnengekomen.\n\n";
    $adminBody .= "Aanvraagnummer: {$publicId}\n";
    $adminBody .= "Naam: {$naam}\n";
    $adminBody .= "Telefoon: {$telefoon}\n";
    $adminBody .= "E-mail: {$email}\n";
    $adminBody .= "Postcode: {$postcode}\n";
    $adminBody .= "Plaats: {$plaats}\n";

    if ($landingCity !== null) {
        $adminBody .= "Regiopagina: {$landingCity}\n";
    }

    if ($landingSlug !== null) {
        $adminBody .= "Landing slug: {$landingSlug}\n";
    }

    if ($sourceHost !== '') {
        $adminBody .= "Host: {$sourceHost}\n";
    }

    if ($utmSource !== '') {
        $adminBody .= "UTM source: {$utmSource}\n";
    }

    if ($utmMedium !== '') {
        $adminBody .= "UTM medium: {$utmMedium}\n";
    }

    if ($utmCampaign !== '') {
        $adminBody .= "Campagne: {$utmCampaign}\n";
    }

    if ($utmTerm !== '') {
        $adminBody .= "Zoekterm/keyword: {$utmTerm}\n";
    }

    if ($utmContent !== '') {
        $adminBody .= "Advertentie/content: {$utmContent}\n";
    }

    if (!empty($message)) {
        $adminBody .= "\nExtra toelichting:\n{$message}\n";
    }

    $adminBody .= "\nAntwoorden:\n";

    foreach ($answersToSave as $answer) {
        $adminBody .= "- {$answer['question_label']}: {$answer['answer_label']}\n";
    }

    if (!empty($validUploads)) {
        $adminBody .= "\nAantal foto’s toegevoegd: " . count($validUploads) . "\n";
    }

    $adminBody .= "\nBekijk de aanvraag in het adminpaneel:\n";
    $adminBody .= APP_URL . "/admin/lead.php?id={$leadId}\n";

    if (defined('ADMIN_NOTIFICATION_EMAIL')) {
        send_app_mail(
            ADMIN_NOTIFICATION_EMAIL,
            $adminSubject,
            $adminBody,
            $email
        );
    }

    $customerSubject = 'Uw aanvraag is ontvangen - ' . $publicId;

    $customerBody = "Beste {$naam},\n\n";
    $customerBody .= "Bedankt voor uw aanvraag via KetelOfferte24.nl.\n\n";
    $customerBody .= "Wij hebben uw ketelcheck ontvangen en bekijken uw situatie. ";
    $customerBody .= "U ontvangt binnen 24 uur duidelijkheid, advies of een passende offerte.\n\n";
    $customerBody .= "Aanvraagnummer: {$publicId}\n\n";
    $customerBody .= "Uw gegevens:\n";
    $customerBody .= "Naam: {$naam}\n";
    $customerBody .= "Telefoon: {$telefoon}\n";
    $customerBody .= "E-mail: {$email}\n";
    $customerBody .= "Locatie: {$postcode} {$plaats}\n\n";

    $customerBody .= "Uw ketelcheck:\n";

    foreach ($answersToSave as $answer) {
        $customerBody .= "- {$answer['question_label']}: {$answer['answer_label']}\n";
    }

    if (!empty($message)) {
        $customerBody .= "\nExtra toelichting:\n{$message}\n";
    }

    if (!empty($validUploads)) {
        $customerBody .= "\nU heeft " . count($validUploads) . " foto('s) toegevoegd aan uw aanvraag.\n";
    }

    $customerBody .= "\nMet vriendelijke groet,\n";
    $customerBody .= "KetelOfferte24.nl\n";

    send_app_mail(
        $email,
        $customerSubject,
        $customerBody
    );

    $_SESSION['lead_success_public_id'] = $publicId;

    header('Location: /bedankt.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Lead submission failed: ' . $e->getMessage());
    $_SESSION['lead_error'] = 'Er ging iets mis bij het versturen van uw aanvraag. Probeer het opnieuw of neem contact op.';

    header('Location: ' . $errorRedirect);
    exit;
}
