#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_dispenser_statuses.php
 * 
 * Script to loop through all open dispensers and validate open/closed state
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../../includes/config.php');

// Define some constants used for locking processes and logging errors
define("LOCKFILE", '/var/tmp/counterparty2mysql-' . $runtype . '.lock');
define("LASTFILE", '/var/tmp/counterparty2mysql-' . $runtype . '.last-block');
define("ERRORLOG", '/var/tmp/counterparty2mysql-' . $runtype . '.errors');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Lookup all dispensers
$sql = "SELECT 
            d.tx_index,
            t.hash as tx_hash,
            d.origin_id,
            d.status
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
        $hash      = $row['tx_hash'];
        $tx_index  = $row['tx_index'];
        print "[{$cnt} / {$errors} / ${fixed}] checking current origin of {$tx_index}...\n";
        $result = $counterparty->execute('get_dispensers', array('filters' => array('field' => 'tx_hash', 'op' => '==', 'value' => $hash)));
        foreach($result as $data){
            $origin    = $data['origin'];
            $origin_id = createAddress($origin);
            if($row['origin_id']!=$origin_id){
                $errors++;
                print "[{$cnt} / {$errors} / ${fixed}] Fixing dispenser {$tx_index} - origin={$origin}\n";
                $sql = "UPDATE dispensers SET origin_id={$origin_id} WHERE tx_index={$tx_index}";
                // print $sql;
                $results2 = $mysqli->query($sql);
                if($results2){
                    $fixed++;
                } else {
                    bye('Error while trying to update dispenser origin');
                }
            }
        }
    }
} else {
    bye('Error while trying to lookup list of open dispensers');
}

print "Checked {$cnt} dispensers, encountered {$errors} errors, and fixed {$fixed} of those errors\n";

