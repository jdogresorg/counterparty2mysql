DROP TABLE IF EXISTS blocks;
CREATE TABLE blocks (
    block_index            INTEGER UNSIGNED,
    block_time             INTEGER UNSIGNED,
    -- block_hash          TEXT,
    block_hash_id          INTEGER UNSIGNED,     -- id of record in index_transactions table
    -- previous_block_hash TEXT,
    previous_block_hash_id INTEGER UNSIGNED,     -- id of record in index_transactions table
    -- ledger_hash         TEXT,
    ledger_hash_id         INTEGER UNSIGNED,     -- id of record in index_transactions table
    -- txlist_hash         TEXT,
    txlist_hash_id         INTEGER UNSIGNED,     -- id of record in index_transactions table
    -- messages_hash       TEXT,
    messages_hash_id       INTEGER UNSIGNED,     -- id of record in index_transactions table
    difficulty             FLOAT
) ENGINE=MyISAM;

CREATE UNIQUE INDEX block_index   ON blocks (block_index);
CREATE        INDEX block_hash_id ON blocks (block_hash_id);

