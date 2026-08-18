<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Skills';
$pageDescription = 'Add, edit, and organize technical skills';
$activeNav = 'skills';
$categories = ['frontend' => 'Frontend', 'backend' => 'Backend', 'database' => 'Database', 'tools' => 'Tools', 'design' => 'Design', 'other' => 'Other'];

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare('DELETE FROM skills WHERE id = ?')->execute([$id]);
    set_flash('success', 'Skill deleted.');
    redirect('admin/skills.php');
}

$edit = null;
$editMode = isset($_GET['add']) || isset($_GET['edit']);
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM skills WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
    if (!$edit) {
        set_flash('error', 'Skill not found.');
        redirect('admin/skills.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)$_POST['name']);
    $category = (string)($_POST['category'] ?? 'other');
    $proficiency = max(0, min(100, (int)($_POST['proficiency'] ?? 70)));
    $icon = trim((string)($_POST['icon_class'] ?? ''));
    $sort = (int)($_POST['sort_order'] ?? 0);
    $visible = isset($_POST['is_visible']) ? 1 : 0;

    if ($name === '' || !isset($categories[$category])) {
        set_flash('error', 'Please provide a valid skill name and category.');
        redirect($id > 0 ? 'admin/skills.php?edit=' . $id : 'admin/skills.php?add=1');
    }

    if ($id > 0) {
        db()->prepare(
            'UPDATE skills SET name=?, category=?, proficiency=?, icon_class=?, sort_order=?, is_visible=? WHERE id=?'
        )->execute([$name, $category, $proficiency, $icon ?: null, $sort, $visible, $id]);
        set_flash('success', 'Skill updated.');
    } else {
        db()->prepare(
            'INSERT INTO skills (name, category, proficiency, icon_class, sort_order, is_visible) VALUES (?,?,?,?,?,?)'
        )->execute([$name, $category, $proficiency, $icon ?: null, $sort, $visible]);
        set_flash('success', 'Skill added.');
    }
    redirect('admin/skills.php');
}

$skills = get_skills(false);
$skillsGrouped = group_skills_by_category($skills);

if ($editMode) {
    $pageTitle = $edit ? 'Edit Skill' : 'Add Skill';
    $pageDescription = $edit ? 'Update an existing skill entry' : 'Create a new skill for your portfolio';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<div class="admin-page">
    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3><?= $edit ? 'Edit skill' : 'Add skill' ?></h3>
                <p><?= $edit ? 'Update an existing skill entry for your portfolio.' : 'Create a new skill for your portfolio.' ?></p>
            </div>
            <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/skills.php">Cancel</a>
        </div>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-group">
                <label for="name">Skill name *</label>
                <input type="text" id="name" name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="PHP">
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <?php foreach ($categories as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= (($edit['category'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="proficiency">Proficiency (0–100)</label>
                <input type="number" id="proficiency" name="proficiency" min="0" max="100" value="<?= (int)($edit['proficiency'] ?? 70) ?>">
            </div>
            <div class="form-group">
                <label for="icon_class">Icon class (Font Awesome)</label>
                <input type="text" id="icon_class" name="icon_class" value="<?= e($edit['icon_class'] ?? '') ?>" placeholder="fa-brands fa-php">
            </div>
            <div class="form-group">
                <label for="sort_order">Sort order</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label class="checkbox-row"><input type="checkbox" name="is_visible" value="1" <?= !isset($edit['is_visible']) || $edit['is_visible'] ? 'checked' : '' ?>> Visible on portfolio</label>
            </div>
            <div class="form-group full form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= $edit ? 'Update' : 'Add' ?> skill</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/skills.php">Cancel</a>
            </div>
        </form>
    </section>
</div>
<?php else: ?>
<div class="admin-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Skills</h2>
                <div class="sub">How your technical toolkit appears on the portfolio · <?= count($skills) ?> total</div>
            </div>
            <div class="admin-view-actions">
                <a class="btn btn-primary" href="?add=1"><i class="fa-solid fa-plus"></i> Add skill</a>
            </div>
        </div>
    </section>

    <section class="panel">
        <?php if (!$skillsGrouped): ?>
            <p class="empty">No skills yet. Click Add skill to create one.</p>
        <?php else: ?>
            <div class="preview-skills">
                <?php foreach ($skillsGrouped as $group): ?>
                    <div class="preview-skill-cat">
                        <h4><?= e($group['label']) ?></h4>
                        <div class="preview-skill-list">
                            <?php foreach ($group['items'] as $s): ?>
                                <div class="preview-skill-item">
                                    <div class="preview-skill-item-main">
                                        <div class="preview-skill-icon"><i class="<?= e($s['icon_class'] ?: 'fa-solid fa-code') ?>"></i></div>
                                        <div class="preview-skill-copy">
                                            <div class="preview-skill-name"><?= e($s['name']) ?></div>
                                            <div class="preview-skill-meta">
                                                <span class="preview-skill-level"><?= (int)$s['proficiency'] ?>%</span>
                                                <?= $s['is_visible'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-skill-actions">
                                        <a class="btn btn-outline btn-sm" href="?edit=<?= (int)$s['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                                        <a class="btn btn-danger btn-sm" href="?delete=<?= (int)$s['id'] ?>" onclick="return confirm('Delete this skill?')"><i class="fa-solid fa-trash"></i> Delete</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
