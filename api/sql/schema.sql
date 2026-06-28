-- The Heart of Jerome — database schema
-- Run this ONCE in hPanel → Databases → phpMyAdmin → SQL tab.

CREATE TABLE IF NOT EXISTS kindness_acts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    num_acts     INT UNSIGNED NOT NULL DEFAULT 1,
    name         VARCHAR(100)      DEFAULT NULL,
    email        VARCHAR(255)  NOT NULL,
    description  TEXT              DEFAULT NULL,
    photo_path   VARCHAR(255)      DEFAULT NULL,
    logged_idaho TINYINT(1)    NOT NULL DEFAULT 0,
    ip_address   VARCHAR(45)       DEFAULT NULL,
    created_at   DATETIME      NOT NULL,
    PRIMARY KEY (id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
