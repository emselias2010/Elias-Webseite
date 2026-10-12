<?php
declare(strict_types=1);

/** Escapes text for HTML output. Use it for every value printed into a page. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Link to a page or file of this site, e.g. url('/assets/css/style.css'). */
function url(string $path = '/'): string
{
    // Only paths of this site get the prefix, not https://... or //cdn...
    return str_starts_with($path, '/') && !str_starts_with($path, '//') ? base_path() . $path : $path;
}

function site_url(string $path = ''): string
{
    $base = rtrim((string) config()['site_url'], '/');
    if ($base === '') {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $host = preg_replace('/[^a-zA-Z0-9.:\-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $base = ($https ? 'https://' : 'http://') . $host . base_path();
    }
    return $base . $path;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Sends a JSON response and stops. */
function json_response(array $data, int $status = 200, array $headers = []): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    foreach ($headers as $name => $value) {
        header($name . ': ' . $value);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(int $status, string $code, string $message, array $headers = []): void
{
    json_response(['ok' => false, 'code' => $code, 'error' => $message], $status, $headers);
}

function request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

/** Reads a JSON request body with a hard size limit. Stops with an error response on failure. */
function read_json_body(int $maxBytes): mixed
{
    $type = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_contains($type, 'application/json')) {
        json_error(415, 'unsupported_media_type', 'Send JSON.');
    }
    $declared = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($declared > $maxBytes) {
        json_error(413, 'too_large', 'The request is too large.');
    }
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false) {
        json_error(400, 'bad_request', 'The request could not be read.');
    }
    if (strlen($raw) > $maxBytes) {
        json_error(413, 'too_large', 'The request is too large.');
    }
    try {
        return json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        json_error(400, 'bad_request', 'The request is not valid JSON.');
    }
}
