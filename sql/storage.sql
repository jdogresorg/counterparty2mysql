DROP TABLE IF EXISTS storage;
CREATE TABLE storage (
    contract_id INTEGER UNSIGNED, -- id of record in index_contracts
    `key`       BLOB,
    `value`     BLOB
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX contract_id ON storage (contract_id);
