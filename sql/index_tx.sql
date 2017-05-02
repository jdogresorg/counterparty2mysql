DROP TABLE IF EXISTS index_tx;
CREATE TABLE index_tx (
  tx_index INTEGER UNSIGNED NOT NULL,
  type_id  INTEGER UNSIGNED NOT NULL -- id of record in index_tx_types table
) ENGINE=MyISAM;

CREATE UNIQUE INDEX tx_index on index_tx (tx_index);