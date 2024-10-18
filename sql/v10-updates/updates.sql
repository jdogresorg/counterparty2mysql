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

-- sends table
ALTER TABLE sends ADD fee_paid INTEGER UNSIGNED;

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
    -- tx_hash                  TEXT,
    tx_hash_id                  INTEGER UNSIGNED,
    tx_index                    INTEGER UNSIGNED,
    block_index                 INTEGER UNSIGNED,
    source_id                   INTEGER UNSIGNED,
    asset_id                    INTEGER UNSIGNED,
    asset_parent_id             INTEGER UNSIGNED,
    asset_longname              VARCHAR(255),
    description                 VARCHAR(10000),
    price                       INTEGER UNSIGNED,
    quantity_by_price           INTEGER UNSIGNED,
    hard_cap                    VARCHAR(250),
    burn_payment                VARCHAR(250),
    max_mint_per_tx             VARCHAR(250),
    premint_quantity            VARCHAR(250),,
    start_block                 INTEGER UNSIGNED,
    end_block                   INTEGER UNSIGNED,
    minted_asset_commission_int VARCHAR(250),
    soft_cap                    VARCHAR(250),
    soft_cap_deadline_block     INTEGER UNSIGNED,
    lock_description            VARCHAR(250),
    lock_quantity               VARCHAR(250),
    divisible                   VARCHAR(250),
    pre_minted                  VARCHAR(250),
    status                      VARCHAR(250),
    earned_quantity             VARCHAR(250),
    commission                  VARCHAR(250),
    paid_quantity               VARCHAR(250)
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
    source_id                   INTEGER UNSIGNED, 
    fairminter_tx_hash_id       INTEGER UNSIGNED,
    asset_id                    INTEGER UNSIGNED, 
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

-- transaction_count table
DROP TABLE IF EXISTS transaction_count;
CREATE TABLE transaction_count (
    block_index                 INTEGER UNSIGNED,
    transaction_id              INTEGER UNSIGNED,
    count                       INTEGER UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX block_index     ON transaction_count (block_index);
CREATE INDEX transaction_id  ON transaction_count (transaction_id);