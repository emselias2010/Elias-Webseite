<section id="contact" class="section section--clip tone-deep" aria-labelledby="contact-title">
    <div class="wrap">
        <div class="section-head">
            <h2 id="contact-title" class="display display--gradient reveal">Say hello.</h2>
            <p class="lead reveal" <?= style_var('--order', 1) ?>>Questions, ideas or a training route worth sharing: this is where to find me.</p>
        </div>

        <?php /* The tiles fly in from the left and the right, each with the platform's logo. */ ?>
        <ul class="tile-grid tile-grid--4 contact-grid mt-xl">
            <?php foreach (content('social') as $index => $link):
                $href = social_href($link);
                $detail = trim((string) ($link['handle'] ?? '')) !== '' ? $link['handle'] : $link['note'];
                $external = $link['platform'] !== 'email'; ?>
                <?php if ($href === null): ?>
            <li class="tile contact-tile reveal fly-in" <?= style_var('--order', $index) ?>>
                <?php view('social-icon', ['platform' => $link['platform']]); ?>
                <span class="contact-tile__name muted"><?= e($link['label']) ?></span>
                <span class="small muted">Not linked yet</span>
            </li>
                <?php else: ?>
            <li class="contact-tile-wrap reveal fly-in" <?= style_var('--order', $index) ?>>
                <a class="tile contact-tile contact-tile--link" href="<?= e($href) ?>"<?= $external ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                    <?php view('social-icon', ['platform' => $link['platform']]); ?>
                    <span class="contact-tile__name"><?= e($link['label']) ?></span>
                    <span class="small muted contact-tile__detail"><?= str_replace('@', '@<wbr>', e($detail)) ?><?= $external ? '<span class="sr-only"> (opens in a new tab)</span>' : '' ?></span>
                </a>
            </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
