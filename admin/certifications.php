<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Certifications';
$pageDescription = 'Certifications, awards, and training';
$activeNav = 'certifications';
$types = certification_types();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = db()->prepare('SELECT image FROM certifications WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        delete_upload('certificates', $row['image'] ?? null);
        db()->prepare('DELETE FROM certifications WHERE id = ?')->execute([$id]);
        set_flash('success', 'Entry deleted.');
    }
    redirect('admin/certifications.php');
}

$edit = null;
$editMode = isset($_GET['add']) || isset($_GET['edit']);
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM certifications WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
    if (!$edit) {
        set_flash('error', 'Entry not found.');
        redirect('admin/certifications.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)$_POST['title']);
        $issuer = trim((string)$_POST['issuer']);
        $type = (string)($_POST['type'] ?? 'certification');
        $date = compose_month_year(
            (string)($_POST['issue_month'] ?? ''),
            (string)($_POST['issue_year'] ?? '')
        );
        $desc = trim((string)$_POST['description']);
        $url = trim((string)$_POST['credential_url']);
        $visible = isset($_POST['is_visible']) ? 1 : 0;

        if ($title === '' || !isset($types[$type])) {
            throw new RuntimeException('Title and type are required.');
        }

        $issueMonth = trim((string)($_POST['issue_month'] ?? ''));
        $issueYear = trim((string)($_POST['issue_year'] ?? ''));
        if (($issueMonth !== '' xor $issueYear !== '') || ($issueMonth !== '' && $date === null)) {
            throw new RuntimeException('Issue date needs both a month and a year.');
        }

        $image = $edit['image'] ?? null;
        $newImage = handle_upload(
            $_FILES['image'] ?? [],
            'certificates',
            app_config('uploads.allowed_images', ['jpg', 'jpeg', 'png', 'webp']),
            (int)app_config('uploads.max_size', 5242880)
        );
        if ($newImage) {
            delete_upload('certificates', $image);
            $image = $newImage;
        }
        if (!empty($_POST['remove_image']) && $image) {
            delete_upload('certificates', $image);
            $image = null;
        }

        if ($id > 0) {
            db()->prepare(
                'UPDATE certifications SET title=?, issuer=?, type=?, issue_date=?, description=?, credential_url=?, image=?, is_visible=? WHERE id=?'
            )->execute([$title, $issuer ?: null, $type, $date, $desc ?: null, $url ?: null, $image, $visible, $id]);
            set_flash('success', 'Updated.');
        } else {
            db()->prepare(
                'INSERT INTO certifications (title, issuer, type, issue_date, description, credential_url, image, is_visible) VALUES (?,?,?,?,?,?,?,?)'
            )->execute([$title, $issuer ?: null, $type, $date, $desc ?: null, $url ?: null, $image, $visible]);
            set_flash('success', 'Added.');
        }
        redirect('admin/certifications.php');
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        $failId = (int)($_POST['id'] ?? 0);
        redirect($failId > 0 ? 'admin/certifications.php?edit=' . $failId : 'admin/certifications.php?add=1');
    }
}

$items = get_certifications(false);
$editRow = $edit ?? [];
$issueParts = split_month_year($editRow['issue_date'] ?? null);
$yearNow = (int)date('Y');
$years = range($yearNow + 2, 1980, -1);
$monthOptions = static function (string $selected): void {
    for ($m = 1; $m <= 12; $m++) {
        $label = date('F', mktime(0, 0, 0, $m, 1, 2000));
        $sel = (string)$m === (string)$selected ? ' selected' : '';
        echo '<option value="' . $m . '"' . $sel . '>' . $label . '</option>';
    }
};

if ($editMode) {
    $pageTitle = $edit ? 'Edit Certification' : 'Add Certification';
    $pageDescription = 'Credentials and accomplishments for recruiters';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<div class="admin-page">
    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3><?= $edit ? 'Edit entry' : 'Add certification / achievement' ?></h3>
                <p>Credentials and accomplishments for recruiters.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/certifications.php">Cancel</a>
        </div>
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Issuer / organization</label>
                <input type="text" name="issuer" value="<?= e($edit['issuer'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <?php foreach ($types as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= (($edit['type'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Issue date</label>
                <div class="date-my">
                    <select name="issue_month" aria-label="Issue month">
                        <option value="">Month</option>
                        <?php $monthOptions($issueParts['month']); ?>
                    </select>
                    <select name="issue_year" aria-label="Issue year">
                        <option value="">Year</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?= $year ?>" <?= $issueParts['year'] === (string)$year ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="hint">Month and year only. Example: June 2024.</p>
            </div>
            <div class="form-group full">
                <label>Description</label>
                <textarea name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Credential URL</label>
                <input type="url" name="credential_url" value="<?= e($edit['credential_url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" <?= !isset($edit['is_visible']) || !empty($edit['is_visible']) ? 'checked' : '' ?>> Visible on portfolio</label>
            </div>
            <div class="form-group full">
                <label>Certificate image</label>
                <input type="file" name="image" accept="image/*">
                <?php if (!empty($edit['image'])): ?>
                    <img class="preview-img" src="<?= e(upload_url('certificates', $edit['image'])) ?>" alt="">
                    <label class="checkbox-row"><input type="checkbox" name="remove_image" value="1"> Remove image</label>
                <?php endif; ?>
            </div>
            <div class="form-group full form-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/certifications.php">Cancel</a>
            </div>
        </form>
    </section>
</div>
<?php else: ?>
<div class="admin-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Certifications</h2>
                <div class="sub">Credentials and awards as shown on the portfolio · <?= count($items) ?> total · newest first</div>
            </div>
            <div class="admin-view-actions">
                <a class="btn btn-primary" href="?add=1"><i class="fa-solid fa-plus"></i> Add entry</a>
            </div>
        </div>
    </section>

    <section class="panel">
        <?php if (!$items): ?>
            <p class="empty">No certifications yet. Click Add entry to create one.</p>
        <?php else: ?>
            <div class="preview-cert-grid">
                <?php foreach ($items as $item): ?>
                    <?php $cimg = upload_url('certificates', $item['image'] ?? null); ?>
                    <article class="preview-card">
                        <div class="preview-card-header">
                            <div>
                                <span class="badge badge-muted"><?= e($types[$item['type']] ?? $item['type']) ?></span>
                                <h4 style="margin-top:0.45rem"><?= e($item['title']) ?></h4>
                                <?php if ($item['issuer']): ?>
                                    <div class="preview-card-sub"><?= e($item['issuer']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?= $item['is_visible'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?>
                        </div>
                        <?php if ($item['issue_date']): ?>
                            <div class="preview-card-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?= e(format_month_year($item['issue_date'])) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($item['description']): ?>
                            <p><?= e($item['description']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['credential_url'])): ?>
                            <p><a href="<?= e($item['credential_url']) ?>" target="_blank" rel="noopener">View credential</a></p>
                        <?php endif; ?>
                        <?php if ($cimg): ?>
                            <img class="preview-img" src="<?= e($cimg) ?>" alt="<?= e($item['title']) ?>">
                        <?php endif; ?>
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
