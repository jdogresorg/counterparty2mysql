DROP TABLE IF EXISTS index_transactions;
CREATE TABLE index_transactions (
  id   INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  hash VARCHAR(129) NOT NULL
) ENGINE=MyISAM;

CREATE INDEX hash on index_transactions (hash(10));
