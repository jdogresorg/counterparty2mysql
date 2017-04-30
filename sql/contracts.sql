DROP TABLE IF EXISTS contracts;
CREATE TABLE contracts (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    contract_id INTEGER UNSIGNED, -- id of record in contract_ids
    tx_index    INTEGER UNSIGNED,
    -- tx_hash  TEXT,
    tx_hash_id  INTEGER UNSIGNED, -- id of record in transactions
    block_index INTEGER,
    -- source   TEXT,
    source_id   INTEGER UNSIGNED, -- id of record in addresses
    code        BLOB,
    nonce       INTEGER UNSIGNED
) ENGINE=MyISAM;

CREATE INDEX block_index ON contracts (block_index);
CREATE INDEX source_id   ON contracts (source_id);
CREATE INDEX tx_hash_id  ON contracts (tx_hash_id);
CREATE INDEX contract_id ON contracts (contract_id);