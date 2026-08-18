<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Social Links';
$pageDescription = 'GitHub, LinkedIn, and other professional links';
$activeNav = 'social';

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM social_links WHERE id = ?')->execute([(int)$_GET['delete']]);
    set_flash('success', 'Link deleted.');
    redirect('admin/social.php');
}

$edit = null;
$editMode = isset($_GET['add']) || isset($_GET['edit']);
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM social_links WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
    if (!$edit) {
        set_flash('error', 'Link not found.');
        redirect('admin/social.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $platform = trim((string)$_POST['platform']);
    $label = trim((string)$_POST['label']);
    $url = trim((string)$_POST['url']);
    $icon = trim((string)$_POST['icon_class']);
    $sort = (int)$_POST['sort_order'];
    $visible = isset($_POST['is_visible']) ? 1 : 0;

    if ($platform === '' || $url === '') {
        set_flash('error', 'Platform and URL are required.');
        redirect($id > 0 ? 'admin/social.php?edit=' . $id : 'admin/social.php?add=1');
    }

    if ($id > 0) {
        db()->prepare(
            'UPDATE social_links SET platform=?, label=?, url=?, icon_class=?, sort_order=?, is_visible=? WHERE id=?'
        )->execute([$platform, $label ?: null, $url, $icon ?: null, $sort, $visible, $id]);
        set_flash('success', 'Link updated.');
    } else {
        db()->prepare(
            'INSERT INTO social_links (platform, label, url, icon_class, sort_order, is_visible) VALUES (?,?,?,?,?,?)'
        )->execute([$platform, $label ?: null, $url, $icon ?: null, $sort, $visible]);
        set_flash('success', 'Link added.');
    }
    redirect('admin/social.php');
}

$items = get_social_links(false);

if ($editMode) {
    $pageTitle = $edit ? 'Edit Social Link' : 'Add Social Link';
    $pageDescription = 'Shown in the contact section and footer';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<div class="admin-page">
    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3><?= $edit ? 'Edit link' : 'Add social / professional link' ?></h3>
                <p>Shown in the contact section and footer.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/social.php">Cancel</a>
        </div>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-group">
                <label>Platform key *</label>
                <input type="text" name="platform" required value="<?= e($edit['platform'] ?? '') ?>" placeholder="github, linkedin, facebook">
            </div>
            <div class="form-group">
                <label>Display label</label>
                <input type="text" name="label" value="<?= e($edit['label'] ?? '') ?>" placeholder="GitHub">
            </div>
            <div class="form-group full">
                <label>URL *</label>
                <input type="url" name="url" required value="<?= e($edit['url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Icon class</label>
                <input type="text" name="icon_class" value="<?= e($edit['icon_class'] ?? '') ?>" placeholder="fa-brands fa-github">
            </div>
            <div class="form-group">
                <label>Sort order</label>
                <input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" <?= !isset($edit['is_visible']) || !empty($edit['is_visible']) ? 'checked' : '' ?>> Visible</label>
            </div>
            <div class="form-group full form-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/social.php">Cancel</a>
            </div>
        </form>
    </section>
</div>
<?php else: ?>
<div class="admin-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Social links</h2>
                <div class="sub">Professional links as shown on the portfolio · <?= count($items) ?> total</div>
            </div>
            <div class="admin-view-actions">
                <a class="btn btn-primary" href="?add=1"><i class="fa-solid fa-plus"></i> Add link</a>
            </div>
        </div>
    </section>

    <section class="panel">
        <?php if (!$items): ?>
            <p class="empty">No social links yet. Click Add link to create one.</p>
        <?php else: ?>
            <div class="preview-social-grid">
                <?php foreach ($items as $item): ?>
                    <article class="preview-card">
                        <div class="preview-social-card" style="padding:0;border:0;background:transparent">
                            <div class="preview-social-icon"><i class="<?= e($item['icon_class'] ?: 'fa-solid fa-link') ?>"></i></div>
                            <div style="min-width:0;flex:1">
                                <strong><?= e($item['label'] ?: $item['platform']) ?></strong>
                                <a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"><?= e(mb_substr($item['url'], 0, 48)) ?></a>
                                <div class="preview-card-meta" style="margin-top:0.35rem;margin-bottom:0">
                                    <span class="badge badge-muted"><?= e($item['platform']) ?></span>
                                    <?= $item['is_visible'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?>
                                </div>
                            </div>
                        </div>
                        <div class="preview-item-actions">
                            <a class="btn btn-outline btn-sm" href="?edit=<?= (int)$item['id'] ?>">Edit</a>
                            <a class="btn btn-danger btn-sm" href="?delete=<?= (int)$item['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
