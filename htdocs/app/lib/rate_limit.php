<?php
declare(strict_types=1);

/**
 * Rate limiting with fixed time windows.
 *
 * Shared store: with a database the counters live in the table `rate_limits`
 * and hold for every request. Without a database a small file store in the
 * system temp folder is used (enough for the assistant, which needs no database).
 *
 * Returns 'ok', 'limited' or 'unavailable' (store could not be reached and
 * $failClosed is true).
 */
function rate_limit(string $name, string $subject, int $limit, int $windowSeconds, bool $failClosed = true): string
{
    $key = substr($name . ':' . $subject, 0, 190);
    $window = intdiv(time(), $windowSeconds) * $windowSeconds;

    if (db_configured()) {
        try {
            $pdo = db();
            $upsert = db_driver() === 'sqlite'
                ? 'INSERT INTO rate_limits (rl_key, window_start, hits) VALUES (?, ?, 1)
                   ON CONFLICT (rl_key, window_start) DO UPDATE SET hits = hits + 1'
                : 'INSERT INTO rate_limits (rl_key, window_start, hits) VALUES (?, ?, 1)
                   ON DUPLICATE KEY UPDATE hits = hits + 1';
            $pdo->prepare($upsert)->execute([$key, $window]);
            $select = $pdo->prepare('SELECT hits FROM rate_limits WHERE rl_key = ? AND window_start = ?');
            $select->execute([$key, $window]);
            $hits = (int) $select->fetchColumn();
            if (random_int(1, 50) === 1) {
                $pdo->prepare('DELETE FROM rate_limits WHERE window_start < ?')->execute([time() - 172800]);
            }
            return $hits <= $limit ? 'ok' : 'limited';
        } catch (Throwable $exception) {
            error_log('Rate limit store failed: ' . $exception->getMessage());
            if ($failClosed) {
                return 'unavailable';
            }
        }
    }

    return file_rate_limit($key, $window, $limit) ? 'ok' : 'limited';
}

/** Several rules in a row; stops at the first one that is not 'ok'. */
function rate_limit_all(array $rules, bool $failClosed = true): string
{
    foreach ($rules as [$name, $subject, $limit, $window]) {
        $result = rate_limit($name, $subject, $limit, $window, $failClosed);
        if ($result !== 'ok') {
            return $result;
        }
    }
    return 'ok';
}

function file_rate_limit(string $key, int $window, int $limit): bool
{
    $dir = getenv('ELIAS_RATE_DIR') ?: (sys_get_temp_dir() . '/elias-rate-limits');
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return true; // No place to count: do not block visitors.
    }
    $file = $dir . '/' . hash('sha256', $key . '|' . $window) . '.cnt';
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        return true;
    }
    flock($handle, LOCK_EX);
    $hits = (int) stream_get_contents($handle) + 1;
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, (string) $hits);
    flock($handle, LOCK_UN);
    fclose($handle);
    if (random_int(1, 100) === 1) {
        foreach (glob($dir . '/*.cnt') ?: [] as $old) {
            if (filemtime($old) < time() - 172800) {
                @unlink($old);
            }
        }
    }
    return $hits <= $limit;
}
