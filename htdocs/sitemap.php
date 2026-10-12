<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require_once APP_DIR . '/data/projects.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
echo '  <url><loc>', e(site_url('/')), '</loc><priority>1.0</priority></url>', "\n";
foreach (PROJECTS as $project) {
    echo '  <url><loc>', e(site_url('/projects/' . $project['slug'])), '</loc><priority>0.6</priority></url>', "\n";
}
echo '</urlset>', "\n";
