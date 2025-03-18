DROP TABLE IF EXISTS assets;
CREATE TABLE assets (
    id                 INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    asset_id           VARCHAR(40) NOT NULL, -- asset_id (encoded asset name)
    asset              VARCHAR(40) NOT NULL, -- asset name
    asset_longname     VARCHAR(255),         -- subasset name
    block_index        INTEGER UNSIGNED,     -- block that asset was created
    description        VARCHAR(10000),       -- store up to 10k characters
    description_locked TINYINT(1) default 0,
    divisible          TINYINT(1),
    owner_id           INTEGER UNSIGNED,     -- id of record in index_addresses
    issuer_id          INTEGER UNSIGNED,     -- id of record in index_addresses
    locked             TINYINT(1) default 0,
    supply             BIGINT  UNSIGNED,
    type               TINYINT(1),           -- asset type (1=Named, 2=Numeric, 3=Subasset, 4=Failed issuance)
    xcp_price          BIGINT  UNSIGNED,     -- last price of XCP matched order on DEX
    btc_price          BIGINT  UNSIGNED      -- last price of BTC matched order on DEX or Dispense
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO assets (asset_id, asset, divisible, locked) values (0,'BTC', 1, 1);
INSERT INTO assets (asset_id, asset, divisible, locked, xcp_price) values (1,'XCP', 1, 1, 100000000);

CREATE UNIQUE INDEX asset     ON assets (asset);
CREATE        INDEX issuer_id ON assets (issuer_id);
CREATE        INDEX owner_id  ON assets (owner_id);

-- ALTER TABLE assets ADD btc_price BIGINT UNSIGNED AFTER xcp_price;
-- ALTER TABLE assets MODIFY description VARCHAR(10000);