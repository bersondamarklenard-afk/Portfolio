<?php
/**
 * One-time migration: upgrades legacy portfolio_db to the new CMS schema
 * while preserving admin account, profile settings, and projects.
 *
 * Run once via browser: http://localhost/portfolio/database/migrate.php
 * Or CLI: php database/migrate.php
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';

function out(string $msg): void
{
    global $isCli;
    echo $isCli ? $msg . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
}

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migration</title>';
    echo '<style>body{font-family:system-ui;max-width:720px;margin:2rem auto;padding:0 1rem;line-height:1.5}</style></head><body>';
    echo '<h1>Portfolio Database Migration</h1>';
}

try {
    require_once dirname(__DIR__) . '/config/database.php';
    $pdo = db();

    // Ensure new tables exist
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    // Strip CREATE DATABASE / USE for already-connected DB
    $schema = preg_replace('/CREATE DATABASE.*?;/is', '', $schema);
    $schema = preg_replace('/USE\s+\w+\s*;/i', '', $schema);
    $pdo->exec($schema);
    out('✓ Schema applied');

    $eduCategory = $pdo->query("SHOW COLUMNS FROM education LIKE 'category'")->fetch();
    if (!$eduCategory) {
        $pdo->exec("ALTER TABLE education ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'college' AFTER institution");
        out('✓ Added education.category');
    }

    $eduHonor = $pdo->query("SHOW COLUMNS FROM education LIKE 'academic_honor'")->fetch();
    if (!$eduHonor) {
        $pdo->exec("ALTER TABLE education ADD COLUMN academic_honor VARCHAR(200) DEFAULT NULL AFTER description");
        out('✓ Added education.academic_honor');
    }

    $whatIDo = $pdo->query("SHOW COLUMNS FROM personal_information LIKE 'what_i_do'")->fetch();
    if (!$whatIDo) {
        $pdo->exec("ALTER TABLE personal_information ADD COLUMN what_i_do TEXT DEFAULT NULL AFTER about_me");
        $rows = $pdo->query('SELECT id, professional_title FROM personal_information')->fetchAll();
        $upd = $pdo->prepare('UPDATE personal_information SET what_i_do = ? WHERE id = ?');
        foreach ($rows as $row) {
            $title = trim((string)($row['professional_title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $normalized = trim((string)preg_replace('/\s*[·|•]\s*/u', "\n", $title));
            $upd->execute([$normalized !== '' ? $normalized : $title, (int)$row['id']]);
        }
        out('✓ Added personal_information.what_i_do');
    }

    // Migrate admin_users → admins
    $hasLegacyAdmin = (bool)$pdo->query("SHOW TABLES LIKE 'admin_users'")->fetch();
    $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();

    if ($adminCount === 0 && $hasLegacyAdmin) {
        $legacy = $pdo->query('SELECT username, password_hash, created_at FROM admin_users')->fetchAll();
        $ins = $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, ?)');
        foreach ($legacy as $row) {
            $ins->execute([$row['username'], $row['password_hash'], $row['created_at']]);
        }
        out('✓ Migrated ' . count($legacy) . ' admin user(s)');
    } elseif ($adminCount === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)')
            ->execute(['admin', 'admin@example.com', $hash]);
        out('✓ Created default admin (admin / admin123)');
    } else {
        out('• Admins already present (' . $adminCount . ')');
    }

    // Helper to read legacy settings
    $getSetting = static function (string $key, string $default = '') use ($pdo): string {
        try {
            $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false && $val !== null ? (string)$val : $default;
        } catch (Throwable $e) {
            return $default;
        }
    };

    // personal_information
    $piCount = (int)$pdo->query('SELECT COUNT(*) FROM personal_information')->fetchColumn();
    if ($piCount === 0) {
        $avatar = $getSetting('profile_avatar', '');
        // Fix known filename mismatch from older uploads
        if ($avatar && !file_exists(APP_ROOT . '/assets/uploads/avatars/' . $avatar)) {
            if (file_exists(APP_ROOT . '/assets/uploads/avatars/avatar_1782716975.jpg')) {
                $avatar = 'avatar_1782716975.jpg';
            } elseif (file_exists(APP_ROOT . '/uploads/' . $avatar)) {
                @copy(APP_ROOT . '/uploads/' . $avatar, APP_ROOT . '/assets/uploads/avatars/' . $avatar);
            } elseif (file_exists(APP_ROOT . '/uploads/avatar_1782716975.jpg')) {
                @copy(
                    APP_ROOT . '/uploads/avatar_1782716975.jpg',
                    APP_ROOT . '/assets/uploads/avatars/avatar_1782716975.jpg'
                );
                $avatar = 'avatar_1782716975.jpg';
            }
        }

        $pdo->prepare(
            'INSERT INTO personal_information
            (full_name, professional_title, tagline, about_me, what_i_do, career_objective, interests, strengths,
             email, phone, location, profile_photo, years_experience, availability_status,
             meta_title, meta_description, footer_text)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            trim($getSetting('profile_name', 'Mark Lenard G. Bersonda')),
            $getSetting('profile_title', 'IT Graduate · Web Developer'),
            $getSetting('hero_subtitle', 'Building practical web solutions with PHP, MySQL, and modern front-end tools.'),
            $getSetting('profile_bio', 'Passionate IT graduate with hands-on experience in web development. Specialized in PHP, MySQL, and modern JavaScript.'),
            "IT Graduate\nWeb Developer",
            'Seeking an entry-level web developer or IT role where I can contribute to real projects, grow with a team, and apply my skills in PHP, MySQL, and front-end development.',
            'Web development, UI design, system analysis, continuous learning',
            'Problem-solving, attention to detail, collaboration, fast learner',
            '',
            '',
            'Philippines',
            $avatar ?: null,
            $getSetting('profile_stats_experience', '1+ year'),
            'Open to opportunities',
            $getSetting('site_title', 'Mark Lenard Bersonda · IT Graduate & Web Developer'),
            'Portfolio of Mark Lenard G. Bersonda — IT graduate specializing in PHP, MySQL, and web development. View projects, skills, and experience.',
            $getSetting('footer_text', 'IT Graduate · Web Developer Portfolio'),
        ]);
        out('✓ Migrated personal information');
    } else {
        out('• Personal information already exists');
    }

    // Migrate legacy projects columns if needed
    $cols = $pdo->query('SHOW COLUMNS FROM projects')->fetchAll(PDO::FETCH_COLUMN);
    $legacyCols = ['tech_stack', 'deploy_status', 'url', 'icon_class'];
    $isLegacy = in_array('tech_stack', $cols, true) && !in_array('is_featured', $cols, true);

    if ($isLegacy) {
        out('• Detected legacy projects table — rebuilding…');
        $oldProjects = $pdo->query('SELECT * FROM projects ORDER BY id')->fetchAll();
        $pdo->exec('RENAME TABLE projects TO projects_legacy_backup');

        $pdo->exec("
            CREATE TABLE projects (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              title VARCHAR(150) NOT NULL,
              short_description VARCHAR(300) DEFAULT NULL,
              description TEXT DEFAULT NULL,
              problem_purpose TEXT DEFAULT NULL,
              key_features TEXT DEFAULT NULL,
              technologies VARCHAR(255) DEFAULT NULL,
              image VARCHAR(255) DEFAULT NULL,
              github_url VARCHAR(255) DEFAULT NULL,
              live_url VARCHAR(255) DEFAULT NULL,
              is_featured TINYINT(1) NOT NULL DEFAULT 0,
              is_visible TINYINT(1) NOT NULL DEFAULT 1,
              sort_order INT NOT NULL DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_projects_featured (is_featured),
              KEY idx_projects_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $ins = $pdo->prepare(
            'INSERT INTO projects
            (id, title, short_description, description, problem_purpose, key_features, technologies,
             github_url, live_url, is_featured, is_visible, sort_order, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );

        foreach ($oldProjects as $i => $p) {
            $desc = (string)($p['description'] ?? '');
            $short = mb_substr($desc, 0, 180);
            if (mb_strlen($desc) > 180) {
                $short .= '…';
            }
            $isPrivate = ($p['deploy_status'] ?? '') === 'private';
            $ins->execute([
                $p['id'],
                $p['title'],
                $short,
                $desc,
                null,
                null,
                $p['tech_stack'] ?? null,
                null,
                $isPrivate ? null : ($p['url'] ?? null),
                $i === 0 ? 1 : 0,
                1,
                $i,
                $p['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }
        out('✓ Migrated ' . count($oldProjects) . ' project(s); legacy table kept as projects_legacy_backup');
    } elseif ((int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn() === 0) {
        // Seed will handle empty projects
        out('• Projects table empty — seed will populate');
    } else {
        out('• Projects table already on new schema');
    }

    // Seed skills if empty
    if ((int)$pdo->query('SELECT COUNT(*) FROM skills')->fetchColumn() === 0) {
        $skills = [
            ['HTML', 'frontend', 90, 'fa-brands fa-html5', 1],
            ['CSS', 'frontend', 88, 'fa-brands fa-css3-alt', 2],
            ['JavaScript', 'frontend', 80, 'fa-brands fa-js', 3],
            ['Bootstrap', 'frontend', 85, 'fa-brands fa-bootstrap', 4],
            ['PHP', 'backend', 85, 'fa-brands fa-php', 1],
            ['MySQL', 'database', 82, 'fa-solid fa-database', 1],
            ['Git', 'tools', 78, 'fa-brands fa-git-alt', 1],
            ['GitHub', 'tools', 80, 'fa-brands fa-github', 2],
            ['VS Code', 'tools', 90, 'fa-solid fa-code', 3],
            ['XAMPP', 'tools', 85, 'fa-solid fa-server', 4],
            ['Figma', 'design', 70, 'fa-brands fa-figma', 1],
            ['Adobe Photoshop', 'design', 65, 'fa-solid fa-image', 2],
            ['Canva', 'design', 75, 'fa-solid fa-palette', 3],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO skills (name, category, proficiency, icon_class, sort_order) VALUES (?,?,?,?,?)'
        );
        foreach ($skills as $s) {
            $stmt->execute($s);
        }
        out('✓ Seeded ' . count($skills) . ' skills');
    }

    // Education
    if ((int)$pdo->query('SELECT COUNT(*) FROM education')->fetchColumn() === 0) {
        $pdo->prepare(
            'INSERT INTO education (degree, institution, location, start_date, end_date, description, sort_order)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([
            'Bachelor of Science in Information Technology',
            'Your University Name',
            'Philippines',
            '2021-08-01',
            '2025-06-01',
            'Focused on web development, databases, and systems analysis. Capstone and internship projects involving PHP/MySQL applications.',
            0,
        ]);
        out('✓ Seeded education (update institution in admin)');
    }

    // Experience from internship project hint
    if ((int)$pdo->query('SELECT COUNT(*) FROM experiences')->fetchColumn() === 0) {
        $pdo->prepare(
            'INSERT INTO experiences
            (position, company, location, start_date, end_date, is_current, description, responsibilities, technologies, sort_order)
            VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            'IT Intern / Web Development Intern',
            'Internship Host Company',
            'Philippines',
            '2024-06-01',
            '2024-09-01',
            0,
            'Supported development of an internal HR portal covering leave requests, employee directory, and payroll overview.',
            "Assisted in building and maintaining web modules for HR workflows\nCollaborated with team members on UI and database tasks\nDocumented features and tested forms for reliability",
            'Laravel, MySQL, Bootstrap, PHP',
            0,
        ]);
        out('✓ Seeded sample experience (customize in admin)');
    }

    // Social links
    if ((int)$pdo->query('SELECT COUNT(*) FROM social_links')->fetchColumn() === 0) {
        $links = [
            ['github', 'GitHub', 'https://github.com/bersondamarklenard-afk', 'fa-brands fa-github', 1],
            ['linkedin', 'LinkedIn', 'https://www.linkedin.com/in/mark-lenard-bersonda-b22165258', 'fa-brands fa-linkedin', 2],
            ['email', 'Email', 'mailto:bersondamarklenard@gmail.com', 'fa-solid fa-envelope', 3],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO social_links (platform, label, url, icon_class, sort_order) VALUES (?,?,?,?,?)'
        );
        foreach ($links as $l) {
            $stmt->execute($l);
        }
        out('✓ Seeded social link placeholders');
    }

    // Sample certification
    if ((int)$pdo->query('SELECT COUNT(*) FROM certifications')->fetchColumn() === 0) {
        $pdo->prepare(
            'INSERT INTO certifications (title, issuer, type, issue_date, description, sort_order)
             VALUES (?,?,?,?,?,?)'
        )->execute([
            'Web Development Training',
            'Training Provider',
            'training',
            '2024-01-15',
            'Completed training covering HTML, CSS, JavaScript, PHP, and MySQL fundamentals.',
            0,
        ]);
        out('✓ Seeded sample certification');
    }

    $vis = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $vis->execute(['portfolio_visible']);
    if ($vis->fetchColumn() === false) {
        $pdo->prepare(
            'INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)'
        )->execute(['portfolio_visible', '1', 'text']);
        out('✓ Defaulted portfolio visibility to public');
    }

    out('');
    out('Migration complete.');
    out('Admin login: /admin/login.php (default admin / admin123 if newly created)');
    out('Delete or protect database/migrate.php after use.');

} catch (Throwable $e) {
    out('ERROR: ' . $e->getMessage());
    if (!$isCli) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    exit(1);
}

if (!$isCli) {
    echo '<p><a href="../admin/login.php">Go to Admin Login</a> · <a href="../index.php">View Portfolio</a></p>';
    echo '</body></html>';
}
