DROP TABLE IF EXISTS broadcasts;
CREATE TABLE broadcasts (
    tx_index         INTEGER UNSIGNED,
    -- tx_hash       TEXT,
    tx_hash_id       INTEGER UNSIGNED, -- id of record in index_transactions
    block_index      INTEGER,
    -- source        TEXT,
    source_id        INTEGER UNSIGNED, -- id of record in index_addresses
    timestamp        INTEGER UNSIGNED,
    value            REAL,
    fee_fraction_int BIGINT,
    text             TEXT,
    locked           BOOL,
    status           TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index    ON broadcasts (tx_index);
CREATE        INDEX block_index ON broadcasts (block_index);
CREATE        INDEX source_id   ON broadcasts (source_id);
CREATE        INDEX tx_hash_id  ON broadcasts (tx_hash_id);