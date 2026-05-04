DROP TABLE IF EXISTS icons;
CREATE TABLE icons (
    id               INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    asset_id         INTEGER UNSIGNED NOT NULL,                      -- references assets.id
    description_hash CHAR(32) DEFAULT NULL,                          -- md5 of the assets.description we last processed (drives invalidation)
    source_url       VARCHAR(500) DEFAULT NULL,                      -- URL we last fetched from (post-resolver)
    source_hash      CHAR(32) DEFAULT NULL,                          -- md5 of the bytes we fetched
    icon_hash        CHAR(32) DEFAULT NULL,                          -- md5 of the generated 48x48 PNG
    status           ENUM('pending','ok','failed','stale') NOT NULL DEFAULT 'pending',
    attempts         INTEGER UNSIGNED NOT NULL DEFAULT 0,
    last_error       VARCHAR(255) DEFAULT NULL,
    next_retry_at    DATETIME DEFAULT NULL,                          -- backoff: don't reprocess before this time
    last_checked_at  DATETIME DEFAULT NULL,
    created          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX asset_id        ON icons (asset_id);
CREATE        INDEX status          ON icons (status);
CREATE        INDEX next_retry_at   ON icons (next_retry_at);
CREATE        INDEX last_checked_at ON icons (last_checked_at);
