<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Projects';
$pageDescription = 'Showcase projects, images, and links';
$activeNav = 'projects';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = db()->prepare('SELECT image FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        delete_upload('projects', $row['image'] ?? null);
        db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
        set_flash('success', 'Project deleted.');
    }
    redirect('admin/projects.php');
}

$edit = null;
$editMode = isset($_GET['add']) || isset($_GET['edit']);
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
    if (!$edit) {
        set_flash('error', 'Project not found.');
        redirect('admin/projects.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)$_POST['title']);
        $short = trim((string)$_POST['short_description']);
        $desc = trim((string)$_POST['description']);
        $purpose = trim((string)$_POST['problem_purpose']);
        $features = trim((string)$_POST['key_features']);
        $tech = trim((string)$_POST['technologies']);
        $github = trim((string)$_POST['github_url']);
        $live = trim((string)$_POST['live_url']);
        $sort = (int)$_POST['sort_order'];
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $visible = isset($_POST['is_visible']) ? 1 : 0;

        if ($title === '') {
            throw new RuntimeException('Project title is required.');
        }

        $image = $edit['image'] ?? null;
        $newImage = handle_upload(
            $_FILES['image'] ?? [],
            'projects',
            app_config('uploads.allowed_images', ['jpg', 'jpeg', 'png', 'webp']),
            (int)app_config('uploads.max_size', 5242880)
        );
        if ($newImage) {
            delete_upload('projects', $image);
            $image = $newImage;
        }
        if (!empty($_POST['remove_image']) && $image) {
            delete_upload('projects', $image);
            $image = null;
        }

        if ($id > 0) {
            db()->prepare(
                'UPDATE projects SET title=?, short_description=?, description=?, problem_purpose=?, key_features=?,
                 technologies=?, image=?, github_url=?, live_url=?, is_featured=?, is_visible=?, sort_order=? WHERE id=?'
            )->execute([
                $title, $short ?: null, $desc ?: null, $purpose ?: null, $features ?: null,
                $tech ?: null, $image, $github ?: null, $live ?: null, $featured, $visible, $sort, $id,
            ]);
            set_flash('success', 'Project updated.');
        } else {
            db()->prepare(
                'INSERT INTO projects (title, short_description, description, problem_purpose, key_features, technologies, image, github_url, live_url, is_featured, is_visible, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $title, $short ?: null, $desc ?: null, $purpose ?: null, $features ?: null,
                $tech ?: null, $image, $github ?: null, $live ?: null, $featured, $visible, $sort,
            ]);
            set_flash('success', 'Project added.');
        }
        redirect('admin/projects.php');
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        $failId = (int)($_POST['id'] ?? 0);
        redirect($failId > 0 ? 'admin/projects.php?edit=' . $failId : 'admin/projects.php?add=1');
    }
}

$projects = get_projects(false);

if ($editMode) {
    $pageTitle = $edit ? 'Edit Project' : 'Add Project';
    $pageDescription = 'Featured projects receive stronger emphasis on the public site';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<div class="admin-page">
    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3><?= $edit ? 'Edit project' : 'Add project' ?></h3>
                <p>Featured projects receive stronger emphasis on the public site.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/projects.php">Cancel</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-block">
                <h4 class="form-block-title">Project details</h4>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Title *</label>
                        <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>">
                    </div>
                    <div class="form-group full">
                        <label>Short description</label>
                        <input type="text" name="short_description" maxlength="300" value="<?= e($edit['short_description'] ?? '') ?>">
                    </div>
                    <div class="form-group full">
                        <label>Full description</label>
                        <textarea name="description" rows="5"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Problem / purpose</label>
                        <textarea name="problem_purpose" rows="4"><?= e($edit['problem_purpose'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Key features (one per line)</label>
                        <textarea name="key_features" rows="5"><?= e($edit['key_features'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Technologies (comma-separated)</label>
                        <input type="text" name="technologies" value="<?= e($edit['technologies'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="form-block">
                <h4 class="form-block-title">Links, media &amp; visibility</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>GitHub URL</label>
                        <input type="url" name="github_url" value="<?= e($edit['github_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Live demo URL</label>
                        <input type="url" name="live_url" value="<?= e($edit['live_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Project image</label>
                        <input type="file" name="image" accept="image/*">
                        <?php if (!empty($edit['image'])): ?>
                            <img class="preview-img" src="<?= e(upload_url('projects', $edit['image'])) ?>" alt="">
                            <label class="checkbox-row"><input type="checkbox" name="remove_image" value="1"> Remove image</label>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Sort order</label>
                        <input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                        <label class="checkbox-row"><input type="checkbox" name="is_featured" value="1" <?= !empty($edit['is_featured']) ? 'checked' : '' ?>> Featured project</label>
                        <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" <?= !isset($edit['is_visible']) || !empty($edit['is_visible']) ? 'checked' : '' ?>> Visible</label>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save project</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/projects.php">Cancel</a>
            </div>
        </form>
    </section>
</div>
<?php else: ?>
<div class="admin-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Projects</h2>
                <div class="sub">Selected work as shown on the portfolio · <?= count($projects) ?> total</div>
            </div>
            <div class="admin-view-actions">
                <a class="btn btn-primary" href="?add=1"><i class="fa-solid fa-plus"></i> Add project</a>
            </div>
        </div>
    </section>

    <section class="panel">
        <?php if (!$projects): ?>
            <p class="empty">No projects yet. Click Add project to create one.</p>
        <?php else: ?>
            <div class="preview-projects">
                <?php foreach ($projects as $p): ?>
                    <?php
                    $img = upload_url('projects', $p['image'] ?? null);
                    $techs = tech_to_array($p['technologies'] ?? '');
                    $features = lines_to_array($p['key_features'] ?? '');
                    $viewUrl = !empty($p['live_url'])
                        ? (string)$p['live_url']
                        : (!empty($p['github_url']) ? (string)$p['github_url'] : APP_URL . '/index.php#projects');
                    $viewExternal = !empty($p['live_url']) || !empty($p['github_url']);
                    ?>
                    <article class="preview-project">
                        <div class="preview-project-media">
                            <?php if ($img): ?>
                                <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>">
                            <?php else: ?>
                                <div class="preview-project-media-fallback"><i class="fa-solid fa-code"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="preview-project-body">
                            <div class="preview-project-header">
                                <div class="preview-project-heading">
                                    <h4><?= e($p['title']) ?></h4>
                                    <?php if ($p['short_description']): ?>
                                        <p class="preview-project-lead"><?= e($p['short_description']) ?></p>
                                    <?php elseif ($p['description']): ?>
                                        <p class="preview-project-lead"><?= e(mb_substr((string)$p['description'], 0, 160)) ?><?= mb_strlen((string)$p['description']) > 160 ? '…' : '' ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="preview-project-badges">
                                    <?php if ($p['is_featured']): ?><span class="badge badge-ok">Featured</span><?php endif; ?>
                                    <?= $p['is_visible'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?>
                                </div>
                            </div>
                            <?php if ($p['problem_purpose']): ?>
                                <p class="preview-project-purpose"><strong>Purpose:</strong> <?= e($p['problem_purpose']) ?></p>
                            <?php endif; ?>
                            <?php if ($features): ?>
                                <div class="preview-project-section">
                                    <span class="preview-project-label">Key features</span>
                                    <ul class="preview-project-features">
                                        <?php foreach ($features as $f): ?>
                                            <li><?= e($f) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if ($techs): ?>
                                <div class="preview-project-section preview-project-section--tags">
                                    <span class="preview-project-label">Technologies</span>
                                    <div class="preview-tags">
                                        <?php foreach ($techs as $t): ?>
                                            <span class="preview-tag"><?= e($t) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="preview-project-footer">
                                <div class="preview-project-links">
                                    <?php if (!empty($p['github_url'])): ?>
                                        <a href="<?= e($p['github_url']) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-github"></i> Code</a>
                                    <?php endif; ?>
                                    <?php if (!empty($p['live_url'])): ?>
                                        <a href="<?= e($p['live_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Live</a>
                                    <?php endif; ?>
                                </div>
                                <div class="preview-item-actions preview-project-actions">
                                    <a class="btn btn-outline btn-sm" href="<?= e($viewUrl) ?>"<?= $viewExternal ? ' target="_blank" rel="noopener"' : '' ?>><i class="fa-solid fa-eye"></i> View</a>
                                    <a class="btn btn-outline btn-sm" href="?edit=<?= (int)$p['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                                    <a class="btn btn-danger btn-sm" href="?delete=<?= (int)$p['id'] ?>" onclick="return confirm('Delete this project?')"><i class="fa-solid fa-trash"></i> Delete</a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
