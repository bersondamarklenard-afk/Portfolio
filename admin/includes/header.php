<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $activeNav */
/** @var string $pageDescription */

$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';
$pageDescription = $pageDescription ?? '';
$unread = unread_messages_count();
$adminName = current_admin_username();
$adminInitial = mb_strtoupper(mb_substr($adminName, 0, 1));
try {
    $portfolioPublic = is_portfolio_public();
} catch (Throwable $e) {
    $portfolioPublic = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · Portfolio Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#111111">
    <link rel="icon" href="<?= e(asset_href('images/favicon/favicon.ico')) ?>" sizes="any">
    <link rel="icon" href="<?= e(asset_href('images/favicon/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('images/favicon/favicon-32x32.png')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset_href('images/favicon/apple-touch-icon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset_href('css/admin.css')) ?>?v=16">
</head>
<body>
<div class="overlay" id="sidebarOverlay"></div>
<div class="admin-shell">
    <aside class="sidebar" id="sidebar" aria-label="Admin navigation" aria-hidden="true">
        <div class="sidebar-brand">
            <div class="sidebar-brand-mark">P</div>
            <div class="sidebar-brand-text">
                Portfolio
                <small>Admin</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Menu</div>
            <a href="<?= e(APP_URL) ?>/admin/index.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="<?= e(APP_URL) ?>/admin/personal.php" class="<?= $activeNav === 'personal' ? 'active' : '' ?>">
                <i class="fa-solid fa-user"></i> Personal Information
            </a>
            <a href="<?= e(APP_URL) ?>/admin/skills.php" class="<?= $activeNav === 'skills' ? 'active' : '' ?>">
                <i class="fa-solid fa-code"></i> Skills
            </a>
            <a href="<?= e(APP_URL) ?>/admin/experience.php" class="<?= $activeNav === 'experience' ? 'active' : '' ?>">
                <i class="fa-solid fa-briefcase"></i> Experience
            </a>
            <a href="<?= e(APP_URL) ?>/admin/projects.php" class="<?= $activeNav === 'projects' ? 'active' : '' ?>">
                <i class="fa-solid fa-folder-open"></i> Projects
            </a>
            <a href="<?= e(APP_URL) ?>/admin/education.php" class="<?= $activeNav === 'education' ? 'active' : '' ?>">
                <i class="fa-solid fa-graduation-cap"></i> Education
            </a>
            <a href="<?= e(APP_URL) ?>/admin/certifications.php" class="<?= $activeNav === 'certifications' ? 'active' : '' ?>">
                <i class="fa-solid fa-certificate"></i> Certifications
            </a>
            <a href="<?= e(APP_URL) ?>/admin/social.php" class="<?= $activeNav === 'social' ? 'active' : '' ?>">
                <i class="fa-solid fa-share-nodes"></i> Social Links
            </a>
            <a href="<?= e(APP_URL) ?>/admin/messages.php" class="<?= $activeNav === 'messages' ? 'active' : '' ?>">
                <i class="fa-solid fa-envelope"></i> Messages
                <?php if ($unread > 0): ?>
                    <span class="nav-badge"><?= (int)$unread ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= e(APP_URL) ?>/admin/settings.php" class="<?= $activeNav === 'settings' ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i> Settings
            </a>
            <a href="<?= e(APP_URL) ?>/admin/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </nav>
        <div class="sidebar-foot">
            <div class="sidebar-avatar"><?= e($adminInitial) ?></div>
            <div class="sidebar-user">
                <strong><?= e($adminName) ?></strong>
                <span>Administrator</span>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">
                    <span class="hamburger-icon" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
                <div class="topbar-title-wrap">
                    <h1><?= e($pageTitle) ?></h1>
                    <?php if ($pageDescription !== ''): ?>
                        <p class="topbar-desc"><?= e($pageDescription) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="topbar-actions">
                <a class="visibility-chip<?= $portfolioPublic ? ' is-public' : ' is-private' ?>" href="<?= e(APP_URL) ?>/admin/settings.php" title="Portfolio visibility">
                    <i class="fa-solid <?= $portfolioPublic ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                    <span><?= $portfolioPublic ? 'Public' : 'Hidden' ?></span>
                </a>
                <a class="btn btn-outline btn-sm" href="<?= e(APP_URL) ?>/index.php" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span class="btn-label-full">View site</span>
                    <span class="btn-label-short">Site</span>
                </a>
            </div>
        </header>

        <div class="content">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= e($flash['type'] === 'error' ? 'error' : 'success') ?>" role="status">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
