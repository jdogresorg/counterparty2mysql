DROP TABLE IF EXISTS sweeps;
CREATE TABLE sweeps (
    tx_index       INTEGER UNSIGNED,
    -- tx_hash     TEXT,
    tx_hash_id     INTEGER UNSIGNED, -- id of record in index_transactions
    block_index    INTEGER UNSIGNED,
    -- source      TEXT,
    source_id      INTEGER UNSIGNED, -- id of record in index_addresses
    -- destination TEXT,
    destination_id INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset       TEXT,
    flags          INTEGER UNSIGNED, -- id of record in assets table
    memo           BLOB,
    fee_paid       INTEGER UNSIGNED, -- id of record in assets table
    status         TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index       ON sweeps (tx_index);
CREATE        INDEX block_index    ON sweeps (block_index);
CREATE        INDEX source_id      ON sweeps (source_id);
CREATE        INDEX destination_id ON sweeps (destination_id);
CREATE        INDEX tx_hash_id     ON sweeps (tx_hash_id);
