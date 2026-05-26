<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return isset($_SESSION['admin_user_id'], $_SESSION['admin_login_fingerprint'])
        && hash_equals($_SESSION['admin_login_fingerprint'], admin_session_fingerprint());
}

function require_admin_login(): void
{
    if (!is_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function current_admin_name(): string
{
    return $_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Admin';
}

function admin_session_fingerprint(): string
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return hash('sha256', $userAgent);
}

function csrf_secret(): string
{
    $secret = env_value('CSRF_SECRET', '');

    if ($secret !== '') {
        return $secret;
    }

    return hash('sha256', implode('|', [
        env_value('APP_NAME', 'KetelOfferte24'),
        env_value('DB_DATABASE', ''),
        env_value('DB_USERNAME', ''),
        env_value('DB_PASSWORD', ''),
    ]));
}

function stateless_csrf_token(): string
{
    $timestamp = (string) time();
    $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $signature = hash_hmac('sha256', $timestamp . '|' . $userAgentHash, csrf_secret());

    return $timestamp . ':' . $signature;
}

function verify_stateless_csrf_token(string $token): bool
{
    $parts = explode(':', $token, 2);

    if (count($parts) !== 2 || !ctype_digit($parts[0])) {
        return false;
    }

    $timestamp = (int) $parts[0];

    if ($timestamp < time() - 7200 || $timestamp > time() + 300) {
        return false;
    }

    $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $expected = hash_hmac('sha256', (string) $timestamp . '|' . $userAgentHash, csrf_secret());

    return hash_equals($expected, $parts[1]);
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return stateless_csrf_token();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    if (
        session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token)
    ) {
        return true;
    }

    return verify_stateless_csrf_token($token);
}

function require_valid_csrf_token(string $redirectTo, string $errorSessionKey = 'admin_error'): void
{
    if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
        return;
    }

    $_SESSION[$errorSessionKey] = 'Beveiligingscontrole mislukt. Probeer het opnieuw.';
    header('Location: ' . $redirectTo);
    exit;
}
