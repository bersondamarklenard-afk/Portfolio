<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

function resume_not_available(): void
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo 'Resume is not available.';
    exit;
}

try {
    if (!is_portfolio_public()) {
        resume_not_available();
    }
} catch (Throwable $e) {
    resume_not_available();
}

try {
    $personal = get_personal();
} catch (Throwable $e) {
    resume_not_available();
}

$stored = safe_upload_filename($personal['resume_file'] ?? null);
if ($stored === null) {
    resume_not_available();
}

$baseDir = realpath(upload_path('resumes'));
$filePath = upload_path('resumes', $stored);
$realFile = is_file($filePath) ? realpath($filePath) : false;
if ($baseDir === false || $realFile === false) {
    resume_not_available();
}

$baseNorm = strtolower(str_replace('\\', '/', $baseDir));
$fileNorm = strtolower(str_replace('\\', '/', $realFile));
if (!str_starts_with($fileNorm, rtrim($baseNorm, '/') . '/')) {
    resume_not_available();
}

$extension = strtolower(pathinfo($realFile, PATHINFO_EXTENSION) ?: 'pdf');
$downloadName = resume_download_filename($personal['full_name'] ?? '', $extension);
$mime = $extension === 'pdf' ? 'application/pdf' : 'application/octet-stream';
$size = filesize($realFile);
if ($size === false) {
    resume_not_available();
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Transfer-Encoding: binary');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . $size);
$asciiName = str_replace('"', '', $downloadName);
header(
    'Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName)
);

readfile($realFile);
exit;
