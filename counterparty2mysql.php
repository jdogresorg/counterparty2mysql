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
require_once(__DIR__ . '/includes/config.php');

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
                    $assets[$value] = createAsset($value, $block);
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
        }
        // Create record in tx_index (so we can map tx_index to tx_hash and table with data)
        if(isset($obj->tx_index) && isset($transactions[$obj->tx_hash]))
            createTxIndex($obj->tx_index, $msg->category, $transactions[$obj->tx_hash]);

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
            $ignore = false;
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
            // Unset certain fields with no value set (fixes mysql complaints)
            if($table=='issuances'){
                if(in_array($field, array('locked','transfer','divisible','callable')) && $value=='')
                    $ignore = true;
            }
            // Force locked to numeric value
            if($table=='broadcasts'){
                if($field=='locked')
                    $value = intval($value);
            }
            // Rock / Paper / Sciscors
            if($table=='rps'){
                // Force move_random_hash_id to numeric value
                if($field=='move_random_hash_id' && !isset($value))
                    $value = intval($value);
                // Ignore the 'calling_function'
                if($field=='calling_function')
                    $ignore = true;
            }
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
            // Handle ignoring certain items in the bindings that cause issues
            if(in_array($field,array('asset_longname')) || $ignore)
                continue;
            // Add final field and value values to arrays
            array_push($fields, $field);
            array_push($values, $value);
        }

        // Change command to 'replace'
        if($msg->category=='replace')
            $command = 'replace';

        // Handle creating/updating records in the 'addresses' table
        if($command=='replace'){
            // Extract data to usable variable name
            foreach($fields as $ndx => $field)
                $$field = $values[$ndx];
            // Check if this record already exists
            $sql = "SELECT * FROM addresses WHERE address_id='{$address_id}'";
            $results = $mysqli->query($sql);
            if($results){
                // Only create the record if it does not already exist
                if($results->num_rows==0){
                    $sql2 = "INSERT INTO addresses (" . implode(",", $fields)  . ") values ('" . implode("', '", $values) . "')";
                } else {
                    $sql2 = "UPDATE addresses SET options='{$options}', block_index='{$block_index}' WHERE address_id='{$address_id}'";
                }
                $results2 = $mysqli->query($sql2);
                if(!$results2)
                    byeLog('Error while trying to create or record in addresses table : ' . $sql2);
            } else {
                byeLog('Error while trying to check if record already exists in addresses table : ' . $sql);
            }
        }

        // Handle 'insert' commands
        if($command=='insert'){
            // Check if this record already exists
            $sql = "SELECT * FROM {$table} WHERE";
            foreach($fields as $index => $field)
                $sql .= " {$field}='{$values[$index]}' AND";
            $sql = rtrim($sql, " AND");
            // print $sql;
            $results = $mysqli->query($sql);
            if($results){
                // Only create the record if it does not already exist
                if($results->num_rows==0){
                    $sql = "INSERT INTO {$table} (" . implode(",", $fields)  . ") values ('" . implode("', '", $values) . "')";
                    $results = $mysqli->query($sql);
                    if(!$results)
                        byeLog('Error while trying to create record in ' . $table . ' : ' . $sql);
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
                    $where .= " tx_hash_id='{$values[$index]}'";
                // Update *_matches tables using id field
                } else if(in_array($table,array('order_matches','bet_matches','rps_matches')) && 
                          in_array($field,array('order_match_id','bet_match_id','rps_match_id'))){
                    $where .= " id='{$values[$index]}'";
                // Update rps table using tx_hash or tx_index
                } else if($table=='rps' && in_array($field,array('tx_hash_id','tx_index'))){
                    $where .= " {$field}='{$values[$index]}'";
                // Update nonces table using address_id
                } else if($table=='nonces' && $field=='address_id'){
                    $where .= " {$field}='{$values[$index]}'";
                } else {
                    $sql .= " {$field}='{$values[$index]}',";
                }
            }
            // Only proceed if we have a valid where criteria
            if($where!=""){
                $sql = rtrim($sql,',') . " WHERE " .  $where;
            } else {
                byeLog('Error - no WHERE criteria found');
            }
            $results = $mysqli->query($sql);
            if(!$results)
                byeLog('Error while trying to update record in ' . $table . ' : ' . $sql);
        }

    }

    // Loop through assets and update XCP price 
    foreach($assets as $asset =>$id)
        updateAssetPrice($asset);

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