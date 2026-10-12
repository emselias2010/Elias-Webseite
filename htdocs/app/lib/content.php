<?php
declare(strict_types=1);

/**
 * Editable content. The PHP files in app/data hold the defaults; changes made
 * in /admin/ are stored in the database table "site_content" (one JSON row per
 * key) and win over the defaults. Without a database the defaults are used.
 *
 * Every value is checked against a schema when it is saved and again when it
 * is loaded, so a broken row can never break the page.
 */

require_once APP_DIR . '/data/profile.php';
require_once APP_DIR . '/data/sport.php';
require_once APP_DIR . '/data/knowledge.php';

const BODY_CONTENT = 200000;
const CONTENT_KEYS = ['profile', 'social', 'sport_intro', 'sport_stats', 'strava_stats', 'gallery', 'knowledge'];

/** Current value of an editable block: the saved version or the default. */
function content(string $key): array
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $saved = content_saved_rows()[$key] ?? null;
        $value = null;
        if ($saved !== null) {
            try {
                $value = content_clean(content_schema($key), $saved, $key);
            } catch (InvalidArgumentException $exception) {
                error_log('Saved content "' . $key . '" ignored: ' . $exception->getMessage());
            }
        }
        $cache[$key] = content_merge($key, $value);
    }
    return $cache[$key];
}

/** The default from app/data, in the shape the admin edits. */
function content_default(string $key): array
{
    return match ($key) {
        'profile' => [
            'role' => PROFILE['role'] ?? '',
            'headline' => PROFILE['headline'],
            'intro' => PROFILE['intro'],
            'description' => PROFILE['description'],
            'about' => [
                'title' => PROFILE['about']['title'],
                'paragraphs' => PROFILE['about']['paragraphs'],
                'facts' => PROFILE['about']['facts'],
                'interests' => PROFILE['about']['interests'],
            ],
        ],
        'social' => array_map(static fn (array $link): array => [
            'platform' => $link['platform'],
            'url' => $link['url'],
            'handle' => $link['handle'],
            'note' => $link['note'],
        ], SOCIAL_LINKS),
        'sport_intro' => [
            'title' => SPORT_INTRO['title'],
            'text' => SPORT_INTRO['text'],
            'quote' => SPORT_INTRO['quote'] ?? '',
            'strava_as_of' => SPORT_INTRO['strava_as_of'] ?? '',
        ],
        'sport_stats' => array_map(static fn (array $stat): array => $stat + ['note' => ''], SPORT_STATS),
        'strava_stats' => STRAVA_STATS,
        'gallery' => array_map(static fn (array $item): array => [
            'category' => $item['category'],
            'title' => $item['title'],
            'date' => $item['date'] ?? '',
            'stat' => $item['stat'] ?? ['value' => '', 'unit' => ''],
            'facts' => $item['facts'] ?? [],
            'description' => $item['description'],
            'route' => $item['route'] ?? '',
        ], GALLERY_ITEMS),
        'knowledge' => [
            'sections' => array_map(static fn (array $section): array => [
                'topic' => $section['topic'],
                'facts' => $section['facts'] ?? [],
                'plans' => $section['plans'] ?? [],
            ], AI_KNOWLEDGE),
            'questions' => SUGGESTED_QUESTIONS,
        ],
        default => throw new InvalidArgumentException('Unknown content.'),
    };
}

/** Turns the edited shape back into what the templates use. */
function content_merge(string $key, ?array $value): array
{
    if ($value === null) {
        // Nothing saved: the data files as they are (including photos).
        return match ($key) {
            'profile' => PROFILE,
            'social' => SOCIAL_LINKS,
            'sport_intro' => SPORT_INTRO,
            'sport_stats' => SPORT_STATS,
            'strava_stats' => STRAVA_STATS,
            'gallery' => GALLERY_ITEMS,
            'knowledge' => ['sections' => AI_KNOWLEDGE, 'questions' => SUGGESTED_QUESTIONS],
        };
    }
    switch ($key) {
        case 'profile':
            $profile = PROFILE;
            foreach (['role', 'headline', 'intro', 'description'] as $field) {
                $profile[$field] = $value[$field];
            }
            $profile['about'] = array_replace($profile['about'], $value['about']);
            return $profile;
        case 'social':
            $byPlatform = array_column($value, null, 'platform');
            return array_map(static fn (array $link): array => array_replace($link, array_intersect_key($byPlatform[$link['platform']] ?? [], array_flip(['url', 'handle', 'note']))), SOCIAL_LINKS);
        case 'sport_intro':
            return array_replace(SPORT_INTRO, $value);
        case 'gallery':
            return array_map(static fn (array $item): array => $item + ['alt' => '', 'image' => null], $value);
        default:
            return $value;
    }
}

/* ------------------------------- Schemas -------------------------------- */

function content_schema(string $key): array
{
    $text = static fn (int $max, bool $multiline = false): array => ['type' => 'string', 'max' => $max, 'multiline' => $multiline];
    $list = static fn (array $item, int $max, int $min = 0): array => ['type' => 'list', 'item' => $item, 'max' => $max, 'min' => $min];
    $object = static fn (array $fields): array => ['type' => 'object', 'fields' => $fields];

    return match ($key) {
        'profile' => $object([
            'role' => $text(80),
            'headline' => $list($text(40), 5, 1),
            'intro' => $text(600, true),
            'description' => $text(300),
            'about' => $object([
                'title' => $text(120),
                'paragraphs' => $list($text(1500, true), 6, 1),
                'facts' => $list($object(['label' => $text(40), 'value' => $text(80)]), 8),
                'interests' => $list($text(60), 30),
            ]),
        ]),
        'social' => $list($object([
            'platform' => ['type' => 'enum', 'values' => array_column(SOCIAL_LINKS, 'platform')],
            'url' => ['type' => 'link', 'max' => 300],
            'handle' => $text(80),
            'note' => $text(80),
        ]), count(SOCIAL_LINKS)),
        'sport_intro' => $object(['title' => $text(120), 'text' => $text(800, true), 'quote' => $text(160), 'strava_as_of' => $text(60)]),
        'sport_stats' => $list($object(['label' => $text(40), 'value' => $text(60), 'note' => $text(120)]), 8),
        'strava_stats' => $list($object([
            'value' => ['type' => 'number', 'min' => 0, 'max' => 10000000],
            'decimals' => ['type' => 'int', 'min' => 0, 'max' => 2],
            'unit' => $text(12),
            'label' => $text(40),
            'note' => $text(120),
        ]), 8),
        'gallery' => $list($object([
            'category' => ['type' => 'enum', 'values' => SPORT_CATEGORIES],
            'title' => $text(80),
            'date' => $text(40),
            'stat' => $object(['value' => $text(16), 'unit' => $text(16)]),
            'facts' => $list($text(60), 6),
            'description' => $text(400, true),
            'route' => ['type' => 'route', 'max' => 8000],
        ]), 40),
        // Only facts for Ask Elias. The rules of the assistant stay fixed in app/data/knowledge.php.
        'knowledge' => $object([
            'sections' => $list($object([
                'topic' => $text(80),
                'facts' => $list($text(500), 30),
                'plans' => $list($text(300), 15),
            ]), 30, 1),
            'questions' => $list($text(120), 8),
        ]),
        default => throw new InvalidArgumentException('Unknown content.'),
    };
}

/**
 * Checks and cleans a value against a schema. Unknown fields are dropped,
 * missing optional fields get an empty value. Throws InvalidArgumentException.
 */
function content_clean(array $schema, mixed $value, string $path): mixed
{
    switch ($schema['type']) {
        case 'string':
            if ($value === null) {
                $value = '';
            }
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException("$path must be text.");
            }
            $value = $schema['multiline'] ? clean_text((string) $value) : clean_line((string) $value);
            if (mb_strlen($value) > $schema['max']) {
                throw new InvalidArgumentException("$path is longer than {$schema['max']} characters.");
            }
            return $value;
        case 'enum':
            if (!in_array($value, $schema['values'], true)) {
                throw new InvalidArgumentException("$path has an unknown value.");
            }
            return $value;
        case 'number':
        case 'int':
            if ($value === null || $value === '') {
                $value = 0;
            }
            if (!is_numeric($value)) {
                throw new InvalidArgumentException("$path must be a number.");
            }
            $number = $schema['type'] === 'int' ? (int) $value : (float) $value;
            if ($number < $schema['min'] || $number > $schema['max']) {
                throw new InvalidArgumentException("$path is out of range.");
            }
            return $number;
        case 'link':
            // Empty, an https:// address or an e-mail address. Nothing else (no javascript: etc.).
            $value = trim((string) ($value ?? ''));
            $address = preg_replace('/^mailto:/i', '', $value) ?? '';
            if ($value !== '' && (strlen($value) > $schema['max'] || !(preg_match('~^https://[^\s"<>]+$~i', $value) || filter_var($address, FILTER_VALIDATE_EMAIL)))) {
                throw new InvalidArgumentException("$path must start with https:// or be an e-mail address.");
            }
            return $value;
        case 'route':
            $value = preg_replace('/\s+/', ' ', trim((string) ($value ?? ''))) ?? '';
            if ($value !== '' && (strlen($value) > $schema['max'] || !preg_match('/^M\d{1,3}(\.\d)? \d{1,3}(\.\d)?(L\d{1,3}(\.\d)? \d{1,3}(\.\d)?)*$/', $value))) {
                throw new InvalidArgumentException("$path is not a valid route.");
            }
            return $value;
        case 'list':
            if ($value === null) {
                $value = [];
            }
            if (!is_array($value) || !array_is_list($value)) {
                throw new InvalidArgumentException("$path must be a list.");
            }
            if (count($value) > $schema['max'] || count($value) < $schema['min']) {
                throw new InvalidArgumentException("$path needs between {$schema['min']} and {$schema['max']} entries.");
            }
            $clean = [];
            foreach ($value as $index => $item) {
                $clean[] = content_clean($schema['item'], $item, $path . '[' . ($index + 1) . ']');
            }
            return $clean;
        case 'object':
            if (!is_array($value)) {
                throw new InvalidArgumentException("$path is incomplete.");
            }
            $clean = [];
            foreach ($schema['fields'] as $field => $fieldSchema) {
                $clean[$field] = content_clean($fieldSchema, $value[$field] ?? null, $path . '.' . $field);
            }
            return $clean;
    }
    throw new InvalidArgumentException("$path cannot be checked.");
}

/* ------------------------------- Storage -------------------------------- */

/** All saved rows as decoded JSON, or [] when there is no database or no table yet. */
function content_saved_rows(): array
{
    static $rows = null;
    if ($rows !== null) {
        return $rows;
    }
    $rows = [];
    if (!db_configured()) {
        return $rows;
    }
    try {
        foreach (db()->query('SELECT name, data FROM site_content')->fetchAll() as $row) {
            $decoded = json_decode((string) $row['data'], true);
            if (is_array($decoded)) {
                $rows[(string) $row['name']] = $decoded;
            }
        }
    } catch (Throwable) {
        // No table yet (it is created with the first save) or no connection.
        $rows = [];
    }
    return $rows;
}

function content_ensure_table(): void
{
    $sql = db_driver() === 'sqlite'
        ? 'CREATE TABLE IF NOT EXISTS site_content (name VARCHAR(64) NOT NULL PRIMARY KEY, data TEXT NOT NULL, updated_at DATETIME NOT NULL)'
        : 'CREATE TABLE IF NOT EXISTS site_content (name VARCHAR(64) NOT NULL, data MEDIUMTEXT NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    db()->exec($sql);
}

/** Validates and stores a block. Throws InvalidArgumentException with a readable message. */
function content_save(string $key, mixed $value): array
{
    $clean = content_clean(content_schema($key), $value, content_label($key));
    content_ensure_table();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM site_content WHERE name = ?')->execute([$key]);
        $pdo->prepare('INSERT INTO site_content (name, data, updated_at) VALUES (?, ?, ?)')
            ->execute([$key, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), db_now()]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    return $clean;
}

/** Removes the saved version, so the default from app/data is used again. */
function content_reset(string $key): array
{
    content_ensure_table();
    db()->prepare('DELETE FROM site_content WHERE name = ?')->execute([$key]);
    return content_clean(content_schema($key), content_default($key), $key);
}

/** Everything the admin needs: current values, which ones are changed, choices. */
function content_for_admin(): array
{
    $saved = content_saved_rows();
    $values = [];
    foreach (CONTENT_KEYS as $key) {
        $value = null;
        if (isset($saved[$key])) {
            try {
                $value = content_clean(content_schema($key), $saved[$key], $key);
            } catch (InvalidArgumentException) {
                $value = null;
            }
        }
        $values[$key] = ['value' => $value ?? content_clean(content_schema($key), content_default($key), $key), 'changed' => $value !== null];
    }
    return ['content' => $values, 'categories' => SPORT_CATEGORIES];
}

function content_label(string $key): string
{
    return [
        'profile' => 'Texts',
        'social' => 'Links',
        'sport_intro' => 'Sport intro',
        'sport_stats' => 'Highlights',
        'strava_stats' => 'Strava numbers',
        'gallery' => 'Activities',
        'knowledge' => 'Ask Elias',
    ][$key] ?? $key;
}
