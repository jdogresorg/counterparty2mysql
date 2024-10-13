-- v10.0.0 updates
-- messages table
ALTER TABLE messages add event      VARCHAR(120);
ALTER TABLE messages add tx_hash    VARCHAR(120);
ALTER TABLE messages add event_hash VARCHAR(120);
CREATE INDEX event on messages (event);

-- transactions 
ALTER TABLE transactions ADD utxos_info TEXT;

-- credits table
ALTER TABLE credits add tx_index     INTEGER UNSIGNED;
ALTER TABLE credits add utxo         TEXT;
ALTER TABLE credits add utxo_address TEXT;
CREATE INDEX tx_index ON credits (tx_index);

-- debits table
ALTER TABLE debits add tx_index     INTEGER UNSIGNED;
ALTER TABLE debits add utxo         TEXT;
ALTER TABLE debits add utxo_address TEXT;
CREATE INDEX tx_index ON debits (tx_index);

-- dispensers table
ALTER TABLE dispensers ADD dispense_count INTEGER DEFAULT 0;
ALTER TABLE dispensers ADD last_status_tx_source_ID INTEGER UNSIGNED;
ALTER TABLE dispensers ADD close_block_index INTEGER UNSIGNED;
CREATE INDEX last_status_tx_source_id ON dispensers (last_status_tx_source_id);

-- dispenses table
ALTER TABLE dispenses ADD btc_amount INTEGER UNSIGNED DEFAULT 0;

-- issuances table
ALTER TABLE issuances ADD description_locked VARCHAR(1);
ALTER TABLE issuances ADD fair_minting BOOL DEFAULT 0;
ALTER TABLE issuances ADD asset_events TEXT;

-- address_events table
DROP TABLE IF EXISTS address_events;
CREATE TABLE address_events (
    address_id  INTEGER UNSIGNED,  -- id from index_addresses table
    event_index INTEGER UNSIGNED   
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE UNIQUE INDEX address_id ON addresses (address_id);

-- fairminters table
DROP TABLE IF EXISTS fairminters;
CREATE TABLE fairminters (
    tx_hash_id                  INTEGER UNSIGNED,
    tx_index                    INTEGER UNSIGNED,
    block_index                 INTEGER UNSIGNED,
    source_id                   INTEGER UNSIGNED, -- id of record in index_addresses table
    asset_id                    INTEGER UNSIGNED, -- id of record in assets table
    asset_parent_id             INTEGER UNSIGNED, -- id of record in assets table
    asset_longname              VARCHAR(255),
    description                 VARCHAR(10000),
    price                       INTEGER UNSIGNED,
    quantity_by_price           INTEGER UNSIGNED,
    hard_cap                    INTEGER UNSIGNED,
    burn_payment                BOOL,
    max_mint_per_tx             INTEGER UNSIGNED,
    premint_quantity            INTEGER UNSIGNED,
    start_block                 INTEGER UNSIGNED,
    end_block                   INTEGER UNSIGNED,
    minted_asset_commission_int INTEGER UNSIGNED,
    soft_cap                    INTEGER UNSIGNED,
    soft_cap_deadline_block     INTEGER UNSIGNED,
    lock_description            BOOL,
    lock_quantity               BOOL,
    divisible                   BOOL,
    pre_minted                  BOOL DEFAULT 0,
    status                      VARCHAR(250),
    earned_quantity             INTEGER UNSIGNED,
    commission                  INTEGER UNSIGNED,
    paid_quantity               INTEGER UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE INDEX tx_hash_id      ON fairminters (tx_hash_id);
CREATE INDEX block_index     ON fairminters (block_index);
CREATE INDEX source_id       ON fairminters (source_id);
CREATE INDEX asset_id        ON fairminters (asset_id);
CREATE INDEX asset_parent_id ON fairminters (asset_parent_id);
CREATE INDEX asset_longname  ON fairminters (asset_longname);
CREATE INDEX status          ON fairminters (status);


-- fairmints table
DROP TABLE IF EXISTS fairmints;
CREATE TABLE fairmints (
    tx_hash_id                  INTEGER UNSIGNED,
    tx_index                    INTEGER UNSIGNED,
    block_index                 INTEGER UNSIGNED,
    source_id                   INTEGER UNSIGNED, -- id of record in index_addresses table
    fairminter_tx_hash_id       INTEGER UNSIGNED,
    asset_id                    INTEGER UNSIGNED, -- id of record in assets table
    earn_quantity               INTEGER UNSIGNED,
    paid_quantity               INTEGER UNSIGNED,
    commission                  INTEGER UNSIGNED,
    status                      VARCHAR(250)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE INDEX tx_hash_id            ON fairmints (tx_hash_id);
CREATE INDEX block_index           ON fairmints (block_index);
CREATE INDEX source_id             ON fairmints (source_id);
CREATE INDEX fairminter_tx_hash_id ON fairmints (fairminter_tx_hash_id);
CREATE INDEX asset_id              ON fairmints (asset_id);
CREATE INDEX status                ON fairmints (status);

