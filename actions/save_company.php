<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/companies.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$redirectTo = $id > 0 ? '/admin/company.php?id=' . $id : '/admin/company.php';

require_valid_csrf_token($redirectTo, 'company_admin_error');

function clean_company_string(?string $value): string
{
    return trim((string) $value);
}

$companyName = clean_company_string($_POST['company_name'] ?? '');
$contactName = clean_company_string($_POST['contact_name'] ?? '');
$email = clean_company_string($_POST['email'] ?? '');
$phone = clean_company_string($_POST['phone'] ?? '');
$city = clean_company_string($_POST['city'] ?? '');
$serviceArea = clean_company_string($_POST['service_area'] ?? '');
$status = clean_company_string($_POST['status'] ?? 'active');

if ($companyName === '' || $email === '') {
    $_SESSION['company_admin_error'] = 'Bedrijfsnaam en e-mail zijn verplicht.';
    header('Location: ' . $redirectTo);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['company_admin_error'] = 'Vul een geldig e-mailadres in.';
    header('Location: ' . $redirectTo);
    exit;
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $_SESSION['company_admin_error'] = 'Ongeldige status.';
    header('Location: ' . $redirectTo);
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE companies
            SET
                company_name = :company_name,
                contact_name = :contact_name,
                email = :email,
                phone = :phone,
                city = :city,
                service_area = :service_area,
                status = :status
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':company_name' => $companyName,
            ':contact_name' => $contactName ?: null,
            ':email' => $email,
            ':phone' => $phone ?: null,
            ':city' => $city ?: null,
            ':service_area' => $serviceArea ?: null,
            ':status' => $status,
            ':id' => $id,
        ]);

        $_SESSION['company_admin_success'] = 'Bedrijf opgeslagen.';
        header('Location: /admin/company.php?id=' . $id);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO companies (
            company_name,
            contact_name,
            email,
            phone,
            city,
            service_area,
            status
        ) VALUES (
            :company_name,
            :contact_name,
            :email,
            :phone,
            :city,
            :service_area,
            :status
        )
    ");

    $stmt->execute([
        ':company_name' => $companyName,
        ':contact_name' => $contactName ?: null,
        ':email' => $email,
        ':phone' => $phone ?: null,
        ':city' => $city ?: null,
        ':service_area' => $serviceArea ?: null,
        ':status' => $status,
    ]);

    $newId = (int) $pdo->lastInsertId();

    $_SESSION['company_admin_success'] = 'Nieuw bedrijf aangemaakt.';
    header('Location: /admin/company.php?id=' . $newId);
    exit;
} catch (Throwable $e) {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $_SESSION['company_admin_error'] = 'Fout bij opslaan: ' . $e->getMessage();
    } else {
        $_SESSION['company_admin_error'] = 'Er ging iets mis bij het opslaan.';
    }

    header('Location: ' . $redirectTo);
    exit;
}
