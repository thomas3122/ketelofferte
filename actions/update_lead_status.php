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
$status = trim($_POST['status'] ?? '');

$allowedStatuses = [
    'new',
    'contacted',
    'waiting_info',
    'quote_made',
    'sent_to_company',
    'won',
    'lost',
];

if ($leadId <= 0 || !in_array($status, $allowedStatuses, true)) {
    $_SESSION['lead_admin_error'] = 'Ongeldige statuswijziging.';
    header('Location: /admin/leads.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE leads
    SET status = :status
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':status' => $status,
    ':id' => $leadId,
]);

$_SESSION['lead_admin_success'] = 'Status bijgewerkt.';

header('Location: /admin/lead.php?id=' . $leadId);
exit;
