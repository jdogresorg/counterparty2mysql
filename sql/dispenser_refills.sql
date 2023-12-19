DROP TABLE IF EXISTS dispenser_refills;
CREATE TABLE dispenser_refills (
    tx_index               INTEGER UNSIGNED,
    -- tx_hash             TEXT,
    tx_hash_id             INTEGER UNSIGNED, -- id of record in index_transactions
    block_index            INTEGER UNSIGNED,
    -- source              TEXT,
    source_id              INTEGER UNSIGNED, -- id of record in index_addresses
    -- destination         TEXT,
    destination_id         INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset               TEXT,
    asset_id               INTEGER UNSIGNED, -- id of record in assets table
    -- dispenser_tx_hash   TEXT,
    dispenser_tx_hash_id    INTEGER UNSIGNED, -- id of record in index_transactions
    dispense_quantity      BIGINT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index               ON dispenser_refills (tx_index);
CREATE        INDEX tx_hash_id             ON dispenser_refills (tx_hash_id);
CREATE        INDEX block_index            ON dispenser_refills (block_index);
CREATE        INDEX source_id              ON dispenser_refills (source_id);
CREATE        INDEX destination_id         ON dispenser_refills (destination_id);
CREATE        INDEX asset_id               ON dispenser_refills (asset_id);
CREATE        INDEX dispenser_tx_hash_id   ON dispenser_refills (dispenser_tx_hash_id);
