<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/google-oauth.php';

if (is_admin_logged_in()) {
    redirect('admin/index.php');
}

if (!google_oauth_is_configured()) {
    google_oauth_login_error_redirect('google_config');
}

try {
    $url = google_oauth_authorization_url();
    session_write_close();
    redirect($url);
} catch (Throwable $e) {
    google_oauth_remember_debug($e->getMessage());
    google_oauth_login_error_redirect('google_failed');
}
