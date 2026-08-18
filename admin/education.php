<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
ensure_education_schema();

$pageTitle = 'Education';
$pageDescription = 'Degrees and academic background';
$activeNav = 'education';
$categories = education_categories();

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM education WHERE id = ?')->execute([(int)$_GET['delete']]);
    set_flash('success', 'Education entry deleted.');
    redirect('admin/education.php');
}

$edit = null;
$editMode = isset($_GET['add']) || isset($_GET['edit']);
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM education WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
    if (!$edit) {
        set_flash('error', 'Education entry not found.');
        redirect('admin/education.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $category = strtolower(trim((string)($_POST['category'] ?? '')));
    $degree = trim((string)$_POST['degree']);
    $institution = trim((string)$_POST['institution']);
    $location = trim((string)$_POST['location']);
    $current = isset($_POST['is_current']) ? 1 : 0;
    $desc = trim((string)$_POST['description']);
    $honor = trim((string)($_POST['academic_honor'] ?? ''));
    $visible = isset($_POST['is_visible']) ? 1 : 0;

    $start = compose_month_year(
        (string)($_POST['start_month'] ?? ''),
        (string)($_POST['start_year'] ?? '')
    );
    $end = $current ? null : compose_month_year(
        (string)($_POST['end_month'] ?? ''),
        (string)($_POST['end_year'] ?? '')
    );

    $returnTo = $id > 0 ? 'admin/education.php?edit=' . $id : 'admin/education.php?add=1';

    if (!isset($categories[$category])) {
        set_flash('error', 'Please select a valid education category.');
        redirect($returnTo);
    }
    if ($degree === '' || $institution === '') {
        set_flash('error', 'School/institution and degree/program are required.');
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
            set_flash('error', 'End date needs both a month and a year, or check Currently studying.');
            redirect($returnTo);
        }
    }

    if ($id > 0) {
        db()->prepare(
            'UPDATE education SET degree=?, institution=?, category=?, location=?, start_date=?, end_date=?, is_current=?, description=?, academic_honor=?, is_visible=? WHERE id=?'
        )->execute([$degree, $institution, $category, $location ?: null, $start, $end, $current, $desc ?: null, $honor !== '' ? $honor : null, $visible, $id]);
        set_flash('success', 'Education updated.');
    } else {
        db()->prepare(
            'INSERT INTO education (degree, institution, category, location, start_date, end_date, is_current, description, academic_honor, is_visible) VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([$degree, $institution, $category, $location ?: null, $start, $end, $current, $desc ?: null, $honor !== '' ? $honor : null, $visible]);
        set_flash('success', 'Education added.');
    }
    redirect('admin/education.php');
}

$items = get_education(false);
$startParts = split_month_year($edit['start_date'] ?? null);
$endParts = split_month_year($edit['end_date'] ?? null);
$selectedCategory = (string)($edit['category'] ?? 'college');
$yearNow = (int)date('Y');
$years = range($yearNow + 6, 1980, -1);

$monthOptions = static function (string $selected): void {
    for ($m = 1; $m <= 12; $m++) {
        $label = date('F', mktime(0, 0, 0, $m, 1, 2000));
        $sel = (string)$m === (string)$selected ? ' selected' : '';
        echo '<option value="' . $m . '"' . $sel . '>' . $label . '</option>';
    }
};

if ($editMode) {
    $pageTitle = $edit ? 'Edit Education' : 'Add Education';
    $pageDescription = 'School, program, and study period';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<div class="admin-page">
    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3><?= $edit ? 'Edit education' : 'Add education' ?></h3>
                <p>Add one school at a time. Entries are listed automatically from most recent to oldest.</p>
            </div>
            <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/education.php">Cancel</a>
        </div>
        <form method="post" class="form-grid" id="educationForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="">Select level of education</option>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $selectedCategory === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="hint">Shown on the public portfolio as the education level.</p>
            </div>
            <div class="form-group">
                <label for="institution">School / institution *</label>
                <input type="text" id="institution" name="institution" required value="<?= e($edit['institution'] ?? '') ?>" placeholder="e.g. Cavite State University">
            </div>
            <div class="form-group full">
                <label for="degree">Degree / program *</label>
                <input type="text" id="degree" name="degree" required value="<?= e($edit['degree'] ?? '') ?>" placeholder="e.g. Bachelor of Science in Information Technology">
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= e($edit['location'] ?? '') ?>" placeholder="City, Country">
            </div>
            <div class="form-group">
                <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" <?= !isset($edit['is_visible']) || !empty($edit['is_visible']) ? 'checked' : '' ?>> Visible on portfolio</label>
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
                <p class="hint">Month and year only. Example: August 2022.</p>
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
                <label class="checkbox-row"><input type="checkbox" name="is_current" id="is_current" value="1" <?= !empty($edit['is_current']) ? 'checked' : '' ?>> Currently studying (no end date)</label>
                <p class="hint">Leave blank or check Currently studying if this is ongoing. Example: June 2026.</p>
            </div>

            <div class="form-group full">
                <label for="academic_honor">Award / Academic Average (Optional)</label>
                <input type="text" id="academic_honor" name="academic_honor" maxlength="200" value="<?= e($edit['academic_honor'] ?? '') ?>" placeholder="e.g., Dean's Lister, Cum Laude, 1.25 GWA">
                <p class="hint">Optional. Shown on the public portfolio only if filled.</p>
            </div>
            <div class="form-group full">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Optional summary of focus, coursework, or highlights."><?= e($edit['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group full form-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/education.php">Cancel</a>
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
                <h2>Education</h2>
                <div class="sub">Academic background as shown on the portfolio · <?= count($items) ?> total · newest first</div>
            </div>
            <div class="admin-view-actions">
                <a class="btn btn-primary" href="?add=1"><i class="fa-solid fa-plus"></i> Add education</a>
            </div>
        </div>
    </section>

    <section class="panel">
        <?php if (!$items): ?>
            <p class="empty">No education entries yet. Click Add education to create one.</p>
        <?php else: ?>
            <div class="preview-edu-grid">
                <?php foreach ($items as $item): ?>
                    <article class="preview-card">
                        <div class="preview-card-header">
                            <div>
                                <h4><?= e($item['degree']) ?></h4>
                                <div class="preview-card-sub"><?= e(education_category_label($item['category'] ?? '')) ?> · <?= e($item['institution']) ?></div>
                            </div>
                            <?= $item['is_visible'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?>
                        </div>
                        <div class="preview-card-meta">
                            <span><i class="fa-regular fa-calendar"></i> <?= e(format_month_year_range($item['start_date'], $item['end_date'], (bool)$item['is_current'])) ?></span>
                            <?php if ($item['location']): ?>
                                <span><i class="fa-solid fa-location-dot"></i> <?= e($item['location']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['academic_honor'])): ?>
                                <span><i class="fa-solid fa-award"></i> <?= e($item['academic_honor']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item['description']): ?>
                            <p><?= e($item['description']) ?></p>
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
