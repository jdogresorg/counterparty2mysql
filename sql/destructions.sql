DROP TABLE IF EXISTS destructions;
CREATE TABLE destructions (
    row_index     INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index      INTEGER UNSIGNED,
    -- tx_hash       TEXT,
    tx_hash_id    INTEGER UNSIGNED, -- id of record in transactions
    block_index   INTEGER UNSIGNED,
    -- source        TEXT,
    source_id     INTEGER UNSIGNED, -- id of record in addresses
    -- asset         TEXT,
    asset_id      INTEGER UNSIGNED, -- id of record in assets table
    quantity      INTEGER UNSIGNED,
    tag           TEXT,
    status        TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index   ON destructions (block_index);
CREATE INDEX source_id     ON destructions (source_id);
CREATE INDEX asset_id      ON destructions (asset_id);
CREATE INDEX tx_hash_id    ON destructions (tx_hash_id);