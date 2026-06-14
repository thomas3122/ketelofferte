<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/leads.php');
    exit;
}

require_valid_csrf_token('/admin/leads.php', 'lead_admin_error');

$leadId = (int) ($_POST['lead_id'] ?? 0);
$note = trim($_POST['note'] ?? '');

if ($leadId <= 0 || $note === '') {
    $_SESSION['lead_admin_error'] = 'Notitie is leeg of lead is ongeldig.';
    header('Location: /admin/leads.php');
    exit;
}

$checkStmt = $pdo->prepare("
    SELECT id
    FROM leads
    WHERE id = :id
    LIMIT 1
");

$checkStmt->execute([
    ':id' => $leadId,
]);

if (!$checkStmt->fetch()) {
    $_SESSION['lead_admin_error'] = 'Lead niet gevonden.';
    header('Location: /admin/leads.php');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO lead_notes (
        lead_id,
        note
    ) VALUES (
        :lead_id,
        :note
    )
");

$stmt->execute([
    ':lead_id' => $leadId,
    ':note' => $note,
]);

$_SESSION['lead_admin_success'] = 'Notitie toegevoegd.';

header('Location: /admin/lead.php?id=' . $leadId);
exit;
