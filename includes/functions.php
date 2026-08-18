<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Escape for HTML output
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Flash message helpers
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    if ($type === 'success') {
        try {
            touch_portfolio_content();
        } catch (Throwable $e) {
            // Content revision is best-effort and must not block admin saves.
        }
    }
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $path): void
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    }
    exit;
}

function asset(string $path): string
{
    return APP_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Host-independent asset URL path (works locally and on Hostinger).
 * Example: /portfolio/assets/... or /assets/...
 */
function site_base_path(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== '' && str_contains($script, '/admin/')) {
        $base = dirname(dirname($script));
    } else {
        $base = dirname($script);
    }

    $base = str_replace('\\', '/', (string)$base);
    if ($base === '/' || $base === '.' || $base === '\\') {
        $base = '';
    }

    return rtrim($base, '/');
}

function site_href(string $path): string
{
    $base = site_base_path();
    $path = ltrim($path, '/');

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset_href(string $path): string
{
    return site_href('assets/' . ltrim($path, '/'));
}

/**
 * Absolute URL for Open Graph / social sharing.
 */
function absolute_url(string $pathOrUrl): string
{
    if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
        return $pathOrUrl;
    }
    if (str_starts_with($pathOrUrl, '//')) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
        return $scheme . $pathOrUrl;
    }
    if (str_starts_with($pathOrUrl, '/')) {
        return rtrim(APP_URL, '/') . $pathOrUrl;
    }
    return rtrim(APP_URL, '/') . '/' . ltrim($pathOrUrl, '/');
}

function send_no_store_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0, s-maxage=0', true);
    header('Pragma: no-cache', true);
    header('Expires: 0', true);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true);
    header_remove('ETag');
}

function upload_url(string $folder, ?string $filename): ?string
{
    if (!$filename) {
        return null;
    }
    $relative = 'uploads/' . trim($folder, '/') . '/' . ltrim($filename, '/');
    $url = asset($relative);
    $path = APP_ROOT . '/assets/' . $relative;
    if (is_file($path)) {
        $url .= '?v=' . filemtime($path);
    }
    return $url;
}

function safe_upload_filename(?string $filename): ?string
{
    if ($filename === null || $filename === '') {
        return null;
    }
    $normalized = str_replace('\\', '/', $filename);
    $base = basename($normalized);
    if ($base === '' || $base === '.' || $base === '..' || $base !== $normalized) {
        return null;
    }
    return $base;
}

function upload_exists(string $folder, ?string $filename): bool
{
    $safe = safe_upload_filename($filename);
    return $safe !== null && is_file(upload_path($folder, $safe));
}

function resume_download_filename(?string $fullName, string $extension = 'pdf'): string
{
    $name = trim((string)$fullName);
    if ($name === '') {
        $name = 'Mark Lenard Golfo Bersonda';
    }
    $name = preg_replace('/[\\\\\/:\*\?"<>|]+/u', '', $name) ?? $name;
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    if ($name === '') {
        $name = 'Mark Lenard Golfo Bersonda';
    }
    $ext = strtolower(ltrim($extension, '.'));
    if ($ext === '') {
        $ext = 'pdf';
    }
    return $name . '.' . $ext;
}

function upload_path(string $folder, string $filename = ''): string
{
    $dir = APP_ROOT . '/assets/uploads/' . trim($folder, '/');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $filename === '' ? $dir : $dir . '/' . ltrim($filename, '/');
}

/**
 * Secure image/document upload. Returns stored filename or null.
 */
function handle_upload(array $file, string $folder, array $allowedExt, int $maxSize = 5242880): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > $maxSize) {
        throw new RuntimeException('File is too large. Max ' . round($maxSize / 1048576, 1) . 'MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Invalid file type. Allowed: ' . implode(', ', $allowedExt));
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
    ];
    if (!isset($allowedMimes[$ext]) || !in_array($mime, $allowedMimes[$ext], true)) {
        throw new RuntimeException('File content does not match extension.');
    }

    $filename = $folder . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = upload_path($folder, $filename);
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save uploaded file.');
    }
    return $filename;
}

function delete_upload(string $folder, ?string $filename): void
{
    if (!$filename) {
        return;
    }
    $path = upload_path($folder, $filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}

function get_personal(): ?array
{
    ensure_personal_schema();
    $stmt = db()->query('SELECT * FROM personal_information ORDER BY id ASC LIMIT 1');
    $row = $stmt->fetch();
    return $row ?: null;
}

function ensure_personal_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    try {
        $stmt = db()->query("SHOW COLUMNS FROM personal_information LIKE 'what_i_do'");
        if ($stmt->fetch()) {
            return;
        }

        db()->exec("ALTER TABLE personal_information ADD COLUMN what_i_do TEXT DEFAULT NULL AFTER about_me");
        $rows = db()->query('SELECT id, professional_title FROM personal_information')->fetchAll();
        $update = db()->prepare('UPDATE personal_information SET what_i_do = ? WHERE id = ?');
        foreach ($rows as $row) {
            $title = trim((string)($row['professional_title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $normalized = trim((string)preg_replace('/\s*[·|•]\s*/u', "\n", $title));
            $update->execute([$normalized !== '' ? $normalized : $title, (int)$row['id']]);
        }
    } catch (Throwable $e) {
        $ready = false;
    }
}

function get_skills(bool $visibleOnly = true): array
{
    $sql = 'SELECT * FROM skills';
    if ($visibleOnly) {
        $sql .= ' WHERE is_visible = 1';
    }
    $sql .= ' ORDER BY category ASC, sort_order ASC, name ASC';
    return db()->query($sql)->fetchAll();
}

function group_skills_by_category(array $skills): array
{
    $labels = [
        'frontend' => 'Frontend',
        'backend' => 'Backend',
        'database' => 'Database',
        'tools' => 'Tools & Technologies',
        'design' => 'Design',
        'other' => 'Other',
    ];
    $grouped = [];
    foreach ($skills as $skill) {
        $cat = $skill['category'] ?? 'other';
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = ['label' => $labels[$cat] ?? ucfirst($cat), 'items' => []];
        }
        $grouped[$cat]['items'][] = $skill;
    }
    return $grouped;
}

function get_experiences(bool $visibleOnly = true): array
{
    $sql = 'SELECT * FROM experiences';
    if ($visibleOnly) {
        $sql .= ' WHERE is_visible = 1';
    }
    $sql .= ' ORDER BY is_current DESC, COALESCE(end_date, \'9999-12-01\') DESC, start_date DESC, id DESC';
    return db()->query($sql)->fetchAll();
}

function get_projects(bool $visibleOnly = true, ?bool $featuredOnly = null): array
{
    $sql = 'SELECT * FROM projects WHERE 1=1';
    $params = [];
    if ($visibleOnly) {
        $sql .= ' AND is_visible = 1';
    }
    if ($featuredOnly === true) {
        $sql .= ' AND is_featured = 1';
    }
    $sql .= ' ORDER BY is_featured DESC, sort_order ASC, created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function education_categories(): array
{
    return [
        'college' => 'College',
        'senior_high' => 'Senior High School (SHS)',
        'high_school' => 'High School',
        'masters' => "Master's Degree",
        'doctorate' => 'Doctorate',
        'other' => 'Other',
    ];
}

function education_category_label(?string $key): string
{
    $map = education_categories();
    $key = strtolower(trim((string)$key));
    return $map[$key] ?? $map['college'];
}

function infer_education_category(string $degree): string
{
    $d = strtolower($degree);
    if (str_contains($d, 'doctor') || str_contains($d, 'phd') || str_contains($d, 'ph.d')) {
        return 'doctorate';
    }
    if (str_contains($d, 'master') || str_contains($d, 'msc') || str_contains($d, 'm.a') || str_contains($d, 'mba')) {
        return 'masters';
    }
    if (str_contains($d, 'senior high') || str_contains($d, 'shs') || str_contains($d, 'k-12') || str_contains($d, 'k12')) {
        return 'senior_high';
    }
    if (str_contains($d, 'high school') || str_contains($d, 'secondary') || str_contains($d, 'junior high')) {
        return 'high_school';
    }
    return 'college';
}

function ensure_education_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    $hasColumn = static function (string $column): bool {
        $stmt = db()->query("SHOW COLUMNS FROM education LIKE " . db()->quote($column));
        return (bool)$stmt->fetch();
    };

    if (!$hasColumn('category')) {
        db()->exec("ALTER TABLE education ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'college' AFTER institution");
        $rows = db()->query('SELECT id, degree FROM education')->fetchAll();
        $stmt = db()->prepare('UPDATE education SET category = ? WHERE id = ?');
        foreach ($rows as $row) {
            $stmt->execute([infer_education_category((string)$row['degree']), (int)$row['id']]);
        }
    }

    if (!$hasColumn('academic_honor')) {
        db()->exec("ALTER TABLE education ADD COLUMN academic_honor VARCHAR(200) DEFAULT NULL AFTER description");
    }
}

function compose_month_year(string $month, string $year): ?string
{
    $m = (int)$month;
    $y = (int)$year;
    if ($m < 1 || $m > 12 || $y < 1950 || $y > 2110) {
        return null;
    }
    return sprintf('%04d-%02d-01', $y, $m);
}

function split_month_year(?string $date): array
{
    if (!$date) {
        return ['month' => '', 'year' => ''];
    }
    $ts = strtotime($date);
    if (!$ts) {
        return ['month' => '', 'year' => ''];
    }
    return [
        'month' => (string)(int)date('n', $ts),
        'year' => date('Y', $ts),
    ];
}

function format_month_year(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('F Y', $ts) : '';
}

function format_month_year_range(?string $start, ?string $end, bool $isCurrent = false): string
{
    $s = format_month_year($start);
    $e = $isCurrent ? 'Present' : format_month_year($end);
    if ($s && $e) {
        return $s . ' – ' . $e;
    }
    return $s ?: $e;
}

function get_education(bool $visibleOnly = true): array
{
    ensure_education_schema();
    $sql = 'SELECT * FROM education';
    if ($visibleOnly) {
        $sql .= ' WHERE is_visible = 1';
    }
    $sql .= ' ORDER BY is_current DESC, COALESCE(end_date, \'9999-12-01\') DESC, start_date DESC, id DESC';
    return db()->query($sql)->fetchAll();
}

function get_certifications(bool $visibleOnly = true): array
{
    $sql = 'SELECT * FROM certifications';
    if ($visibleOnly) {
        $sql .= ' WHERE is_visible = 1';
    }
    $sql .= ' ORDER BY (issue_date IS NULL) ASC, issue_date DESC, id DESC';
    return db()->query($sql)->fetchAll();
}

function certification_types(): array
{
    return [
        'certification' => 'Certification',
        'award' => 'Award',
        'achievement' => 'Achievement',
        'training' => 'Training',
        'other' => 'Other',
    ];
}

function certification_type_label(?string $type): string
{
    $types = certification_types();
    $key = (string) $type;
    return $types[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

function get_social_links(bool $visibleOnly = true): array
{
    $sql = 'SELECT * FROM social_links';
    if ($visibleOnly) {
        $sql .= ' WHERE is_visible = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    return db()->query($sql)->fetchAll();
}

function is_mailto_href(string $url): bool
{
    return str_starts_with(strtolower(trim($url)), 'mailto:');
}

function resolve_social_url(array $link, ?string $fallbackEmail = null): ?string
{
    $url = trim((string)($link['url'] ?? ''));
    $platform = strtolower(trim((string)($link['platform'] ?? '')));

    if ($platform === 'email' || is_mailto_href($url)) {
        $address = $url;
        if (is_mailto_href($address)) {
            $address = trim(substr($address, 7));
        }
        if ($address === '' && $fallbackEmail) {
            $address = trim($fallbackEmail);
        }
        return $address !== '' ? 'mailto:' . $address : null;
    }

    return $url !== '' ? $url : null;
}

function social_link_is_external(string $url): bool
{
    $url = strtolower(trim($url));
    return $url !== '' && !str_starts_with($url, 'mailto:') && !str_starts_with($url, 'tel:');
}

function format_date_range(?string $start, ?string $end, bool $isCurrent = false): string
{
    $fmt = static function (?string $d): string {
        if (!$d) {
            return '';
        }
        $ts = strtotime($d);
        return $ts ? date('M Y', $ts) : '';
    };
    $s = $fmt($start);
    $e = $isCurrent ? 'Present' : $fmt($end);
    if ($s && $e) {
        return $s . ' – ' . $e;
    }
    return $s ?: $e;
}

function lines_to_array(?string $text): array
{
    if (!$text) {
        return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static fn($l) => $l !== ''));
}

function split_portfolio_items(?string $text): array
{
    $text = trim((string) $text);
    if ($text === '') {
        return [];
    }

    if (preg_match('/\R/u', $text)) {
        $parts = preg_split('/\R+/u', $text) ?: [];
    } else {
        $parts = preg_split('/\s*(?:,|;|•|·|\|)\s*(?:and\s+)?|\s+and\s+/iu', $text) ?: [];
    }

    $items = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        $part = preg_replace('/[.]+$/u', '', $part) ?? $part;
        if ($part !== '') {
            $items[] = ucfirst($part);
        }
    }

    return $items;
}

function tech_to_array(?string $tech): array
{
    if (!$tech) {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $tech))));
}

function ensure_site_settings(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    try {
        db()->query('SELECT 1 FROM site_settings LIMIT 1');
    } catch (Throwable $e) {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS site_settings (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              setting_key VARCHAR(100) NOT NULL,
              setting_value TEXT DEFAULT NULL,
              setting_type ENUM('text','textarea','image','file') DEFAULT 'text',
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_setting_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    $stmt = db()->prepare('SELECT id FROM site_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute(['portfolio_visible']);
    if (!$stmt->fetch()) {
        db()->prepare(
            'INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)'
        )->execute(['portfolio_visible', '1', 'text']);
    }

    $stmt->execute(['content_revision']);
    if (!$stmt->fetch()) {
        db()->prepare(
            'INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)'
        )->execute(['content_revision', (string)time(), 'text']);
    }

    $ready = true;
}

function get_site_setting(string $key, ?string $default = null): ?string
{
    ensure_site_settings();
    $stmt = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return $default;
    }
    return (string)$value;
}

function set_site_setting(string $key, string $value, string $type = 'text'): void
{
    ensure_site_settings();
    $stmt = db()->prepare(
        'INSERT INTO site_settings (setting_key, setting_value, setting_type)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)'
    );
    $stmt->execute([$key, $value, $type]);
}

function touch_portfolio_content(): void
{
    set_site_setting('content_revision', (string)time(), 'text');
}

function is_portfolio_public(): bool
{
    $value = strtolower(trim((string)get_site_setting('portfolio_visible', '1')));
    return $value === '1' || $value === 'true' || $value === 'on' || $value === 'public';
}

function count_table(string $table): int
{
    $allowed = [
        'projects', 'skills', 'experiences', 'education',
        'certifications', 'social_links', 'contact_messages', 'admins',
    ];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }
    return (int)db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function unread_messages_count(): int
{
    try {
        return (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function initials(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string)$name)) ?: [];
    $out = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $out .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        if (mb_strlen($out) >= 2) {
            break;
        }
    }
    return $out ?: 'ME';
}
