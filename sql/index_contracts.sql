DROP TABLE IF EXISTS index_contracts;
CREATE TABLE index_contracts (
  id       INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  contract CHAR(40)
) ENGINE=MyISAM;

CREATE INDEX contract on index_contracts (contract(10));
