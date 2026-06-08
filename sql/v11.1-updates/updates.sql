-- v11.1.0 updates - Automated Market Maker (AMM) pool tables
-- Apply to existing databases:
--   cat sql/v11.1-updates/updates.sql | mysql Counterparty
--   cat sql/v11.1-updates/updates.sql | mysql Counterparty_Testnet

-- pools table
CREATE TABLE IF NOT EXISTS pools (
    tx_index    INTEGER UNSIGNED,
    tx_hash_id  INTEGER UNSIGNED, -- id of record in index_transactions
    block_index INTEGER UNSIGNED,
    source_id   INTEGER UNSIGNED, -- id of record in index_addresses
    asset_a_id  INTEGER UNSIGNED, -- id of record in assets table
    asset_b_id  INTEGER UNSIGNED, -- id of record in assets table
    reserve_a   BIGINT,
    reserve_b   BIGINT,
    lp_asset_id INTEGER UNSIGNED  -- id of record in assets table
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE UNIQUE INDEX tx_index    ON pools (tx_index);
CREATE        INDEX tx_hash_id  ON pools (tx_hash_id);
CREATE        INDEX block_index ON pools (block_index);
CREATE        INDEX source_id   ON pools (source_id);
CREATE        INDEX asset_a_id  ON pools (asset_a_id);
CREATE        INDEX asset_b_id  ON pools (asset_b_id);
CREATE        INDEX lp_asset_id ON pools (lp_asset_id);

-- pool_deposits table
CREATE TABLE IF NOT EXISTS pool_deposits (
    tx_index        INTEGER UNSIGNED,
    tx_hash_id      INTEGER UNSIGNED, -- id of record in index_transactions
    block_index     INTEGER UNSIGNED,
    source_id       INTEGER UNSIGNED, -- id of record in index_addresses
    asset_a_id      INTEGER UNSIGNED, -- id of record in assets table
    asset_b_id      INTEGER UNSIGNED, -- id of record in assets table
    quantity_a      BIGINT,
    quantity_b      BIGINT,
    quantity_minted BIGINT,
    status          TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE UNIQUE INDEX tx_index    ON pool_deposits (tx_index);
CREATE        INDEX tx_hash_id  ON pool_deposits (tx_hash_id);
CREATE        INDEX block_index ON pool_deposits (block_index);
CREATE        INDEX source_id   ON pool_deposits (source_id);
CREATE        INDEX asset_a_id  ON pool_deposits (asset_a_id);
CREATE        INDEX asset_b_id  ON pool_deposits (asset_b_id);

-- pool_withdrawals table
CREATE TABLE IF NOT EXISTS pool_withdrawals (
    tx_index           INTEGER UNSIGNED,
    tx_hash_id         INTEGER UNSIGNED, -- id of record in index_transactions
    block_index        INTEGER UNSIGNED,
    source_id          INTEGER UNSIGNED, -- id of record in index_addresses
    asset_a_id         INTEGER UNSIGNED, -- id of record in assets table
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

-- pool_matches table
CREATE TABLE IF NOT EXISTS pool_matches (
    tx_index          INTEGER UNSIGNED,
    tx_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    block_index       INTEGER UNSIGNED,
    source_id         INTEGER UNSIGNED, -- id of record in index_addresses
    asset_a_id        INTEGER UNSIGNED, -- id of record in assets table
    asset_b_id        INTEGER UNSIGNED, -- id of record in assets table
    forward_asset_id  INTEGER UNSIGNED, -- id of record in assets table
    forward_quantity  BIGINT,
    backward_asset_id INTEGER UNSIGNED, -- id of record in assets table
    backward_quantity BIGINT,
    fee_quantity      BIGINT,
    fee_bps           INTEGER UNSIGNED,
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
