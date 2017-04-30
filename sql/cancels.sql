DROP TABLE IF EXISTS cancels;
CREATE TABLE cancels (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index        INTEGER UNSIGNED,
    -- tx_hash         TEXT,
    tx_hash_id      INTEGER UNSIGNED, -- id of record in transactions
    block_index     INTEGER UNSIGNED,
    -- source          TEXT,
    source_id       INTEGER UNSIGNED, -- id of record in addresses
    -- offer_hash      TEXT,
    offer_hash_id   INTEGER UNSIGNED, -- id of record in transactions
    status          TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index   ON cancels (block_index);
CREATE INDEX source_id     ON cancels (source_id);
CREATE INDEX offer_hash_id ON cancels (offer_hash_id);