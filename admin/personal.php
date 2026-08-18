<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$pageTitle = 'Personal Information';
$pageDescription = 'Profile, contact details, photo, and SEO';
$activeNav = 'personal';
$personal = get_personal();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $data = [
            'full_name' => trim((string)$_POST['full_name']),
            'professional_title' => trim((string)$_POST['professional_title']),
            'tagline' => trim((string)$_POST['tagline']),
            'about_me' => trim((string)$_POST['about_me']),
            'what_i_do' => trim((string)($_POST['what_i_do'] ?? '')),
            'career_objective' => trim((string)$_POST['career_objective']),
            'interests' => trim((string)$_POST['interests']),
            'strengths' => trim((string)$_POST['strengths']),
            'email' => trim((string)$_POST['email']),
            'phone' => trim((string)$_POST['phone']),
            'location' => trim((string)$_POST['location']),
            'years_experience' => trim((string)$_POST['years_experience']),
            'availability_status' => trim((string)$_POST['availability_status']),
            'meta_title' => trim((string)$_POST['meta_title']),
            'meta_description' => trim((string)$_POST['meta_description']),
            'footer_text' => trim((string)$_POST['footer_text']),
        ];

        if ($data['full_name'] === '') {
            throw new RuntimeException('Full name is required.');
        }

        $nullable = static fn(string $value): ?string => $value === '' ? null : $value;
        $data['professional_title'] = $nullable($data['professional_title']);
        $data['tagline'] = $nullable($data['tagline']);
        $data['about_me'] = $nullable($data['about_me']);
        $data['what_i_do'] = $nullable($data['what_i_do']);
        $data['career_objective'] = $nullable($data['career_objective']);
        $data['interests'] = $nullable($data['interests']);
        $data['strengths'] = $nullable($data['strengths']);

        $photo = $personal['profile_photo'] ?? null;
        $resume = $personal['resume_file'] ?? null;

        $newPhoto = handle_upload(
            $_FILES['profile_photo'] ?? [],
            'avatars',
            app_config('uploads.allowed_images', ['jpg', 'jpeg', 'png', 'webp']),
            (int)app_config('uploads.max_size', 5242880)
        );
        if ($newPhoto) {
            delete_upload('avatars', $photo);
            $photo = $newPhoto;
        }

        $newResume = handle_upload(
            $_FILES['resume_file'] ?? [],
            'resumes',
            app_config('uploads.allowed_docs', ['pdf']),
            (int)app_config('uploads.max_size', 5242880)
        );
        if ($newResume) {
            delete_upload('resumes', $resume);
            $resume = $newResume;
        }

        if (!empty($_POST['remove_photo']) && $photo) {
            delete_upload('avatars', $photo);
            $photo = null;
        }
        if (!empty($_POST['remove_resume']) && $resume) {
            delete_upload('resumes', $resume);
            $resume = null;
        }

        if ($personal) {
            $stmt = db()->prepare(
                'UPDATE personal_information SET
                full_name=?, professional_title=?, tagline=?, about_me=?, what_i_do=?, career_objective=?,
                interests=?, strengths=?, email=?, phone=?, location=?, profile_photo=?, resume_file=?,
                years_experience=?, availability_status=?, meta_title=?, meta_description=?, footer_text=?
                WHERE id=?'
            );
            $stmt->execute([
                $data['full_name'], $data['professional_title'], $data['tagline'], $data['about_me'],
                $data['what_i_do'], $data['career_objective'], $data['interests'], $data['strengths'], $data['email'],
                $data['phone'], $data['location'], $photo, $resume, $data['years_experience'],
                $data['availability_status'], $data['meta_title'], $data['meta_description'],
                $data['footer_text'], $personal['id'],
            ]);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO personal_information
                (full_name, professional_title, tagline, about_me, what_i_do, career_objective, interests, strengths,
                 email, phone, location, profile_photo, resume_file, years_experience, availability_status,
                 meta_title, meta_description, footer_text)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $data['full_name'], $data['professional_title'], $data['tagline'], $data['about_me'],
                $data['what_i_do'], $data['career_objective'], $data['interests'], $data['strengths'], $data['email'],
                $data['phone'], $data['location'], $photo, $resume, $data['years_experience'],
                $data['availability_status'], $data['meta_title'], $data['meta_description'],
                $data['footer_text'],
            ]);
        }

        set_flash('success', 'Personal information updated.');
        redirect('admin/personal.php');
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect('admin/personal.php?edit=1');
    }
}

$personal = get_personal() ?: [];
$editMode = isset($_GET['edit']);

if ($editMode) {
    $pageTitle = 'Edit Personal Information';
    $pageDescription = 'Update the details shown on your public portfolio';
}

$name = trim((string)($personal['full_name'] ?? ''));
$title = trim((string)($personal['professional_title'] ?? ''));
$tagline = trim((string)($personal['tagline'] ?? ''));
$about = trim((string)($personal['about_me'] ?? ''));
$whatIDo = trim((string)($personal['what_i_do'] ?? ''));
$objective = trim((string)($personal['career_objective'] ?? ''));
$strengths = trim((string)($personal['strengths'] ?? ''));
$interests = trim((string)($personal['interests'] ?? ''));
$roleItems = split_portfolio_items($whatIDo);
$strengthItems = split_portfolio_items($strengths);
$interestItems = split_portfolio_items($interests);
$email = trim((string)($personal['email'] ?? ''));
$phone = trim((string)($personal['phone'] ?? ''));
$location = trim((string)($personal['location'] ?? ''));
$years = trim((string)($personal['years_experience'] ?? ''));
$availability = trim((string)($personal['availability_status'] ?? ''));
$metaTitle = trim((string)($personal['meta_title'] ?? ''));
$metaDesc = trim((string)($personal['meta_description'] ?? ''));
$footerText = trim((string)($personal['footer_text'] ?? ''));
$photo = $personal['profile_photo'] ?? null;
$resume = $personal['resume_file'] ?? null;
$photoUrl = $photo ? upload_url('avatars', $photo) : null;
$resumeUrl = $resume ? upload_url('resumes', $resume) : null;

require __DIR__ . '/includes/header.php';
?>

<?php if ($editMode): ?>
<form method="post" enctype="multipart/form-data" class="personal-edit admin-page">
    <?= csrf_field() ?>

    <div class="panel personal-edit-intro">
        <div class="panel-header personal-edit-header">
            <div>
                <h2>Edit Personal Information</h2>
                <div class="sub">Update the profile details displayed on your public portfolio. Changes appear after you save.</div>
            </div>
            <a href="<?= e(APP_URL) ?>/admin/personal.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to view</a>
        </div>
    </div>

    <section class="panel form-section">
        <div class="form-section-header">
            <h3>Basic Information</h3>
            <p>Your name, role, and how you present yourself in the hero section.</p>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Full name *</label>
                <input type="text" id="full_name" name="full_name" required value="<?= e($personal['full_name'] ?? '') ?>" placeholder="Mark Lenard G. Bersonda">
            </div>
            <div class="form-group">
                <label for="professional_title">Professional title / role</label>
                <input type="text" id="professional_title" name="professional_title" value="<?= e($personal['professional_title'] ?? '') ?>" placeholder="IT Graduate · Web Developer">
                <p class="hint">Shown under your name in the hero section. The What I do card is edited separately below.</p>
            </div>
            <div class="form-group full">
                <label for="tagline">Tagline</label>
                <input type="text" id="tagline" name="tagline" value="<?= e($personal['tagline'] ?? '') ?>" placeholder="A short statement that supports your title">
            </div>
            <div class="form-group">
                <label for="availability_status">Availability status</label>
                <input type="text" id="availability_status" name="availability_status" value="<?= e($personal['availability_status'] ?? '') ?>" placeholder="Open to opportunities">
            </div>
            <div class="form-group">
                <label for="years_experience">Years of experience</label>
                <input type="text" id="years_experience" name="years_experience" value="<?= e($personal['years_experience'] ?? '') ?>" placeholder="1+ year">
            </div>
        </div>
    </section>

    <section class="panel form-section">
        <div class="form-section-header">
            <h3>About / Who I am</h3>
            <p>These fields map directly to the About cards on the public portfolio. Leave a field empty to hide that card.</p>
        </div>
        <div class="form-grid about-form-grid">
            <div class="form-group full">
                <label for="about_me">Who I am</label>
                <textarea id="about_me" name="about_me" rows="7" placeholder="Write a concise introduction about your background and focus."><?= e($personal['about_me'] ?? '') ?></textarea>
                <p class="hint">Public card: <strong>Who I am</strong>. One paragraph per line.</p>
            </div>
            <div class="form-group full">
                <label for="what_i_do">What I do</label>
                <textarea id="what_i_do" name="what_i_do" rows="5" placeholder="IT Graduate&#10;Web Developer&#10;Technical Support"><?= e($personal['what_i_do'] ?? '') ?></textarea>
                <p class="hint">Public card: <strong>What I do</strong>. One role per line. You can also separate items with commas or ·.</p>
            </div>
            <div class="form-group full">
                <label for="career_objective">Career objective</label>
                <textarea id="career_objective" name="career_objective" rows="4" placeholder="What kind of role or impact you are aiming for."><?= e($personal['career_objective'] ?? '') ?></textarea>
                <p class="hint">Public card: <strong>Career objective</strong>.</p>
            </div>
            <div class="form-group">
                <label for="strengths">Strengths</label>
                <textarea id="strengths" name="strengths" rows="4" placeholder="Problem-solving&#10;Attention to detail&#10;Organized&#10;Collaboration"><?= e($personal['strengths'] ?? '') ?></textarea>
                <p class="hint">Public card: <strong>Strengths &amp; interests</strong>. One item per line, or separate with commas.</p>
            </div>
            <div class="form-group">
                <label for="interests">Interests</label>
                <textarea id="interests" name="interests" rows="4" placeholder="Web Development&#10;UI/UX Design&#10;System Analysis"><?= e($personal['interests'] ?? '') ?></textarea>
                <p class="hint">Shown with Strengths on the same public card. Technical skills are managed under Skills.</p>
            </div>
        </div>
    </section>

    <section class="panel form-section">
        <div class="form-section-header">
            <h3>Contact Information</h3>
            <p>How employers and visitors can reach you.</p>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($personal['email'] ?? '') ?>" placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= e($personal['phone'] ?? '') ?>" placeholder="09XXXXXXXXX">
            </div>
            <div class="form-group full">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= e($personal['location'] ?? '') ?>" placeholder="Cavite, Philippines">
            </div>
        </div>
    </section>

    <section class="panel form-section">
        <div class="form-section-header">
            <h3>Profile Media</h3>
            <p>Photo and resume used across the portfolio.</p>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label for="profile_photo">Profile photo</label>
                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                <p class="hint">JPG, PNG, or WebP. Leave empty to keep the current photo.</p>
                <?php if (!empty($personal['profile_photo'])): ?>
                    <img class="preview-img" src="<?= e(upload_url('avatars', $personal['profile_photo'])) ?>" alt="Current photo">
                    <label class="checkbox-row"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="resume_file">Resume (PDF)</label>
                <input type="file" id="resume_file" name="resume_file" accept="application/pdf">
                <p class="hint">Upload a PDF resume. Leave empty to keep the current file.</p>
                <?php if (!empty($personal['resume_file'])): ?>
                    <p class="hint">Current: <a href="<?= e(upload_url('resumes', $personal['resume_file'])) ?>" target="_blank" rel="noopener"><?= e($personal['resume_file']) ?></a></p>
                    <label class="checkbox-row"><input type="checkbox" name="remove_resume" value="1"> Remove resume</label>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="panel form-section">
        <div class="form-section-header">
            <h3>Site &amp; SEO</h3>
            <p>Optional metadata and footer text for search engines and site chrome.</p>
        </div>
        <div class="form-grid">
            <div class="form-group full">
                <label for="meta_title">SEO title</label>
                <input type="text" id="meta_title" name="meta_title" value="<?= e($personal['meta_title'] ?? '') ?>" placeholder="Name · Role">
            </div>
            <div class="form-group full">
                <label for="meta_description">SEO description</label>
                <textarea id="meta_description" name="meta_description" rows="3" placeholder="A short summary for search results and social previews."><?= e($personal['meta_description'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label for="footer_text">Footer short description</label>
                <input type="text" id="footer_text" name="footer_text" value="<?= e($personal['footer_text'] ?? '') ?>" placeholder="Brief line shown in the site footer">
            </div>
        </div>
    </section>

    <div class="panel form-actions-bar">
        <p class="form-actions-note">Save to update the portfolio. Cancel returns to the saved view without changes.</p>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
            <a href="<?= e(APP_URL) ?>/admin/personal.php" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php else: ?>
<div class="panel personal-view">
    <div class="panel-header">
        <div>
            <h2>Personal information</h2>
            <div class="sub">How this profile appears across your public portfolio</div>
        </div>
        <a href="<?= e(APP_URL) ?>/admin/personal.php?edit=1" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
    </div>

    <?php if ($name === '' && empty($personal)): ?>
        <p class="empty-hint">No personal information saved yet. Click Edit to add your profile details.</p>
    <?php else: ?>
        <div class="personal-hero">
            <div class="personal-hero-copy">
                <?php if ($availability !== ''): ?>
                    <div class="personal-availability"><?= e($availability) ?></div>
                <?php endif; ?>
                <h3 class="personal-name"><?= e($name !== '' ? $name : 'Name not set') ?></h3>
                <?php if ($title !== ''): ?>
                    <p class="personal-title"><?= e($title) ?></p>
                <?php endif; ?>
                <?php if ($tagline !== ''): ?>
                    <p class="personal-tagline"><?= e($tagline) ?></p>
                <?php endif; ?>
                <div class="personal-meta">
                    <?php if ($location !== ''): ?>
                        <span><i class="fa-solid fa-location-dot"></i> <?= e($location) ?></span>
                    <?php endif; ?>
                    <?php if ($years !== ''): ?>
                        <span><i class="fa-solid fa-briefcase"></i> <?= e($years) ?> experience</span>
                    <?php endif; ?>
                    <?php if ($email !== ''): ?>
                        <span><i class="fa-solid fa-envelope"></i> <?= e($email) ?></span>
                    <?php endif; ?>
                    <?php if ($phone !== ''): ?>
                        <span><i class="fa-solid fa-phone"></i> <?= e($phone) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($resumeUrl): ?>
                    <div class="personal-resume">
                        <a class="btn btn-outline btn-sm" href="<?= e($resumeUrl) ?>" target="_blank" rel="noopener">
                            <i class="fa-solid fa-file-pdf"></i> View resume
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="personal-hero-photo">
                <?php if ($photoUrl): ?>
                    <img src="<?= e($photoUrl) ?>" alt="Profile photo of <?= e($name !== '' ? $name : 'portfolio owner') ?>">
                <?php else: ?>
                    <div class="personal-photo-fallback"><?= e(initials($name !== '' ? $name : 'P')) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="personal-about">
            <div class="personal-section">
                <h4>Who I am</h4>
                <?php if ($about !== ''): ?>
                    <div class="personal-prose">
                        <?php foreach (lines_to_array($about) ?: [$about] as $para): ?>
                            <?php if (trim((string)$para) !== ''): ?>
                                <p><?= e($para) ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-hint">No Who I am text yet. This card is hidden on the public site.</p>
                <?php endif; ?>
            </div>

            <div class="personal-section">
                <h4>What I do</h4>
                <?php if ($roleItems): ?>
                    <ul class="personal-chip-row">
                        <?php foreach ($roleItems as $role): ?>
                            <li><?= e($role) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-hint">No What I do items yet. This card is hidden on the public site.</p>
                <?php endif; ?>
            </div>

            <?php if ($objective !== '' || $strengths !== '' || $interests !== ''): ?>
                <div class="personal-cards">
                    <?php if ($objective !== ''): ?>
                        <article class="personal-card">
                            <h4><i class="fa-solid fa-bullseye"></i> Career objective</h4>
                            <p><?= e($objective) ?></p>
                        </article>
                    <?php endif; ?>
                    <?php if ($strengths !== '' || $interests !== ''): ?>
                        <article class="personal-card">
                            <h4><i class="fa-solid fa-layer-group"></i> Strengths &amp; interests</h4>
                            <div class="personal-split<?= ($strengths !== '' && $interests !== '') ? '' : ' personal-split--solo' ?>">
                                <?php if ($strengths !== ''): ?>
                                    <div>
                                        <h5>Strengths</h5>
                                        <?php if (count($strengthItems) > 1): ?>
                                            <ul class="personal-list">
                                                <?php foreach ($strengthItems as $item): ?>
                                                    <li><?= e($item) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p><?= e($strengths) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($interests !== ''): ?>
                                    <div>
                                        <h5>Interests</h5>
                                        <?php if (count($interestItems) > 1): ?>
                                            <ul class="personal-list">
                                                <?php foreach ($interestItems as $item): ?>
                                                    <li><?= e($item) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p><?= e($interests) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="personal-section personal-seo">
            <h4>Site &amp; SEO</h4>
            <dl class="personal-dl">
                <div>
                    <dt>SEO title</dt>
                    <dd><?php if ($metaTitle !== ''): ?><?= e($metaTitle) ?><?php else: ?><span class="empty-hint">Not set</span><?php endif; ?></dd>
                </div>
                <div>
                    <dt>SEO description</dt>
                    <dd><?php if ($metaDesc !== ''): ?><?= e($metaDesc) ?><?php else: ?><span class="empty-hint">Not set</span><?php endif; ?></dd>
                </div>
                <div>
                    <dt>Footer text</dt>
                    <dd><?php if ($footerText !== ''): ?><?= e($footerText) ?><?php else: ?><span class="empty-hint">Not set</span><?php endif; ?></dd>
                </div>
            </dl>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
