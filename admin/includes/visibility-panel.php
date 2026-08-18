<?php

declare(strict_types=1);

/** @var bool $portfolioPublic */
$portfolioPublic = $portfolioPublic ?? is_portfolio_public();
?>
<section class="panel visibility-panel">
    <div class="form-section-header">
        <div>
            <h3>Portfolio visibility</h3>
            <p>Control whether public visitors can view the portfolio homepage.</p>
        </div>
        <span class="badge <?= $portfolioPublic ? 'badge-ok' : 'badge-muted' ?>">
            <?= $portfolioPublic ? 'Public' : 'Hidden' ?>
        </span>
    </div>
    <div class="visibility-status">
        <div class="visibility-copy">
            <strong>Portfolio status: <?= $portfolioPublic ? 'ON — Public' : 'OFF — Hidden' ?></strong>
            <?php if ($portfolioPublic): ?>
                <p>The public site is accessible. Visitors can view your portfolio at <code>index.php</code>.</p>
            <?php else: ?>
                <p>The public site is hidden. Visitors see an unavailable page instead of your portfolio content. The admin panel remains available.</p>
            <?php endif; ?>
        </div>
        <form method="post" action="<?= e(APP_URL) ?>/admin/settings.php" class="visibility-form" id="visibilityForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="visibility">
            <?php if ($portfolioPublic): ?>
                <input type="hidden" name="portfolio_visible" value="0">
                <button class="btn btn-danger" type="button" data-visibility-hide>
                    <i class="fa-solid fa-eye-slash"></i> Hide portfolio
                </button>
            <?php else: ?>
                <input type="hidden" name="portfolio_visible" value="1">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-eye"></i> Make public
                </button>
            <?php endif; ?>
        </form>
    </div>
</section>

<?php if ($portfolioPublic): ?>
<dialog class="admin-dialog" id="hidePortfolioDialog" aria-labelledby="hidePortfolioTitle" aria-describedby="hidePortfolioDesc">
    <div class="admin-dialog-card">
        <div class="admin-dialog-icon" aria-hidden="true">
            <i class="fa-solid fa-eye-slash"></i>
        </div>
        <h3 id="hidePortfolioTitle">Hide Portfolio?</h3>
        <p id="hidePortfolioDesc">Your portfolio will no longer be visible to public visitors. You can make it visible again anytime from the Admin panel.</p>
        <div class="admin-dialog-actions">
            <button class="btn btn-outline" type="button" data-visibility-cancel>Cancel</button>
            <button class="btn btn-danger" type="button" data-visibility-confirm>Hide Portfolio</button>
        </div>
    </div>
</dialog>
<?php endif; ?>
