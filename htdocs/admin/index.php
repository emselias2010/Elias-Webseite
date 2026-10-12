<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_DIR . '/lib/view.php';

send_page_headers();
header('Cache-Control: no-store');
view('head', ['title' => 'Admin | ' . content('profile')['name'], 'noindex' => true]);
?>
<main id="main" class="page tone-soft page--top">
    <div class="wrap" data-admin>
        <h1 class="display">Admin</h1>
        <p class="lead muted mt-s">Edit texts, Strava activities and what Ask Elias knows, and remove comments. Changes are live as soon as you save.</p>

        <p class="muted mt-l" data-admin-checking>Checking session…</p>

        <p class="notice mt-l" data-admin-off hidden>The admin area is not set up. Set <code>ADMIN_PASSWORD_HASH</code> in <code>app/.env</code>.</p>

        <form class="admin-login mt-l" data-admin-login hidden>
            <label for="admin-password" class="field-label">Password</label>
            <input id="admin-password" class="field" type="password" autocomplete="current-password" required>
            <button type="submit" class="btn mt-m">Sign in</button>
        </form>

        <div class="mt-l" data-admin-panel hidden>
            <div class="admin-toolbar">
                <div class="button-row" role="group" aria-label="Area">
                    <button type="button" class="btn btn-quiet" aria-pressed="true" data-area="comments">Comments <span class="admin-count" data-reported-count hidden></span></button>
                    <button type="button" class="btn btn-quiet" aria-pressed="false" data-area="texts">Texts</button>
                    <button type="button" class="btn btn-quiet" aria-pressed="false" data-area="strava">Strava</button>
                    <button type="button" class="btn btn-quiet" aria-pressed="false" data-area="ask">Ask Elias</button>
                </div>
                <div class="button-row">
                    <a href="<?= e(url('/')) ?>" class="text-link small" target="_blank" rel="noopener">View site<span class="sr-only"> (opens in a new tab)</span></a>
                    <button type="button" class="text-link small" data-admin-logout>Sign out</button>
                </div>
            </div>

            <section class="mt-l" data-area-panel="comments">
                <div class="button-row" role="group" aria-label="Filter by status">
                    <button type="button" class="btn btn-quiet" aria-pressed="true" data-status="published">Published</button>
                    <button type="button" class="btn btn-quiet" aria-pressed="false" data-status="pending">Waiting</button>
                    <button type="button" class="btn btn-quiet" aria-pressed="false" data-status="rejected">Rejected</button>
                    <button type="button" class="btn btn-quiet" aria-pressed="false" data-status="reported">Reported <span class="admin-count" data-reported-count hidden></span></button>
                </div>
                <p class="muted mt-l" data-admin-state></p>
                <ul class="comment-list mt-m" data-admin-list></ul>
            </section>

            <section class="mt-l" data-area-panel="texts" hidden></section>
            <section class="mt-l" data-area-panel="strava" hidden></section>
            <section class="mt-l" data-area-panel="ask" hidden></section>
        </div>

        <p class="notice notice-error mt-l" role="alert" data-admin-error hidden></p>
    </div>
</main>
<script type="module" src="<?= asset('/assets/js/admin.js') ?>"></script>
<?php view('footer'); ?>
