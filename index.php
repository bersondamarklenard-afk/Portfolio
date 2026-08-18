<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
send_no_store_headers();

try {
    if (!is_portfolio_public()) {
        require __DIR__ . '/includes/portfolio-private.php';
        exit;
    }
} catch (Throwable $e) {
    // If settings cannot be read, keep the public site available.
}

try {
    $personal = get_personal();
    $skills = get_skills(true);
    $skillsGrouped = group_skills_by_category($skills);
    $experiences = get_experiences(true);
    $projects = get_projects(true);
    $education = get_education(true);
    $certifications = get_certifications(true);
    $socialLinks = get_social_links(true);
} catch (Throwable $e) {
    if (APP_DEBUG) {
        die('Setup required. Run <a href="database/migrate.php">database/migrate.php</a> first.<br>' . e($e->getMessage()));
    }
    die('Portfolio is being set up. Please check back shortly.');
}

if (!$personal) {
    die('Portfolio content not found. Please run <a href="database/migrate.php">database/migrate.php</a>.');
}

$name = trim((string)($personal['full_name'] ?? '')) ?: 'Portfolio';
$title = trim((string)($personal['professional_title'] ?? ''));
$tagline = trim((string)($personal['tagline'] ?? ''));
$about = trim((string)($personal['about_me'] ?? ''));
$whatIDo = trim((string)($personal['what_i_do'] ?? ''));
$objective = trim((string)($personal['career_objective'] ?? ''));
$interests = trim((string)($personal['interests'] ?? ''));
$strengths = trim((string)($personal['strengths'] ?? ''));
$email = trim((string)($personal['email'] ?? ''));
$phone = trim((string)($personal['phone'] ?? ''));
$location = trim((string)($personal['location'] ?? ''));
$photo = $personal['profile_photo'] ?? null;
$resume = $personal['resume_file'] ?? null;
$availability = trim((string)($personal['availability_status'] ?? ''));

$defaultMetaTitle = trim($name . ' · ' . $title);
$defaultMetaDesc = 'Portfolio of ' . $name . ' — IT graduate and aspiring IT professional specializing in PHP, MySQL, JavaScript, and web development. Explore projects, skills, experience, and contact information.';
$metaTitle = trim((string)($personal['meta_title'] ?? '')) ?: $defaultMetaTitle;
$metaDesc = trim((string)($personal['meta_description'] ?? '')) ?: $defaultMetaDesc;
if (mb_strlen($metaDesc) > 165) {
    $metaDesc = rtrim(mb_substr($metaDesc, 0, 162)) . '…';
}

$footerText = $personal['footer_text'] ?? '';
$photoUrl = upload_url('avatars', $photo);
$resumeDownloadUrl = null;
$resumeDownloadName = null;
if (upload_exists('resumes', $resume)) {
    $resumeDownloadUrl = site_href('download-resume.php');
    $resumeExt = strtolower(pathinfo((string)$resume, PATHINFO_EXTENSION) ?: 'pdf');
    $resumeDownloadName = resume_download_filename($name, $resumeExt);
}

$contactFlash = $_SESSION['contact_flash'] ?? null;
unset($_SESSION['contact_flash']);

$faviconBase = 'images/favicon';
$ogImage = $photoUrl ?: asset($faviconBase . '/android-chrome-512x512.png');
$canonicalUrl = rtrim(APP_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDesc) ?>">
    <meta name="author" content="<?= e($name) ?>">
    <meta name="robots" content="index, follow">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta name="theme-color" content="#111111">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($name) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDesc) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:locale" content="en_US">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDesc) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <link rel="icon" href="<?= e(asset_href($faviconBase . '/favicon.ico')) ?>" sizes="any">
    <link rel="icon" href="<?= e(asset_href($faviconBase . '/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href($faviconBase . '/favicon-32x32.png')) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset_href($faviconBase . '/favicon-16x16.png')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset_href($faviconBase . '/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= e(asset_href($faviconBase . '/site.webmanifest')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset_href('css/portfolio.css')) ?>?v=26">
</head>
<body>
<header class="site-nav" id="siteNav">
    <div class="nav-inner">
        <a class="nav-brand" href="#home"><?= e(explode(' ', trim($name))[0] ?? 'Portfolio') ?><span>.</span></a>
        <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-links" id="navLinks" aria-label="Primary">
            <a href="#about">About</a>
            <a href="#skills">Skills</a>
            <a href="#experience">Experience</a>
            <a href="#projects">Projects</a>
            <a href="#education">Education</a>
            <a href="#certifications">Certifications</a>
            <a class="nav-cta" href="#contact">Contact</a>
        </nav>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="hero" id="home">
        <div class="container hero-grid">
            <div class="hero-copy">
                <?php if ($availability): ?>
                    <div class="hero-availability"><?= e($availability) ?></div>
                <?php endif; ?>
                <h1 class="hero-name"><?= e($name) ?></h1>
                <?php if ($title !== ''): ?>
                    <p class="hero-title"><?= e($title) ?></p>
                <?php endif; ?>
                <?php if ($tagline): ?>
                    <p class="hero-value"><?= e($tagline) ?></p>
                <?php endif; ?>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#projects"><i class="fa-solid fa-folder-open"></i> View My Projects</a>
                    <?php if ($resumeDownloadUrl): ?>
                        <a class="btn btn-outline" href="<?= e($resumeDownloadUrl) ?>" download="<?= e($resumeDownloadName) ?>"><i class="fa-solid fa-download"></i> Download Resume</a>
                    <?php endif; ?>
                    <a class="btn btn-accent" href="#contact"><i class="fa-solid fa-paper-plane"></i> Contact Me</a>
                </div>
                <div class="hero-meta">
                    <?php if ($location): ?>
                        <span><i class="fa-solid fa-location-dot"></i> <?= e($location) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($personal['years_experience'])): ?>
                        <span><i class="fa-solid fa-briefcase"></i> <?= e($personal['years_experience']) ?> experience</span>
                    <?php endif; ?>
                    <?php if ($email): ?>
                        <span><i class="fa-solid fa-envelope"></i> <?= e($email) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-accent-block" aria-hidden="true"></div>
                <div class="hero-photo-wrap">
                    <?php if ($photoUrl): ?>
                        <img src="<?= e($photoUrl) ?>" alt="Professional photo of <?= e($name) ?>" width="400" height="500">
                    <?php else: ?>
                        <div class="hero-photo-fallback"><?= e(initials($name)) ?></div>
                    <?php endif; ?>
                    <div class="hero-photo-frame" aria-hidden="true"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="section section-alt" id="about">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-eyebrow">About</span>
                <h2>Who I am</h2>
                <p>A concise look at my background, focus, and what I bring to a team.</p>
            </div>
            <?php
            $roleItems = split_portfolio_items($whatIDo);
            $strengthItems = split_portfolio_items($strengths);
            $interestItems = split_portfolio_items($interests);
            $hasIntroCard = trim((string) $about) !== '';
            $showRoleCard = $roleItems !== [];
            $hasTraitCard = trim((string) $strengths) !== '' || trim((string) $interests) !== '';
            $aboutCardCount = (int) $hasIntroCard + (int) $showRoleCard + (int) (trim((string) $objective) !== '') + (int) $hasTraitCard;
            ?>
            <div class="about-cards<?= $aboutCardCount <= 1 ? ' about-cards--solo' : '' ?>">
                <?php if ($hasIntroCard): ?>
                    <article class="about-card about-card--wide reveal">
                        <header class="about-card-head">
                            <span class="about-card-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                            <h3>Who I am</h3>
                        </header>
                        <?php foreach (lines_to_array($about) ?: [$about] as $para): ?>
                            <?php if (trim((string) $para) !== ''): ?>
                                <p><?= e($para) ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </article>
                <?php endif; ?>
                <?php if ($showRoleCard): ?>
                    <article class="about-card about-card--wide reveal">
                        <header class="about-card-head">
                            <span class="about-card-icon" aria-hidden="true"><i class="fa-solid fa-briefcase"></i></span>
                            <h3>What I do</h3>
                        </header>
                        <ul class="about-focus about-focus--row" style="--roles: <?= max(count($roleItems), 1) ?>">
                            <?php foreach ($roleItems as $role): ?>
                                <li><?= e($role) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endif; ?>
                <?php if (trim((string) $objective) !== ''): ?>
                    <article class="about-card reveal">
                        <header class="about-card-head">
                            <span class="about-card-icon" aria-hidden="true"><i class="fa-solid fa-bullseye"></i></span>
                            <h3>Career objective</h3>
                        </header>
                        <p><?= e($objective) ?></p>
                    </article>
                <?php endif; ?>
                <?php if ($hasTraitCard): ?>
                    <article class="about-card reveal">
                        <header class="about-card-head">
                            <span class="about-card-icon" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
                            <h3>Strengths &amp; interests</h3>
                        </header>
                        <div class="about-card-split<?= (trim((string) $strengths) !== '' && trim((string) $interests) !== '') ? '' : ' about-card-split--solo' ?>">
                            <?php if (trim((string) $strengths) !== ''): ?>
                                <div>
                                    <h4>Strengths</h4>
                                    <?php if (count($strengthItems) > 1): ?>
                                        <ul class="about-list">
                                            <?php foreach ($strengthItems as $item): ?>
                                                <li><?= e($item) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p><?= e($strengths) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (trim((string) $interests) !== ''): ?>
                                <div>
                                    <h4>Interests</h4>
                                    <?php if (count($interestItems) > 1): ?>
                                        <ul class="about-list">
                                            <?php foreach ($interestItems as $item): ?>
                                                <li><?= e($item) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p><?= e($interests) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endif; ?>
                <?php if ($aboutCardCount === 0): ?>
                    <p class="empty-hint">About content coming soon.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SKILLS -->
    <section class="section" id="skills">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-eyebrow">Skills</span>
                <h2>Technical toolkit</h2>
                <p>Technologies and tools I use to design, build, and ship web solutions.</p>
            </div>
            <?php if ($skillsGrouped): ?>
                <div class="skills-categories">
                    <?php foreach ($skillsGrouped as $group): ?>
                        <div class="skill-category reveal">
                            <h3><?= e($group['label']) ?></h3>
                            <div class="skill-grid">
                                <?php foreach ($group['items'] as $skill): ?>
                                    <div class="skill-item" style="--level: <?= (int)($skill['proficiency'] ?? 70) ?>%">
                                        <div class="skill-item-top">
                                            <div class="skill-icon">
                                                <i class="<?= e($skill['icon_class'] ?: 'fa-solid fa-code') ?>"></i>
                                            </div>
                                            <span class="skill-name"><?= e($skill['name']) ?></span>
                                        </div>
                                        <div class="skill-bar" aria-hidden="true"><span></span></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-hint">Skills will appear here once added in the admin panel.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- EXPERIENCE -->
    <section class="section section-alt" id="experience">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-eyebrow">Experience</span>
                <h2>Work &amp; internships</h2>
                <p>Roles where I applied IT and web development skills in real environments.</p>
            </div>
            <?php if ($experiences): ?>
                <div class="timeline">
                    <?php foreach ($experiences as $exp): ?>
                        <article class="timeline-item reveal">
                            <div class="timeline-dot" aria-hidden="true"></div>
                            <div class="exp-card">
                                <div class="exp-header">
                                    <div>
                                        <h3><?= e($exp['position']) ?></h3>
                                        <div class="exp-company"><?= e($exp['company']) ?></div>
                                    </div>
                                    <div class="exp-meta">
                                        <span><?= e(format_month_year_range($exp['start_date'], $exp['end_date'], (bool)$exp['is_current'])) ?></span>
                                        <?php if ($exp['location']): ?>
                                            <span><i class="fa-solid fa-location-dot"></i> <?= e($exp['location']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($exp['description']): ?>
                                    <p><?= e($exp['description']) ?></p>
                                <?php endif; ?>
                                <?php $resp = lines_to_array($exp['responsibilities'] ?? ''); ?>
                                <?php if ($resp): ?>
                                    <ul class="exp-list">
                                        <?php foreach ($resp as $item): ?>
                                            <li><?= e($item) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php $techs = tech_to_array($exp['technologies'] ?? ''); ?>
                                <?php if ($techs): ?>
                                    <div class="tech-tags">
                                        <?php foreach ($techs as $t): ?>
                                            <span class="tech-tag"><?= e($t) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-hint">Experience entries will appear here once added in the admin panel.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- PROJECTS -->
    <section class="section" id="projects">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-eyebrow">Projects</span>
                <h2>Selected work</h2>
                <p>Featured projects that demonstrate problem-solving, stack choices, and delivery.</p>
            </div>
            <?php if ($projects): ?>
                <div class="project-grid">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $isFeatured = (bool)$project['is_featured'];
                        $img = upload_url('projects', $project['image'] ?? null);
                        $features = lines_to_array($project['key_features'] ?? '');
                        $techs = tech_to_array($project['technologies'] ?? '');
                        ?>
                        <article class="project-card<?= $isFeatured ? ' featured' : '' ?> reveal">
                            <div class="project-media">
                                <?php if ($isFeatured): ?>
                                    <span class="project-badge">Featured</span>
                                <?php endif; ?>
                                <?php if ($img): ?>
                                    <img src="<?= e($img) ?>" alt="<?= e($project['title']) ?> screenshot" loading="lazy" width="640" height="400">
                                <?php else: ?>
                                    <div class="project-media-fallback"><i class="fa-solid fa-code"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="project-body">
                                <h3><?= e($project['title']) ?></h3>
                                <?php if ($project['short_description']): ?>
                                    <p class="lead"><?= e($project['short_description']) ?></p>
                                <?php elseif ($project['description']): ?>
                                    <p class="lead"><?= e(mb_substr((string)$project['description'], 0, 120)) ?><?= mb_strlen((string)$project['description']) > 120 ? '…' : '' ?></p>
                                <?php endif; ?>
                                <?php if ($project['problem_purpose']): ?>
                                    <p class="project-purpose"><strong>Purpose:</strong> <?= e($project['problem_purpose']) ?></p>
                                <?php endif; ?>
                                <?php if ($features): ?>
                                    <ul class="project-features">
                                        <?php foreach (array_slice($features, 0, 3) as $f): ?>
                                            <li><?= e($f) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <div class="project-footer">
                                    <div class="tech-tags">
                                        <?php foreach (array_slice($techs, 0, 6) as $t): ?>
                                            <span class="tech-tag"><?= e($t) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="project-links">
                                        <?php if (!empty($project['github_url'])): ?>
                                            <a href="<?= e($project['github_url']) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-github"></i> Code</a>
                                        <?php endif; ?>
                                        <?php if (!empty($project['live_url'])): ?>
                                            <a href="<?= e($project['live_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Live</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-hint">Projects will appear here once added in the admin panel.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- EDUCATION -->
    <section class="section section-alt" id="education">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-eyebrow">Education</span>
                <h2>Academic background</h2>
                <p>Formal education that shaped my foundation in IT and software development.</p>
            </div>
            <?php if ($education): ?>
                <div class="edu-list">
                    <div class="edu-list-head" aria-hidden="true">
                        <span>Level</span>
                        <span>Institution</span>
                        <span>Degree / program</span>
                        <span>Years</span>
                    </div>
                    <?php foreach ($education as $edu): ?>
                        <article class="edu-row reveal">
                            <span class="edu-level" data-label="Level"><?= e(education_category_label($edu['category'] ?? '')) ?></span>
                            <span class="edu-school" data-label="Institution"><?= e($edu['institution']) ?></span>
                            <h3 class="edu-degree" data-label="Degree / program"><?= e($edu['degree']) ?></h3>
                            <span class="edu-year" data-label="Years">
                                <?= e(format_month_year_range($edu['start_date'], $edu['end_date'], (bool)$edu['is_current'])) ?>
                                <?php if (!empty($edu['location'])): ?>
                                    <span class="edu-location"><?= e($edu['location']) ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($edu['academic_honor'])): ?>
                                <p class="edu-honor" data-label="Award / average"><?= e($edu['academic_honor']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($edu['description'])): ?>
                                <p class="edu-desc"><?= e($edu['description']) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-hint">Education details will appear here once added in the admin panel.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- CERTIFICATIONS -->
    <section class="section" id="certifications">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-eyebrow">Achievements</span>
                <h2>Certifications &amp; awards</h2>
                <p>Training, credentials, and recognitions that support my professional profile.</p>
            </div>
            <?php if ($certifications): ?>
                <?php
                $certCount = count($certifications);
                $certGridMod = $certCount === 1 ? ' cert-grid--solo' : ($certCount === 2 ? ' cert-grid--two' : '');
                ?>
                <div class="cert-grid<?= $certGridMod ?>">
                    <?php foreach ($certifications as $cert): ?>
                        <?php
                        $cimg = upload_url('certificates', $cert['image'] ?? null);
                        $certTitle = (string)($cert['title'] ?? '');
                        $certIssuer = (string)($cert['issuer'] ?? '');
                        ?>
                        <article class="cert-card<?= $cimg ? ' cert-card--clickable' : '' ?> reveal">
                            <div class="cert-card-body">
                                <span class="cert-type"><?= e(certification_type_label($cert['type'] ?? '')) ?></span>
                                <h3><?= e($certTitle) ?></h3>
                                <?php if ($certIssuer !== ''): ?>
                                    <p class="cert-org"><?= e($certIssuer) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($cert['issue_date'])): ?>
                                    <time class="cert-date" datetime="<?= e(substr((string)$cert['issue_date'], 0, 7)) ?>"><?= e(format_month_year($cert['issue_date'])) ?></time>
                                <?php endif; ?>
                                <?php if (!empty($cert['description'])): ?>
                                    <p class="cert-desc"><?= e($cert['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($cert['credential_url'])): ?>
                                <a class="cert-link" href="<?= e($cert['credential_url']) ?>" target="_blank" rel="noopener">View credential <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                            <?php endif; ?>
                            <?php if ($cimg): ?>
                                <span class="cert-cue" aria-hidden="true"><i class="fa-solid fa-expand"></i> View certificate</span>
                                <button
                                    type="button"
                                    class="cert-card-hit"
                                    data-cert-src="<?= e($cimg) ?>"
                                    data-cert-title="<?= e($certTitle) ?>"
                                    data-cert-org="<?= e($certIssuer) ?>"
                                    aria-haspopup="dialog"
                                    aria-label="View certificate: <?= e($certTitle) ?>"
                                ></button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-hint">Certifications will appear here once added in the admin panel.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="section section-alt" id="contact">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-eyebrow">Contact</span>
                <h2>Let’s connect</h2>
                <p>Open to roles, interviews, and collaborations. Reach out and I’ll respond promptly.</p>
            </div>
            <div class="contact-grid">
                <div class="contact-info reveal">
                    <?php if ($email): ?>
                        <div class="contact-item">
                            <i class="fa-solid fa-envelope"></i>
                            <div>
                                <strong>Email</strong>
                                <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                        <div class="contact-item">
                            <i class="fa-solid fa-phone"></i>
                            <div>
                                <strong>Phone</strong>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($location): ?>
                        <div class="contact-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <div>
                                <strong>Location</strong>
                                <span><?= e($location) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($socialLinks): ?>
                        <div class="social-row">
                            <?php foreach ($socialLinks as $link): ?>
                                <?php
                                $socialHref = resolve_social_url($link, $email);
                                if (!$socialHref) {
                                    continue;
                                }
                                $socialLabel = (string)($link['label'] ?: $link['platform']);
                                $socialExternal = social_link_is_external($socialHref);
                                ?>
                                <a
                                    href="<?= e($socialHref) ?>"
                                    title="<?= e($socialLabel) ?>"
                                    aria-label="<?= e($socialLabel) ?>"
                                    <?php if ($socialExternal): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                                >
                                    <i class="<?= e($link['icon_class'] ?: 'fa-solid fa-link') ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form class="contact-form reveal" method="post" action="<?= e(APP_URL . '/contact.php') ?>">
                    <?= csrf_field() ?>
                    <?php if ($contactFlash): ?>
                        <div class="form-alert <?= e($contactFlash['type']) ?>"><?= e($contactFlash['message']) ?></div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required maxlength="120" autocomplete="name">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required maxlength="150" autocomplete="email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" maxlength="200" placeholder="Job opportunity, collaboration, etc.">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required maxlength="5000" placeholder="Share a short note about the role or how I can help."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                </form>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand"><?= e(explode(' ', trim($name))[0] ?? 'Portfolio') ?><span>.</span></div>
                <?php if ($footerText !== '' || $title !== ''): ?>
                    <p><?= e($footerText !== '' ? $footerText : $title) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h4>Navigate</h4>
                <div class="footer-links">
                    <a href="#about">About</a>
                    <a href="#skills">Skills</a>
                    <a href="#projects">Projects</a>
                    <a href="#contact">Contact</a>
                </div>
            </div>
            <div>
                <h4>Connect</h4>
                <div class="footer-links">
                    <?php foreach ($socialLinks as $link): ?>
                        <?php
                        $socialHref = resolve_social_url($link, $email);
                        if (!$socialHref) {
                            continue;
                        }
                        ?>
                        <a
                            href="<?= e($socialHref) ?>"
                            <?php if (social_link_is_external($socialHref)): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                        ><?= e($link['label'] ?: ucfirst((string)$link['platform'])) ?></a>
                    <?php endforeach; ?>
                    <?php if ($email): ?>
                        <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e($name) ?>. All rights reserved.</span>
            <span>Built with PHP &amp; MySQL · Hostinger-ready</span>
        </div>
    </div>
</footer>

<div class="cert-modal" id="certModal" aria-hidden="true">
    <div class="cert-modal-backdrop" data-cert-close></div>
    <button type="button" class="cert-modal-close" data-cert-close aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <div class="cert-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="certModalCaption" tabindex="-1">
        <img id="certModalImage" alt="">
        <p id="certModalCaption" class="cert-modal-caption"></p>
    </div>
</div>

<script src="<?= e(asset_href('js/portfolio.js')) ?>?v=3"></script>
</body>
</html>
