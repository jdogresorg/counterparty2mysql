DROP TABLE IF EXISTS pools;
CREATE TABLE pools (
    tx_index    INTEGER UNSIGNED,
    -- tx_hash  TEXT,
    tx_hash_id  INTEGER UNSIGNED, -- id of record in index_transactions
    block_index INTEGER UNSIGNED,
    -- source   TEXT,
    source_id   INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset_a  TEXT,
    asset_a_id  INTEGER UNSIGNED, -- id of record in assets table
    -- asset_b  TEXT,
    asset_b_id  INTEGER UNSIGNED, -- id of record in assets table
    reserve_a   BIGINT,
    reserve_b   BIGINT,
    -- lp_asset TEXT,
    lp_asset_id INTEGER UNSIGNED  -- id of record in assets table
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index    ON pools (tx_index);
CREATE        INDEX tx_hash_id  ON pools (tx_hash_id);
CREATE        INDEX block_index ON pools (block_index);
CREATE        INDEX source_id   ON pools (source_id);
CREATE        INDEX asset_a_id  ON pools (asset_a_id);
CREATE        INDEX asset_b_id  ON pools (asset_b_id);
CREATE        INDEX lp_asset_id ON pools (lp_asset_id);
