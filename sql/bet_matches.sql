DROP TABLE IF EXISTS bet_matches;
CREATE TABLE bet_matches (
    id                 TEXT,
    tx0_index          INTEGER,
    -- tx0_hash        TEXT,
    tx0_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    -- tx0_address     TEXT,
    tx0_address_id     INTEGER UNSIGNED, -- id of record in index_addresses
    tx1_index          INTEGER,
    -- tx1_hash        TEXT,
    tx1_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    -- tx1_address     TEXT,
    tx1_address_id     INTEGER UNSIGNED, -- id of record in index_addresses
    tx0_bet_type       INTEGER UNSIGNED,
    tx1_bet_type       INTEGER UNSIGNED,
    -- feed_address    TEXT,
    feed_address_id    INTEGER UNSIGNED, -- id of record in index_addresses
    initial_value      INTEGER UNSIGNED,
    deadline           INTEGER UNSIGNED,
    target_value       REAL,
    leverage           INTEGER UNSIGNED,
    forward_quantity   INTEGER UNSIGNED,
    backward_quantity  INTEGER UNSIGNED,
    tx0_block_index    INTEGER UNSIGNED,
    tx1_block_index    INTEGER UNSIGNED,
    block_index        INTEGER UNSIGNED,
    tx0_expiration     INTEGER UNSIGNED,
    tx1_expiration     INTEGER UNSIGNED,
    match_expire_index INTEGER UNSIGNED,
    fee_fraction_int   BIGINT  UNSIGNED,
    status             TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index     ON bet_matches (block_index);
CREATE INDEX tx0_hash_id     ON bet_matches (tx0_hash_id);
CREATE INDEX tx1_hash_id     ON bet_matches (tx1_hash_id);
CREATE INDEX tx1_address_id  ON bet_matches (tx1_address_id);
CREATE INDEX tx0_address_id  ON bet_matches (tx0_address_id);
CREATE INDEX feed_address_id ON bet_matches (feed_address_id);




