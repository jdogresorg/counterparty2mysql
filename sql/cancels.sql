DROP TABLE IF EXISTS cancels;
CREATE TABLE cancels (
    tx_index      INTEGER UNSIGNED,
    -- tx_hash    TEXT,
    tx_hash_id    INTEGER UNSIGNED, -- id of record in index_transactions
    block_index   INTEGER UNSIGNED,
    -- source     TEXT,
    source_id     INTEGER UNSIGNED, -- id of record in index_addresses
    -- offer_hash TEXT,
    offer_hash_id INTEGER UNSIGNED, -- id of record in index_transactions
    status        TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index      ON cancels (tx_index);
CREATE        INDEX block_index   ON cancels (block_index);
CREATE        INDEX source_id     ON cancels (source_id);
CREATE        INDEX offer_hash_id ON cancels (offer_hash_id);