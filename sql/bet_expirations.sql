DROP TABLE IF EXISTS bet_expirations;
CREATE TABLE bet_expirations (
    bet_index   INTEGER UNSIGNED,
    -- bet_hash TEXT,
    bet_hash_id INTEGER UNSIGNED, -- id of record in index_transactions
    -- source   TEXT,
    source_id   INTEGER UNSIGNED, -- id of record in index_addresses
    block_index INTEGER UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE UNIQUE INDEX bet_index   ON bet_expirations (bet_index);
CREATE        INDEX block_index ON bet_expirations (block_index);
CREATE        INDEX bet_hash_id ON bet_expirations (bet_hash_id);
CREATE        INDEX source_id   ON bet_expirations (source_id);