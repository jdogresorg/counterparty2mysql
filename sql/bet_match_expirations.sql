DROP TABLE IF EXISTS bet_match_expirations;
CREATE TABLE bet_match_expirations (
    row_index      INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bet_match_id   VARCHAR(255),
    -- tx0_address    TEXT,
    tx0_address_id INTEGER UNSIGNED, -- id of record in addresses
    -- tx1_address    TEXT,
    tx1_address_id INTEGER UNSIGNED, -- id of record in addresses
    block_index    INTEGER UNSIGNED
) ENGINE=MyISAM;

CREATE INDEX block_index    ON bet_match_expirations (block_index);
CREATE INDEX bet_match_id   ON bet_match_expirations (bet_match_id);
CREATE INDEX tx0_address_id ON bet_match_expirations (tx0_address_id);
CREATE INDEX tx1_address_id ON bet_match_expirations (tx1_address_id);




