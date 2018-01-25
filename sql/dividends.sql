DROP TABLE IF EXISTS dividends;
CREATE TABLE dividends (
    tx_index          INTEGER UNSIGNED,
    -- tx_hash        TEXT,
    tx_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    block_index       INTEGER UNSIGNED,
    -- source         TEXT,
    source_id         INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset          TEXT,
    asset_id          INTEGER UNSIGNED, -- id of record in assets table
    -- dividend_asset TEXT,
    dividend_asset_id INTEGER UNSIGNED, -- id of record in assets table
    quantity_per_unit BIGINT,
    fee_paid          BIGINT,
    status            TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index          ON dividends (tx_index);
CREATE        INDEX block_index       ON dividends (block_index);
CREATE        INDEX source_id         ON dividends (source_id);
CREATE        INDEX asset_id          ON dividends (asset_id);
CREATE        INDEX dividend_asset_id ON dividends (asset_id);
CREATE        INDEX tx_hash_id        ON dividends (tx_hash_id);