DROP TABLE IF EXISTS debits;
CREATE TABLE debits (
    row_index        INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    block_index      INTEGER UNSIGNED,
    -- address          TEXT,
    address_id       INTEGER UNSIGNED, -- id of record in addresses
    -- asset            TEXT,
    asset_id         INTEGER UNSIGNED, -- id of record in assets table
    quantity         INTEGER UNSIGNED,
    action           TEXT,
    -- event            TEXT,
    event_id         INTEGER UNSIGNED -- id of record in transactions
) ENGINE=MyISAM;

CREATE INDEX block_index   ON debits (block_index);
CREATE INDEX address_id    ON debits (address_id);
CREATE INDEX asset_id      ON debits (asset_id);
CREATE INDEX event_id      ON debits (event_id);

