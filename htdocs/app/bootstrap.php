<?php
/**
 * Loaded by every page and API endpoint.
 */
declare(strict_types=1);

if (!defined('APP_DIR')) {
    define('APP_DIR', __DIR__);
}

// Never show PHP errors to visitors; they can contain paths and details.
ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

/**
 * Values from app/.env (passwords, keys, database). The file is never
 * committed to Git and is blocked from the web by app/.htaccess.
 * Format: KEY=value, one per line; '...' keeps the value exactly as written.
 */
function env(string $key, string $default = ''): string
{
    static $values = null;
    if ($values === null) {
        $values = [];
        $file = getenv('ELIAS_ENV_FILE') ?: APP_DIR . '/.env';
        $lines = is_file($file) ? (file($file, FILE_IGNORE_NEW_LINES) ?: []) : [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if (strlen($value) >= 2 && ($value[0] === "'" || $value[0] === '"') && substr($value, -1) === $value[0]) {
                $value = substr($value, 1, -1);
            }
            $values[$name] = $value;
        }
    }
    return $values[$key] ?? $default;
}

/**
 * Configuration: app/config.php, which reads its secrets from app/.env.
 * Without them every optional feature is off. ELIAS_CONFIG is only used by
 * the automated tests.
 */
function config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $defaults = [
        'site_url' => '',
        'db' => ['host' => '', 'port' => 3306, 'name' => '', 'user' => '', 'pass' => ''],
        'ai' => ['api_key' => '', 'base_url' => 'https://openrouter.ai/api/v1', 'model' => 'openai/gpt-4o-mini', 'daily_limit' => 400],
        'admin_password' => '',
        'admin_password_hash' => '',
        'ip_salt' => '',
        'trust_forwarded_for' => false,
    ];
    $file = getenv('ELIAS_CONFIG') ?: APP_DIR . '/config.php';
    $loaded = is_file($file) ? require $file : [];
    if (!is_array($loaded)) {
        $loaded = [];
    }
    $config = array_replace_recursive($defaults, $loaded);
    return $config;
}

// PHP 8.0 (XAMPP) has no array_is_list yet.
if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool
    {
        return $array === [] || array_keys($array) === range(0, count($array) - 1);
    }
}

/**
 * URL path under which the site is reached, without a trailing slash.
 * '' when htdocs/ is the web root (InfinityFree), '/Elias-Webseite' when the
 * project folder sits in XAMPP's htdocs and is opened as http://localhost/Elias-Webseite/.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $root = str_replace('\\', '/', (string) realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));
    $site = str_replace('\\', '/', (string) realpath(dirname(APP_DIR)));
    $base = '';
    if ($root !== '' && $root !== '/' && stripos($site, rtrim($root, '/') . '/') === 0) {
        $base = substr($site, strlen(rtrim($root, '/')));
        // Requests through the project folder's .htaccess don't contain "/htdocs".
        $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        if ($uri !== $base && stripos($uri, $base . '/') !== 0) {
            $base = rtrim(str_replace('\\', '/', dirname($base)), '/');
        }
    }
    return $base;
}

require_once APP_DIR . '/lib/http.php';
require_once APP_DIR . '/lib/security.php';
require_once APP_DIR . '/lib/validation.php';
require_once APP_DIR . '/lib/db.php';
require_once APP_DIR . '/lib/rate_limit.php';
