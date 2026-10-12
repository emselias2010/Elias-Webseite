<header class="site-nav">
    <div class="wrap site-nav__bar">
        <a href="<?= e(url('/#top')) ?>" class="site-nav__brand"><?= e(content('profile')['name']) ?></a>
        <nav aria-label="Main" class="site-nav__links">
            <?php foreach (NAV_ITEMS as $item): ?>
            <a href="<?= e(url($item['href'])) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <button type="button" class="site-nav__toggle" aria-expanded="false" aria-controls="site-menu" data-menu-toggle>
            <span data-menu-label>Menu</span>
            <span class="site-nav__toggle-icon" aria-hidden="true"></span>
        </button>
    </div>
    <div class="scroll-progress" aria-hidden="true"></div>
</header>
<div id="site-menu" class="site-menu" hidden>
    <div class="site-menu__backdrop" data-menu-close></div>
    <nav aria-label="Main, mobile" class="site-menu__panel">
        <?php foreach (NAV_ITEMS as $index => $item): ?>
        <a href="<?= e(url($item['href'])) ?>">
            <span class="site-menu__num" aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
            <span class="site-menu__label"><?= e($item['label']) ?></span>
            <span class="site-menu__arrow" aria-hidden="true">&rarr;</span>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
