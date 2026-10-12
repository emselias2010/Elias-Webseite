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

/** @param array<int, array{role: string, content: string}> $messages */
function ask_assistant(array $messages): string
{
    $ai = config()['ai'];
    $base = rtrim((string) $ai['base_url'], '/');
    $payload = json_encode([
        'model' => (string) $ai['model'],
        'messages' => array_merge([['role' => 'system', 'content' => build_system_prompt()]], $messages),
        'max_tokens' => AI_MAX_OUTPUT_TOKENS,
        'temperature' => 0.4,
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
        CURLOPT_TIMEOUT => AI_TIMEOUT_SECONDS,
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
    return mb_substr($reply, 0, AI_MAX_REPLY_CHARS);
}
