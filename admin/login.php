<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/google-oauth.php';

if (is_admin_logged_in()) {
    redirect('admin/index.php');
}

$error = '';
$errorTitle = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } elseif (attempt_login($username, $password)) {
        redirect('admin/index.php');
    } else {
        $error = 'Invalid username or password.';
    }
} else {
    $code = (string)($_GET['error'] ?? '');
    if ($code === 'google_denied') {
        $errorTitle = 'Access Denied';
        $error = 'This Google account is not authorized to access the Admin panel.';
    } elseif ($code === 'google_cancelled') {
        $error = 'Google sign-in was cancelled.';
    } elseif ($code === 'google_config') {
        $error = 'Google sign-in is not configured yet. Add your Client ID, Client Secret, and authorized email in config/config.php.';
    } elseif ($code === 'google_session') {
        $error = 'Your Google sign-in session expired. Please click Continue with Google again.';
    } elseif ($code === 'google_failed') {
        $error = 'Google sign-in could not be completed. Please try again.';
        $debug = trim((string)($_SESSION['google_oauth_debug'] ?? ''));
        unset($_SESSION['google_oauth_debug']);
        if (APP_DEBUG && $debug !== '') {
            $error .= ' (' . $debug . ')';
        }
    }
}

$googleReady = google_oauth_is_configured();
$googleHref = site_href('admin/google-login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login · Portfolio</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#111111">
    <link rel="icon" href="<?= e(asset_href('images/favicon/favicon.ico')) ?>" sizes="any">
    <link rel="icon" href="<?= e(asset_href('images/favicon/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('images/favicon/favicon-32x32.png')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset_href('images/favicon/apple-touch-icon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset_href('css/admin.css')) ?>?v=16">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-brand">Portfolio CMS</div>
        <div class="login-icon"><i class="fa-solid fa-lock"></i></div>
        <h1>Sign in</h1>
        <p class="sub">Access your portfolio content manager.</p>

        <?php if ($error): ?>
            <div class="alert alert-error login-alert">
                <?php if ($errorTitle !== ''): ?>
                    <strong><?= e($errorTitle) ?></strong>
                <?php endif; ?>
                <p><?= e($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="post" class="stack-form" id="passwordLoginForm">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username or email</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-right-to-bracket"></i> Sign in
            </button>
        </form>

        <div class="login-divider" role="separator"><span>or</span></div>

        <a class="btn btn-google btn-block<?= $googleReady ? '' : ' is-unconfigured' ?>" href="<?= e($googleHref) ?>" id="googleLoginBtn">
            <span class="google-g" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            </span>
            Continue with Google
        </a>

        <p class="login-footer">
            <a href="<?= e(APP_URL) ?>/index.php">← Back to portfolio</a>
        </p>
    </div>
    <script>
      document.getElementById('googleLoginBtn')?.addEventListener('click', (event) => {
        const btn = event.currentTarget;
        if (!(btn instanceof HTMLAnchorElement) || btn.classList.contains('is-loading')) return;
        btn.classList.add('is-loading');
        btn.setAttribute('aria-disabled', 'true');
        const label = btn.childNodes[btn.childNodes.length - 1];
        if (label && label.nodeType === Node.TEXT_NODE) {
          label.textContent = ' Connecting to Google…';
        }
      });
    </script>
</body>
</html>
