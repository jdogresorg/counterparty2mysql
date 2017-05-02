DROP TABLE IF EXISTS assets;
CREATE TABLE assets (
    id             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    asset_id       VARCHAR(40) NOT NULL, -- asset_id (encoded asset name)
    asset          VARCHAR(40) NOT NULL, -- asset name
    asset_longname VARCHAR(255),         -- subasset name
    description    VARCHAR(250),
    divisible      TINYINT(1),
    owner_id       INTEGER UNSIGNED,     -- id of record in index_addresses
    issuer_id      INTEGER UNSIGNED,     -- id of record in index_addresses
    locked         TINYINT(1),
    supply         BIGINT(20) UNSIGNED,
    type           TINYINT(1)            -- asset type (1=Named, 2=Numeric, 3=Subasset, 4=Failed issuance)
) ENGINE=MyISAM;

INSERT INTO assets (asset_id, asset, divisible, locked) values (0,'BTC', 1, 1);
INSERT INTO assets (asset_id, asset, divisible, locked) values (1,'XCP', 1, 1);

CREATE UNIQUE INDEX asset     ON assets (asset);
CREATE        INDEX issuer_id ON assets (issuer_id);
CREATE        INDEX owner_id  ON assets (owner_id);