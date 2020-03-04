#!/usr/bin/env php
<?php
/*********************************************************************
 * find_missing_messages.php 
 * 
 * Handles finding any missing messages from the messages table
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

// Get highest message
$results = $mysqli->query("SELECT message_index FROM messages ORDER BY message_index DESC LIMIT 1");
if($results){
    $row = $results->fetch_assoc();
    $msg = $row['message_index'];
} else {
    bye('Error looking up last message index');
}

print "current message index = {$msg}\n";
$missing = [];
$cnt = 0;
for( $i = 0; $i <= $msg; $i++ ){
    if($cnt==10000){
        print "{$i} / {$msg}\n";
        $cnt = 0;
    }
    $cnt++;
    $results = $mysqli->query("SELECT message_index from messages WHERE message_index='{$i}'");
    if($results){
        if($results->num_rows==0){
            print "Found missing message {$i}\n";
            array_push($missing,$i);
        }
    } else {
        bye('Error looking up message_index');
    }
}

foreach($missing as $idx){
    print "Found missing message {$idx}\n";
}
