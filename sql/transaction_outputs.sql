DROP TABLE IF EXISTS transaction_outputs;
CREATE TABLE transaction_outputs (
    tx_index       INTEGER UNSIGNED,
    -- tx_hash     TEXT,
    tx_hash_id     INTEGER UNSIGNED, -- id of record in index_transactions
    block_index    INTEGER UNSIGNED,
    out_index      INTEGER UNSIGNED,
    -- destination TEXT,
    destination_id INTEGER UNSIGNED, -- id of record in index_addresses
    btc_amount     BIGINT           -- BTC amount sent
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index       ON transaction_outputs (tx_index);
CREATE UNIQUE INDEX tx_hash_id     ON transaction_outputs (tx_hash_id);
CREATE        INDEX block_index    ON transaction_outputs (block_index);
CREATE        INDEX destination_id ON transaction_outputs (destination_id);

