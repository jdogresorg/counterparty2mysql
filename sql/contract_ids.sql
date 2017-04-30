DROP TABLE IF EXISTS contract_ids;
CREATE TABLE contract_ids (
  id          INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  contract_id CHAR(40)
) ENGINE=MyISAM;

CREATE INDEX contract_ids on contract_ids (contract_id(10));
