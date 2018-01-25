DROP TABLE IF EXISTS order_expirations;
CREATE TABLE order_expirations (
    order_index   INTEGER UNSIGNED,
    -- order_hash TEXT,
    order_hash_id INTEGER UNSIGNED, -- id of record in index_transactions
    -- source     TEXT,
    source_id     INTEGER UNSIGNED, -- id of record in index_addresses
    block_index   INTEGER UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX block_index   ON order_expirations (block_index);
CREATE INDEX source_id     ON order_expirations (source_id);
CREATE INDEX order_hash_id ON order_expirations (order_hash_id);