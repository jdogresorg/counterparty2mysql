DROP TABLE IF EXISTS pool_matches;
CREATE TABLE pool_matches (
    tx_index          INTEGER UNSIGNED,
    -- tx_hash        TEXT,
    tx_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    block_index       INTEGER UNSIGNED,
    -- source         TEXT,
    source_id         INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset_a        TEXT,
    asset_a_id        INTEGER UNSIGNED, -- id of record in assets table
    -- asset_b        TEXT,
    asset_b_id        INTEGER UNSIGNED, -- id of record in assets table
    -- forward_asset  TEXT,
    forward_asset_id  INTEGER UNSIGNED, -- id of record in assets table
    forward_quantity  BIGINT,
    -- backward_asset TEXT,
    backward_asset_id INTEGER UNSIGNED, -- id of record in assets table
    backward_quantity BIGINT,
    fee_quantity      BIGINT,
    fee_bps           INTEGER UNSIGNED,
    -- order_tx_hash  TEXT,
    order_tx_hash_id  INTEGER UNSIGNED, -- id of record in index_transactions
    status            TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE        INDEX tx_index          ON pool_matches (tx_index);
CREATE        INDEX tx_hash_id        ON pool_matches (tx_hash_id);
CREATE        INDEX block_index       ON pool_matches (block_index);
CREATE        INDEX source_id         ON pool_matches (source_id);
CREATE        INDEX asset_a_id        ON pool_matches (asset_a_id);
CREATE        INDEX asset_b_id        ON pool_matches (asset_b_id);
CREATE        INDEX forward_asset_id  ON pool_matches (forward_asset_id);
CREATE        INDEX backward_asset_id ON pool_matches (backward_asset_id);
CREATE        INDEX order_tx_hash_id  ON pool_matches (order_tx_hash_id);
