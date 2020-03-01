#!/usr/bin/env php
<?php
/*********************************************************************
 * find_missing_transactions.php 
 * 
 * Handles using the messages table to find any missing transactions
 * 
 * Command line arguments :
 * --testnet    Load data from testnet
 * --block=#    Load data for given block
 * --single     Load single block
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::", "block::", "single::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';
$block   = (is_numeric($args['block'])) ? intval($args['block']) : false;
$single  = (isset($args['single'])) ? true : false;  

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);

// If no block number is given, assume user wants to check all blocks and lookup first block in messages table
if(!$block){
    $results = $mysqli->query("SELECT block_index FROM messages ORDER BY message_index ASC LIMIT 1");
    if($results){
        $row = $results->fetch_assoc();
        $block = $row['block_index'];
    } else {
        bye('Error looking up first block index');
    }
}

// If not parsing a single block, get the highest block index we have parsed so far
if(!$single){
    $results = $mysqli->query("SELECT block_index FROM messages ORDER BY message_index DESC LIMIT 1");
    if($results){
        $row = $results->fetch_assoc();
        $current = $row['block_index'];
    } else {
        bye('Error looking up last message index');
    }
} else {
    $current = $block;
}

// Array to hold missing tx_index transactions
$missing = [];
while($block <= $current){
    $timer = new Profiler();
    print "processing block {$block}...";

    // Get all messages for this block
    $messages = [];
    $results = $mysqli->query("SELECT * FROM messages WHERE block_index='{$block}'");
    if($results && $results->num_rows>0){
        while($row = $results->fetch_assoc())
            array_push($messages, $row);
    } 

    // Loop through all messages and check that a record exists related this this message 
    foreach($messages as $msg){
        $msg   = (object) $msg;
        $table = $msg->category;
        $info  = json_decode($msg->bindings);
        $sql   = '';
        // Handle updates
        if($msg->command=='update'){
            // Match records using the tx_index (transaction hash)
            if(isset($info->tx_index)){
                $sql   = "SELECT tx_index FROM {$table} WHERE tx_index='{$info->tx_index}'";
            // Match using the tx_hash
            } else if(in_array($table, array('orders','bets','rps'))){
                $tx_hash_id = createTransaction($info->tx_hash);
                $sql = "SELECT block_index FROM {$table} WHERE tx_hash_id='{$tx_hash_id}'";
            // Match using the tx hashes from the match_id
            } else if(in_array($table, array('order_matches','bet_matches','rps_matches'))){
                if($table=='order_matches') $data = $info->order_match_id;
                if($table=='bet_matches')   $data = $info->bet_match_id;
                if($table=='rps_matches')   $data = $info->rps_match_id;
                $arr = explode('_', $data);
                $tx0_hash_id = createTransaction($arr[0]);
                $tx1_hash_id = createTransaction($arr[1]);
                $sql = "SELECT block_index FROM {$table} WHERE tx0_hash_id='{$tx0_hash_id}' AND tx1_hash_id='{$tx1_hash_id}'";
            // Match using the order_match_id
            } else if(in_array($table, array('order_match_expirations','bet_match_expirations'))){
                $match_id = ($table=='order_match_expirations') ? $info->order_match_id : $info->bet_match_id;
                $field_id = ($table=='order_match_expirations') ? 'order_match_id' : 'bet_match_id';
                $sql = "SELECT block_index FROM {$table} WHERE {$field_id}='{$match_id}'";
            } else if($table=='dispensers'){
                $asset_id    = getAssetDatabaseId($info->asset);
                $address_id = createAddress($info->source);
                $sql = "SELECT block_index FROM {$table} WHERE asset_id='{$asset_id}' AND source_id='{$address_id}'";
            }
        }
        // Handle inserts
        if($msg->command=='insert'){
            if(isset($info->tx_index)){
                $sql   = "SELECT tx_index FROM {$table} WHERE tx_index='{$info->tx_index}'";
            } else if(in_array($table,array('credits','debits'))){
                $event_id = createTransaction($info->event);
                $sql = "SELECT block_index FROM {$table} WHERE event_id='{$event_id}'";
            } else if($table=='order_expirations'){
                $sql = "SELECT block_index FROM {$table} WHERE order_index='{$info->order_index}'";
            } else if($table=='bet_expirations'){
                $sql = "SELECT block_index FROM {$table} WHERE bet_index='{$info->bet_index}'";
            } else if($table=='rps_expirations'){
                $sql = "SELECT block_index FROM {$table} WHERE rps_index='{$info->rps_index}'";
            } else if($table=='bet_match_resolutions'){
                $sql = "SELECT block_index FROM {$table} WHERE bet_match_id='{$info->bet_match_id}'";
            } else if($table=='order_match_expirations'){
                $sql = "SELECT block_index FROM {$table} WHERE order_match_id='{$info->order_match_id}'";
            } else if($table=='bet_match_expirations'){
                $sql = "SELECT block_index FROM {$table} WHERE bet_match_id='{$info->bet_match_id}'";
            } else if($table=='rps_match_expirations'){
                $sql = "SELECT block_index FROM {$table} WHERE rps_match_id='{$info->rps_match_id}'";
            } else if(in_array($table, array('order_matches','bet_matches','rps_matches'))){
                $sql = "SELECT block_index FROM {$table} WHERE tx0_index='{$info->tx0_index}' AND tx1_index='{$info->tx1_index}'";
            } else if($table=='replace'){
                $address_id = createAddress($info->address);
                $sql = "SELECT block_index FROM addresses WHERE address_id='{$address_id}'";
            }
        }
        // Ignore reorgs 
        if($msg->command=='reorg')
            continue;
        $results = $mysqli->query($sql);
        if($results){
            if($results->num_rows==0)
                bye("Found missing data message_index={$msg->message_index} table={$table}");
        } else {
            bye("Error while trying to lookup message_index {$msg->message_index} in table {$table}\n");
        }
    }

    // Report time to process block
    $time = $timer->finish();
    print " Done [{$time}ms]\n";

    // Bail out of user only wants to parse a single block
    if($single){
        print "detected single block... bailing out\n";
        break;
    }

    // Increase block before next loop
    $block++;

}