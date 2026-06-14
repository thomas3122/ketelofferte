<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$requestPublicId = trim((string) ($_GET['request'] ?? ''));
$fileId = (int) ($_GET['file'] ?? 0);

if ($requestPublicId === '' || $fileId <= 0) {
    http_response_code(404);
    exit('Bestand niet gevonden.');
}

$stmt = $pdo->prepare("
    SELECT
        lf.id,
        lf.lead_id,
        lf.original_name,
        lf.stored_name,
        lf.file_path,
        lf.mime_type,
        lf.file_size
    FROM lead_files lf
    INNER JOIN lead_company_requests lcr ON lcr.lead_id = lf.lead_id
    WHERE lf.id = :file_id
      AND lcr.public_id = :request_public_id
      AND lcr.status IN ('sent', 'opened', 'quoted')
    LIMIT 1
");

$stmt->execute([
    ':file_id' => $fileId,
    ':request_public_id' => $requestPublicId,
]);

$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('Bestand niet gevonden.');
}

$storageBase = realpath(__DIR__ . '/../storage/uploads/leads');
$absolutePath = realpath(__DIR__ . '/..' . $file['file_path']);

if (
    $storageBase === false
    || $absolutePath === false
    || !str_starts_with($absolutePath, $storageBase . DIRECTORY_SEPARATOR)
    || !is_file($absolutePath)
) {
    http_response_code(404);
    exit('Bestand bestaat niet.');
}

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
];

$mimeType = (string) ($file['mime_type'] ?? '');

if (!in_array($mimeType, $allowedMimeTypes, true)) {
    http_response_code(403);
    exit('Bestandstype niet toegestaan.');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($absolutePath));
header('Content-Disposition: inline; filename="' . str_replace(['"', "\\", "\r", "\n"], '', basename((string) $file['original_name'])) . '"');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($absolutePath);
exit;
