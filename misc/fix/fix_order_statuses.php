#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_order_statuses.php
 * 
 * Script to loop through all open orders and validate order status
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

// Lookup all open orders
$sql = "SELECT
            o.tx_index,
            t.hash as tx_hash,
            o.status
        FROM
            orders o,
            index_transactions t
        WHERE
            o.tx_hash_id = t.id AND
            o.status = 'open' 
        ORDER BY o.tx_index DESC";
// print $sql;
$results = $mysqli->query($sql);
if($results && $results->num_rows){
    $cnt    = 0;
    $errors = 0;
    $fixed  = 0;
    while($row = $results->fetch_assoc()){
        $cnt++;
        $hash     = $row['tx_hash'];
        $tx_index = $row['tx_index'];
        print "[{$cnt} / {$errors} / ${fixed}] checking current status of tx {$tx_index} - {$hash} - ";
        $result = $counterparty->execute('get_orders', array('filters' => array('field' => 'tx_hash', 'op' => '==', 'value' => $hash)));
        foreach($result as $data){
            if($row['status']!=$data['status']){
                $errors++;
                print "Fixing ({$row['status']}->{$data['status']})\n";
                $sql = "UPDATE orders SET status='{$data['status']}' WHERE tx_index={$row['tx_index']}";
                $results2 = $mysqli->query($sql);
                if($results2){
                    $fixed++;
                } else {
                    bye('Error while trying to update order status');
                }
            } else {
                print "OK\n";
            }
        }
    }
} else {
    bye('Error while trying to lookup list of open orders');
}

print "Checked {$cnt} orders, encountered {$errors} errors, and fixed {$fixed} of those errors\n";