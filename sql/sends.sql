DROP TABLE IF EXISTS sends;
CREATE TABLE sends (
    tx_index                INTEGER UNSIGNED,
    -- tx_hash              TEXT,
    tx_hash_id              INTEGER UNSIGNED, -- id of record in index_transactions
    block_index             INTEGER UNSIGNED,
    -- source               TEXT,
    source_id               INTEGER UNSIGNED, -- id of record in index_addresses
    -- source_address          TEXT, 
    source_utxo_id          INTEGER UNSIGNED, -- id of record in index_transactions
    source_utxo_output      INTEGER UNSIGNED,
    -- destination          TEXT,
    destination_id          INTEGER UNSIGNED, -- id of record in index_addresses
    -- destination_address     TEXT, 
    destination_utxo_id     INTEGER UNSIGNED, -- id of record in index_transactions
    destination_utxo_output INTEGER UNSIGNED, 
    -- asset                TEXT,
    asset_id                INTEGER UNSIGNED, -- id of record in assets table
    quantity                BIGINT,
    memo                    LONGTEXT,
    fee_paid                INTEGER UNSIGNED,    
    send_type               TEXT,    
    status                  TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE        INDEX tx_index               ON sends (tx_index);
CREATE        INDEX block_index            ON sends (block_index);
CREATE        INDEX tx_hash_id             ON sends (tx_hash_id);
CREATE        INDEX source_id              ON sends (source_id);
CREATE        INDEX destination_id         ON sends (destination_id);
CREATE        INDEX destination_utxo_id    ON sends (destination_utxo_id);
CREATE        INDEX source_utxo_id         ON sends (source_utxo_id);
CREATE        INDEX asset_id               ON sends (asset_id);
