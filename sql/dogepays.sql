DROP TABLE IF EXISTS dogepays;
CREATE TABLE dogepays (
    tx_index       INTEGER UNSIGNED,
    -- tx_hash     TEXT,
    tx_hash_id     INTEGER UNSIGNED, -- id of record in index_transactions
    block_index    INTEGER UNSIGNED,
    -- source      TEXT,
    source_id      INTEGER UNSIGNED, -- id of record in index_addresses
    -- destination TEXT,
    destination_id INTEGER UNSIGNED, -- id of record in index_addresses
    doge_amount     BIGINT,
    order_match_id TEXT,
    status         TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index        ON dogepays (tx_index);
CREATE        INDEX block_index     ON dogepays (block_index);
CREATE        INDEX source_id       ON dogepays (source_id);
CREATE        INDEX destination_id  ON dogepays (destination_id);
CREATE        INDEX tx_hash_id      ON dogepays (tx_hash_id);

