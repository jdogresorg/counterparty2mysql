DROP TABLE IF EXISTS dispenses;
CREATE TABLE dispenses (
    id                    INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index              INTEGER UNSIGNED,           -- TX index
    dispense_index        INTEGER UNSIGNED,          
    -- tx_hash            TEXT,
    tx_hash_id            INTEGER UNSIGNED, -- id of record in index_transactions
    block_index           INTEGER UNSIGNED, -- Block which the dispense took place
    -- source             TEXT,
    source_id             INTEGER UNSIGNED, -- id of record in index_addresses
    -- destination        TEXT,
    destination_id        INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset              TEXT,
    asset_id              INTEGER UNSIGNED, -- id of record in assets table
    dispense_quantity     BIGINT  UNSIGNED, 
    -- dispenser_tx_hash TEXT
    dispenser_tx_hash_id  INTEGER UNSIGNED  -- id of record in index_transactions
) ENGINE=MyISAM;

CREATE        INDEX block_index          ON dispenses (block_index);
CREATE        INDEX tx_hash_id           ON dispenses (tx_hash_id);
CREATE        INDEX dispenser_tx_hash_id ON dispenses (dispenser_tx_hash_id);
CREATE        INDEX source_id            ON dispenses (source_id);
CREATE        INDEX destination_id       ON dispenses (destination_id);
CREATE        INDEX asset_id             ON dispenses (asset_id);
