DROP TABLE IF EXISTS index_tx;
CREATE TABLE index_tx (
  tx_index   INTEGER UNSIGNED NOT NULL,
  tx_hash_id INTEGER UNSIGNED NOT NULL,
  type_id    INTEGER UNSIGNED NOT NULL -- id of record in index_tx_types table
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX tx_index   on index_tx (tx_index);
CREATE UNIQUE INDEX tx_hash_id on index_tx (tx_hash_id);
