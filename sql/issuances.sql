DROP TABLE IF EXISTS issuances;
CREATE TABLE issuances (
    tx_index           INTEGER UNSIGNED,
    -- tx_hash         TEXT,
    tx_hash_id         INTEGER UNSIGNED, -- id of record in index_transactions
    block_index        INTEGER UNSIGNED,
    msg_index          INTEGER UNSIGNED default 0,
    -- asset           TEXT,
    asset_id           INTEGER UNSIGNED, -- id of record in assets table
    quantity           BIGINT UNSIGNED,
    divisible          BOOL,
    -- source          TEXT,
    source_id          INTEGER UNSIGNED, -- id of record in index_addresses
    -- issuer          TEXT,
    issuer_id          INTEGER UNSIGNED, -- id of record in index_addresses
    transfer           BOOL,
    callable           BOOL,
    call_date          INTEGER UNSIGNED,
    call_price         REAL,
    description        VARCHAR(10000),   -- store up to 10k characters
    fee_paid           BIGINT,
    locked             BOOL,
    reset              BOOL,
    status             TEXT,
    description_locked VARCHAR(1) DEFAULT 0,
    fair_minting       VARCHAR(1), 
    asset_events       TEXT,
    mime_type          VARCHAR(250) DEFAULT 'text/plain'

) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE        INDEX tx_index     ON issuances (tx_index);
CREATE        INDEX block_index  ON issuances (block_index);
CREATE        INDEX source_id    ON issuances (source_id);
CREATE        INDEX issuer_id    ON issuances (issuer_id);
CREATE        INDEX asset_id     ON issuances (asset_id);
CREATE        INDEX tx_hash_id   ON issuances (tx_hash_id);
CREATE        INDEX fair_minting ON issuances (fair_minting);