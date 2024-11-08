DROP TABLE IF EXISTS transaction_count;
CREATE TABLE transaction_count (
    block_index                 INTEGER UNSIGNED,
    transaction_id              INTEGER UNSIGNED,
    count                       INTEGER UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX block_index     ON transaction_count (block_index);
CREATE INDEX transaction_id  ON transaction_count (transaction_id);