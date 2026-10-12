<?php
$links = [];
foreach (content('social') as $link) {
    $href = social_href($link);
    if ($href !== null) {
        $links[] = ['link' => $link, 'href' => $href];
    }
}
?>
<footer class="site-footer tone-ivory">
    <div class="wrap footer-row">
        <p class="small muted">© <?= e(gmdate('Y')) ?> <?= e(content('profile')['name']) ?></p>
        <?php if ($links): ?>
        <ul class="footer-links">
            <?php foreach ($links as $entry): ?>
            <li>
                <a href="<?= e($entry['href']) ?>" class="text-link small"<?= $entry['link']['platform'] !== 'email' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= e($entry['link']['label']) ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <a href="<?= e(url('/#top')) ?>" class="text-link small">Back to top</a>
    </div>
</footer>
</body>
</html>
