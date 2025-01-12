#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_dispenser_prices.php
 * 
 * Script to loop through all  dispensers and validate satoshirate
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Lookup all dispensers
$sql = "SELECT
            d.tx_index,
            t.hash as tx_hash,
            d.satoshirate
        FROM
            dispensers d,
            index_transactions t
        WHERE
            d.tx_hash_id = t.id
        ORDER BY d.tx_index";
// print $sql;
$results = $mysqli->query($sql);
if($results && $results->num_rows){
    $cnt    = 0;
    $errors = 0;
    $fixed  = 0;
    while($row = $results->fetch_assoc()){
        $cnt++;
        $hash   = $row['tx_hash'];
        print "[{$cnt} / {$errors} / ${fixed}] checking current price of {$hash}...\n";
        $result = $counterparty->execute('get_dispensers', array('filters' => array('field' => 'tx_hash', 'op' => '==', 'value' => $hash)));
        foreach($result as $data){
            if($row['satoshirate']!=$data['satoshirate']){
                $errors++;
                print "[{$cnt} / {$errors} / ${fixed}] Fixing dispenser {$hash} (satoshirate ({$row['satoshirate']})->({$data['satoshirate']}))\n";
                $sql = "UPDATE dispensers SET satoshirate={$data['satoshirate']} WHERE tx_index={$row['tx_index']}";
                $results2 = $mysqli->query($sql);
                if($results2){
                    $fixed++;
                } else {
                    bye('Error while trying to update dispenser status');
                }
            }
        }
    }

} else {
    bye('Error while trying to lookup list of open dispensers');
}

print "Checked {$cnt} dispensers, encountered {$errors} errors, and fixed {$fixed} of those errors\n";