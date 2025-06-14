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
    hard_cap                    VARCHAR(250),
    burn_payment                VARCHAR(250),
    max_mint_per_tx             VARCHAR(250),
    premint_quantity            VARCHAR(250),
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
    paid_quantity               VARCHAR(250),
    max_mint_per_address        VARCHAR(250),
    mime_type                   VARCHAR(250) DEFAULT 'text/plain'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX tx_hash_id      ON fairminters (tx_hash_id);
CREATE INDEX block_index     ON fairminters (block_index);
CREATE INDEX source_id       ON fairminters (source_id);
CREATE INDEX asset_id        ON fairminters (asset_id);
CREATE INDEX asset_parent_id ON fairminters (asset_parent_id);
CREATE INDEX asset_longname  ON fairminters (asset_longname);
CREATE INDEX status          ON fairminters (status);
