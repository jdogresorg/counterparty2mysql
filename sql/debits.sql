DROP TABLE IF EXISTS debits;
CREATE TABLE debits (
    block_index INTEGER UNSIGNED,
    -- address  TEXT,
    address_id  INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset    TEXT,
    asset_id    INTEGER UNSIGNED, -- id of record in assets table
    quantity    BIGINT  UNSIGNED,
    action      TEXT,
    -- event    TEXT,
    event_id    INTEGER UNSIGNED  -- id of record in index_transactions
) ENGINE=MyISAM;

CREATE INDEX block_index   ON debits (block_index);
CREATE INDEX address_id    ON debits (address_id);
CREATE INDEX asset_id      ON debits (asset_id);
CREATE INDEX event_id      ON debits (event_id);

