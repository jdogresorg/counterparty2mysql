DROP TABLE IF EXISTS bets;
CREATE TABLE bets (
    tx_index               INTEGER UNSIGNED,
    -- tx_hash             TEXT,    
    tx_hash_id             INTEGER UNSIGNED, -- id of record in index_transactions
    block_index            INTEGER UNSIGNED,
    -- source              TEXT,
    source_id              INTEGER UNSIGNED, -- id of record in index_addresses
    -- feed_address        TEXT,
    feed_address_id        INTEGER UNSIGNED, -- id of record in index_addresses
    bet_type               INTEGER UNSIGNED,
    deadline               INTEGER UNSIGNED,
    wager_quantity         BIGINT,
    wager_remaining        BIGINT,
    counterwager_quantity  BIGINT,
    counterwager_remaining BIGINT,
    target_value           REAL,
    leverage               INTEGER UNSIGNED,
    expiration             INTEGER UNSIGNED,
    expire_index           INTEGER UNSIGNED,
    fee_fraction_int       BIGINT,
    status                 TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index ON bets (tx_index);
CREATE        INDEX block_index     ON bets (block_index);
CREATE        INDEX source_id       ON bets (source_id);
CREATE        INDEX feed_address_id ON bets (feed_address_id);
CREATE        INDEX tx_hash_id      ON bets (tx_hash_id);
