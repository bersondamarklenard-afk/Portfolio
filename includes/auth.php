<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'cache_limiter' => '',
    ]);
}

send_no_store_headers();

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect('admin/login.php');
    }
}

function establish_admin_session(array $user, string $method = 'password'): void
{
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = (int)$user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_auth_method'] = $method;
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    establish_admin_session($user, 'password');
    return true;
}

function google_allowed_emails(): array
{
    $raw = app_config('google.allowed_email', '');
    if (is_array($raw)) {
        $list = $raw;
    } else {
        $list = preg_split('/[,;]+/', (string)$raw) ?: [];
    }

    $emails = [];
    foreach ($list as $item) {
        $email = strtolower(trim((string)$item));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email;
        }
    }

    return array_values(array_unique($emails));
}

function is_google_email_allowed(string $email): bool
{
    $email = strtolower(trim($email));
    return $email !== '' && in_array($email, google_allowed_emails(), true);
}

function attempt_google_admin_login(string $verifiedEmail): bool
{
    $email = strtolower(trim($verifiedEmail));
    if (!is_google_email_allowed($email)) {
        return false;
    }

    $stmt = db()->prepare('SELECT * FROM admins WHERE LOWER(email) = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $user = db()->query('SELECT * FROM admins ORDER BY id ASC LIMIT 1')->fetch();
    }

    if (!$user) {
        return false;
    }

    establish_admin_session($user, 'google');
    $_SESSION['admin_google_email'] = $email;
    return true;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function current_admin_username(): string
{
    return (string)($_SESSION['admin_username'] ?? 'Admin');
}
