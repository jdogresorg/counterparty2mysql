DROP TABLE IF EXISTS storage;
CREATE TABLE storage (
    row_index   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    contract_id INTEGER UNSIGNED, -- id of record in contract_ids
    `key`       BLOB,
    `value`     BLOB
) ENGINE=MyISAM;

CREATE INDEX contract_id ON storage (contract_id);
