<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/login.php');
    exit;
}

require_valid_csrf_token('/admin/login.php', 'login_error');

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Vul uw gebruikersnaam en wachtwoord in.';
    header('Location: /admin/login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username, password_hash, full_name, role, status
    FROM admin_users
    WHERE username = :username
    LIMIT 1
");

$stmt->execute([
    ':username' => $username
]);

$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active') {
    $_SESSION['login_error'] = 'Ongeldige inloggegevens.';
    header('Location: /admin/login.php');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Ongeldige inloggegevens.';
    header('Location: /admin/login.php');
    exit;
}

session_regenerate_id(true);

$_SESSION['admin_user_id'] = (int) $user['id'];
$_SESSION['admin_username'] = $user['username'];
$_SESSION['admin_full_name'] = $user['full_name'] ?: $user['username'];
$_SESSION['admin_role'] = $user['role'];
$_SESSION['admin_login_fingerprint'] = admin_session_fingerprint();
unset($_SESSION['csrf_token']);

$updateStmt = $pdo->prepare("
    UPDATE admin_users
    SET last_login_at = NOW()
    WHERE id = :id
");

$updateStmt->execute([
    ':id' => $user['id']
]);

header('Location: /admin/dashboard.php');
exit;
