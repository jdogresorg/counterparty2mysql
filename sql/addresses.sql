DROP TABLE IF EXISTS addresses;
CREATE TABLE addresses (
    id             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    address_id     VARCHAR(40) NOT NULL, -- id from index_addresses table
    options        INTEGER UNSIGNED,     -- address options
    block_index    INTEGER UNSIGNED      -- block that option was specified
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX address_id ON addresses (address_id);