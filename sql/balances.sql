DROP TABLE IF EXISTS balances;
CREATE TABLE balances (
    -- address      TEXT,
    address_id      INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset        TEXT
    asset_id        INTEGER UNSIGNED, -- id of record in assets
    quantity        BIGINT  UNSIGNED,
    -- utxo         TEXT,
    utxo_id         INTEGER UNSIGNED,  -- id of record in index_transactions
    utxo_output     INTEGER UNSIGNED,  -- utxo output index
    -- utxo_address TEXT,
    utxo_address_id INTEGER UNSIGNED   -- id of record in index_addresses
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX address_id      ON balances (address_id);
CREATE INDEX asset_id        ON balances (asset_id);
CREATE INDEX utxo_id         ON balances (utxo_id);
CREATE INDEX utxo_address_id ON balances (utxo_address_id);