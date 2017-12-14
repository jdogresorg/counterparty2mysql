DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    tx_index               INTEGER UNSIGNED,
    -- tx_hash             TEXT,
    tx_hash_id             INTEGER UNSIGNED, -- id of record in index_transactions
    block_index            INTEGER UNSIGNED,
    -- source              TEXT,
    source_id              INTEGER UNSIGNED, -- id of record in index_addresses
    -- give_asset          TEXT,
    give_asset_id          INTEGER UNSIGNED, -- id of record in assets table
    give_quantity          BIGINT,
    give_remaining         BIGINT,
    -- get_asset           TEXT,
    get_asset_id           INTEGER UNSIGNED, -- id of record in assets table
    get_quantity           BIGINT,
    get_remaining          BIGINT,           -- handles negative integers
    expiration             INTEGER UNSIGNED,
    expire_index           INTEGER UNSIGNED,
    fee_required           BIGINT,
    fee_required_remaining BIGINT,
    fee_provided           BIGINT,
    fee_provided_remaining BIGINT,
    status                 TEXT
) ENGINE=MyISAM;

CREATE UNIQUE INDEX tx_index      ON orders (tx_index);
CREATE        INDEX block_index   ON orders (block_index);
CREATE        INDEX tx_hash_id    ON orders (tx_hash_id);
CREATE        INDEX source_id     ON orders (source_id);
CREATE        INDEX give_asset_id ON orders (give_asset_id);
CREATE        INDEX get_asset_id  ON orders (get_asset_id);