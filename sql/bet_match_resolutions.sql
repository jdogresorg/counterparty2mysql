DROP TABLE IF EXISTS bet_match_resolutions;
CREATE TABLE bet_match_resolutions (
    bet_match_id      VARCHAR(255),
    bet_match_type_id INTEGER UNSIGNED,
    block_index       INTEGER UNSIGNED,
    winner            TEXT,
    settled           TEXT,
    bull_credit       TEXT,
    bear_credit       TEXT,
    escrow_less_fee   TEXT,
    fee               BIGINT UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX block_index    ON bet_match_resolutions (block_index);
CREATE INDEX bet_match_id   ON bet_match_resolutions (bet_match_id);

