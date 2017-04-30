DROP TABLE IF EXISTS order_matches;
CREATE TABLE order_matches (
    row_index           INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id                  TEXT,
    tx0_index           INTEGER UNSIGNED,
    -- tx0_hash            TEXT,
    tx0_hash_id         INTEGER UNSIGNED, -- id of record in transactions
    -- tx0_address         TEXT,
    tx0_address_id      INTEGER UNSIGNED, -- id of record in addresses
    tx1_index           INTEGER UNSIGNED,
    -- tx1_hash            TEXT,
    tx1_hash_id         INTEGER UNSIGNED, -- id of record in transactions
    -- tx1_address         TEXT,
    tx1_address_id      INTEGER UNSIGNED, -- id of record in addresses
    -- forward_asset       TEXT,
    forward_asset_id    INTEGER UNSIGNED, -- id of record in assets table
    forward_quantity    INTEGER UNSIGNED,
    -- backward_asset      TEXT,
    backward_asset_id   INTEGER UNSIGNED, -- id of record in assets table
    backward_quantity   INTEGER UNSIGNED,
    tx0_block_index     INTEGER UNSIGNED,
    tx1_block_index     INTEGER UNSIGNED,
    block_index         INTEGER UNSIGNED,
    tx0_expiration      INTEGER UNSIGNED,
    tx1_expiration      INTEGER UNSIGNED,
    match_expire_index  INTEGER UNSIGNED,
    fee_paid            INTEGER UNSIGNED,
    status              TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index       ON order_matches (block_index);
CREATE INDEX tx0_hash_id       ON order_matches (tx0_hash_id);
CREATE INDEX tx1_hash_id       ON order_matches (tx1_hash_id);
CREATE INDEX tx0_address_id    ON order_matches (tx0_address_id);
CREATE INDEX tx1_address_id    ON order_matches (tx1_address_id);
CREATE INDEX forward_asset_id  ON order_matches (forward_asset_id);
CREATE INDEX backward_asset_id ON order_matches (backward_asset_id);
