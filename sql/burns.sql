DROP TABLE IF EXISTS burns;
CREATE TABLE burns (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index      INTEGER UNSIGNED,
    -- tx_hash       TEXT,
    tx_hash_id    INTEGER UNSIGNED, -- id of record in transactions
    block_index   INTEGER UNSIGNED,
    -- source        TEXT,
    source_id     INTEGER UNSIGNED, -- id of record in addresses
    burned        INTEGER UNSIGNED,
    earned        INTEGER UNSIGNED,
    status        TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index   ON burns (block_index);
CREATE INDEX source_id     ON burns (source_id);
CREATE INDEX tx_hash_id    ON burns (tx_hash_id);