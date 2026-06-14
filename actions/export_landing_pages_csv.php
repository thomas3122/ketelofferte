<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/landing_page_csv.php';

require_admin_login();

$fields = landing_page_csv_fields($pdo);

$selectFields = implode(",\n        ", array_map(
    static fn (string $field): string => '`' . $field . '`',
    $fields
));

$stmt = $pdo->query("
    SELECT
        {$selectFields}
    FROM landing_pages
    ORDER BY
        municipality ASC,
        CASE WHEN area_type = 'city' THEN 0 ELSE 1 END,
        city ASC
");

$filename = 'landing-pages-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if ($output === false) {
    exit;
}

fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, $fields, ';');

while ($row = $stmt->fetch()) {
    $line = [];

    foreach ($fields as $field) {
        $line[] = $row[$field] ?? '';
    }

    fputcsv($output, $line, ';');
}

fclose($output);
exit;

