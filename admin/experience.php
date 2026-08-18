<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Experience';
$pageDescription = 'Work history and internship entries';
$activeNav = 'experience';

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM experiences WHERE id = ?')->execute([(int)$_GET['delete']]);
    set_flash('success', 'Experience deleted.');
    redirect('admin/experience.php');
}

$edit = null;
$editMode = isset($_GET['add']) || isset($_GET['edit']);
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM experiences WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
    if (!$edit) {
        set_flash('error', 'Experience entry not found.');
        redirect('admin/experience.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $current = isset($_POST['is_current']) ? 1 : 0;
    $start = compose_month_year(
        (string)($_POST['start_month'] ?? ''),
        (string)($_POST['start_year'] ?? '')
    );
    $end = $current ? null : compose_month_year(
        (string)($_POST['end_month'] ?? ''),
        (string)($_POST['end_year'] ?? '')
    );
    $returnTo = $id > 0 ? 'admin/experience.php?edit=' . $id : 'admin/experience.php?add=1';

    $fields = [
        trim((string)$_POST['position']),
        trim((string)$_POST['company']),
        trim((string)$_POST['location']),
        $start,
        $end,
        $current,
        trim((string)$_POST['description']),
        trim((string)$_POST['responsibilities']),
        trim((string)$_POST['technologies']),
        isset($_POST['is_visible']) ? 1 : 0,
    ];

    if ($fields[0] === '' || $fields[1] === '') {
        set_flash('error', 'Position and company are required.');
        redirect($returnTo);
    }

    $startMonth = trim((string)($_POST['start_month'] ?? ''));
    $startYear = trim((string)($_POST['start_year'] ?? ''));
    if (($startMonth !== '' xor $startYear !== '') || ($startMonth !== '' && $start === null)) {
        set_flash('error', 'Start date needs both a month and a year.');
        redirect($returnTo);
    }

    if (!$current) {
        $endMonth = trim((string)($_POST['end_month'] ?? ''));
        $endYear = trim((string)($_POST['end_year'] ?? ''));
        if (($endMonth !== '' xor $endYear !== '') || ($endMonth !== '' && $end === null)) {
            set_flash('error', 'End date needs both a month and a year, or check Currently working here.');
            redirect($returnTo);
        }
    }

    if ($id > 0) {
        db()->prepare(
            'UPDATE experiences SET position=?, company=?, location=?, start_date=?, end_date=?, is_current=?,
             description=?, responsibilities=?, technologies=?, is_visible=? WHERE id=?'
        )->execute([...$fields, $id]);
        set_flash('success', 'Experience updated.');
    } else {
        db()->prepare(
            'INSERT INTO experiences (position, company, location, start_date, end_date, is_current, description, responsibilities, technologies, is_visible)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute($fields);
        set_flash('success', 'Experience added.');
    }
    redirect('admin/experience.php');
}

$items = get_experiences(false);
$editRow = $edit ?? [];
$startParts = split_month_year($editRow['start_date'] ?? null);
$endParts = split_month_year($editRow['end_date'] ?? null);
$yearNow = (int)date('Y');
$years = range($yearNow + 2, 1980, -1);

$monthOptions = static function (string $selected): void {
    for ($m = 1; $m <= 12; $m++) {
        $label = date('F', mktime(0, 0, 0, $m, 1, 2000));
        $sel = (string)$m === (string)$selected ? 'selected' : '';
        echo '<option value="' . $m . '"' . $sel . '>' . $label . '</option>';
    }
};

if ($editMode) {
    $pageTitle = $edit ? 'Edit Experience' : 'Add Experience';
    $pageDescription = 'Roles, dates, responsibilities, and technologies';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<div class="admin-page">
    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3><?= $edit ? 'Edit experience' : 'Add experience' ?></h3>
                <p>Roles, dates, responsibilities, and technologies shown on the portfolio.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/experience.php">Cancel</a>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-block">
                <h4 class="form-block-title">Role details</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Position *</label>
                        <input type="text" name="position" required value="<?= e($edit['position'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Company / organization *</label>
                        <input type="text" name="company" required value="<?= e($edit['company'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?= e($edit['location'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Start date</label>
                        <div class="date-my">
                            <select name="start_month" aria-label="Start month">
                                <option value="">Month</option>
                                <?php $monthOptions($startParts['month']); ?>
                            </select>
                            <select name="start_year" aria-label="Start year">
                                <option value="">Year</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year ?>" <?= $startParts['year'] === (string)$year ? 'selected' : '' ?>><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="hint">Month and year only. Example: June 2024.</p>
                    </div>
                    <div class="form-group">
                        <label>End date</label>
                        <div class="date-my">
                            <select name="end_month" id="end_month" aria-label="End month" <?= !empty($edit['is_current']) ? 'disabled' : '' ?>>
                                <option value="">Month</option>
                                <?php $monthOptions($endParts['month']); ?>
                            </select>
                            <select name="end_year" id="end_year" aria-label="End year" <?= !empty($edit['is_current']) ? 'disabled' : '' ?>>
                                <option value="">Year</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year ?>" <?= $endParts['year'] === (string)$year ? 'selected' : '' ?>><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <label class="checkbox-row"><input type="checkbox" name="is_current" id="is_current" value="1" <?= !empty($edit['is_current']) ? 'checked' : '' ?>> Currently working here (Present)</label>
                        <p class="hint">Month and year only, or check Currently working here for Present. Example: May 2024.</p>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" <?= !isset($edit['is_visible']) || !empty($edit['is_visible']) ? 'checked' : '' ?>> Visible</label>
                    </div>
                </div>
            </div>
            <div class="form-block">
                <h4 class="form-block-title">Description</h4>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Responsibilities (one per line)</label>
                        <textarea name="responsibilities" rows="6"><?= e($edit['responsibilities'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Technologies (comma-separated)</label>
                        <input type="text" name="technologies" value="<?= e($edit['technologies'] ?? '') ?>" placeholder="PHP, MySQL, Bootstrap">
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/experience.php">Cancel</a>
            </div>
        </form>
    </section>
</div>
<script>
(() => {
  const current = document.getElementById('is_current');
  const endMonth = document.getElementById('end_month');
  const endYear = document.getElementById('end_year');
  if (!current || !endMonth || !endYear) return;
  const sync = () => {
    endMonth.disabled = current.checked;
    endYear.disabled = current.checked;
  };
  current.addEventListener('change', sync);
  sync();
})();
</script>
<?php else: ?>
<div class="admin-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Experience</h2>
                <div class="sub">Work and internship entries as shown on the portfolio · <?= count($items) ?> total · newest first</div>
            </div>
            <div class="admin-view-actions">
                <a class="btn btn-primary" href="?add=1"><i class="fa-solid fa-plus"></i> Add experience</a>
            </div>
        </div>
    </section>

    <section class="panel">
        <?php if (!$items): ?>
            <p class="empty">No experience entries yet. Click Add experience to create one.</p>
        <?php else: ?>
            <div class="preview-cards">
                <?php foreach ($items as $item): ?>
                    <?php
                    $resp = lines_to_array($item['responsibilities'] ?? '');
                    $techs = tech_to_array($item['technologies'] ?? '');
                    ?>
                    <article class="preview-card">
                        <div class="preview-card-header">
                            <div>
                                <h4><?= e($item['position']) ?></h4>
                                <div class="preview-card-sub"><?= e($item['company']) ?></div>
                            </div>
                            <?= $item['is_visible'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?>
                        </div>
                        <div class="preview-card-meta">
                            <span><i class="fa-regular fa-calendar"></i> <?= e(format_month_year_range($item['start_date'], $item['end_date'], (bool)$item['is_current'])) ?></span>
                            <?php if ($item['location']): ?>
                                <span><i class="fa-solid fa-location-dot"></i> <?= e($item['location']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item['description']): ?>
                            <p><?= e($item['description']) ?></p>
                        <?php endif; ?>
                        <?php if ($resp): ?>
                            <ul>
                                <?php foreach ($resp as $line): ?>
                                    <li><?= e($line) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($techs): ?>
                            <div class="preview-tags">
                                <?php foreach ($techs as $t): ?>
                                    <span class="preview-tag"><?= e($t) ?></span>
                                <?php endforeach; ?>
                            </div>
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
