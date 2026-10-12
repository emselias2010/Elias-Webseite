<?php $image = content('profile')['hero_image']; ?>
<section class="hero tone-deep" aria-labelledby="hero-title" data-scroll="page">
    <div class="hero__glow" aria-hidden="true"></div>
    <?php view('topo'); ?>
    <div class="wrap hero__inner">
        <p class="hero__name"><?= e(content('profile')['name']) ?><?php if (!empty(content('profile')['role'])): ?> <span class="hero__role">· <?= e(content('profile')['role']) ?></span><?php endif; ?></p>
        <h1 id="hero-title" class="hero__title">
            <?php foreach (content('profile')['headline'] as $index => $word): ?>
            <span <?= style_var('--i', $index) ?>><?= e($word) ?> </span>
            <?php endforeach; ?>
        </h1>
        <div class="hero__foot">
            <?php if ($image): ?>
            <div class="hero__portrait">
                <img src="<?= e(url($image['src'])) ?>" alt="<?= e($image['alt']) ?>" width="<?= (int) $image['width'] ?>" height="<?= (int) $image['height'] ?>" fetchpriority="high">
            </div>
            <?php endif; ?>
            <p class="lead hero__intro"><?= e(content('profile')['intro']) ?></p>
            <div class="hero__actions">
                <a href="#about" class="btn">Get to know me</a>
                <a href="#projects" class="btn btn-quiet">See the projects</a>
                <a href="#ask" class="btn btn-quiet">Ask my AI</a>
            </div>
        </div>
    </div>
</section>
