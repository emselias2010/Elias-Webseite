<?php
$strava = null;
foreach (content('social') as $link) {
    if ($link['platform'] === 'strava') {
        $strava = social_href($link);
    }
}
?>
<section id="endurance" class="section tone-deep" aria-labelledby="endurance-title">
    <div class="wrap">
        <div class="section-head">
            <h2 id="endurance-title" class="display display--gradient reveal"><?= e(content('sport_intro')['title']) ?></h2>
            <p class="lead reveal" <?= style_var('--order', 1) ?>><?= e(content('sport_intro')['text']) ?></p>
        </div>

        <?php if (!empty(content('sport_intro')['quote'])): ?>
        <blockquote class="big-quote mt-xl" data-scroll="read" <?= style_var('--n', count(preg_split('/\s+/', content('sport_intro')['quote']))) ?>>
            <p class="sr-only"><?= e(content('sport_intro')['quote']) ?></p>
            <p aria-hidden="true"><?php foreach (preg_split('/\s+/', content('sport_intro')['quote']) as $index => $word): ?><span class="scroll-text__word" <?= style_var('--i', $index) ?>><?= e($word) ?> </span><?php endforeach; ?></p>
        </blockquote>
        <?php endif; ?>

        <dl class="tile-grid tile-grid--4 mt-xl">
            <?php foreach (content('sport_stats') as $index => $stat): ?>
            <div class="tile reveal" <?= style_var('--order', $index) ?>>
                <dt class="tile__label"><?= e($stat['label']) ?></dt>
                <dd class="tile__value"><?= e($stat['value']) ?></dd>
                <?php if (!empty($stat['note'])): ?>
                <dd class="small muted mt-xs"><?= e($stat['note']) ?></dd>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </dl>

        <?php if (content('strava_stats')): ?>
        <h3 class="heading-3 mt-xl reveal">Strava in numbers</h3>
        <?php if (!empty(content('sport_intro')['strava_as_of'])): ?>
        <p class="small muted mt-xs reveal"><?= e(content('sport_intro')['strava_as_of']) ?></p>
        <?php endif; ?>
        <ul class="stat-grid mt-m">
            <?php foreach (content('strava_stats') as $index => $stat):
                $decimals = (int) ($stat['decimals'] ?? 0); ?>
            <li class="stat reveal" <?= style_var('--order', $index) ?>>
                <span class="stat__number"><span data-count-to="<?= e((string) $stat['value']) ?>" data-decimals="<?= $decimals ?>"><?= e(number_format((float) $stat['value'], $decimals, '.', ',')) ?></span><?php if (!empty($stat['unit'])): ?> <span class="stat__unit"><?= e($stat['unit']) ?></span><?php endif; ?></span>
                <span class="stat__label"><?= e($stat['label']) ?></span>
                <?php if (!empty($stat['note'])): ?><span class="small muted"><?= e($stat['note']) ?></span><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if (STRAVA_HIGHLIGHTS): ?>
        <h3 class="heading-3 mt-xl reveal">Best efforts</h3>
        <ul class="tile-grid highlight-grid mt-m">
            <?php foreach (STRAVA_HIGHLIGHTS as $index => $activity): ?>
            <li class="tile highlight reveal" <?= style_var('--order', $index) ?>>
                <p class="tile__label"><?= e($activity['sport']) ?><?= !empty($activity['date']) ? ' · ' . e($activity['date']) : '' ?></p>
                <h4 class="highlight__title"><?= e($activity['title']) ?></h4>
                <dl class="highlight__stats">
                    <?php foreach ($activity['stats'] as $label => $value): ?>
                    <div><dt class="small muted"><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="mt-xl" data-gallery>
            <div class="filter-row" role="group" aria-label="Filter by sport">
                <?php foreach (array_merge(['All'], SPORT_CATEGORIES) as $category): ?>
                <button type="button" class="btn btn-quiet" aria-pressed="<?= $category === 'All' ? 'true' : 'false' ?>" data-filter="<?= e($category) ?>"><?= e($category) ?></button>
                <?php endforeach; ?>
            </div>
            <p class="muted mt-l" data-gallery-empty hidden>Nothing in this category yet.</p>

            <ul class="gallery mt-m">
                <?php foreach (content('gallery') as $index => $item): ?>
                <li class="reveal" data-category="<?= e($item['category']) ?>" <?= style_var('--order', $index % 3) ?>>
                    <?php if ($item['image']): ?>
                    <button type="button" class="tile__frame" aria-label="Enlarge: <?= e($item['title']) ?>"
                        data-lightbox-src="<?= e(url($item['image']['src'])) ?>"
                        data-lightbox-alt="<?= e($item['alt']) ?>"
                        data-lightbox-title="<?= e($item['title']) ?>"
                        data-lightbox-text="<?= e($item['description']) ?>">
                        <img src="<?= e(url($item['image']['src'])) ?>" alt="<?= e($item['alt']) ?>" width="<?= (int) $item['image']['width'] ?>" height="<?= (int) $item['image']['height'] ?>" loading="lazy" decoding="async">
                    </button>
                    <?php elseif (!empty($item['stat'])): ?>
                    <?php
                    // Route shape: only "M"/"L" commands with numbers are accepted.
                    $route = (string) ($item['route'] ?? '');
                    $points = preg_match('/^M[\d .L]+$/', $route) ? preg_split('/[ML]/', $route, -1, PREG_SPLIT_NO_EMPTY) : [];
                    ?>
                    <div class="tile__frame stat-frame<?= $points ? ' stat-frame--route' : '' ?>" data-sport="<?= e($item['category']) ?>">
                        <span class="stat-frame__sport"><?= e($item['category']) ?></span>
                        <?php if ($points):
                            [$startX, $startY] = explode(' ', trim($points[0]));
                            [$endX, $endY] = explode(' ', trim($points[count($points) - 1])); ?>
                        <svg class="route" viewBox="0 0 200 200" aria-hidden="true" focusable="false">
                            <path class="route__shadow" d="<?= e($route) ?>"/>
                            <path class="route__line" d="<?= e($route) ?>" pathLength="1"/>
                            <circle class="route__start" cx="<?= e($startX) ?>" cy="<?= e($startY) ?>" r="5"/>
                            <circle class="route__end" cx="<?= e($endX) ?>" cy="<?= e($endY) ?>" r="5"/>
                        </svg>
                        <?php endif; ?>
                        <span class="stat-frame__value"><?= e($item['stat']['value']) ?><?php if ($item['stat']['unit'] !== ''): ?> <span class="stat-frame__unit"><?= e($item['stat']['unit']) ?></span><?php endif; ?></span>
                    </div>
                    <?php else: ?>
                    <div class="tile__frame">
                        <div class="tile__placeholder">Photo coming soon</div>
                    </div>
                    <?php endif; ?>
                    <p class="small muted mt-s"><?= e($item['category']) ?><?= !empty($item['date']) ? ' · ' . e($item['date']) : '' ?></p>
                    <h3 class="heading-4"><?= e($item['title']) ?></h3>
                    <?php if (!empty($item['facts'])): ?>
                    <ul class="fact-chips mt-xs">
                        <?php foreach ($item['facts'] as $fact): ?><li><?= e($fact) ?></li><?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <p class="small muted mt-xs"><?= e($item['description']) ?></p>
                </li>
                <?php endforeach; ?>
            </ul>

            <dialog class="lightbox" aria-label="Photo" data-lightbox>
                <div class="lightbox__body">
                    <div class="lightbox__image"><img src="" alt="" data-lightbox-img></div>
                    <div class="lightbox__bar">
                        <div>
                            <p class="heading-4" data-lightbox-heading></p>
                            <p class="small muted" data-lightbox-caption></p>
                        </div>
                        <div class="lightbox__buttons">
                            <button type="button" class="btn btn-quiet" data-lightbox-prev>Previous</button>
                            <button type="button" class="btn btn-quiet" data-lightbox-next>Next</button>
                            <button type="button" class="btn" data-lightbox-close>Close</button>
                        </div>
                    </div>
                </div>
            </dialog>
        </div>

        <?php if ($strava): ?>
        <p class="mt-l">
            <a href="<?= e($strava) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-quiet"><?= e(content('sport_intro')['strava_note']) ?><span class="sr-only"> (opens in a new tab)</span></a>
        </p>
        <?php endif; ?>
    </div>
</section>
