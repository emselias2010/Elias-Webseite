<section id="projects" class="section tone-soft" aria-labelledby="projects-title">
    <div class="wrap">
        <div class="section-head">
            <h2 id="projects-title" class="display reveal">Currently building.</h2>
            <p class="lead reveal" <?= style_var('--order', 1) ?>>Software, hardware and a few ideas that are still on paper. Each one says how far along it is.</p>
        </div>

        <ul class="project-grid mt-xl">
            <?php foreach (PROJECTS as $index => $project): ?>
            <li class="project-card reveal<?= $index === 0 ? ' project-card--featured' : '' ?>" <?= style_var('--order', $index % 2) ?>>
                <?php if (!empty($project['cover'])): ?>
                <div class="project-card__cover">
                    <img src="<?= e(url($project['cover']['src'])) ?>" alt="<?= e($project['cover']['alt']) ?>" width="<?= (int) $project['cover']['width'] ?>" height="<?= (int) $project['cover']['height'] ?>" loading="lazy" decoding="async">
                </div>
                <?php endif; ?>
                <div class="project-card__body">
                    <?php if (!empty($project['status'])): ?>
                    <p class="status" data-status="<?= e($project['status']) ?>"><?= e($project['status']) ?></p>
                    <?php endif; ?>
                    <h3 class="project-card__title"><a href="<?= e(url('/projects/' . $project['slug'])) ?>"><?= e($project['title']) ?></a></h3>
                    <p class="project-card__tagline"><?= e($project['tagline']) ?></p>
                    <p class="project-card__summary"><?= e($project['summary']) ?></p>
                    <p class="small muted mt-s"><?= e(implode(', ', $project['technologies'])) ?></p>
                </div>
                <span class="project-card__more" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
