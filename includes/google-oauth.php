<?php

declare(strict_types=1);

function google_oauth_is_configured(): bool
{
    $clientId = trim((string)app_config('google.client_id', ''));
    $clientSecret = trim((string)app_config('google.client_secret', ''));
    return $clientId !== '' && $clientSecret !== '' && google_allowed_emails() !== [];
}

function google_oauth_redirect_uri(): string
{
    $configured = trim((string)app_config('google.redirect_uri', ''));
    if ($configured !== '') {
        return $configured;
    }

    return rtrim((string)APP_URL, '/') . '/admin/google-callback.php';
}

function google_oauth_clear_handshake(): void
{
    unset(
        $_SESSION['google_oauth_state'],
        $_SESSION['google_oauth_nonce'],
        $_SESSION['google_oauth_verifier']
    );
}

function google_base64url_encode(string $raw): string
{
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

function google_base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder > 0) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    if ($decoded === false) {
        throw new RuntimeException('Invalid token encoding.');
    }
    return $decoded;
}

function google_jwt_payload(string $jwt): array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        throw new RuntimeException('Invalid ID token format.');
    }
    $payload = json_decode(google_base64url_decode($parts[1]), true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid ID token payload.');
    }
    return $payload;
}

function google_oauth_authorization_url(): string
{
    if (!google_oauth_is_configured()) {
        throw new RuntimeException('Google sign-in is not configured.');
    }

    $state = bin2hex(random_bytes(16));
    $nonce = bin2hex(random_bytes(16));
    $verifier = bin2hex(random_bytes(32));
    $challenge = google_base64url_encode(hash('sha256', $verifier, true));

    $_SESSION['google_oauth_state'] = $state;
    $_SESSION['google_oauth_nonce'] = $nonce;
    $_SESSION['google_oauth_verifier'] = $verifier;

    $params = [
        'client_id' => trim((string)app_config('google.client_id', '')),
        'redirect_uri' => google_oauth_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'nonce' => $nonce,
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
        'prompt' => 'select_account',
        'access_type' => 'online',
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function google_http_request(string $method, string $url, array $fields = []): array
{
    $query = http_build_query($fields);
    $body = '';
    $status = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ];
        if (defined('CURLPROTO_HTTP')) {
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        if (strtoupper($method) === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $query;
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
        } elseif ($query !== '') {
            $options[CURLOPT_URL] = $url . (str_contains($url, '?') ? '&' : '?') . $query;
        }
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($result === false) {
            throw new RuntimeException('Could not reach Google (' . $error . ').');
        }
        $body = (string)$result;
    } else {
        $header = "Accept: application/json\r\n";
        $contextOptions = [
            'http' => [
                'method' => strtoupper($method),
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => $header,
            ],
        ];
        if (strtoupper($method) === 'POST') {
            $contextOptions['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $contextOptions['http']['content'] = $query;
        } elseif ($query !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }
        $result = @file_get_contents($url, false, stream_context_create($contextOptions));
        if ($result === false) {
            throw new RuntimeException('Could not reach Google.');
        }
        $body = (string)$result;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('Google returned an unexpected response.');
    }

    return ['status' => $status, 'data' => $data];
}

function google_oauth_exchange_code(string $code): array
{
    $response = google_http_request('POST', 'https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => trim((string)app_config('google.client_id', '')),
        'client_secret' => trim((string)app_config('google.client_secret', '')),
        'redirect_uri' => google_oauth_redirect_uri(),
        'grant_type' => 'authorization_code',
        'code_verifier' => (string)($_SESSION['google_oauth_verifier'] ?? ''),
    ]);

    if ($response['status'] < 200 || $response['status'] >= 300 || empty($response['data']['id_token'])) {
        $detail = (string)($response['data']['error_description'] ?? $response['data']['error'] ?? '');
        throw new RuntimeException(
            $detail !== ''
                ? 'Google token exchange failed: ' . $detail
                : 'Google did not return a valid ID token.'
        );
    }

    return $response['data'];
}

function google_oauth_verify_id_token(string $idToken): array
{
    $response = google_http_request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
        'id_token' => $idToken,
    ]);

    if ($response['status'] < 200 || $response['status'] >= 300) {
        throw new RuntimeException(
            'Google identity could not be verified: ' . (string)($response['data']['error_description'] ?? $response['data']['error'] ?? 'tokeninfo failed')
        );
    }

    $jwtClaims = google_jwt_payload($idToken);
    $claims = array_merge($jwtClaims, $response['data']);

    $clientId = trim((string)app_config('google.client_id', ''));
    $nonce = (string)($_SESSION['google_oauth_nonce'] ?? '');
    $iss = (string)($claims['iss'] ?? '');
    $aud = (string)($claims['aud'] ?? '');
    $email = strtolower(trim((string)($claims['email'] ?? '')));
    $verified = $claims['email_verified'] ?? false;
    $exp = (int)($claims['exp'] ?? 0);
    $tokenNonce = (string)($jwtClaims['nonce'] ?? $claims['nonce'] ?? '');

    $emailVerified = $verified === true || $verified === 'true' || $verified === '1' || $verified === 1;
    $issuerOk = $iss === 'https://accounts.google.com' || $iss === 'accounts.google.com';

    if (!$issuerOk || !hash_equals($clientId, $aud)) {
        throw new RuntimeException('Google identity could not be verified: client mismatch.');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified) {
        throw new RuntimeException('Google identity could not be verified: email is missing or unverified.');
    }
    if ($exp < (time() - 60)) {
        throw new RuntimeException('Google identity could not be verified: token expired.');
    }
    if ($nonce === '' || $tokenNonce === '' || !hash_equals($nonce, $tokenNonce)) {
        throw new RuntimeException('Google identity could not be verified: sign-in session mismatch.');
    }

    return $claims;
}

function google_oauth_remember_debug(string $message): void
{
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $_SESSION['google_oauth_debug'] = $message;
    }
}

function google_oauth_login_error_redirect(string $code): void
{
    $debug = $_SESSION['google_oauth_debug'] ?? null;
    google_oauth_clear_handshake();
    if (is_string($debug) && $debug !== '') {
        $_SESSION['google_oauth_debug'] = $debug;
    }
    redirect('admin/login.php?error=' . rawurlencode($code));
}
