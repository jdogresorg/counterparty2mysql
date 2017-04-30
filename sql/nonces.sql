DROP TABLE IF EXISTS nonces;
CREATE TABLE nonces (
    row_index  INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- address TEXT,
    address_id INTEGER UNSIGNED, -- id of record in addresses
    nonce      INTEGER UNSIGNED
) ENGINE=MyISAM;

CREATE INDEX address_id on nonces (address_id);