<section id="journey" class="section tone-ivory" aria-labelledby="journey-title">
    <div class="wrap split">
        <div class="split__sticky">
            <h2 id="journey-title" class="display reveal">The journey.</h2>
            <p class="lead mt-m">Two tracks that keep crossing. These are the threads so far, grouped by theme and not by date.</p>
            <p class="legend small muted mt-m" aria-hidden="true">
                <span><span class="marker"></span> Tech</span>
                <span><span class="marker marker-sport"></span> Sport</span>
            </p>
        </div>

        <?php /* The line fills as the list scrolls past the middle of the screen. */ ?>
        <ol class="journey" data-scroll="through">
            <?php foreach (TIMELINE as $entry):
                $project = isset($entry['project']) ? find_project($entry['project']) : null; ?>
            <li class="journey__item" data-scroll="enter">
                <span class="marker<?= $entry['track'] === 'Sport' ? ' marker-sport' : '' ?>" aria-hidden="true"></span>
                <p class="small muted"><?= e($entry['track']) ?><?= isset($entry['period']) ? ', ' . e($entry['period']) : '' ?></p>
                <h3 class="journey__title mt-xs"><?= e($entry['title']) ?></h3>
                <p class="journey__text"><?= e($entry['text']) ?></p>
                <?php if ($project): ?>
                <a href="<?= e(url('/projects/' . $project['slug'])) ?>" class="text-link small">About <?= e($project['title']) ?></a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
