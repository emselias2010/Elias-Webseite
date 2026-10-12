<?php
/**
 * Contour lines for the hero, like the height lines on a hiking map.
 * Generated from fixed numbers, so the drawing is always the same.
 */
$ring = static function (float $cx, float $cy, float $radius, int $index): array {
    $points = [];
    $count = 28;
    for ($i = 0; $i < $count; $i++) {
        $angle = ($i / $count) * M_PI * 2;
        $wobble = 1 + 0.16 * sin($angle * 2 + $index * 0.35 + 0.6) + 0.09 * sin($angle * 3 - $index * 0.22 + 2.1) + 0.05 * sin($angle * 5 + $index * 0.5);
        $r = $radius * $wobble;
        $points[] = [$cx + cos($angle) * $r * 1.25, $cy + sin($angle) * $r * 0.86];
    }
    return $points;
};
$smooth = static function (array $points): string {
    $n = count($points);
    $f = static fn (float $v): string => number_format($v, 1, '.', '');
    $d = 'M' . $f($points[0][0]) . ' ' . $f($points[0][1]);
    for ($i = 0; $i < $n; $i++) {
        $p0 = $points[($i - 1 + $n) % $n];
        $p1 = $points[$i];
        $p2 = $points[($i + 1) % $n];
        $p3 = $points[($i + 2) % $n];
        $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, $p1[1] + ($p2[1] - $p0[1]) / 6];
        $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, $p2[1] - ($p3[1] - $p1[1]) / 6];
        $d .= 'C' . $f($c1[0]) . ' ' . $f($c1[1]) . ' ' . $f($c2[0]) . ' ' . $f($c2[1]) . ' ' . $f($p2[0]) . ' ' . $f($p2[1]);
    }
    return $d . 'Z';
};
$peaks = [[1010, 300, 11, 26, 44], [250, 860, 7, 34, 50]];
?>
<svg class="hero__topo" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <?php foreach ($peaks as $peakIndex => [$cx, $cy, $rings, $start, $gap]): ?>
        <?php for ($i = 0; $i < $rings; $i++): ?>
    <path d="<?= e($smooth($ring($cx, $cy, $start + $i * $gap, $i + $peakIndex * 4))) ?>" pathLength="1" <?= style_var('--i', $i + $peakIndex * 3) ?>></path>
        <?php endfor; ?>
    <?php endforeach; ?>
</svg>
