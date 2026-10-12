<?php
declare(strict_types=1);

/**
 * Visitor address. By default REMOTE_ADDR is used; X-Forwarded-For only when
 * the configuration says the host sits behind a trusted proxy.
 */
function client_ip(): string
{
    $candidate = '';
    if (config()['trust_forwarded_for']) {
        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        $candidate = trim(explode(',', $forwarded)[0] ?? '');
    }
    if ($candidate === '') {
        $candidate = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
    return filter_var($candidate, FILTER_VALIDATE_IP) !== false ? $candidate : 'unknown';
}

/** One-way hash of the visitor address, so raw addresses are never stored. */
function ip_hash(?string $ip = null): string
{
    $salt = (string) config()['ip_salt'];
    if ($salt === '') {
        // Fallback: derived from other secrets. Setting ip_salt is better.
        $salt = hash('sha256', (string) config()['admin_password'] . '|' . (string) config()['db']['pass'] . '|elias');
    }
    return substr(hash('sha256', $salt . ':' . ($ip ?? client_ip())), 0, 40);
}

/**
 * CSRF guard for state-changing requests: when the browser sends an Origin
 * header it has to match this host. With $requireOrigin a missing header is
 * rejected as well (used for moderation).
 */
function is_same_origin(bool $requireOrigin = false): bool
{
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($origin === '') {
        $site = (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
        if ($site !== '' && $site !== 'same-origin' && $site !== 'none') {
            return false;
        }
        return !$requireOrigin;
    }
    $parts = parse_url($origin);
    if (!is_array($parts) || empty($parts['host'])) {
        return false;
    }
    $originHost = strtolower($parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''));
    return $host !== '' && hash_equals($host, $originHost);
}

function require_same_origin(bool $requireOrigin = false): void
{
    if (!is_same_origin($requireOrigin)) {
        json_error(403, 'forbidden', 'Request blocked.');
    }
}

/* -------------------------------------------------------------------------- */
/* Spam heuristics for the guestbook                                           */
/* -------------------------------------------------------------------------- */

function count_links(string $text): int
{
    return (int) preg_match_all(
        '~(https?://|www\.)\S+|\b[a-z0-9-]+\.(com|net|org|ru|cn|xyz|top|info|biz|io|shop|online|site)\b~i',
        $text
    );
}

/**
 * Cheap server-side checks. They do not replace moderation: every comment
 * still starts as "pending" and has to be approved.
 * Returns null when the comment looks fine, otherwise a reason.
 */
function spam_reason(string $name, string $message, string $honeypot, int $elapsedMs): ?string
{
    if (trim($honeypot) !== '') {
        return 'honeypot';
    }
    if ($elapsedMs < 1500) {
        return 'too-fast';
    }
    if (count_links($message) > 1 || count_links($name) > 0) {
        return 'links';
    }
    if (preg_match('/(.)\1{14,}/u', $message)) {
        return 'repetition';
    }
    return null;
}

/* -------------------------------------------------------------------------- */
/* Admin session                                                               */
/* -------------------------------------------------------------------------- */

const ADMIN_SESSION_SECONDS = 8 * 60 * 60;

/** A bcrypt/argon2 hash from password_hash(), preferred over a plain password. */
function admin_password_hash(): string
{
    $hash = (string) config()['admin_password_hash'];
    return password_get_info($hash)['algo'] !== null && password_get_info($hash)['algo'] !== 0 ? $hash : '';
}

function admin_configured(): bool
{
    return admin_password_hash() !== '' || mb_strlen((string) config()['admin_password']) >= 12;
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('eb_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function admin_signed_in(): bool
{
    if (!admin_configured()) {
        return false;
    }
    start_admin_session();
    $ok = ($_SESSION['admin'] ?? false) === true && (int) ($_SESSION['expires'] ?? 0) > time();
    if (!$ok && isset($_SESSION['admin'])) {
        $_SESSION = [];
    }
    return $ok;
}

function admin_password_matches(string $password): bool
{
    $hash = admin_password_hash();
    if ($hash !== '') {
        return password_verify($password, $hash);
    }
    // Plain password: both sides are hashed first so the comparison takes the same time for any input.
    return hash_equals(hash('sha256', (string) config()['admin_password']), hash('sha256', $password));
}

function admin_sign_in(): string
{
    start_admin_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['expires'] = time() + ADMIN_SESSION_SECONDS;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function admin_sign_out(): void
{
    start_admin_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
    session_destroy();
}

function admin_csrf_valid(): bool
{
    $sent = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = (string) ($_SESSION['csrf'] ?? '');
    return $expected !== '' && $sent !== '' && hash_equals($expected, $sent);
}
