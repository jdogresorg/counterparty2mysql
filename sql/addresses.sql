DROP TABLE IF EXISTS addresses;
CREATE TABLE addresses (
  id      INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  address VARCHAR(120) NOT NULL -- we do varchar because we need to support older multisig format 
                                -- 2-of-3 multisig 2_address1_address2_adddress3_3
) ENGINE=MyISAM;

CREATE INDEX address on addresses (address(10));
