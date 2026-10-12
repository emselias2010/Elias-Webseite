<?php
declare(strict_types=1);

/**
 * Database connection (PDO, prepared statements only).
 * Returns null when no database is configured.
 * The "sqlite" driver exists only for the automated tests.
 */
function db(): ?PDO
{
    static $pdo = null;
    static $tried = false;
    if ($tried) {
        return $pdo;
    }
    $tried = true;
    $cfg = config()['db'];

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];

    if (($cfg['driver'] ?? 'mysql') === 'sqlite') {
        $pdo = new PDO('sqlite:' . $cfg['sqlite_path'], null, null, $options);
        return $pdo;
    }
    if (trim((string) $cfg['host']) === '' || trim((string) $cfg['name']) === '') {
        return null;
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], (int) $cfg['port'], $cfg['name']);
    try {
        try {
            $pdo = new PDO($dsn, (string) $cfg['user'], (string) $cfg['pass'], $options);
        } catch (PDOException $exception) {
            // 1049 = unknown database. Only DB_CREATE_IF_MISSING=true (XAMPP) creates it.
            $unknownDatabase = (int) $exception->getCode() === 1049 || (int) ($exception->errorInfo[1] ?? 0) === 1049;
            if (empty($cfg['create_if_missing']) || !$unknownDatabase) {
                throw $exception;
            }
            $pdo = db_create_local($cfg, $options);
        }
        $pdo->exec("SET time_zone = '+00:00'");
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        throw new RuntimeException('database-unavailable');
    }
    return $pdo;
}

/** XAMPP only: creates the database and its tables from database/schema.sql. */
function db_create_local(array $cfg, array $options): PDO
{
    $name = (string) $cfg['name'];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new PDOException('Invalid database name for automatic creation.');
    }
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $cfg['host'], (int) $cfg['port']);
    $pdo = new PDO($dsn, (string) $cfg['user'], (string) $cfg['pass'], $options);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . $name . '`');
    $schema = (string) file_get_contents(dirname(APP_DIR) . '/database/schema.sql');
    $schema = (string) preg_replace('/^\s*--.*$/m', '', $schema);
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        $pdo->exec($statement);
    }
    return $pdo;
}

function db_configured(): bool
{
    $cfg = config()['db'];
    return ($cfg['driver'] ?? 'mysql') === 'sqlite' || (trim((string) $cfg['host']) !== '' && trim((string) $cfg['name']) !== '');
}

function db_driver(): string
{
    return (string) (config()['db']['driver'] ?? 'mysql');
}

/** Current UTC time as stored in the database. */
function db_now(int $offsetSeconds = 0): string
{
    return gmdate('Y-m-d H:i:s', time() + $offsetSeconds);
}

/** "2026-01-02 10:00:00" (UTC) -> "2026-01-02T10:00:00Z" for JavaScript. */
function iso_date(?string $value): ?string
{
    return $value ? str_replace(' ', 'T', $value) . 'Z' : null;
}
