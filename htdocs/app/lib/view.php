<?php
declare(strict_types=1);

require_once APP_DIR . '/lib/content.php';

/** Renders a template from app/views with the given variables. */
function view(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require APP_DIR . '/views/' . $name . '.php';
}

/** Security headers for HTML pages. Scripts and fonts come only from this site. */
function send_page_headers(): void
{
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), browsing-topics=()');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

/** Version string for CSS/JS URLs, so browsers load changed files right away. */
function asset(string $path): string
{
    $file = dirname(APP_DIR) . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return e(url($path) . '?v=' . $version);
}

/** Inline CSS custom property, e.g. style_var('--i', 2) -> style="--i:2" */
function style_var(string $name, int|float $value): string
{
    return 'style="' . e($name . ':' . $value) . '"';
}
