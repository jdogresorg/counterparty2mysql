DROP TABLE IF EXISTS address_events;
CREATE TABLE address_events (
    address_id  INTEGER UNSIGNED,  -- id from index_addresses table
    event_index INTEGER UNSIGNED   
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX address_id ON addresses (address_id);
