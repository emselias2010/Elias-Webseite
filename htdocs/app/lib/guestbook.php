<?php
declare(strict_types=1);

/** Database access for the guestbook. All queries use prepared statements. */

const GUESTBOOK_PAGE_SIZE = 10;

/** Published comments, newest first, with likes ($visitor: whether this visitor liked them). */
function guestbook_published(?array $cursor, string $visitor = ''): array
{
    $sql = 'SELECT id, name, message, published_at FROM guestbook_comments WHERE status = ?';
    $params = ['published'];
    if ($cursor !== null) {
        $sql .= ' AND (published_at < ? OR (published_at = ? AND id < ?))';
        array_push($params, $cursor['published_at'], $cursor['published_at'], $cursor['id']);
    }
    $sql .= ' ORDER BY published_at DESC, id DESC LIMIT ' . (GUESTBOOK_PAGE_SIZE + 1);
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $rows = $statement->fetchAll();

    $page = array_slice($rows, 0, GUESTBOOK_PAGE_SIZE);
    $likes = guestbook_likes(array_map(static fn (array $row): int => (int) $row['id'], $page), $visitor);
    $comments = array_map(static fn (array $row): array => [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'message' => (string) $row['message'],
        'publishedAt' => iso_date((string) $row['published_at']),
        'likes' => $likes[(int) $row['id']]['count'] ?? 0,
        'liked' => $likes[(int) $row['id']]['liked'] ?? false,
    ], $page);

    $next = null;
    if (count($rows) > GUESTBOOK_PAGE_SIZE && $page !== []) {
        $last = $page[count($page) - 1];
        $next = $last['published_at'] . '|' . $last['id'];
    }
    return ['comments' => $comments, 'next' => $next];
}

function guestbook_is_duplicate(string $ipHash, string $message): bool
{
    $statement = db()->prepare(
        'SELECT 1 FROM guestbook_comments WHERE ip_hash = ? AND message = ? AND created_at >= ? LIMIT 1'
    );
    $statement->execute([$ipHash, $message, db_now(-86400)]);
    return $statement->fetchColumn() !== false;
}

/** New comments are published right away. Reported ones can be removed in /admin/. */
function guestbook_insert(string $name, string $message, string $ipHash): array
{
    $now = db_now();
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO guestbook_comments (name, message, status, ip_hash, created_at, published_at) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$name, $message, 'published', $ipHash, $now, $now]);
    return [
        'id' => (int) $pdo->lastInsertId(),
        'name' => $name,
        'message' => $message,
        'publishedAt' => iso_date($now),
        'likes' => 0,
        'liked' => false,
    ];
}

function guestbook_published_exists(int $id): bool
{
    $statement = db()->prepare('SELECT 1 FROM guestbook_comments WHERE id = ? AND status = ? LIMIT 1');
    $statement->execute([$id, 'published']);
    return $statement->fetchColumn() !== false;
}

/** One report per visitor and comment; repeats are ignored. */
function guestbook_report(int $commentId, string $reason, string $ipHash): void
{
    $sql = db_driver() === 'sqlite'
        ? 'INSERT OR IGNORE INTO guestbook_reports (comment_id, reason, ip_hash, created_at) VALUES (?, ?, ?, ?)'
        : 'INSERT IGNORE INTO guestbook_reports (comment_id, reason, ip_hash, created_at) VALUES (?, ?, ?, ?)';
    db()->prepare($sql)->execute([$commentId, $reason === '' ? null : $reason, $ipHash, db_now()]);
}

/* --------------------------------- Likes --------------------------------- */

/** Older installations imported schema.sql before likes existed. */
function guestbook_ensure_likes(): void
{
    db()->exec(db_driver() === 'sqlite'
        ? 'CREATE TABLE IF NOT EXISTS guestbook_likes (comment_id INTEGER NOT NULL, ip_hash TEXT NOT NULL, created_at TEXT NOT NULL, PRIMARY KEY (comment_id, ip_hash))'
        : 'CREATE TABLE IF NOT EXISTS guestbook_likes (comment_id INT UNSIGNED NOT NULL, ip_hash CHAR(40) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (comment_id, ip_hash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

/**
 * Like counts for some comments: [id => ['count' => n, 'liked' => bool]].
 * Returns [] if the likes table does not exist yet.
 */
function guestbook_likes(array $ids, string $visitor = ''): array
{
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $statement = db()->prepare(
            "SELECT comment_id, COUNT(*) AS likes, SUM(CASE WHEN ip_hash = ? THEN 1 ELSE 0 END) AS mine
             FROM guestbook_likes WHERE comment_id IN ($placeholders) GROUP BY comment_id"
        );
        $statement->execute(array_merge([$visitor], $ids));
    } catch (Throwable) {
        return [];
    }
    $result = [];
    foreach ($statement->fetchAll() as $row) {
        $result[(int) $row['comment_id']] = ['count' => (int) $row['likes'], 'liked' => (int) $row['mine'] > 0];
    }
    return $result;
}

/** Sets or removes this visitor's like and returns the new count. */
function guestbook_set_like(int $id, string $visitor, bool $liked): int
{
    guestbook_ensure_likes();
    if ($liked) {
        $sql = db_driver() === 'sqlite'
            ? 'INSERT OR IGNORE INTO guestbook_likes (comment_id, ip_hash, created_at) VALUES (?, ?, ?)'
            : 'INSERT IGNORE INTO guestbook_likes (comment_id, ip_hash, created_at) VALUES (?, ?, ?)';
        db()->prepare($sql)->execute([$id, $visitor, db_now()]);
    } else {
        db()->prepare('DELETE FROM guestbook_likes WHERE comment_id = ? AND ip_hash = ?')->execute([$id, $visitor]);
    }
    return guestbook_likes([$id], $visitor)[$id]['count'] ?? 0;
}

/* ------------------------------- Moderation ------------------------------- */

/** $status: pending, published, rejected, or "reported" (any status, at least one report). */
function guestbook_for_moderation(string $status): array
{
    if ($status === 'reported') {
        $statement = db()->prepare(
            'SELECT id, name, message, status, created_at, published_at FROM guestbook_comments
             WHERE id IN (SELECT comment_id FROM guestbook_reports) ORDER BY created_at DESC, id DESC LIMIT 100'
        );
        $statement->execute();
    } else {
        $statement = db()->prepare(
            'SELECT id, name, message, status, created_at, published_at FROM guestbook_comments
             WHERE status = ? ORDER BY created_at DESC, id DESC LIMIT 100'
        );
        $statement->execute([$status]);
    }
    $rows = $statement->fetchAll();
    if ($rows === []) {
        return [];
    }

    $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $reports = db()->prepare(
        "SELECT comment_id, reason, created_at FROM guestbook_reports WHERE comment_id IN ($placeholders) ORDER BY created_at DESC"
    );
    $reports->execute($ids);
    $byComment = [];
    foreach ($reports->fetchAll() as $report) {
        $byComment[(int) $report['comment_id']][] = ['reason' => (string) ($report['reason'] ?? ''), 'createdAt' => iso_date($report['created_at'])];
    }

    $likes = guestbook_likes($ids);
    return array_map(static fn (array $row): array => [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'message' => (string) $row['message'],
        'likes' => $likes[(int) $row['id']]['count'] ?? 0,
        'status' => (string) $row['status'],
        'createdAt' => iso_date($row['created_at']),
        'publishedAt' => iso_date($row['published_at']),
        'reports' => $byComment[(int) $row['id']] ?? [],
    ], $rows);
}

/** Returns false when the comment does not exist. */
function guestbook_set_status(int $id, string $status): bool
{
    $statement = db()->prepare('UPDATE guestbook_comments SET status = ?, published_at = ? WHERE id = ?');
    $statement->execute([$status, $status === 'published' ? db_now() : null, $id]);
    if ($statement->rowCount() > 0) {
        return true;
    }
    // MySQL reports 0 changed rows when nothing changed; check that the row exists.
    $exists = db()->prepare('SELECT 1 FROM guestbook_comments WHERE id = ?');
    $exists->execute([$id]);
    return $exists->fetchColumn() !== false;
}

/** Number of comments with at least one report (for the badge in /admin/). */
function guestbook_reported_count(): int
{
    return (int) db()->query('SELECT COUNT(DISTINCT comment_id) FROM guestbook_reports')->fetchColumn();
}

/** Removes all reports from a comment (keeps the comment). False if it does not exist. */
function guestbook_clear_reports(int $id): bool
{
    db()->prepare('DELETE FROM guestbook_reports WHERE comment_id = ?')->execute([$id]);
    $exists = db()->prepare('SELECT 1 FROM guestbook_comments WHERE id = ?');
    $exists->execute([$id]);
    return $exists->fetchColumn() !== false;
}

function guestbook_delete(int $id): bool
{
    db()->prepare('DELETE FROM guestbook_reports WHERE comment_id = ?')->execute([$id]);
    try {
        db()->prepare('DELETE FROM guestbook_likes WHERE comment_id = ?')->execute([$id]);
    } catch (Throwable) {
        // No likes table yet.
    }
    $statement = db()->prepare('DELETE FROM guestbook_comments WHERE id = ?');
    $statement->execute([$id]);
    return $statement->rowCount() > 0;
}
