DROP TABLE IF EXISTS pool_withdrawals;
CREATE TABLE pool_withdrawals (
    tx_index           INTEGER UNSIGNED,
    -- tx_hash         TEXT,
    tx_hash_id         INTEGER UNSIGNED, -- id of record in index_transactions
    block_index        INTEGER UNSIGNED,
    -- source          TEXT,
    source_id          INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset_a         TEXT,
    asset_a_id         INTEGER UNSIGNED, -- id of record in assets table
    -- asset_b         TEXT,
    asset_b_id         INTEGER UNSIGNED, -- id of record in assets table
    quantity_destroyed BIGINT,
    quantity_a         BIGINT,
    quantity_b         BIGINT,
    status             TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index    ON pool_withdrawals (tx_index);
CREATE        INDEX tx_hash_id  ON pool_withdrawals (tx_hash_id);
CREATE        INDEX block_index ON pool_withdrawals (block_index);
CREATE        INDEX source_id   ON pool_withdrawals (source_id);
CREATE        INDEX asset_a_id  ON pool_withdrawals (asset_a_id);
CREATE        INDEX asset_b_id  ON pool_withdrawals (asset_b_id);
