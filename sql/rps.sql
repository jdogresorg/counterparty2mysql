DROP TABLE IF EXISTS rps;
CREATE TABLE rps (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tx_index            INTEGER UNIQUE,
    -- tx_hash             TEXT,
    tx_hash_id          INTEGER UNSIGNED, -- id of record in transactions
    block_index         INTEGER UNSIGNED,
    -- source              TEXT,
    source_id           INTEGER UNSIGNED, -- id of record in addresses
    possible_moves      INTEGER UNSIGNED,
    wager               INTEGER UNSIGNED,
    -- move_random_hash    TEXT,
    move_random_hash_id INTEGER UNSIGNED, -- id of record in transactions
    expiration          INTEGER UNSIGNED,
    expire_index        INTEGER UNSIGNED,
    status              TEXT
) ENGINE=MyISAM;

CREATE INDEX block_index         ON rps (block_index);
CREATE INDEX tx_hash_id          ON rps (tx_hash_id);
CREATE INDEX source_id           ON rps (source_id);
CREATE INDEX move_random_hash_id ON rps (move_random_hash_id);
