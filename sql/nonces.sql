DROP TABLE IF EXISTS nonces;
CREATE TABLE nonces (
    -- address TEXT,
    address_id INTEGER UNSIGNED, -- id of record in index_addresses
    nonce      INTEGER UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX address_id on nonces (address_id);