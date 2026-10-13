<?php
declare(strict_types=1);

/**
 * AI moderation of guestbook comments. New comments are checked on submit,
 * and older ones that have not been checked yet are re-checked a few at a time
 * in the background. A blocked comment is set to "rejected" (hidden from the
 * page); the owner still sees it under /admin/.
 */

require_once APP_DIR . '/lib/ai.php';
require_once APP_DIR . '/lib/guestbook.php';

/**
 * Moderates one already-stored comment. On "block" it is hidden (rejected).
 * Returns the verdict: 'allow', 'block' or 'unknown'. Never throws.
 */
function moderate_stored_comment(int $id, string $name, string $message): string
{
    $verdict = ai_moderate($name, $message);
    try {
        if ($verdict === 'block') {
            guestbook_set_status($id, 'rejected');
            guestbook_mark_checked($id);
        } elseif ($verdict === 'allow') {
            guestbook_mark_checked($id);
        }
    } catch (Throwable $exception) {
        error_log('Moderation update failed: ' . $exception->getMessage());
    }
    return $verdict;
}

/**
 * Re-checks up to $limit published comments the AI has not seen yet.
 * Safe to call from a request handler; never throws. Stops early if the
 * provider cannot be reached, so one outage does not burn the whole batch.
 */
function moderate_backlog(int $limit = 3): void
{
    if (!ai_configured()) {
        return;
    }
    try {
        $pending = guestbook_unchecked_published($limit);
    } catch (Throwable $exception) {
        error_log('Moderation backlog read failed: ' . $exception->getMessage());
        return;
    }
    foreach ($pending as $row) {
        if (moderate_stored_comment((int) $row['id'], (string) $row['name'], (string) $row['message']) === 'unknown') {
            break;
        }
    }
}
