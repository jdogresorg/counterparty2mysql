DROP TABLE IF EXISTS storage;
CREATE TABLE storage (
    contract_id INTEGER UNSIGNED, -- id of record in index_contracts
    `key`       BLOB,
    `value`     BLOB
) ENGINE=MyISAM;

CREATE INDEX contract_id ON storage (contract_id);
