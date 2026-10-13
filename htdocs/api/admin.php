<?php
/**
 * Guestbook moderation. One endpoint, the action decides:
 *   GET  ?action=session            signed in? (+ CSRF token)
 *   POST ?action=login              { password }
 *   POST ?action=logout
 *   GET  ?action=list&status=...    comments with a given status
 *   POST ?action=update             { id, status }
 *   POST ?action=delete             { id }
 *   GET  ?action=content            editable texts and Strava data
 *   POST ?action=content_save       { key, value }
 *   POST ?action=content_reset      { key }   back to the default from app/data
 * Changes need a valid session, the CSRF token header and a same-origin request.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_DIR . '/lib/guestbook.php';
require_once APP_DIR . '/lib/content.php';
require_once APP_DIR . '/lib/moderation.php';

const STATUSES = ['pending', 'published', 'rejected'];
const LIST_FILTERS = ['pending', 'published', 'rejected', 'reported'];
$action = (string) ($_GET['action'] ?? '');
$method = request_method();

if (!admin_configured()) {
    if ($action === 'session') {
        json_response(['ok' => true, 'configured' => false, 'signedIn' => false]);
    }
    json_error(503, 'not_configured', 'The admin area is not set up. Set ADMIN_PASSWORD_HASH in app/.env.');
}

if ($action === 'session' && $method === 'GET') {
    $signedIn = admin_signed_in();
    json_response(['ok' => true, 'configured' => true, 'signedIn' => $signedIn, 'csrf' => $signedIn ? $_SESSION['csrf'] : null]);
}

if ($action === 'login' && $method === 'POST') {
    require_same_origin(true);
    if (rate_limit('admin:login', ip_hash(), 5, 900, false) !== 'ok') {
        json_error(429, 'rate_limited', 'Too many attempts. Try again in 15 minutes.', ['Retry-After' => '900']);
    }
    $data = read_json_body(BODY_ADMIN);
    $password = is_array($data) && is_string($data['password'] ?? null) ? $data['password'] : '';
    if ($password === '' || strlen($password) > 200 || !admin_password_matches($password)) {
        json_error(401, 'unauthorized', 'Wrong password.');
    }
    json_response(['ok' => true, 'csrf' => admin_sign_in()]);
}

if ($action === 'logout' && $method === 'POST') {
    require_same_origin(true);
    admin_sign_out();
    json_response(['ok' => true]);
}

// Everything below needs a signed-in moderator.
if (!admin_signed_in()) {
    json_error(401, 'unauthorized', 'Please sign in.');
}
if (!db_configured()) {
    json_error(503, 'not_configured', 'The guestbook is not connected to a database yet.');
}

if ($action === 'list' && $method === 'GET') {
    $status = (string) ($_GET['status'] ?? 'pending');
    if (!in_array($status, LIST_FILTERS, true)) {
        json_error(400, 'invalid', 'Unknown status.');
    }
    // Opportunity to re-check a few older comments while the owner is here.
    if (ai_configured() && rate_limit('gb:moderate', 'all', 1, 120, false) === 'ok') {
        moderate_backlog(3);
    }
    try {
        json_response(['ok' => true, 'comments' => guestbook_for_moderation($status), 'reported' => guestbook_reported_count()]);
    } catch (Throwable $exception) {
        error_log('Moderation list failed: ' . $exception->getMessage());
        json_error(503, 'unavailable', 'Comments could not be loaded.');
    }
}

if (in_array($action, ['update', 'delete', 'unreport'], true) && $method === 'POST') {
    require_same_origin(true);
    if (!admin_csrf_valid()) {
        json_error(403, 'forbidden', 'Request blocked. Reload the page and try again.');
    }
    $data = read_json_body(BODY_ADMIN);
    $id = parse_id(is_array($data) ? ($data['id'] ?? null) : null);
    if ($id === null) {
        json_error(400, 'invalid', 'Invalid request.');
    }
    try {
        if ($action === 'update') {
            $status = is_array($data) ? ($data['status'] ?? null) : null;
            if (!in_array($status, STATUSES, true)) {
                json_error(400, 'invalid', 'Unknown status.');
            }
            $found = guestbook_set_status($id, $status);
        } elseif ($action === 'unreport') {
            $found = guestbook_clear_reports($id);
        } else {
            $found = guestbook_delete($id);
        }
    } catch (Throwable $exception) {
        error_log('Moderation change failed: ' . $exception->getMessage());
        json_error(503, 'unavailable', 'The change could not be saved.');
    }
    if (!$found) {
        json_error(404, 'not_found', 'This comment no longer exists.');
    }
    json_response(['ok' => true, 'reported' => guestbook_reported_count()]);
}

if ($action === 'content' && $method === 'GET') {
    json_response(['ok' => true] + content_for_admin());
}

if (($action === 'content_save' || $action === 'content_reset') && $method === 'POST') {
    require_same_origin(true);
    if (!admin_csrf_valid()) {
        json_error(403, 'forbidden', 'Request blocked. Reload the page and try again.');
    }
    $data = read_json_body(BODY_CONTENT);
    $key = is_array($data) ? ($data['key'] ?? null) : null;
    if (!in_array($key, CONTENT_KEYS, true)) {
        json_error(400, 'invalid', 'Unknown section.');
    }
    try {
        $value = $action === 'content_save' ? content_save($key, $data['value'] ?? null) : content_reset($key);
    } catch (InvalidArgumentException $exception) {
        json_error(422, 'invalid', $exception->getMessage());
    } catch (Throwable $exception) {
        error_log('Content change failed: ' . $exception->getMessage());
        json_error(503, 'unavailable', 'The change could not be saved.');
    }
    json_response(['ok' => true, 'value' => $value, 'changed' => $action === 'content_save']);
}

json_error(400, 'invalid', 'Unknown action.');
