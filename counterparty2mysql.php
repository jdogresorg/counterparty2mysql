#!/usr/bin/env php
<?php
/*********************************************************************
 * counterparty2mysql.php 
 * 
 * Script to handle parsing counterparty data into mysql database
 * 
 * Command line arguments :
 * --testnet  Load testnet data
 * --block=#  Load data for given block
 * --single   Load single block
 ********************************************************************/
require_once('includes/config.php');

// Parse in the command line args and set some flags based on them
$args    = getopt("", array("testnet::","block::","single::","verbose::"));
$testnet = (isset($args['testnet'])) ? true : false;
$single  = (isset($args['single'])) ? true : false;
$network = ($testnet) ? 'testnet' : 'mainnet';
$block   = (is_numeric($args['block'])) ? intval($args['block']) : false;

// Define some constants used for locking processes and logging errors
define("LOCKFILE", '/var/tmp/counterparty2mysql-' . $network . '.lock');
define("LASTFILE", '/var/tmp/counterparty2mysql-' . $network . '.last-block');
define("ERRORLOG", '/var/tmp/counterparty2mysql-' . $network . '.errors');

// Setup database and counterparty connection info
$db_host = ($testnet) ? TEST_DB_HOST : MAIN_DB_HOST;
$db_user = ($testnet) ? TEST_DB_USER : MAIN_DB_USER;
$db_pass = ($testnet) ? TEST_DB_PASS : MAIN_DB_PASS;
$db_data = ($testnet) ? TEST_DB_DATA : MAIN_DB_DATA;
$cp_host = ($testnet) ? TEST_CP_HOST : MAIN_CP_HOST;
$cp_user = ($testnet) ? TEST_CP_USER : MAIN_CP_USER;
$cp_pass = ($testnet) ? TEST_CP_PASS : MAIN_CP_PASS;

// Initialize the database and counterparty API connections
initDB($db_host, $db_user, $db_pass, $db_data, true);
initCP($cp_host, $cp_user, $cp_pass, true);

// Create a lock file, and bail if we detect an instance is already running
createLockFile();

// If no block given, load last block from state file, or use first block with CP tx
if(!$block){
    $last  = file_get_contents(LASTFILE);
    $first = ($testnet) ? 310000 : 278270;
    $block = (isset($last) && $last>=$first) ? (intval($last) + 1) : $first;
}

// Get the current block index from status info
$current = $counterparty->status['last_block']['block_index'];

// Define array of fields that contain assets, addresses, transactions, and contracts
$fields_asset       = array('asset', 'backward_asset', 'dividend_asset', 'forward_asset', 'get_asset', 'give_asset');
$fields_address     = array('address', 'bet_hash', 'destination', 'feed_address', 'issuer', 'source', 'tx0_address', 'tx1_address');
$fields_transaction = array('event', 'move_random_hash', 'offer_hash', 'order_hash', 'rps_hash', 'tx_hash', 'tx0_hash', 'tx0_move_random_hash', 'tx1_hash', 'tx1_move_random_hash');
$fields_contract    = array('contract_id');

// Loop through the blocks until we are current
while($block <= $current){
    $timer = new Profiler();
    print "processing block {$block}...";

    // create block record
    createBlock($block);

    // Define array hold asset/address/tranaction id mappings for this block
    // We want to reset these every block since we use the assets list querying address balances
    $assets       = array(); // array of asset id mappings
    $addresses    = array(); // array of address id mappings
    $transactions = array(); // array of transaction id mappings
    $contracts    = array(); // arrray of contract id mappings

    // Get list of messages (updates to counterparty tables)
    $messages = $counterparty->execute('get_messages', array('block_index' => $block));
    // Loop through messages and create assets, addresses, transactions and setup id mappings
    foreach($messages as $message){
        $msg = (object) $message;
        $obj = json_decode($msg->bindings);
        foreach($obj as $field => $value){
            // assets
            foreach($fields_asset as $name)
                if($field==$name && !isset($assets[$value]))
                    $assets[$value] = createAsset($value);
            // addresses
            foreach($fields_address as $name)
                if($field==$name && !isset($addresses[$value]))
                    $addresses[$value] = createAddress($value);
            // transactions
            foreach($fields_transaction as $name)
                if($field==$name && !isset($transactions[$value]))
                    $transactions[$value] = createTransaction($value);
            // contracts
            foreach($fields_contract as $name)
                if($field==$name && !isset($contracts[$value]))
                    $contracts[$value] = createContract($value);
            // Create record in tx_index (so we can map tx_index to table with data)
            if($field=='tx_index')
                createTxIndex($value, $msg->category);
        }
    }

    // Loop through addresses and update any asset balances
    // Doing this first ensures that address balances are correct immediately
    foreach($addresses as $address => $address_id)
        updateAddressBalance($address, array_keys($assets));

    // Loop through the messages and create/update the counterparty tables
    foreach($messages as $message){
        $msg      = (object) $message;
        $table    = $msg->category;
        $bindings = json_decode($msg->bindings);
        $command  = $msg->command;

        // Build out array of fields and values
        $fields = array();
        $values = array();
        foreach($bindings as $field => $value){
            // swap asset name for id
            foreach($fields_asset as $name)
                if($field==$name){
                    $field = $name . '_id';
                    $value = $assets[$value];
                }
            // swap address for id
            foreach($fields_address as $name)
                if($field==$name){
                    $field = $name . '_id';
                    $value = $addresses[$value];
                }
            // swap transaction for id
            foreach($fields_transaction as $name)
                if($field==$name){
                    $field = $name . '_id';
                    $value = $transactions[$value];
                }
            // swap contract for id
            foreach($fields_contract as $name)
                if($field==$name)
                    $value = $contracts[$value];
            // Encode some values to make safe for SQL queries  
            if($table=='broadcasts' && $field=='text')
                $value = $mysqli->real_escape_string($value);
            if($table=='issuances' && $field=='description')
                $value = $mysqli->real_escape_string($value);
            // Translate some field names where bindings field names and table field names differ
            if($table=='credits' && $field=='action')
                $field='calling_function';
            // EVM fields
            if($field=='gasprice')
                $field = 'gas_price';
            if($field=='startgas')
                $field = 'gas_start';
            if($field=='payload')
                $field = 'data';
            // Escape key/value field names to prevent sql errors 
            if($field=='key')
                $field = '`key`';            
            if($field=='value')
                $field = '`value`';            
            // Add final field and value values to arrays
            array_push($fields, $field);
            array_push($values, $value);
        }

        // Handle 'insert' commands
        if($command=='insert'){
            // Check if this record already exists
            $sql = "SELECT block_index FROM {$table} WHERE block_index IS NOT NULL ";
            foreach($fields as $index => $field)
                $sql .= " AND {$field}='{$values[$index]}'";
            // print $sql;
            $results = $mysqli->query($sql);
            if($results){
                // Only create the record if it does not already exist
                if($results->num_rows==0){
                    $sql = "INSERT INTO {$table} (" . implode(",", $fields)  . ") values ('" . implode("', '", $values) . "')";
                    $results = $mysqli->query($sql);
                    // print $sql;
                    if(!$results)
                        byeLog('Error while trying to create record in ' . $table);
                }
            } else {
                byeLog('Error while trying to check if record already exists in ' . $table . ' : ' . $sql);
            }
        }

        // Handle 'update' commands
        if($command=='update'){
            $sql   = "UPDATE {$table} SET";
            $where = "";
            foreach($fields as $index => $field){
                // Update bets and orders records using tx_hash
                if(in_array($table,array('orders','bets')) && $field=='tx_hash_id'){
                    $where .= " AND tx_hash_id='{$values[$index]}'";
                // Update *_matches tables using id field
                } else if(in_array($table,array('order_matches','bet_matches','rps_matches')) && 
                          in_array($field,array('order_match_id','bet_match_id','rps_match_id'))){
                    $where .= " AND id='{$values[$index]}'";
                // Update rps table using tx_hash or tx_index
                } else if($table=='rps' && in_array($field,array('tx_hash_id','tx_index'))){
                    $where .= " AND {$field}='{$values[$index]}'";
                // Update nonces table using address_id
                } else if($table=='nonces' && $field=='address_id'){
                    $where .= " AND {$field}='{$values[$index]}'";
                } else {
                    $sql .= " {$field}='{$values[$index]}',";
                }
            }
            // Only proceed if we have a valid where criteria
            if($where!=""){
                $sql = rtrim($sql,',');
                $sql .= " WHERE block_index IS NOT NULL" . $where;
            } else {
                byeLog('Error - no WHERE criteria found');
            }
            $results = $mysqli->query($sql);
            if(!$results)
                byeLog('Error while trying to update record in ' . $table);
        }

    }
    // Report time to process block
    $time = $timer->finish();
    print " Done [{$time}ms]\n";

    // Bail out if user only wants to process one block
    if($single){
        print "detected single block... bailing out\n";
        break;
    } else {
        // Save block# to state file (so we can resume from this block next run)
        file_put_contents(LASTFILE, $block);
    }

    // Increase block before next loop
    $block++;
}    

// Remove the lockfile now that we are done running
removeLockFile();

print "Total Execution time: " . $runtime->finish() ." seconds\n";


?>