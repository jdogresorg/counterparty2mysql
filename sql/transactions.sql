DROP TABLE IF EXISTS transactions;
CREATE TABLE transactions (
    tx_index       INTEGER UNSIGNED,
    -- tx_hash     TEXT,
    tx_hash_id     INTEGER UNSIGNED, -- id of record in index_transactions
    block_index    INTEGER UNSIGNED,
    -- block_hash  TEXT,
    block_hash_id  INTEGER UNSIGNED, -- id of record in index_transactions
    block_time     INTEGER UNSIGNED,
    -- source      TEXT,
    source_id      INTEGER UNSIGNED, -- id of record in index_addresses
    -- destination TEXT,
    destination_id INTEGER UNSIGNED, -- id of record in index_addresses
    btc_amount     VARCHAR(250),     -- BTC amount sent
    fee            VARCHAR(250),     -- BTC Fee paid (miners fee)
    data           MEDIUMTEXT,
    supported      TINYINT(1),
    utxos_info     TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index       ON transactions (tx_index);
CREATE UNIQUE INDEX tx_hash_id     ON transactions (tx_hash_id);
CREATE        INDEX block_index    ON transactions (block_index);
CREATE        INDEX block_hash_id  ON transactions (block_hash_id);
CREATE        INDEX source_id      ON transactions (source_id);
CREATE        INDEX destination_id ON transactions (destination_id);