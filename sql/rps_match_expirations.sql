DROP TABLE IF EXISTS rps_match_expirations;
CREATE TABLE rps_match_expirations (
    rps_match_id   TEXT,
    -- tx0_address TEXT,
    tx0_address_id INTEGER UNSIGNED, -- id of record in index_addresses
    -- tx1_address TEXT,
    tx1_address_id INTEGER UNSIGNED, -- id of record in index_addresses
    block_index    INTEGER UNSIGNED
) ENGINE=MyISAM;

CREATE INDEX block_index    ON rps_match_expirations (block_index);
CREATE INDEX tx0_address_id ON rps_match_expirations (tx0_address_id);
CREATE INDEX tx1_address_id ON rps_match_expirations (tx1_address_id);
