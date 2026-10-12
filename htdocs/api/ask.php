<?php
/**
 * GET  /api/ask.php  is the assistant configured?
 * POST /api/ask.php  answers one question.
 *
 * (The file is deliberately not called "chat": InfinityFree blocks URLs that
 * contain that word.) Conversations are not stored: the browser sends the
 * recent history with each request and the server keeps nothing.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_DIR . '/lib/ai.php';

$method = request_method();
if ($method === 'GET') {
    json_response(['ok' => true, 'configured' => ai_configured()]);
}
if ($method !== 'POST') {
    json_error(405, 'method_not_allowed', 'Not allowed.', ['Allow' => 'GET, POST']);
}

require_same_origin();
if (!ai_configured()) {
    json_error(503, 'not_configured', 'The assistant is not set up yet: no AI provider is configured.');
}

$input = validate_question_history(read_json_body(BODY_ASK));
if (!$input['ok']) {
    json_error(400, 'invalid', $input['error']);
}

$visitor = ip_hash();
if (rate_limit_all([['ask:5m', $visitor, 8, 300], ['ask:day', $visitor, 40, 86400]], false) !== 'ok') {
    json_error(429, 'rate_limited', "That's a lot of questions. Give it a few minutes and ask again.", ['Retry-After' => '300']);
}
// Cost control: one budget per day for all visitors together.
$daily = max(1, (int) config()['ai']['daily_limit']);
if (rate_limit('ask:global:day', 'all', $daily, 86400, false) !== 'ok') {
    json_error(429, 'budget_reached', 'The assistant has answered its daily share of questions. Come back tomorrow.');
}

try {
    $reply = ask_assistant($input['messages']);
} catch (AiError $error) {
    if ($error->kind === 'timeout') {
        json_error(504, 'timeout', 'The assistant took too long to answer. Please try again.');
    }
    json_error(502, 'upstream', 'The assistant could not answer right now. Please try again later.');
}

json_response(['ok' => true, 'reply' => $reply]);
