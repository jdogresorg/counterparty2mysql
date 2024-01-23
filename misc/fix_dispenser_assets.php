#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_dispenser_assets.php 
 * 
 * Handles finding any dispensers with asset_id=0 and fixes
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);

//
$results = $mysqli->query("SELECT block_index, tx_index FROM dispensers where asset_id=0 order by tx_index");
if($results){
    while($row = $results->fetch_assoc()){
        $row = (object) $row;
        print "Processing tx {$row->tx_index}...\n";
        $results2 = $mysqli->query("SELECT * FROM messages WHERE category='dispensers' AND command='insert' AND bindings LIKE '%" . '"tx_index": ' . $row->tx_index .  "}'");
        if($results2 && $results2->num_rows){
            $data = $results2->fetch_assoc();
            $info = json_decode($data['bindings']);
            $results3 = $mysqli->query("SELECT id FROM assets where asset='{$info->asset}'");
            if($results3 && $results3->num_rows){
                $data = $results3->fetch_assoc();
                $asset_id = $data['id'];
            } else {
                bye('Error looking up asset');
            }
            $sql = "UPDATE dispensers SET asset_id='{$asset_id}' WHERE tx_index='{$row->tx_index}'";
            $results4 = $mysqli->query($sql);
            if(!$results4)
                bye('Error updating dispenser');

        } else {
            bye('Error looking up message data');
        }
    }
} else {
    bye('Error looking up orders missing block index data');
}
