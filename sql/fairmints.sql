DROP TABLE IF EXISTS fairmints;
CREATE TABLE fairmints (
    -- tx_hash                  TEXT,
    tx_hash_id                  INTEGER UNSIGNED,
    tx_index                    INTEGER UNSIGNED,
    block_index                 INTEGER UNSIGNED,
    -- source                   TEXT,
    source_id                   INTEGER UNSIGNED, -- id of record in index_addresses table
    -- fairminter_tx_hash       TEXT,
    fairminter_tx_hash_id       INTEGER UNSIGNED,
    -- asset                    TEXT,
    asset_id                    INTEGER UNSIGNED, -- id of record in assets table
    earn_quantity               VARCHAR(250),
    paid_quantity               VARCHAR(250),
    commission                  VARCHAR(250),
    status                      VARCHAR(250)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX tx_hash_id            ON fairmints (tx_hash_id);
CREATE INDEX block_index           ON fairmints (block_index);
CREATE INDEX source_id             ON fairmints (source_id);
CREATE INDEX fairminter_tx_hash_id ON fairmints (fairminter_tx_hash_id);
CREATE INDEX asset_id              ON fairmints (asset_id);
CREATE INDEX status                ON fairmints (status);