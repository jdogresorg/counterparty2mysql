DROP TABLE IF EXISTS credits;
CREATE TABLE credits (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    block_index      INTEGER UNSIGNED,
    -- address          TEXT,
    address_id       INTEGER UNSIGNED, -- id of record in addresses
    -- asset            TEXT,
    asset_id         INTEGER UNSIGNED, -- id of record in assets table
    quantity         INTEGER UNSIGNED,
    calling_function TEXT,
    -- event            TEXT,
    event_id         INTEGER UNSIGNED -- id of record in transactions
) ENGINE=MyISAM;

CREATE INDEX block_index   ON credits (block_index);
CREATE INDEX address_id    ON credits (address_id);
CREATE INDEX asset_id      ON credits (asset_id);
CREATE INDEX event_id      ON credits (event_id);
