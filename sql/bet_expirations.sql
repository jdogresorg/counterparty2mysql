DROP TABLE IF EXISTS bet_expirations;
CREATE TABLE bet_expirations (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bet_index   INTEGER UNSIGNED,
    -- bet_hash    TEXT,
    bet_hash_id INTEGER UNSIGNED, -- id of record in transactions
    -- source      TEXT,
    source_id   INTEGER UNSIGNED, -- id of record in addresses
    block_index INTEGER UNSIGNED
) ENGINE=MyISAM;

CREATE INDEX block_index ON bet_expirations (block_index);
CREATE INDEX bet_hash_id ON bet_expirations (bet_hash_id);
CREATE INDEX source_id   ON bet_expirations (source_id);