<?php
declare(strict_types=1);

/* Limits shared by forms and API endpoints. */
const LIMIT_NAME = 40;
const LIMIT_MESSAGE = 500;
const LIMIT_REPORT_REASON = 200;
const LIMIT_QUESTION = 500;
const LIMIT_ANSWER = 4000;
const LIMIT_HISTORY = 12;
const LIMIT_CONTEXT = 8;
const BODY_GUESTBOOK = 4000;
const BODY_REPORT = 1500;
const BODY_ASK = 24000;
const BODY_ADMIN = 1500;

const INVISIBLE_CHARS = '\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}';

/** Removes control characters and collapses whitespace to single spaces. */
function clean_line(string $value): string
{
    $value = normalize_text($value);
    $value = preg_replace('/[\x{0000}-\x{001F}\x{007F}-\x{009F}' . INVISIBLE_CHARS . ']/u', ' ', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return trim($value);
}

/** Like clean_line, but keeps single line breaks (at most one empty line). */
function clean_text(string $value): string
{
    $value = normalize_text($value);
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[\x{0000}-\x{0009}\x{000B}-\x{001F}\x{007F}-\x{009F}' . INVISIBLE_CHARS . ']/u', ' ', $value) ?? '';
    $value = preg_replace('/[^\S\n]+/u', ' ', $value) ?? '';
    $value = preg_replace('/ ?\n ?/u', "\n", $value) ?? '';
    $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?? '';
    return trim($value);
}

function normalize_text(string $value): string
{
    if (!mb_check_encoding($value, 'UTF-8')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
    if (class_exists('Normalizer')) {
        $value = Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;
    }
    return $value;
}

/**
 * Validates a guestbook submission.
 * Returns ['ok' => true, 'name' => ..., 'message' => ..., 'website' => ..., 'elapsed' => ...]
 * or ['ok' => false, 'error' => message].
 */
function validate_guestbook(mixed $data): array
{
    if (!is_array($data) || !is_string($data['name'] ?? null) || !is_string($data['message'] ?? null)) {
        return ['ok' => false, 'error' => 'Please fill in a name and a message.'];
    }
    if (strlen($data['name']) > 200 || strlen($data['message']) > 3000) {
        return ['ok' => false, 'error' => 'That is too long.'];
    }
    $name = clean_line($data['name']);
    $message = clean_text($data['message']);
    if ($name === '') {
        return ['ok' => false, 'error' => 'Please enter a name.'];
    }
    if (mb_strlen($name) > LIMIT_NAME) {
        return ['ok' => false, 'error' => 'Name: at most ' . LIMIT_NAME . ' characters.'];
    }
    if ($message === '') {
        return ['ok' => false, 'error' => 'Please write a message.'];
    }
    if (mb_strlen($message) > LIMIT_MESSAGE) {
        return ['ok' => false, 'error' => 'Message: at most ' . LIMIT_MESSAGE . ' characters.'];
    }
    $website = $data['website'] ?? '';
    $elapsed = $data['elapsedMs'] ?? 0;
    if (!is_string($website) || strlen($website) > 200 || !(is_int($elapsed) || is_float($elapsed))) {
        return ['ok' => false, 'error' => 'Invalid request.'];
    }
    return ['ok' => true, 'name' => $name, 'message' => $message, 'website' => $website, 'elapsed' => (int) max(0, min($elapsed, 86400000))];
}

/** Cursor for "show older comments": "<published_at>|<id>". */
function parse_cursor(?string $value): array|false|null
{
    if ($value === null || $value === '') {
        return null;
    }
    if (!preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\|(\d{1,10})$/', $value, $m)) {
        return false;
    }
    return ['published_at' => $m[1], 'id' => (int) $m[2]];
}

function parse_id(mixed $value): ?int
{
    if (is_int($value) && $value > 0 && $value < 2147483647) {
        return $value;
    }
    if (is_string($value) && preg_match('/^[1-9]\d{0,9}$/', $value) && (int) $value < 2147483647) {
        return (int) $value;
    }
    return null;
}

/**
 * Validates the chat history sent by the browser.
 * Returns ['ok' => true, 'messages' => [...last LIMIT_CONTEXT...]] or ['ok' => false, 'error' => ...].
 */
function validate_question_history(mixed $data): array
{
    $messages = is_array($data) ? ($data['messages'] ?? null) : null;
    if (!is_array($messages) || !array_is_list($messages) || count($messages) < 1 || count($messages) > LIMIT_HISTORY) {
        return ['ok' => false, 'error' => 'The message could not be read. Keep questions short.'];
    }
    $clean = [];
    foreach ($messages as $message) {
        $role = is_array($message) ? ($message['role'] ?? null) : null;
        $content = is_array($message) ? ($message['content'] ?? null) : null;
        if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || strlen($content) > LIMIT_ANSWER * 4) {
            return ['ok' => false, 'error' => 'The message could not be read. Keep questions short.'];
        }
        $content = clean_text($content);
        if ($content === '') {
            continue;
        }
        if ($role === 'user' && mb_strlen($content) > LIMIT_QUESTION) {
            return ['ok' => false, 'error' => 'Questions can be at most ' . LIMIT_QUESTION . ' characters long.'];
        }
        if (mb_strlen($content) > LIMIT_ANSWER) {
            return ['ok' => false, 'error' => 'The message could not be read. Keep questions short.'];
        }
        $clean[] = ['role' => $role, 'content' => $content];
    }
    $last = end($clean);
    if ($last === false || $last['role'] !== 'user') {
        return ['ok' => false, 'error' => 'The last message must be a question.'];
    }
    return ['ok' => true, 'messages' => array_slice($clean, -LIMIT_CONTEXT)];
}
