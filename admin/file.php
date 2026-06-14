<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

$fileId = (int) ($_GET['id'] ?? 0);

if ($fileId <= 0) {
    http_response_code(404);
    exit('Bestand niet gevonden.');
}

$stmt = $pdo->prepare("
    SELECT
        id,
        original_name,
        stored_name,
        file_path,
        mime_type,
        file_size
    FROM lead_files
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $fileId,
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
