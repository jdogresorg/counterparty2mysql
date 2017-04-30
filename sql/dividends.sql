DROP TABLE IF EXISTS dividends;
CREATE TABLE dividends (
    row_index         INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index          INTEGER UNSIGNED,
    -- tx_hash           TEXT,
    tx_hash_id        INTEGER UNSIGNED, -- id of record in transactions
    block_index       INTEGER UNSIGNED,
    -- source            TEXT,
    source_id         INTEGER UNSIGNED, -- id of record in addresses
    -- asset             TEXT,
    asset_id          INTEGER UNSIGNED, -- id of record in assets table
    -- dividend_asset    TEXT,
    dividend_asset_id INTEGER UNSIGNED, -- id of record in assets table
    quantity_per_unit INTEGER UNSIGNED,
    fee_paid          INTEGER UNSIGNED,
    status            TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index       ON dividends (block_index);
CREATE INDEX source_id         ON dividends (source_id);
CREATE INDEX asset_id          ON dividends (asset_id);
CREATE INDEX dividend_asset_id ON dividends (asset_id);
CREATE INDEX tx_hash_id        ON dividends (tx_hash_id);