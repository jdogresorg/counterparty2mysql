DROP TABLE IF EXISTS dispensers;
CREATE TABLE dispensers (
    tx_index          INTEGER UNSIGNED,
    -- tx_hash        TEXT,
    tx_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    block_index       INTEGER UNSIGNED,
    -- source         TEXT,
    source_id         INTEGER UNSIGNED, -- id of record in index_addresses
    -- asset          TEXT,
    asset_id          INTEGER UNSIGNED, -- id of record in assets table
    give_quantity     BIGINT, -- Tokens to vend per dispense
    escrow_quantity   BIGINT, -- Tokens to escrow in dispenser
    satoshirate       BIGINT, -- Bitcoin satoshis required per dispense
    give_remaining    BIGINT,
    oracle_address_id INTEGER UNSIGNED, -- id of record in index_addresses table
    status            TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index          ON dispensers (tx_index);
CREATE        INDEX tx_hash_id        ON dispensers (tx_hash_id);
CREATE        INDEX block_index       ON dispensers (block_index);
CREATE        INDEX source_id         ON dispensers (source_id);
CREATE        INDEX asset_id          ON dispensers (asset_id);
CREATE        INDEX oracle_address_id ON dispensers (oracle_address_id);

