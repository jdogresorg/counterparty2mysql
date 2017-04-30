DROP TABLE IF EXISTS bet_match_resolutions;
CREATE TABLE bet_match_resolutions (
    row_index         INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bet_match_id      VARCHAR(255),
    bet_match_type_id INTEGER UNSIGNED,
    block_index       INTEGER UNSIGNED,
    winner            TEXT,
    settled           BOOL,
    bull_credit       INTEGER UNSIGNED,
    bear_credit       INTEGER UNSIGNED,
    escrow_less_fee   INTEGER UNSIGNED,
    fee               INTEGER UNSIGNED
) ENGINE=MyISAM;

CREATE INDEX block_index    ON bet_match_resolutions (block_index);
CREATE INDEX bet_match_id   ON bet_match_resolutions (bet_match_id);
