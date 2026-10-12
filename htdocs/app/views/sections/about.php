<?php
$about = content('profile')['about'];
$statement = $about['paragraphs'][0];
$paragraphs = array_slice($about['paragraphs'], 1);
$words = preg_split('/\s+/', trim($statement)) ?: [];
?>
<section id="about" class="section tone-soft" aria-labelledby="about-title">
    <div class="wrap">
        <h2 id="about-title" class="display reveal"><?= e($about['title']) ?></h2>

        <?php /* The first paragraph lights up word by word while scrolling. */ ?>
        <p class="scroll-text mt-l" data-scroll="read" <?= style_var('--n', count($words)) ?>>
            <span class="sr-only"><?= e($statement) ?></span>
            <span aria-hidden="true"><?php foreach ($words as $index => $word): ?><span class="scroll-text__word" <?= style_var('--i', $index) ?>><?= e($word) ?> </span><?php endforeach; ?></span>
        </p>

        <div class="two-col mt-xl">
            <?php foreach ($paragraphs as $index => $paragraph): ?>
            <p class="lead reveal" <?= style_var('--order', $index) ?>><?= e($paragraph) ?></p>
            <?php endforeach; ?>
        </div>

        <dl class="tile-grid tile-grid--4 mt-xl">
            <?php foreach ($about['facts'] as $index => $fact): ?>
            <div class="tile reveal" <?= style_var('--order', $index) ?>>
                <dt class="tile__label"><?= e($fact['label']) ?></dt>
                <dd class="tile__value"><?= e($fact['value']) ?></dd>
            </div>
            <?php endforeach; ?>
        </dl>

        <div class="tile reveal mt-s">
            <h3 class="tile__label">Things I keep coming back to</h3>
            <ul class="tag-list mt-s">
                <?php foreach ($about['interests'] as $interest): ?>
                <li><?= e($interest) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
