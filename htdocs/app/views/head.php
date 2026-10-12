<?php
/** @var string $title  @var string $description  @var string $path  @var bool $noindex */
$pageTitle = $title ?? content('profile')['site_title'];
$pageDescription = $description ?? content('profile')['description'];
$canonical = site_url($path ?? '/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="author" content="<?= e(content('profile')['name']) ?>">
    <meta name="theme-color" content="#14251B">
    <meta name="format-detection" content="telephone=no, address=no, email=no">
    <?php if (!empty($noindex)): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(content('profile')['site_title']) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e(site_url('/assets/images/og-image.png')) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="en_GB">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?= asset('/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preload" href="<?= e(url('/assets/fonts/Geist-Variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <meta name="base-path" content="<?= e(base_path()) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/style.css') ?>">
    <script type="module" src="<?= asset('/assets/js/main.js') ?>"></script>
</head>
<body>
<a href="#main" class="skip-link">Skip to content</a>
<?php view('nav'); ?>
