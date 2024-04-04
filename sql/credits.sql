DROP TABLE IF EXISTS credits;
CREATE TABLE credits (
    tx_index         INTEGER UNSIGNED,
    block_index      INTEGER UNSIGNED,
    -- address       TEXT,
    address_id       INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset         TEXT,
    asset_id         INTEGER UNSIGNED, -- id of record in assets table
    quantity         BIGINT,
    calling_function TEXT,
    -- event         TEXT,
    event_id         INTEGER UNSIGNED  -- id of record in index_transactions
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX tx_index    ON credits (tx_index);
CREATE INDEX block_index ON credits (block_index);
CREATE INDEX address_id  ON credits (address_id);
CREATE INDEX asset_id    ON credits (asset_id);
CREATE INDEX event_id    ON credits (event_id);