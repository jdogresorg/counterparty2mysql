DROP TABLE IF EXISTS rpsresolves;
CREATE TABLE rpsresolves (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index         INTEGER UNSIGNED,
    -- tx_hash          TEXT,
    tx_hash_id       INTEGER UNSIGNED, -- id of record in transactions
    block_index      INTEGER UNSIGNED,
    -- source           TEXT,
    source_id        INTEGER UNSIGNED, -- id of record in addresses
    move             INTEGER UNSIGNED,
    random           TEXT,
    rps_match_id     TEXT,
    status           TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index   ON rpsresolves (block_index);
CREATE INDEX tx_hash_id    ON rpsresolves (tx_hash_id);
CREATE INDEX source_id     ON rpsresolves (source_id);

