DROP TABLE IF EXISTS issuances;
CREATE TABLE issuances (
    tx_index    INTEGER UNSIGNED,
    -- tx_hash  TEXT,
    tx_hash_id  INTEGER UNSIGNED, -- id of record in index_transactions
    block_index INTEGER UNSIGNED,
    -- asset    TEXT,
    asset_id    INTEGER UNSIGNED, -- id of record in assets table
    quantity    INTEGER UNSIGNED,
    divisible   BOOL,
    -- source   TEXT,
    source_id   INTEGER UNSIGNED, -- id of record in index_addresses
    -- issuer   TEXT,
    issuer_id   INTEGER UNSIGNED, -- id of record in index_addresses
    transfer    BOOL,
    callable    BOOL,
    call_date   INTEGER UNSIGNED,
    call_price  REAL,
    description TEXT,
    fee_paid    INTEGER UNSIGNED,
    locked      BOOL,
    status      TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index       ON issuances (block_index);
CREATE INDEX source_id         ON issuances (source_id);
CREATE INDEX issuer_id         ON issuances (issuer_id);
CREATE INDEX asset_id          ON issuances (asset_id);
CREATE INDEX tx_hash_id        ON issuances (tx_hash_id);