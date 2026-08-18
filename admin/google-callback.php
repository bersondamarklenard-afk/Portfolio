<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/google-oauth.php';

if (is_admin_logged_in()) {
    google_oauth_clear_handshake();
    redirect('admin/index.php');
}

$googleError = (string)($_GET['error'] ?? '');
if ($googleError !== '') {
    $code = $googleError === 'access_denied' ? 'google_cancelled' : 'google_failed';
    google_oauth_login_error_redirect($code);
}

$code = (string)($_GET['code'] ?? '');
$state = (string)($_GET['state'] ?? '');
$expectedState = (string)($_SESSION['google_oauth_state'] ?? '');

if ($code === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    google_oauth_remember_debug(
        $expectedState === ''
            ? 'Sign-in session was lost before Google returned. Try Continue with Google again.'
            : 'The Google sign-in state did not match. Try Continue with Google again.'
    );
    google_oauth_login_error_redirect($expectedState === '' ? 'google_session' : 'google_failed');
}

try {
    $tokens = google_oauth_exchange_code($code);
    $claims = google_oauth_verify_id_token((string)$tokens['id_token']);
    $email = strtolower(trim((string)($claims['email'] ?? '')));

    google_oauth_clear_handshake();

    if (!attempt_google_admin_login($email)) {
        google_oauth_login_error_redirect('google_denied');
    }

    redirect('admin/index.php');
} catch (Throwable $e) {
    google_oauth_remember_debug($e->getMessage());
    google_oauth_login_error_redirect('google_failed');
}
