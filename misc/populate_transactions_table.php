#!/usr/bin/env php
<?php
/*********************************************************************
 * populate_transactions_table.php 
 * 
 * Handle populating the transactions table with data from the counterparty API
 *
 * Command line arguments :
 * --testnet    Load data from testnet
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Determine the start and current blocks
$start   = ($testnet) ? 310000 : 278270;
$block   = $start;
$current = $counterparty->status['last_block']['block_index'];

// Loop through the blocks until we are current
while($block <= $current){
    $timer = new Profiler();
    print "processing block {$block}...";

    // Get list of transactions from the transactions table (used to track BTC paid and miners fee)
    $transactions = $counterparty->execute('get_transactions', array('filters' => array("field" => "block_index", "op" => "==", "value" => $block)));
    foreach($transactions as $transaction)
        createTransactionHistory($transaction);

    // Report time to process block
    $time = $timer->finish();
    print " Done [{$time}ms]\n";

    $block++;
}

print "Total Execution time: " . $runtime->finish() ." seconds\n";

