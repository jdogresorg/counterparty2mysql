DROP TABLE IF EXISTS btcpays;
CREATE TABLE btcpays (
    tx_index       INTEGER UNSIGNED,
    -- tx_hash     TEXT,
    tx_hash_id     INTEGER UNSIGNED, -- id of record in index_transactions
    block_index    INTEGER UNSIGNED,
    -- source      TEXT,
    source_id      INTEGER UNSIGNED, -- id of record in index_addresses
    -- destination TEXT,
    destination_id INTEGER UNSIGNED, -- id of record in index_addresses
    btc_amount     INTEGER UNSIGNED,
    order_match_id TEXT,
    status         TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index     ON btcpays (block_index);
CREATE INDEX source_id       ON btcpays (source_id);
CREATE INDEX destination_id  ON btcpays (destination_id);
CREATE INDEX tx_hash_id      ON btcpays (tx_hash_id);