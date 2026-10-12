-- ELIAS — Beyond the Profile
-- Tables for the guestbook, reports and rate limiting (MySQL / MariaDB).
--
-- Import once: InfinityFree control panel -> MySQL Databases -> phpMyAdmin ->
-- select your database -> "Import" -> choose this file -> "Go".
-- Running it again is safe: existing tables are kept.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS guestbook_comments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(40)  NOT NULL,
    message       VARCHAR(500) NOT NULL,
    status        ENUM('pending', 'published', 'rejected') NOT NULL DEFAULT 'pending',
    -- Salted hash of the visitor's IP address. The address itself is never stored.
    ip_hash       CHAR(40)     NOT NULL,
    created_at    DATETIME     NOT NULL,
    published_at  DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY public_list (status, published_at, id),
    KEY moderation (status, created_at),
    KEY duplicates (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guestbook_reports (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    comment_id  INT UNSIGNED NOT NULL,
    reason      VARCHAR(200) NULL DEFAULT NULL,
    ip_hash     CHAR(40)     NOT NULL,
    created_at  DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY one_per_visitor (comment_id, ip_hash),
    CONSTRAINT fk_report_comment FOREIGN KEY (comment_id)
        REFERENCES guestbook_comments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guestbook_likes (
    comment_id  INT UNSIGNED NOT NULL,
    -- Salted hash of the visitor's IP address: one like per visitor and comment.
    ip_hash     CHAR(40)     NOT NULL,
    created_at  DATETIME     NOT NULL,
    PRIMARY KEY (comment_id, ip_hash),
    CONSTRAINT fk_like_comment FOREIGN KEY (comment_id)
        REFERENCES guestbook_comments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    rl_key        VARCHAR(190) NOT NULL,
    window_start  INT UNSIGNED NOT NULL,
    hits          INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (rl_key, window_start),
    KEY cleanup (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Texts and Strava data edited in /admin/ (one JSON document per section).
-- Also created automatically with the first save.
CREATE TABLE IF NOT EXISTS site_content (
    name        VARCHAR(64)  NOT NULL,
    data        MEDIUMTEXT   NOT NULL,
    updated_at  DATETIME     NOT NULL,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
