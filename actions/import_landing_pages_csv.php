<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/landing_page_csv.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

require_valid_csrf_token('/admin/seo/landing-pages.php', 'seo_admin_error');

function landing_csv_clean(?string $value): string
{
    return trim((string) $value);
}

function landing_csv_nullable(?string $value): ?string
{
    $cleaned = trim((string) $value);
    return $cleaned === '' ? null : $cleaned;
}

function landing_csv_normalize_active(string $value): ?int
{
    $value = strtolower(trim($value));

    return match ($value) {
        '1', 'active', 'actief', 'ja', 'yes', 'true' => 1,
        '0', 'inactive', 'inactief', 'nee', 'no', 'false' => 0,
        default => null,
    };
}

$file = $_FILES['landing_pages_csv'] ?? null;
$allowCreate = (int) ($_POST['allow_create'] ?? 0) === 1;

if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $_SESSION['seo_admin_error'] = 'Kies een geldig CSV-bestand.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

if ((int) ($file['size'] ?? 0) > 4 * 1024 * 1024) {
    $_SESSION['seo_admin_error'] = 'Het CSV-bestand is te groot. Gebruik maximaal 4 MB.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

$handle = fopen((string) $file['tmp_name'], 'r');

if ($handle === false) {
    $_SESSION['seo_admin_error'] = 'Het CSV-bestand kon niet worden gelezen.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

$headers = fgetcsv($handle, 0, ';');

if (!$headers) {
    fclose($handle);
    $_SESSION['seo_admin_error'] = 'Het CSV-bestand heeft geen kolomkoppen.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

$headers = array_map(static function (?string $header): string {
    return trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
}, $headers);

$allowedFields = landing_page_csv_fields($pdo);
$importableFields = landing_page_importable_fields($pdo);
$unknownHeaders = array_values(array_diff($headers, $allowedFields));

if ($unknownHeaders) {
    fclose($handle);
    $_SESSION['seo_admin_error'] = 'Onbekende CSV-kolom: ' . implode(', ', $unknownHeaders) . '.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

if (!in_array('slug', $headers, true)) {
    fclose($handle);
    $_SESSION['seo_admin_error'] = 'De kolom slug is verplicht.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

$allowedAreaTypes = ['city', 'district', 'neighborhood', 'village', 'region'];
$allowedTemplates = ['default', 'city'];
$jsonFields = ['hero_badges_json', 'trust_cards_json', 'problem_cards_json'];
$requiredFields = landing_page_required_import_fields();
$updated = 0;
$created = 0;
$skipped = 0;
$errors = [];
$lineNumber = 1;

try {
    $pdo->beginTransaction();

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $lineNumber++;

        if (count(array_filter($row, static fn (?string $value): bool => trim((string) $value) !== '')) === 0) {
            $skipped++;
            continue;
        }

        if ($lineNumber > 501) {
            $errors[] = 'Maximaal 500 regels per import.';
            break;
        }

        $data = [];

        foreach ($headers as $index => $header) {
            $data[$header] = $row[$index] ?? '';
        }

        $id = (int) landing_csv_clean($data['id'] ?? '');
        $slug = strtolower(landing_csv_clean($data['slug'] ?? ''));

        if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = 'Regel ' . $lineNumber . ': slug ontbreekt of is ongeldig.';
            continue;
        }

        $existing = null;

        if ($id > 0) {
            $findStmt = $pdo->prepare("SELECT * FROM landing_pages WHERE id = :id LIMIT 1");
            $findStmt->execute([':id' => $id]);
            $existing = $findStmt->fetch() ?: null;

            if (!$existing) {
                $errors[] = 'Regel ' . $lineNumber . ': ID ' . $id . ' bestaat niet.';
                continue;
            }

            if ((string) $existing['slug'] !== $slug) {
                $errors[] = 'Regel ' . $lineNumber . ': slug wijzigen is geblokkeerd voor behoud van bestaande URL’s.';
                continue;
            }
        } else {
            $findStmt = $pdo->prepare("SELECT * FROM landing_pages WHERE slug = :slug LIMIT 1");
            $findStmt->execute([':slug' => $slug]);
            $existing = $findStmt->fetch() ?: null;
        }

        $isNew = !$existing;

        if ($isNew && !$allowCreate) {
            $errors[] = 'Regel ' . $lineNumber . ': nieuwe slug "' . $slug . '" overgeslagen. Vink nieuwe pagina’s toestaan aan om aan te maken.';
            continue;
        }

        foreach ($requiredFields as $field) {
            if (!in_array($field, $headers, true)) {
                continue;
            }

            if (landing_csv_clean($data[$field] ?? '') === '') {
                $errors[] = 'Regel ' . $lineNumber . ': ' . $field . ' is verplicht.';
            }
        }

        $areaType = landing_csv_clean($data['area_type'] ?? ($existing['area_type'] ?? ''));
        $pageTemplate = landing_csv_clean($data['page_template'] ?? ($existing['page_template'] ?? ''));

        if ($areaType !== '' && !in_array($areaType, $allowedAreaTypes, true)) {
            $errors[] = 'Regel ' . $lineNumber . ': area_type is ongeldig.';
        }

        if ($pageTemplate !== '' && !in_array($pageTemplate, $allowedTemplates, true)) {
            $errors[] = 'Regel ' . $lineNumber . ': page_template is ongeldig.';
        }

        if (in_array('is_active', $headers, true) && landing_csv_normalize_active((string) ($data['is_active'] ?? '')) === null) {
            $errors[] = 'Regel ' . $lineNumber . ': is_active moet 1/0 of actief/inactief zijn.';
        }

        foreach ($jsonFields as $jsonField) {
            if (!in_array($jsonField, $headers, true) || landing_csv_clean($data[$jsonField] ?? '') === '') {
                continue;
            }

            json_decode((string) $data[$jsonField], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Regel ' . $lineNumber . ': ' . $jsonField . ' bevat geen geldige JSON.';
            }
        }

        if ($errors) {
            continue;
        }

        $values = [];

        foreach ($importableFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if ($field === 'is_active') {
                $active = landing_csv_normalize_active((string) $data[$field]);
                $values[$field] = $active ?? 1;
                continue;
            }

            if (in_array($field, ['slug', 'city', 'municipality', 'area_type', 'page_template', 'region', 'page_title', 'meta_description', 'hero_eyebrow', 'hero_title', 'hero_subtitle', 'intro_title', 'intro_text', 'region_text'], true)) {
                $values[$field] = landing_csv_clean($data[$field] ?? '');
                continue;
            }

            $values[$field] = landing_csv_nullable($data[$field] ?? '');
        }

        if ($isNew) {
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $values) || ($field !== 'is_active' && $values[$field] === '')) {
                    $errors[] = 'Regel ' . $lineNumber . ': nieuwe pagina mist verplichte kolom ' . $field . '.';
                }
            }

            if ($errors) {
                continue;
            }

            $columns = array_keys($values);
            $placeholders = array_map(static fn (string $field): string => ':' . $field, $columns);
            $params = [];

            foreach ($values as $field => $value) {
                $params[':' . $field] = $value;
            }

            $stmt = $pdo->prepare("
                INSERT INTO landing_pages (`" . implode('`, `', $columns) . "`)
                VALUES (" . implode(', ', $placeholders) . ")
            ");
            $stmt->execute($params);
            $created++;
            continue;
        }

        unset($values['slug']);

        if (!$values) {
            $skipped++;
            continue;
        }

        $setParts = [];
        $params = [':id' => (int) $existing['id']];

        foreach ($values as $field => $value) {
            $setParts[] = '`' . $field . '` = :' . $field;
            $params[':' . $field] = $value;
        }

        $stmt = $pdo->prepare("
            UPDATE landing_pages
            SET " . implode(",\n                ", $setParts) . "
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute($params);
        $updated++;
    }

    fclose($handle);

    if ($errors) {
        $pdo->rollBack();
        $_SESSION['seo_admin_error'] = 'Import gestopt. ' . implode(' ', array_slice($errors, 0, 5));
        header('Location: /admin/seo/landing-pages.php');
        exit;
    }

    $pdo->commit();

    $_SESSION['seo_admin_success'] = 'CSV import klaar: ' . $updated . ' bijgewerkt, ' . $created . ' aangemaakt, ' . $skipped . ' overgeslagen.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
} catch (Throwable $e) {
    fclose($handle);

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['seo_admin_error'] = defined('ENVIRONMENT') && ENVIRONMENT === 'development'
        ? 'Importfout: ' . $e->getMessage()
        : 'Importeren is mislukt.';
    header('Location: /admin/seo/landing-pages.php');
    exit;
}

