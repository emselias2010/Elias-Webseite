<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require_once APP_DIR . '/lib/view.php';
require_once APP_DIR . '/data/projects.php';
require_once APP_DIR . '/data/timeline.php';
require_once APP_DIR . '/data/sport.php';
require_once APP_DIR . '/data/knowledge.php';

send_page_headers();
view('head', ['path' => '/']);
?>
<?php /* Scroll target for "back to top"; the hero itself is pinned. */ ?>
<div id="top"></div>
<main id="main">
<?php
view('sections/hero');
view('sections/about');
view('sections/journey');
view('sections/endurance');
view('sections/projects');
view('sections/ask');
view('sections/contact');
// The guestbook is always the last section before the footer.
view('sections/guestbook');
?>
</main>
<?php view('footer'); ?>
