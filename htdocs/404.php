<?php
declare(strict_types=1);
if (!defined('APP_DIR')) {
    require __DIR__ . '/app/bootstrap.php';
}
require_once APP_DIR . '/lib/view.php';
http_response_code(404);
send_page_headers();
view('head', ['title' => 'Off the trail | ' . content('profile')['name'], 'noindex' => true]);
?>
<main id="main" class="page tone-deep page--center">
    <div class="wrap">
        <h1 class="display display--gradient">Off the trail.</h1>
        <p class="lead muted mt-m">This page doesn't exist. The way back is short.</p>
        <a href="<?= e(url('/')) ?>" class="btn mt-l">Back to the start</a>
    </div>
</main>
<?php view('footer'); ?>
