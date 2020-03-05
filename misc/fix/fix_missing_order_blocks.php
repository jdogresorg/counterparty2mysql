#!/usr/bin/env php
<?php
/*********************************************************************
 * find_missing_order_blocks.php 
 * 
 * Handles finding any orders with block_index=0 and fixing
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);

//
$results = $mysqli->query("SELECT tx_index FROM orders WHERE block_index=0 ORDER BY tx_index ASC");
if($results){
    while($row = $results->fetch_assoc()){
        $row = (object) $row;
        print "Processing tx {$row->tx_index}...\n";
        $results2 = $mysqli->query("SELECT * FROM messages WHERE bindings LIKE '%" . '"tx_index": ' . $row->tx_index .  "}'");
        if($results2 && $results2->num_rows){
            $data = $results2->fetch_assoc();
            $info = json_decode($data['bindings']);
            $results3 = $mysqli->query("UPDATE orders SET block_index='{$info->block_index}' WHERE tx_index='{$row->tx_index}'");
            if(!$results3)
                bye('Error updating order');

        } else {
            bye('Error looking up message data');
        }
    }
} else {
    bye('Error looking up orders missing block index data');
}
