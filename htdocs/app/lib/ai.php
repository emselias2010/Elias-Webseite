<?php
declare(strict_types=1);

require_once APP_DIR . '/lib/content.php';

/**
 * Calls an OpenAI-compatible chat completions API (OpenRouter by default).
 * The API key stays on the server.
 */

const AI_MAX_OUTPUT_TOKENS = 350;
const AI_TIMEOUT_SECONDS = 20;
const AI_MAX_REPLY_CHARS = 2400;

final class AiError extends RuntimeException
{
    public string $kind;

    public function __construct(string $kind, string $message)
    {
        $this->kind = $kind;
        parent::__construct($message);
    }
}

function ai_configured(): bool
{
    return trim((string) config()['ai']['api_key']) !== '';
}

/**
 * Low-level call to the chat completions API. Returns the assistant's text.
 * @param array<int, array{role: string, content: string}> $messages
 */
function ai_chat(array $messages, int $maxTokens, int $timeout, float $temperature): string
{
    $ai = config()['ai'];
    $base = rtrim((string) $ai['base_url'], '/');
    $payload = json_encode([
        'model' => (string) $ai['model'],
        'messages' => $messages,
        'max_tokens' => $maxTokens,
        'temperature' => $temperature,
    ], JSON_UNESCAPED_UNICODE);

    $curl = curl_init($base . '/chat/completions');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $ai['api_key'],
            'Content-Type: application/json',
            'X-Title: Ask Elias',
        ],
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
    ]);
    $body = curl_exec($curl);
    $errno = curl_errno($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($errno === CURLE_OPERATION_TIMEDOUT) {
        throw new AiError('timeout', 'The AI provider took too long to answer.');
    }
    if ($body === false || $errno !== 0) {
        throw new AiError('upstream', 'The AI provider could not be reached.');
    }
    if ($status < 200 || $status >= 300) {
        error_log('AI provider answered with status ' . $status);
        throw new AiError('upstream', 'The AI provider answered with an error.');
    }
    $data = json_decode((string) $body, true);
    $content = is_array($data) ? ($data['choices'][0]['message']['content'] ?? null) : null;
    $reply = is_string($content) ? trim($content) : '';
    if ($reply === '') {
        throw new AiError('empty', 'The AI provider returned an empty answer.');
    }
    return $reply;
}

/** @param array<int, array{role: string, content: string}> $messages */
function ask_assistant(array $messages): string
{
    $reply = ai_chat(
        array_merge([['role' => 'system', 'content' => build_system_prompt()]], $messages),
        AI_MAX_OUTPUT_TOKENS,
        AI_TIMEOUT_SECONDS,
        0.4,
    );
    return mb_substr($reply, 0, AI_MAX_REPLY_CHARS);
}

const AI_MODERATION_TIMEOUT = 8;

/**
 * Checks a guestbook comment for abuse. Returns 'allow', 'block', or
 * 'unknown' (when the provider could not be reached). Never throws.
 */
function ai_moderate(string $name, string $message): string
{
    if (!ai_configured()) {
        return 'unknown';
    }
    $system = <<<'PROMPT'
You moderate the public guestbook of a personal website. Decide whether a visitor's comment may stay online.

Block it if it contains any of: insults or personal attacks, hate speech or slurs, harassment or threats, sexual or explicit content, spam or advertising, scams or phishing, links to shady sites, or clearly abusive gibberish.

Allow everything else, including short messages, praise, honest criticism, jokes and harmless off-topic notes. When in doubt, allow.

Answer with exactly one word: ALLOW or BLOCK.
PROMPT;
    try {
        $reply = ai_chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => 'Name: ' . $name . "\nComment: " . $message],
        ], 3, AI_MODERATION_TIMEOUT, 0.0);
    } catch (AiError) {
        return 'unknown';
    }
    $verdict = strtoupper($reply);
    if (str_contains($verdict, 'BLOCK')) {
        return 'block';
    }
    if (str_contains($verdict, 'ALLOW')) {
        return 'allow';
    }
    return 'unknown';
}
