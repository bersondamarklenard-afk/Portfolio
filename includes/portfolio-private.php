<?php

declare(strict_types=1);

http_response_code(503);
header('Retry-After: 3600');
send_no_store_headers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Currently Unavailable</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#111111">
    <link rel="icon" href="<?= e(asset_href('images/favicon/favicon.ico')) ?>" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('css/portfolio.css')) ?>?v=24">
</head>
<body class="portfolio-private">
    <main class="private-wrap">
        <div class="private-card">
            <p class="private-eyebrow">Portfolio</p>
            <h1>Portfolio Currently Unavailable</h1>
            <p>This portfolio is currently unavailable.</p>
        </div>
    </main>
</body>
</html>
