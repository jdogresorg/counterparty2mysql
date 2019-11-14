DROP TABLE IF EXISTS dispenses;
CREATE TABLE dispenses (
    id                 INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    block_index        INTEGER UNSIGNED,           -- Block which the dispense took place
    dispense_tx_id     INT(25) UNSIGNED NOT NULL,  -- id of record in index_transactions
    dispenser_tx_id    INT(25) UNSIGNED NOT NULL   -- id of record in index_transactions
) ENGINE=MyISAM;

CREATE        INDEX block_index       ON dispenses (block_index);
CREATE        INDEX dispense_tx_id    ON dispenses (dispense_tx_id);
CREATE        INDEX dispenser_tx_id   ON dispenses (dispenser_tx_id);
