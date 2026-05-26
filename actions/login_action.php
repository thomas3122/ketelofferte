<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/login.php');
    exit;
}

require_valid_csrf_token('/admin/login.php', 'login_error');

function login_rate_limit_key(string $username): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', strtolower($username) . '|' . $ip);
}

function login_rate_limit_path(string $key): string
{
    $dir = __DIR__ . '/../storage/cache/login_attempts';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir . '/' . $key . '.json';
}

function login_attempt_state(string $key): array
{
    $path = login_rate_limit_path($key);

    if (!is_file($path)) {
        return ['count' => 0, 'first_at' => time()];
    }

    $data = json_decode((string) file_get_contents($path), true);

    if (!is_array($data) || (($data['first_at'] ?? 0) < time() - 900)) {
        return ['count' => 0, 'first_at' => time()];
    }

    return [
        'count' => (int) ($data['count'] ?? 0),
        'first_at' => (int) ($data['first_at'] ?? time()),
    ];
}

function record_failed_login(string $key): void
{
    $state = login_attempt_state($key);
    $state['count']++;
    file_put_contents(login_rate_limit_path($key), json_encode($state), LOCK_EX);
}

function clear_failed_logins(string $key): void
{
    $path = login_rate_limit_path($key);

    if (is_file($path)) {
        unlink($path);
    }
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Vul uw gebruikersnaam en wachtwoord in.';
    header('Location: /admin/login.php');
    exit;
}

$rateLimitKey = login_rate_limit_key($username);
$rateLimitState = login_attempt_state($rateLimitKey);

if ($rateLimitState['count'] >= 8) {
    $_SESSION['login_error'] = 'Te veel mislukte pogingen. Probeer het over 15 minuten opnieuw.';
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
    record_failed_login($rateLimitKey);
    $_SESSION['login_error'] = 'Ongeldige inloggegevens.';
    header('Location: /admin/login.php');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    record_failed_login($rateLimitKey);
    $_SESSION['login_error'] = 'Ongeldige inloggegevens.';
    header('Location: /admin/login.php');
    exit;
}

clear_failed_logins($rateLimitKey);

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
