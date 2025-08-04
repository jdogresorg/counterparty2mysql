#!/usr/bin/env php
<?php
/*********************************************************************
 * counterparty2mysql.php 
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

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Flag to indicate if we should show debugging information
$debug = false;

// Flag to indicate if we should save messages in the `messages` table
// Set this to false if you want a faster parse 
$saveMessages = true;

// Flag to indicate if we should update market/asset prices as we parse each block
// Set this to false if you want a faster parse (price updates take a lil while)
// NOTE: If this is set to false, be sure to run the following scripts after your done with your parse to update asset and market prices
// ./misc/update_asset_prices.php
// ./misc/update_market_info.php --update
$updatePrices = true;

// Flag to indicate if we should update balances as we parse each block
// Set this to false if you want a faster parse 
// NOTE: If this is set to false, be sure to run the following scripts after your done with your parse to update all address balances since block_index
// ./misc/fix_address_balances.php --block=block_index
$updateBalances = true;

// Create a lock file, and bail if we detect an instance is already running
createLockFile();

// Handle rollbacks
if($rollback){
    $block_index = $mysqli->real_escape_string($rollback);
    $tables = [
        'addresses',
        'bets',
        'bet_expirations', 
        'bet_match_expirations',
        'bet_match_resolutions',
        'bet_matches',
        'blocks',
        'broadcasts',
        'btcpays',
        'burns',
        'cancels',
        'contracts',
        'credits',
        'debits',
        'destructions',
        'dispensers',
        'dispenses',
        'dividends',
        'executions',
        'fairminters',
        'fairmints',
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
        'sweeps',
        'transactions',
        'transaction_count',
        'transaction_outputs'
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
    $first = ($regtest) ? 1 : (($testnet) ? 310000 : 278270);
    $block = (isset($last) && $last>=$first) ? (intval($last) + 1) : $first;
}

// Get the current block index from status info
$current = $counterparty->status->counterparty_height;

// Define array of fields that contain assets, addresses, transactions, contracts, and integers
$fields_asset       = array('asset', 'backward_asset', 'dividend_asset', 'forward_asset', 'get_asset', 'give_asset','asset_parent');
$fields_address     = array('address', 'destination', 'feed_address', 'issuer', 'source', 'oracle_address', 'tx0_address', 'tx1_address', 'origin', 'last_status_tx_source', 'destination_address', 'source_address', 'utxo_address');
$fields_transaction = array('event', 'bet_hash', 'move_random_hash', 'offer_hash', 'order_hash', 'rps_hash', 'tx_hash', 'tx0_hash', 'tx0_move_random_hash', 'tx1_hash', 'tx1_move_random_hash', 'dispenser_tx_hash', 'last_status_tx_hash', 'dispenser_tx_hash', 'block_hash', 'fairminter_tx_hash', 'utxo', 'previous_block_hash','ledger_hash','txlist_hash','messages_hash');
$fields_contract    = array('contract_id');
$fields_integer     = array('locked','btc_amount','fee','fee_fraction_int','call_date','call_price','quantity','fair_minting','reset','description_locked','supported');

// Loop through the blocks until we are current
while($block <= $current){
    $timer = new Profiler();
    print "processing block {$block}...";

    // Get list of block messages from counterparty API
    $messages = $counterparty->getMessages($block);

    // Define array hold asset/address/tranaction id mappings for this block
    // We want to reset these every block since we use the assets list querying address balances
    $assets       = array(); // array of asset id mappings
    $addresses    = array(); // array of address id mappings
    $transactions = array(); // array of transaction id mappings
    $contracts    = array(); // array of contract id mappings

    // Filter out abusive transactions (optional)
    // $data = array();
    // foreach($messages as $message){
    //     $msg      = (object) $message;
    //     $table    = $msg->category;
    //     $bindings = json_decode($msg->bindings);
    //     if(in_array($table, array('credits','debits','issuances','sends')) && substr($bindings->asset,0,1)=='A')
    //         continue;
    //     array_push($data, $msg);
    // }
    // $messages = $data;

    // Loop through messages and create assets, addresses, transactions and setup id mappings
    foreach($messages as $message){
        $msg = (object) $message;
        $obj = json_decode($msg->bindings);
        foreach($obj as $field => $value){
            // Skip any empty or unset values
            if(!isset($value) || empty($value))
                continue;
            // assets
            foreach($fields_asset as $name)
                if($field==$name && !isset($assets[$value]))
                    $assets[$value] = createAsset($value, $block);
            // addresses
            foreach($fields_address as $name){
                if($field==$name && !isset($addresses[$value])){
                    // Extract transaction hash from any utxos (remove output index)
                    if(str_contains($value, ':')){
                        $value = explode(':',$value)[0];
                        $transactions[$value] = createTransaction($value);
                        continue;
                    }
                    $addresses[$value] = createAddress($value);
                }
            }
            // transactions
            foreach($fields_transaction as $name){
                if($field==$name && !isset($transactions[$value])){
                    // Extract transaction hash from any utxos (remove output index)
                    if(str_contains($value, ':'))
                        $value = explode(':',$value)[0];
                    $transactions[$value] = createTransaction($value);
                }
            }
            // contracts
            foreach($fields_contract as $name)
                if($field==$name && !isset($contracts[$value]))
                    $contracts[$value] = createContract($value);
        }
        // Create record in tx_index (so we can map tx_index to tx_hash and table with data)
        if(isset($obj->tx_index) && isset($obj->block_index) && isset($transactions[$obj->tx_hash]) && $msg->category!='transactions' && $msg->category!='transaction_outputs')
            createTxIndex($obj->tx_index, $obj->block_index, $msg->category, $transactions[$obj->tx_hash]);
        // Create record in the messages table (so we can review the CP messages as needed)
        if($saveMessages)
            createMessage($message);        
    }

    // Loop through addresses and update any asset balances
    // Doing this first ensures that address balances are correct immediately
    if($updateBalances){
        foreach($addresses as $address => $address_id){
            // Ignore any multi-sig addresses (address1-address2)
            if(str_contains($address, '-'))
                continue;
            updateAddressBalances($address, array_keys($assets));
        }
    }

    // Loop through the messages and create/update the counterparty tables
    foreach($messages as $message){
        $msg      = (object) $message;
        $table    = $msg->category;
        $bindings = json_decode($msg->bindings);
        $command  = $msg->command;

        // v10.0.0 - Ignore certain messages for now as they conflict with our already existing tables and bloats database by not indexing addresses/hashes via id
        if(in_array($table,array('assets')))
            continue;

        // Build out array of fields and values
        $fields = array();
        $values = array();

        // Process bindings data to make it safe for use in SQL queries
        foreach($bindings as $field => $value){
            $ignore = false;
            // swap asset name for id
            foreach($fields_asset as $name){
                if($field==$name){
                    $field = $name . '_id';
                    $value = $assets[$value];
                }
            }
            // swap address for id
            foreach($fields_address as $name){
                if($field==$name){
                    // Handle UTXO fields by separating utxo transaction and utxo output 
                    if(in_array($field,array('source','destination')) && str_contains($value, ':')){
                        $utxo = explode(':',$value);
                        $fld  = $field;
                        // Add utxo_output to the field and value values to arrays
                        $field = $fld . '_utxo_output';
                        $value = (isset($utxo) && isset($utxo[1])) ? $utxo[1] : 0;
                        array_push($fields, $field);
                        array_push($values, $value);
                        // Add utxo_id to the field and values arrays
                        $field = $fld . '_utxo_id';
                        $value = (isset($utxo) && isset($utxo[0])) ? $transactions[$utxo[0]] : 0;
                    } else {
                        $field = $name . '_id';
                        $value = $addresses[$value];
                    }
                }
            }
            // swap transaction for id
            foreach($fields_transaction as $name){
                if($field==$name){
                    // Handle UTXO fields by separating utxo transaction and utxo output 
                    if($field=='utxo'){
                        $utxo  = explode(':',$value);
                        // Add utxo_output to the field and values arrays
                        $field = 'utxo_output';
                        $value = (isset($utxo) && isset($utxo[1])) ? $utxo[1] : 0;
                        array_push($fields, $field);
                        array_push($values, $value);
                        // Add utxo_id to the field and values arrays
                        $field = 'utxo_id';
                        $value = (isset($utxo) && isset($utxo[0])) ? $transactions[$utxo[0]] : 0;
                    }  else {
                        $field = $name . '_id';
                        $value = $transactions[$value];
                    }
                }
            }
            // swap contract for id
            foreach($fields_contract as $name){
                if($field==$name)
                    $value = $contracts[$value];
            }
            // Force certain fields to always have a integer value
            // NOTE: fields that end in `_id` are automatically forced to integer value
            if((in_array($field, $fields_integer) || str_contains($field,'_id'))  && (!isset($value) || $value==''))
                $value = intval($value);
            // Replace 4-byte UTF-8 characters (fixes issue with breaking SQL queries) 
            if($field=='description' || ($table=='broadcasts' && $field=='text'))
                $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value);
            // Force numeric value on broadcast value
            if($table=='broadcasts' && $field=='value' && $value=='')
                $value = intval($value);
            // Truncate description to first 10K characters
            if($field=='description')
                $value = substr($value,0,10000); 
            /* Ignore certain fields */
            if($table=='issuances' && in_array($field, array('locked','transfer','divisible','callable')) && $value=='')
                $ignore = true;
            if($table=='rps' && $field=='calling_function')
                $ignore = true;
            if($table=='sends' && $field=='msg_index')
                $ignore = true;
            if($field=='asset_longname')
                $ignore = true;
            if(in_array($field,array('source_address_id','destination_address_id')))
                $ignore = true;
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
            if($ignore)
                continue;
            // Make value safe for use in SQL queries
            // print "field={$field}\n";
            // print "value={$value}\n";
            // var_dump($value);
            $value = $mysqli->real_escape_string($value);
            // Add final field and value values to arrays
            array_push($fields, $field);
            array_push($values, $value);
        }

        // Add some extra fields if they are missing from messages table
        // Handle setting send_type based off event (field is missing from messages data)
        if($table=='sends' && !in_array('send_type', $fields)){
            $type = '';
            if(in_array($msg->event,array('SEND','ENHANCED_SEND','MPMA_SEND')))
                $type = 'send';
            if($msg->event=='ATTACH_TO_UTXO')
                $type = 'attach';
            if($msg->event=='DETACH_FROM_UTXO')
                $type = 'detach';
            if($msg->event=='UTXO_MOVE')
                $type = 'move';
            array_push($fields, 'send_type');
            array_push($values, $type);
        }

        // Handle 'insert' commands
        if($command=='insert'){
            $sql  = "SELECT * FROM {$table} WHERE";
            $sql2 = '';
            foreach($fields as $index => $field){
                $sql  .= " {$field}='{$values[$index]}' AND";
                $sql2 .= " {$field}='{$values[$index]}' AND";
            }
            $sql  = rtrim($sql,  " AND");
            $sql2 = rtrim($sql2, " AND");
            if($debug)
                print "{$sql}\n";
            $results = $mysqli->query($sql);
            if($results){
                // Duplicate key statement will update the row if exists already
                if($results->num_rows==0){
                    $sql = "INSERT INTO {$table} (" . implode(",", $fields)  . ") values ('" . implode("', '", $values) . "') ON DUPLICATE KEY UPDATE $sql2";
                    if($debug)
                        print "{$sql}\n";
                    $results = $mysqli->query($sql);
                    if(!$results && !$silent)
                        byeLog('Error while trying to create record in ' . $table . ' : ' . $sql);
                }
            } else {
                byeLog('Error while trying to check if record already exists in ' . $table . ' : ' . $sql);
            }
        }

        // Handle 'update' and 'parse' commands
        if(in_array($command,array('update','parse'))){
            $sql   = "UPDATE {$table} SET";
            $where = "";
            foreach($fields as $index => $field){
                // Update bets and orders records using tx_hash
                if(in_array($table,array('orders','bets','fairminters')) && $field=='tx_hash_id'){
                    if($where!="")
                        $where .= " AND ";
                    $where .= " tx_hash_id='{$values[$index]}'";
                // Update *_matches tables using id field
                } else if(in_array($table,array('order_matches','bet_matches','rps_matches')) && 
                          in_array($field,array('order_match_id','bet_match_id','rps_match_id', 'id'))){
                    $where = " id='{$values[$index]}'";
                // Update rps table using tx_hash or tx_index
                } else if($table=='rps' && $field=='tx_hash_id'){
                    $where .= " {$field}='{$values[$index]}'";
                // Update nonces table using address_id
                } else if($table=='nonces' && $field=='address_id'){
                    $where .= " {$field}='{$values[$index]}'";
                // Update transactions table using tx_index
                } else if($table=='transactions' && $field=='tx_index'){
                    $where = " tx_index='{$values[$index]}'";
                // Update nonces table using block_index
                } else if($table=='blocks' && $field=='block_index'){
                    $where = " block_index='{$values[$index]}'";
                // Update dispensers table using tx_hash_id
                } else if($table=='dispensers' && $field=='tx_hash_id'){
                    $where = " tx_hash_id='{$values[$index]}'";
                // Skip updating block_index and asset_id in dispensers
                } else if($table=='dispensers' && in_array($field, array('block_index','asset_id'))){
                    continue;
                // Skip updating the id field unnecessarily when updating an order match
                } else if($table=='order_matches' && $field=='id'){
                    continue;
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
            if($debug)
                print "{$sql}\n";
            $results = $mysqli->query($sql);
            if(!$results)
                byeLog('Error while trying to update record in ' . $table . ' : ' . $sql);
        }

    }

    // Loop through assets and update BTC & XCP price 
    foreach($assets as $asset =>$id)
        if($updatePrices)
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
    if(count($markets) && $updatePrices){
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