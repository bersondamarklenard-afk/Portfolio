<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Settings';
$pageDescription = 'Portfolio visibility, account credentials, and security';
$activeNav = 'settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'visibility') {
        $visible = isset($_POST['portfolio_visible']) && (string)$_POST['portfolio_visible'] === '1' ? '1' : '0';
        set_site_setting('portfolio_visible', $visible);
        set_flash(
            'success',
            $visible === '1'
                ? 'Portfolio is now public. Visitors can view the site.'
                : 'Portfolio is now hidden. Public visitors will see an unavailable page.'
        );
        redirect('admin/settings.php');
    }

    if ($action === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([(int)$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($current, $admin['password_hash'])) {
            set_flash('error', 'Current password is incorrect.');
            redirect('admin/settings.php?edit=1');
        } elseif (strlen($new) < 8) {
            set_flash('error', 'New password must be at least 8 characters.');
            redirect('admin/settings.php?edit=1');
        } elseif ($new !== $confirm) {
            set_flash('error', 'New passwords do not match.');
            redirect('admin/settings.php?edit=1');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, $admin['id']]);
            set_flash('success', 'Password updated successfully.');
        }
        redirect('admin/settings.php');
    }

    if ($action === 'account') {
        $username = trim((string)$_POST['username']);
        $email = trim((string)$_POST['email']);
        if ($username === '') {
            set_flash('error', 'Username is required.');
            redirect('admin/settings.php?edit=1');
        }
        try {
            db()->prepare('UPDATE admins SET username = ?, email = ? WHERE id = ?')
                ->execute([$username, $email ?: null, (int)$_SESSION['admin_id']]);
            $_SESSION['admin_username'] = $username;
            set_flash('success', 'Account details updated.');
        } catch (Throwable $e) {
            set_flash('error', 'Could not update account. Username or email may already be in use.');
            redirect('admin/settings.php?edit=1');
        }
        redirect('admin/settings.php');
    }
}

$stmt = db()->prepare('SELECT id, username, email, created_at FROM admins WHERE id = ?');
$stmt->execute([(int)$_SESSION['admin_id']]);
$admin = $stmt->fetch() ?: [];
$editMode = isset($_GET['edit']);
$portfolioPublic = is_portfolio_public();

if ($editMode) {
    $pageTitle = 'Edit Settings';
    $pageDescription = 'Update account credentials and security';
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<div class="admin-page settings-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Edit Settings</h2>
                <div class="sub">Update account credentials and password. Cancel returns to the summary view.</div>
            </div>
            <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/settings.php">Cancel</a>
        </div>
    </section>

    <?php require __DIR__ . '/includes/visibility-panel.php'; ?>

    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3>Account</h3>
                <p>Username and email for admin access.</p>
            </div>
        </div>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="account">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required value="<?= e($admin['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($admin['email'] ?? '') ?>">
            </div>
            <div class="form-group full form-actions">
                <button class="btn btn-primary" type="submit">Save account</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/settings.php">Cancel</a>
            </div>
        </form>
    </section>

    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3>Change password</h3>
                <p>Use a strong password of at least 8 characters.</p>
            </div>
        </div>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <div class="form-group full">
                <label for="current_password">Current password</label>
                <div class="password-field">
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="new_password">New password</label>
                <div class="password-field">
                    <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                    <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm new password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="form-group full form-actions">
                <button class="btn btn-primary" type="submit">Update password</button>
                <a class="btn btn-outline" href="<?= e(APP_URL) ?>/admin/settings.php">Cancel</a>
            </div>
        </form>
    </section>

    <section class="panel form-section">
        <div class="form-section-header">
            <div>
                <h3>Deployment notes</h3>
                <p>Hosting guidance for this PHP/MySQL project.</p>
            </div>
        </div>
        <div class="alert alert-info">
            <strong>GitHub Pages cannot run this project.</strong> This portfolio uses PHP, MySQL, and sessions.
            Host the source on GitHub for version control, and deploy to PHP/MySQL hosting (e.g. Hostinger).
            Update <code>config/config.php</code> with production database credentials and set <code>debug</code> to false.
        </div>
        <p class="panel-body-text mb-0">
            Change your admin password before deploying publicly.
        </p>
    </section>
</div>
<?php else: ?>
<div class="admin-page settings-page">
    <section class="panel">
        <div class="panel-header admin-view-header">
            <div>
                <h2>Settings</h2>
                <div class="sub">Portfolio visibility, account credentials, and security</div>
            </div>
            <div class="admin-view-actions">
                <a class="btn btn-primary" href="?edit=1"><i class="fa-solid fa-pen"></i> Edit</a>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/includes/visibility-panel.php'; ?>

    <section class="panel">
        <div class="form-section-header">
            <div>
                <h3>Account summary</h3>
                <p>Current admin account details.</p>
            </div>
        </div>
        <dl class="preview-settings-dl">
            <div>
                <dt>Username</dt>
                <dd><?= e($admin['username'] ?? '—') ?></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd><?php if (trim((string)($admin['email'] ?? '')) !== ''): ?><?= e($admin['email']) ?><?php else: ?>Not set<?php endif; ?></dd>
            </div>
            <div>
                <dt>Account created</dt>
                <dd><?= !empty($admin['created_at']) ? e(date('M j, Y', strtotime($admin['created_at']))) : '—' ?></dd>
            </div>
            <div>
                <dt>Password</dt>
                <dd>•••••••• <span class="empty-hint">(hidden for security)</span></dd>
            </div>
        </dl>
    </section>

    <section class="panel">
        <div class="form-section-header">
            <div>
                <h3>Deployment notes</h3>
                <p>Hosting guidance for this PHP/MySQL project.</p>
            </div>
        </div>
        <div class="alert alert-info">
            <strong>GitHub Pages cannot run this project.</strong> This portfolio uses PHP, MySQL, and sessions.
            Host the source on GitHub for version control, and deploy to PHP/MySQL hosting (e.g. Hostinger).
            Update <code>config/config.php</code> with production database credentials and set <code>debug</code> to false.
        </div>
        <p class="panel-body-text mb-0">
            Change your admin password before deploying publicly.
        </p>
    </section>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
