<?php
/** POST /api/report.php — flags a published comment for the moderator. */
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
$reasonRaw = is_array($data) ? ($data['reason'] ?? '') : '';
if ($id === null || !is_string($reasonRaw) || strlen($reasonRaw) > 1000) {
    json_error(400, 'invalid', 'Invalid report.');
}
$reason = mb_substr(clean_line($reasonRaw), 0, LIMIT_REPORT_REASON);

$visitor = ip_hash();
$limit = rate_limit_all([
    ['gb:report:10m', $visitor, 5, 600],
    ['gb:report:day', $visitor, 15, 86400],
], true);
if ($limit === 'unavailable') {
    json_error(503, 'unavailable', 'The report could not be saved. Please try again later.');
}
if ($limit === 'limited') {
    json_error(429, 'rate_limited', 'Too many reports. Please try again later.', ['Retry-After' => '600']);
}

try {
    if (!guestbook_published_exists($id)) {
        json_error(404, 'not_found', 'This comment no longer exists.');
    }
    guestbook_report($id, $reason, $visitor);
} catch (Throwable $exception) {
    error_log('Report failed: ' . $exception->getMessage());
    json_error(503, 'unavailable', 'The report could not be saved. Please try again later.');
}

json_response(['ok' => true], 201);
