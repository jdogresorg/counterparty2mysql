DROP TABLE IF EXISTS fairminters;
CREATE TABLE fairminters (
    -- tx_hash                  TEXT,
    tx_hash_id                  INTEGER UNSIGNED,
    tx_index                    INTEGER UNSIGNED,
    block_index                 INTEGER UNSIGNED,
    -- source                   TEXT,
    source_id                   INTEGER UNSIGNED, -- id of record in index_addresses table
    -- asset                    TEXT,
    asset_id                    INTEGER UNSIGNED, -- id of record in assets table
    -- asset_parent             TEXT,
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