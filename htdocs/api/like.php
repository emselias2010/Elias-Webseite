<?php
/**
 * POST /api/like.php  { commentId, liked: true|false }
 * Sets or removes the visitor's like on a published comment. One like per
 * visitor (salted IP hash) and comment; the answer is the new count.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_DIR . '/lib/guestbook.php';

if (request_method() !== 'POST') {
    json_error(405, 'method_not_allowed', 'Not allowed.', ['Allow' => 'POST']);
}
require_same_origin();
if (!db_configured()) {
    json_error(503, 'not_configured', 'The guestbook is not connected to a database yet.');
}

$data = read_json_body(BODY_REPORT);
$id = parse_id(is_array($data) ? ($data['commentId'] ?? null) : null);
$liked = is_array($data) ? ($data['liked'] ?? null) : null;
if ($id === null || !is_bool($liked)) {
    json_error(400, 'invalid', 'Invalid request.');
}

$visitor = ip_hash();
$limit = rate_limit_all([
    ['gb:like:10m', $visitor, 60, 600],
    ['gb:like:day', $visitor, 300, 86400],
], true);
if ($limit === 'unavailable') {
    json_error(503, 'unavailable', 'The like could not be saved. Please try again later.');
}
if ($limit === 'limited') {
    json_error(429, 'rate_limited', 'Too many likes in a short time. Please try again later.', ['Retry-After' => '600']);
}

try {
    if (!guestbook_published_exists($id)) {
        json_error(404, 'not_found', 'This comment no longer exists.');
    }
    $count = guestbook_set_like($id, $visitor, $liked);
} catch (Throwable $exception) {
    error_log('Like failed: ' . $exception->getMessage());
    json_error(503, 'unavailable', 'The like could not be saved. Please try again later.');
}

json_response(['ok' => true, 'liked' => $liked, 'likes' => $count]);
