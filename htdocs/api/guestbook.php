<?php
/**
 * GET  /api/guestbook.php?before=<cursor>  published comments, newest first
 * POST /api/guestbook.php                  new comment (published right away)
 */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_DIR . '/lib/guestbook.php';

const NOT_CONNECTED = 'The guestbook is not connected to a database yet.';

if (!db_configured()) {
    json_error(503, 'not_configured', NOT_CONNECTED);
}

$method = request_method();

if ($method === 'GET') {
    $cursor = parse_cursor(isset($_GET['before']) ? (string) $_GET['before'] : null);
    if ($cursor === false) {
        json_error(400, 'invalid', 'Invalid page parameter.');
    }
    if (rate_limit('gb:read', ip_hash(), 120, 60, false) === 'limited') {
        json_error(429, 'rate_limited', 'Too many requests. Try again in a minute.', ['Retry-After' => '60']);
    }
    try {
        $page = guestbook_published($cursor, ip_hash());
    } catch (Throwable $exception) {
        error_log('Guestbook read failed: ' . $exception->getMessage());
        json_error(503, 'unavailable', 'Comments could not be loaded right now.');
    }
    json_response(['ok' => true, 'comments' => $page['comments'], 'nextBefore' => $page['next']]);
}

if ($method !== 'POST') {
    json_error(405, 'method_not_allowed', 'Not allowed.', ['Allow' => 'GET, POST']);
}

require_same_origin();
$input = validate_guestbook(read_json_body(BODY_GUESTBOOK));
if (!$input['ok']) {
    json_error(400, 'invalid', $input['error']);
}

$visitor = ip_hash();

// The shared counter is required here: if it fails, the comment is not accepted.
$limit = rate_limit_all([
    ['gb:post:10m', $visitor, 3, 600],
    ['gb:post:day', $visitor, 8, 86400],
    ['gb:post:global', 'all', 300, 86400],
], true);
if ($limit === 'unavailable') {
    json_error(503, 'unavailable', 'Your comment could not be saved. Please try again later.');
}
if ($limit === 'limited') {
    json_error(429, 'rate_limited', 'That was a lot of comments in a short time. Please try again later.', ['Retry-After' => '600']);
}

if (spam_reason($input['name'], $input['message'], $input['website'], $input['elapsed']) !== null) {
    json_error(422, 'rejected', 'This comment could not be accepted. Please remove links and try again in a moment.');
}

try {
    if (guestbook_is_duplicate($visitor, $input['message'])) {
        json_error(409, 'duplicate', 'You already sent this comment. It is already in the guestbook.');
    }
    $comment = guestbook_insert($input['name'], $input['message'], $visitor);
} catch (Throwable $exception) {
    error_log('Guestbook insert failed: ' . $exception->getMessage());
    json_error(503, 'unavailable', 'Your comment could not be saved. Please try again later.');
}

json_response(['ok' => true, 'status' => 'published', 'comment' => $comment], 201);
