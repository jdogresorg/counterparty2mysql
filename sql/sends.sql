DROP TABLE IF EXISTS sends;
CREATE TABLE sends (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index         INTEGER UNSIGNED,
    -- tx_hash          TEXT,
    tx_hash_id       INTEGER UNSIGNED, -- id of record in transactions
    block_index      INTEGER UNSIGNED,
    -- source           TEXT,
    source_id        INTEGER UNSIGNED, -- id of record in addresses
    -- destination      TEXT,
    destination_id   INTEGER UNSIGNED, -- id of record in addresses
    -- asset            TEXT,
    asset_id         INTEGER UNSIGNED, -- id of record in assets table
    quantity         INTEGER UNSIGNED,
    status           TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index    ON sends (block_index);
CREATE INDEX tx_hash_id     ON sends (tx_hash_id);
CREATE INDEX source_id      ON sends (source_id);
CREATE INDEX destination_id ON sends (destination_id);
CREATE INDEX asset_id       ON sends (asset_id);

