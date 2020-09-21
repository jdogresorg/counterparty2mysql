DROP TABLE IF EXISTS markets;
CREATE TABLE markets (
    id             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    asset1_id      INTEGER UNSIGNED,                   -- id of record in assets table
    asset2_id      INTEGER UNSIGNED,                   -- id of record in assets table
    price1_bid     VARCHAR(120) NOT NULL default 0, -- asset1 - highest price buyers are paying
    price1_ask     VARCHAR(120) NOT NULL default 0, -- asset1 - highest price sellers are accepting
    price1_last    VARCHAR(120) NOT NULL default 0, -- asset1 - last trade price
    price1_high    VARCHAR(120) NOT NULL default 0, -- asset1 - 24-hour high price
    price1_low     VARCHAR(120) NOT NULL default 0, -- asset1 - 24-hour low price
    price1_24hr    VARCHAR(120) NOT NULL default 0, -- asset1 - Price exactly 24 hours ago
    price2_bid     VARCHAR(120) NOT NULL default 0, -- asset2 - highest price buyers are paying 
    price2_ask     VARCHAR(120) NOT NULL default 0, -- asset2 - highest price sellers are accepting 
    price2_last    VARCHAR(120) NOT NULL default 0, -- asset2 - last trade price 
    price2_high    VARCHAR(120) NOT NULL default 0, -- asset2 - 24-hour high price 
    price2_low     VARCHAR(120) NOT NULL default 0, -- asset2 - 24-hour low price 
    price2_24hr    VARCHAR(120) NOT NULL default 0, -- asset2 - Price exactly 24 hours ago
    price1_change  VARCHAR(120) NOT NULL default 0, -- 24-hour percentage change (asset1)
    price2_change  VARCHAR(120) NOT NULL default 0, -- 24-hour percentage change (asset2)
    asset1_volume  VARCHAR(120) NOT NULL default 0, -- 24-hour volume for asset1
    asset2_volume  VARCHAR(120) NOT NULL default 0, -- 24-hour volume for asset2
    last_updated   DATETIME
) ENGINE=MyISAM;

CREATE INDEX asset1_id on markets (asset1_id);
CREATE INDEX asset2_id on markets (asset2_id);


-- ALTER TABLE markets MODIFY price1_bid     VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price1_ask     VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price1_last    VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price1_high    VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price1_low     VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price1_24hr    VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price2_bid     VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price2_ask     VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price2_last    VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price2_high    VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price2_low     VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price2_24hr    VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price1_change  VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY price2_change  VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY asset1_volume  VARCHAR(120) NOT NULL default 0;
-- ALTER TABLE markets MODIFY asset2_volume  VARCHAR(120) NOT NULL default 0;
