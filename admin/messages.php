<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Messages';
$pageDescription = 'Contact form submissions from your portfolio';
$activeNav = 'messages';

if (isset($_GET['read'])) {
    db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([(int)$_GET['read']]);
    redirect('admin/messages.php');
}

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([(int)$_GET['delete']]);
    set_flash('success', 'Message deleted.');
    redirect('admin/messages.php');
}

if (isset($_GET['mark_all'])) {
    db()->exec('UPDATE contact_messages SET is_read = 1');
    set_flash('success', 'All messages marked as read.');
    redirect('admin/messages.php');
}

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Contact messages</h2>
                <div class="sub">Inquiries from the portfolio contact form · <?= count($messages) ?> total</div>
            </div>
            <?php if ($messages): ?>
                <div class="admin-view-actions">
                    <a class="btn btn-outline" href="?mark_all=1">Mark all read</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <?php if (!$messages): ?>
            <div class="empty">
                <div class="empty-icon"><i class="fa-solid fa-envelope"></i></div>
                No messages yet.
            </div>
        <?php else: ?>
            <div class="toolbar">
                <div class="toolbar-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" placeholder="Filter messages…" data-list-filter=".preview-message" aria-label="Filter messages">
                </div>
            </div>
            <div class="preview-messages" id="messagesList">
                <?php foreach ($messages as $m): ?>
                    <article class="preview-message<?= $m['is_read'] ? '' : ' is-new' ?>" data-filter-text="<?= e(mb_strtolower($m['name'] . ' ' . $m['email'] . ' ' . ($m['subject'] ?? '') . ' ' . $m['message'])) ?>">
                        <div class="preview-message-top">
                            <div>
                                <h4><?= e($m['subject'] ?: '(No subject)') ?></h4>
                                <div class="preview-card-meta" style="margin-bottom:0">
                                    <span><strong><?= e($m['name']) ?></strong></span>
                                    <span><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></span>
                                    <span><i class="fa-regular fa-clock"></i> <?= e(date('M j, Y g:ia', strtotime($m['created_at']))) ?></span>
                                </div>
                            </div>
                            <?= $m['is_read'] ? '<span class="badge badge-muted">Read</span>' : '<span class="badge badge-ok">New</span>' ?>
                        </div>
                        <p class="preview-message-body"><?= e($m['message']) ?></p>
                        <div class="preview-item-actions">
                            <?php if (!$m['is_read']): ?>
                                <a class="btn btn-outline btn-sm" href="?read=<?= (int)$m['id'] ?>">Mark read</a>
                            <?php endif; ?>
                            <a class="btn btn-danger btn-sm" href="?delete=<?= (int)$m['id'] ?>" onclick="return confirm('Delete message?')">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
