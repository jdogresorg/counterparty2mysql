DROP TABLE IF EXISTS markets;
CREATE TABLE markets (
    id             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    asset1_id      INTEGER UNSIGNED,                   -- id of record in assets table
    asset2_id      INTEGER UNSIGNED,                   -- id of record in assets table
    price1_bid     BIGINT UNSIGNED NOT NULL default 0, -- asset1 - highest price buyers are paying
    price1_ask     BIGINT UNSIGNED NOT NULL default 0, -- asset1 - highest price sellers are accepting
    price1_last    BIGINT UNSIGNED NOT NULL default 0, -- asset1 - last trade price
    price1_high    BIGINT UNSIGNED NOT NULL default 0, -- asset1 - 24-hour high price
    price1_low     BIGINT UNSIGNED NOT NULL default 0, -- asset1 - 24-hour low price
    price1_24hr    BIGINT UNSIGNED NOT NULL default 0, -- asset1 - Price exactly 24 hours ago
    price2_bid     BIGINT UNSIGNED NOT NULL default 0, -- asset2 - highest price buyers are paying 
    price2_ask     BIGINT UNSIGNED NOT NULL default 0, -- asset2 - highest price sellers are accepting 
    price2_last    BIGINT UNSIGNED NOT NULL default 0, -- asset2 - last trade price 
    price2_high    BIGINT UNSIGNED NOT NULL default 0, -- asset2 - 24-hour high price 
    price2_low     BIGINT UNSIGNED NOT NULL default 0, -- asset2 - 24-hour low price 
    price2_24hr    BIGINT UNSIGNED NOT NULL default 0, -- asset2 - Price exactly 24 hours ago
    price1_change  BIGINT          NOT NULL default 0, -- 24-hour percentage change (asset1)
    price2_change  BIGINT          NOT NULL default 0, -- 24-hour percentage change (asset2)
    asset1_volume  BIGINT UNSIGNED NOT NULL default 0, -- 24-hour volume for asset1
    asset2_volume  BIGINT UNSIGNED NOT NULL default 0, -- 24-hour volume for asset2
    last_updated   DATETIME
) ENGINE=MyISAM;

CREATE INDEX asset1_id on markets (asset1_id);
CREATE INDEX asset2_id on markets (asset2_id);
