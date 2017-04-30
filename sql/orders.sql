DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    row_index              INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index               INTEGER UNSIGNED,
    -- tx_hash                TEXT,
    tx_hash_id             INTEGER UNSIGNED, -- id of record in transactions
    block_index            INTEGER UNSIGNED,
    -- source                 TEXT,
    source_id              INTEGER UNSIGNED, -- id of record in addresses
    -- give_asset             TEXT,
    give_asset_id          INTEGER UNSIGNED, -- id of record in assets table
    give_quantity          INTEGER UNSIGNED,
    give_remaining         INTEGER UNSIGNED,          
    -- get_asset              TEXT,
    get_asset_id           INTEGER UNSIGNED, -- id of record in assets table
    get_quantity           INTEGER,
    get_remaining          INTEGER,          -- handles negative integers
    expiration             INTEGER UNSIGNED,
    expire_index           INTEGER UNSIGNED,
    fee_required           INTEGER,
    fee_required_remaining INTEGER,          -- handles negative integers
    fee_provided           INTEGER,
    fee_provided_remaining INTEGER,
    status                 TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index   ON orders (block_index);
CREATE INDEX tx_hash_id    ON orders (tx_hash_id);
CREATE INDEX source_id     ON orders (source_id);
CREATE INDEX give_asset_id ON orders (give_asset_id);
CREATE INDEX get_asset_id  ON orders (get_asset_id);