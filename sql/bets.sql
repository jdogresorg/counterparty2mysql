DROP TABLE IF EXISTS bets;
CREATE TABLE bets (
    row_index              INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index               INTEGER UNSIGNED,
    -- tx_hash                TEXT,    
    tx_hash_id             INTEGER UNSIGNED, -- id of record in transactions
    block_index            INTEGER UNSIGNED,
    -- source                 TEXT,
    source_id              INTEGER UNSIGNED, -- id of record in addresses
    -- feed_address           TEXT,
    feed_address_id        INTEGER UNSIGNED, -- id of record in addresses
    bet_type               INTEGER UNSIGNED,
    deadline               INTEGER UNSIGNED,
    wager_quantity         INTEGER,
    wager_remaining        INTEGER,
    counterwager_quantity  INTEGER,
    counterwager_remaining INTEGER,
    target_value           REAL,
    leverage               INTEGER UNSIGNED,
    expiration             INTEGER UNSIGNED,
    expire_index           INTEGER UNSIGNED,
    fee_fraction_int       INTEGER UNSIGNED,
    status                 TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index     ON bets (block_index);
CREATE INDEX source_id       ON bets (source_id);
CREATE INDEX feed_address_id ON bets (feed_address_id);
CREATE INDEX tx_hash_id      ON bets (tx_hash_id);
