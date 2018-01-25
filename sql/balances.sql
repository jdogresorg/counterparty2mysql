DROP TABLE IF EXISTS balances;
CREATE TABLE balances (
    id         INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    address_id INTEGER UNSIGNED, -- id of record in index_addresses
    asset_id   INTEGER UNSIGNED, -- id of record in assets
    quantity   BIGINT  UNSIGNED   
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX address_id ON balances (address_id);
CREATE INDEX asset_id   ON balances (asset_id);