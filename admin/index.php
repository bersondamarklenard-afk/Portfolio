<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Dashboard';
$pageDescription = 'Portfolio content overview';
$activeNav = 'dashboard';

$stats = [
    'projects' => count_table('projects'),
    'skills' => count_table('skills'),
    'experiences' => count_table('experiences'),
    'certifications' => count_table('certifications'),
    'messages' => unread_messages_count(),
];

$recentProjects = array_slice(get_projects(false), 0, 5);
$personal = get_personal();
$firstName = $personal ? (explode(' ', trim($personal['full_name']))[0] ?? '') : '';

require __DIR__ . '/includes/header.php';
?>

<div class="page-intro">
    <h2>Welcome<?= $firstName !== '' ? ', ' . e($firstName) : '' ?></h2>
    <p>Update your content below. Changes appear on the public site immediately when the portfolio is public.</p>
</div>

<?php $portfolioPublic = is_portfolio_public(); ?>
<?php require __DIR__ . '/includes/visibility-panel.php'; ?>

<div class="stats">
    <div class="stat-card">
        <div class="stat-meta">
            <div class="label">Projects</div>
            <div class="value"><?= (int)$stats['projects'] ?></div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-meta">
            <div class="label">Skills</div>
            <div class="value"><?= (int)$stats['skills'] ?></div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-code"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-meta">
            <div class="label">Experience</div>
            <div class="value"><?= (int)$stats['experiences'] ?></div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
    </div>
    <div class="stat-card">
        <div class="stat-meta">
            <div class="label">Certifications</div>
            <div class="value"><?= (int)$stats['certifications'] ?></div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-certificate"></i></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Recent projects</h2>
            <div class="sub">Your latest portfolio work</div>
        </div>
        <a class="btn btn-primary btn-sm" href="<?= e(APP_URL) ?>/admin/projects.php">
            <i class="fa-solid fa-plus"></i> Add project
        </a>
    </div>
    <?php if (!$recentProjects): ?>
        <div class="empty">
            <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
            No projects yet.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentProjects as $p): ?>
                    <tr>
                        <td>
                            <strong><?= e($p['title']) ?></strong>
                            <?php if ($p['is_featured']): ?>
                                <span class="badge badge-ok badge-inline">Featured</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['is_visible'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?></td>
                        <td class="actions">
                            <a class="btn btn-outline btn-sm" href="<?= e(APP_URL) ?>/admin/projects.php?edit=<?= (int)$p['id'] ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ((int)$stats['messages'] > 0): ?>
<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Unread messages</h2>
            <div class="sub"><?= (int)$stats['messages'] ?> waiting for review</div>
        </div>
        <a class="btn btn-outline btn-sm" href="<?= e(APP_URL) ?>/admin/messages.php">Open inbox</a>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
