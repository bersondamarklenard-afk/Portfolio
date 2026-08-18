<?php
/**
 * Copy this file to config.php and update values for your environment.
 * Never commit config.php with real production credentials.
 */

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'portfolio_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Portfolio',
        'url' => 'http://localhost/portfolio',
        'timezone' => 'Asia/Manila',
        'debug' => true,
    ],
    'uploads' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_docs' => ['pdf'],
    ],
    /*
     * Google OAuth 2.0 / OpenID Connect for Admin login.
     * Keep client_secret only in this file (not web-accessible).
     *
     * Google Cloud Console → APIs & Services → Credentials → OAuth client (Web application)
     * Authorized redirect URIs:
     *   Local:  http://localhost/portfolio/admin/google-callback.php
     *   Live:   https://yourdomain.com/admin/google-callback.php
     * If redirect_uri is empty, it is built from app.url automatically.
     */
    'google' => [
        'client_id' => '',
        'client_secret' => '',
        'allowed_email' => '', // Only this Gmail/Google account may enter Admin
        'redirect_uri' => '',
    ],
];
