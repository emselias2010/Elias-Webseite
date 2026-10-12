<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require_once APP_DIR . '/lib/view.php';
require_once APP_DIR . '/data/projects.php';

$slug = (string) ($_GET['slug'] ?? '');
$project = preg_match('/^[a-z0-9-]{1,60}$/', $slug) ? find_project($slug) : null;
if ($project === null) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$github = safe_external_url($project['github_url'] ?? null);
$live = safe_external_url($project['live_url'] ?? null);
$published = null;
if (!empty($project['published_at']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $project['published_at'])) {
    $published = DateTimeImmutable::createFromFormat('!Y-m-d', $project['published_at'], new DateTimeZone('UTC')) ?: null;
}
$lists = [
    'What already works' => $project['implemented'] ?? [],
    'What the project covers' => $project['scope'] ?? [],
    'Planned' => $project['planned'] ?? [],
];

send_page_headers();
view('head', ['title' => $project['title'] . ' | ' . content('profile')['name'], 'description' => $project['summary'], 'path' => '/projects/' . $project['slug']]);
?>
<main id="main" class="page tone-soft page--top">
    <article class="wrap">
        <a href="<?= e(url('/#projects')) ?>" class="text-link small">All projects</a>

        <header class="split split--end mt-m">
            <h1 class="display"><?= e($project['title']) ?></h1>
            <div>
                <p class="lead"><?= e($project['tagline']) ?></p>
                <div class="meta-row mt-s">
                    <?php if (!empty($project['status'])): ?>
                    <p class="status" data-status="<?= e($project['status']) ?>"><?= e($project['status']) ?></p>
                    <?php endif; ?>
                    <?php if ($published): ?>
                    <p class="small muted">Published <time datetime="<?= e($project['published_at']) ?>"><?= e($published->format('F Y')) ?></time></p>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <?php if (!empty($project['cover'])): ?>
        <div class="detail-cover mt-xl">
            <img src="<?= e(url($project['cover']['src'])) ?>" alt="<?= e($project['cover']['alt']) ?>" width="<?= (int) $project['cover']['width'] ?>" height="<?= (int) $project['cover']['height'] ?>" fetchpriority="high">
        </div>
        <?php endif; ?>

        <div class="split detail-body mt-xl">
            <aside>
                <h2 class="heading-3">Built with</h2>
                <ul class="plain-list mt-s measure-s">
                    <?php foreach ($project['technologies'] as $technology): ?>
                    <li><?= e($technology) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($github || $live): ?>
                <div class="button-row mt-l">
                    <?php if ($live): ?>
                    <a href="<?= e($live) ?>" target="_blank" rel="noopener noreferrer" class="btn">Open the project<span class="sr-only"> (opens in a new tab)</span></a>
                    <?php endif; ?>
                    <?php if ($github): ?>
                    <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-quiet">Code on GitHub<span class="sr-only"> (opens in a new tab)</span></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </aside>

            <div>
                <div class="prose-block lead">
                    <?php foreach ($project['description'] as $paragraph): ?>
                    <p><?= e($paragraph) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($lists as $heading => $items): if (!$items) { continue; } ?>
                <section class="mt-xl">
                    <h2 class="heading-3"><?= e($heading) ?></h2>
                    <ul class="plain-list mt-s">
                        <?php foreach ($items as $item): ?>
                        <li><?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($project['screenshots'])): ?>
        <section class="mt-xl">
            <h2 class="heading-3">Screenshots</h2>
            <ul class="screenshot-grid mt-m">
                <?php foreach ($project['screenshots'] as $shot): ?>
                <li><img src="<?= e(url($shot['src'])) ?>" alt="<?= e($shot['alt']) ?>" width="<?= (int) $shot['width'] ?>" height="<?= (int) $shot['height'] ?>" loading="lazy" decoding="async"></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </article>
</main>
<?php view('footer'); ?>
