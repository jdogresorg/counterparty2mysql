#!/usr/bin/env php
<?php
/*********************************************************************
 * dogeparty2mysql.php 
 * 
 * Script to handle parsing counterparty data into mysql database
 * 
 * Command line arguments :
 * --testnet    Load data from testnet
 * --regtest    Load data from regtest
 * --block=#    Load data for given block
 * --rollback=# Rollback data to a given block
 * --single     Load single block
 * --silent     Fail silently on insert errors
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

// Parse in the command line args and set some flags based on them
$args     = getopt("", array("testnet::", "regtest::", "block::", "rollback::", "single::","silent::", "verbose::"));
$testnet  = (isset($args['testnet'])) ? true : false;
$regtest  = (isset($args['regtest'])) ? true : false;
$single   = (isset($args['single'])) ? true : false;  
$silent   = (isset($args['silent'])) ? true : false; // Flag to indicate if we should silently fail on insert errors
$runtype  = ($regtest) ? 'regtest' : (($testnet) ? 'testnet' : 'mainnet');
$rollback = (is_numeric($args['rollback'])) ? intval($args['rollback']) : false;
$block    = (is_numeric($args['block'])) ? intval($args['block']) : false;

// Load config (only after runtype is defined)
require_once(__DIR__ . '/includes/config.php');

// Define some constants used for locking processes and logging errors
define("LOCKFILE", '/var/tmp/dogeparty2mysql-' . $runtype . '.lock');
define("LASTFILE", '/var/tmp/dogeparty2mysql-' . $runtype . '.last-block');
define("ERRORLOG", '/var/tmp/dogeparty2mysql-' . $runtype . '.errors');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(DP_HOST, DP_USER, DP_PASS, true);

// Create a lock file, and bail if we detect an instance is already running
createLockFile();

// Handle rollbacks
if($rollback){
    $block_index = $mysqli->real_escape_string($rollback);
    $tables = [
        'bets',
        'bet_expirations', 
        'bet_match_expirations',
        'bet_match_resolutions',
        'bet_matches',
        'blocks',
        'broadcasts',
        'dogepays',
        'burns',
        'cancels',
        'credits',
        'debits',
        'destructions',
        'dispensers',
        'dispenses',
        'dividends',
        'issuances',
        'index_tx',
        'messages',
        'orders',
        'order_expirations',
        'order_match_expirations',
        'order_matches',
        'rps',
        'rps_expirations',
        'rps_match_expirations',
        'rps_matches',
        'rpsresolves',
        'sends',
        'sweeps'
    ];
    foreach($tables as $table){
        $results = $mysqli->query("DELETE FROM {$table} WHERE block_index>{$block_index}");
        if(!$results)
            byeLog("Error while trying to rollback {$table} table to block {$block_index}");
    }
    byeLog("Rollback to block {$block_index} complete.");
}

// If no block given, load last block from state file, or use first block with CP tx
if(!$block){
    $last  = file_get_contents(LASTFILE);
    $first = ($regtest) ? 1 : (($testnet) ? 166371 : 335643);
    $block = (isset($last) && $last>=$first) ? (intval($last) + 1) : $first;
}

// Get the current block index from status info
$current = $counterparty->status['last_block']['block_index'];

// Define array of fields that contain assets, addresses, transactions, and contracts
$fields_asset       = array('asset', 'backward_asset', 'dividend_asset', 'forward_asset', 'get_asset', 'give_asset');
$fields_address     = array('address', 'bet_hash', 'destination', 'feed_address', 'issuer', 'source', 'tx0_address', 'tx1_address');
$fields_transaction = array('event', 'move_random_hash', 'offer_hash', 'order_hash', 'rps_hash', 'tx_hash', 'tx0_hash', 'tx0_move_random_hash', 'tx1_hash', 'tx1_move_random_hash', 'dispenser_tx_hash');
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
    $contracts    = array(); // array of contract id mappings

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
        if(isset($obj->tx_index) && isset($obj->block_index) && isset($transactions[$obj->tx_hash]))
            createTxIndex($obj->tx_index, $obj->block_index, $msg->category, $transactions[$obj->tx_hash]);
        // Create record in the messages table (so we can review the CP messages as needed)
        createMessage($message);
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
        $fldmap = array();
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
            // Force numeric values on some broadcast values
            if($table=='broadcasts'){
                if(in_array($field,array('locked','fee_fraction_int')))
                    $value = intval($value);
                if($field=='value' && $value=='')
                    $value = 0;
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
            if($table=='sends'){
                if($field=='quantity')
                    $value = intval($value);
                if($field=='msg_index')
                    $ignore = true;
            }
            if($table=='dispensers'){
                if($field=='prev_status')
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
            // Make value safe for use in SQL queries
            $value = $mysqli->real_escape_string($value);
            // Add final field and value values to arrays
            array_push($fields, $field);
            array_push($values, $value);
            $fldmap[$field] = $value;
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

            $sqlUpdate = '';
            foreach($fields as $index => $field){
                $sql .= " {$field}='{$values[$index]}' AND";

                //building potential update statement
                $sqlUpdate .= " {$field}='{$values[$index]}' AND";
            }

            $sql = rtrim($sql, " AND");
            $sqlUpdate = rtrim($sqlUpdate, " AND");

            // print $sql;
            $results = $mysqli->query($sql);
            if($results){
                //on duplicate key statement will update the row if exists already
                if($results->num_rows==0){
                    $sql = "INSERT INTO {$table} (" . implode(",", $fields)  . ") values ('" . implode("', '", $values) . "') ON DUPLICATE KEY UPDATE $sqlUpdate";
                    $results = $mysqli->query($sql);
                    if(!$results && !$silent)
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
                if(in_array($table,array('orders','bets','dispensers')) && $field=='tx_hash_id'){
                    if($where!="")
                        $where .= " AND ";
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
                // Skip updating the block_index on dispenser (so we keep the original block_index where the dispenser was created/updated)
                } else if($table=='dispensers' && in_array($field, array('block_index','status'))){
                    if($field=='block_index')
                        continue;
                    if($field=='status' && $values[$index]==10)
                        $sql   .= " status='10',";
                    $where = " source_id='{$fldmap['source_id']}' AND asset_id='{$fldmap['asset_id']}'";
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

    // array of markets
    $markets = array(); 

    // Loop through messages and detect any DEX market changes
    foreach($messages as $message){
        $msg = (object) $message;
        $obj = json_decode($msg->bindings);
        $market = false;
        if($msg->category='orders'){
            $sql = "SELECT
                        a1.asset as asset1,
                        a2.asset as asset2
                    FROM
                        orders o,
                        assets a1,
                        assets a2,
                        index_transactions t
                    WHERE
                        t.id=o.tx_hash_id AND
                        a1.id=o.give_asset_id AND
                        a2.id=o.get_asset_id AND
                        t.hash='{$obj->tx_hash}'";
            $results = $mysqli->query($sql);
            if($results){
                if($results->num_rows){
                    $row = (object) $results->fetch_assoc();
                    if(!$markets[$row->asset2 . '|' . $row->asset1])
                        $markets[$row->asset1 . '|' . $row->asset2] = 1;
                }
            }
        }
    }
    // If we have any market changes, update the markets
    if(count($markets)){
        $block_24hr = get24HourBlockIndex();
        createUpdateMarkets($markets);
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