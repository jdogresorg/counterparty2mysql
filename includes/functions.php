<?php
/*********************************************************************
 * functions.php - Common functions
 ********************************************************************/

// Handle creating a lockfile and bailing out if lock file already exists (ie, an instance is already running)
function createLockFile($file=null){
    $lockFile = ($file!='') ? $file : LOCKFILE;
    if(file_exists($lockFile)){
        print "detected lockfile at {$lockFile} ... exiting\n";
        exit;
    } else {
        // Write a lockfile so we prevent other runs while we are running
        file_put_contents($lockFile, 1);
    }
}


// Handle removing a lockfile
function removeLockFile($file=null){
    $lockFile = ($file!='') ? $file : LOCKFILE;
    if(file_exists($lockFile))
        unlink($lockFile);
}


// Simple function to print message and exit
function bye($msg=null){
    print $msg . "\n";
    exit;
}

// Log/Print an error and exit
function byeLog($error=null, $log=null){
    $logFile   = (strlen($log)) ? $log : ERRORLOG;
    $errorLine = '[' . gmdate("Y-m-d H:i:s") . ' UTC] - '. $error . "\n";
    if(strlen($logFile))
        file_put_contents($logFile, $errorLine, FILE_APPEND);
    print $errorLine;
    // Try to remove the lockfile, so we can continue running next time
    removeLockFile();
    exit;
}


// Setup database connection
function initDB($hostname=null, $username=null, $password=null, $database=null, $log=false){
    global $mysqli;
    // Try to establish database connection and exit if we are not able to
    $mysqli = new mysqli($hostname, $username, $password, $database);
    if($mysqli->connect_errno){
        $msg = 'Database Connection Failure: ' . $mysqli->connect_error;
        if($log){
            byeLog($msg);
        } else {
            print $msg;
            exit;
        }
    }
}


// Setup Counterparty API connection
function initCP($hostname=null, $username=null, $password=null, $log=false){
    global $counterparty;
    $counterparty = new Client($hostname);
    $counterparty->authentication($username, $password);
    $status = $counterparty->execute('get_running_info');
    // If we have a successfull response, store it in 'status'
    if(isset($status)){
        $counterparty->status = $status;
    } else {
        // If we failed to establish a connection, bail out
        $msg = 'Counterparty Connection Failure';
        if($log){
            byeLog($msg);
        } else {
            print $msg;
            exit;
        }
    }
}

// Handle getting database id for a given asset
function getAssetDatabaseId($asset=null){
    global $mysqli;
    $id = false;
    $results = $mysqli->query("SELECT id FROM assets WHERE asset='{$asset}' OR asset_longname='{$asset}' LIMIT 1");
    if($results){
        $row = $results->fetch_assoc();
        $id  = $row['id'];
    }
    return $id;
}


// Convert asset name into a numeric asset_id
// Big thanks to Joe looney <hello@joelooney.org> for pointing me towards his similar javascript function
function getAssetId($asset=null){
    $id = false;
    if($asset == 'XCP'){
        $id =  1;
    } else if(substr($asset,0,1)=='A'){
        $id = substr($asset,1);
    } else {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $array = str_split($asset);
        $n = 0;
        for ($i = 0; $i < count($array); $i++) { 
            $n *= 26;
            $n += strpos($chars, $array[$i]);
        }
        $id = $n;
    }
    return $id;
}


// Create/Update records in the 'blocks' table and return record id
function createBlock( $block_index=null ){
    global $mysqli, $counterparty;
    $data = (object) $counterparty->execute('get_block_info', array('block_index' => $block_index));
    $data->block_hash_id          = createTransaction($data->block_hash);
    $data->previous_block_hash_id = createTransaction($data->previous_block_hash);
    $data->ledger_hash_id         = createTransaction($data->ledger_hash);
    $data->txlist_hash_id         = createTransaction($data->txlist_hash);
    $data->messages_hash_id       = createTransaction($data->messages_hash);
    $results = $mysqli->query("SELECT block_index FROM blocks WHERE block_index='{$data->block_index}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            $id  = $row['id'];
            $sql = "UPDATE blocks SET
                       block_time             = '{$data->block_time}',
                       block_hash_id          = '{$data->block_hash_id}',
                       previous_block_hash_id = '{$data->previous_block_hash_id}',
                       ledger_hash_id         = '{$data->ledger_hash_id}',
                       txlist_hash_id         = '{$data->txlist_hash_id}',
                       messages_hash_id       = '{$data->messages_hash_id}',
                       difficulty             = '{$data->difficulty}'
                    WHERE
                        block_index='{$block_index}'";
            $results = $mysqli->query($sql);
            if($results){
                return $id;
            } else {
                byeLog('Error while trying to update block ' . $data->block_index);
            }
        } else {
            // Grab data on the asset from api and set some values before stashing info in db
            $sql = "INSERT INTO blocks (block_index, block_time, block_hash_id, previous_block_hash_id, ledger_hash_id, txlist_hash_id, messages_hash_id, difficulty) values (
                '{$data->block_index}',
                '{$data->block_time}',
                '{$data->block_hash_id}',
                '{$data->previous_block_hash_id}',
                '{$data->ledger_hash_id}',
                '{$data->txlist_hash_id}',
                '{$data->messages_hash_id}',
                '{$data->difficulty}')";
            $results = $mysqli->query($sql);
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create block ' . $data->block_index);
            }
        }
    } else {
        byeLog('Error while trying to lookup record in blocks table');
    }
}


// Create/Update records in the 'assets' table and return record id
function createAsset( $asset=null, $block_index=null ){
    global $mysqli, $counterparty;
    // Get current information on this asset
    $info = $counterparty->execute('get_asset_info', array('assets' => array($asset)));
    // Create data object using asset info (if any)
    $data                 = (count($info)) ? (object) $info[0] : (object) [];
    // Replace 4-byte UTF-8 characters (fixes issue with breaking SQL queries) 
    $description          = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $data->description);
    // Truncate to 10,000 chars (max field length)
    $description          = substr($description,0,10000); 
    $data->asset_id       = getAssetId($asset);
    $data->issuer_id      = createAddress($data->issuer);
    $data->owner_id       = createAddress($data->owner);
    $data->divisible      = ($data->divisible) ? 1 : 0;  // convert to boolean
    $data->locked         = ($data->locked) ? 1 : 0 ;    // convert to boolean
    $data->supply         = intval($data->supply);
    $data->description    = $mysqli->real_escape_string($description);
    $data->asset_longname = $mysqli->real_escape_string($data->asset_longname);
    // Set asset type (1=Named, 2=Numeric, 3=Subasset, 4=Failed issuance)
    $data->type           = (substr($asset,0,1)=='A') ? 2 : 1;
    if($data->asset_longname!='')
        $data->type = 3;
    if(count($info)==0)
        $data->type = 4;
    // Force numeric values for special assets
    if(in_array($data->asset, array('XCP','BTC'))){
        $data->issuer_id = 0;
        $data->owner_id  = 0;
    }
    // Check if this asset already exists
    $results = $mysqli->query("SELECT id FROM assets WHERE asset='{$asset}' LIMIT 1");
    if($results){
        if($results->num_rows){
            // Update asset information
            $row = $results->fetch_assoc();
            $id  = $row['id'];
            // If we don't have any asset data, skip update and just return asset id
            if(!isset($data->asset_id))
                return $id;
            $sql = "UPDATE assets SET
                        asset_id       = '{$data->asset_id}',
                        asset_longname = '{$data->asset_longname}',
                        divisible      = '{$data->divisible}',
                        description    = '{$data->description}',
                        issuer_id      = '{$data->issuer_id}',
                        owner_id       = '{$data->owner_id}',
                        locked         = '{$data->locked}',
                        type           = '{$data->type}',
                        supply         = '{$data->supply}'
                    WHERE
                        id='{$id}'";
            $results = $mysqli->query($sql);
            if($results){
                return $id;
            } else {
                byeLog('Error while trying to update asset record for ' . $asset . ' : ' . $sql);
            }
        } else {
            // If we don't have any asset data, throw error
            if(!isset($data->asset_id))
                byeLog('Error while trying to create asset record for ' . $asset . ': no asset data found!');
            // Create asset information
            $sql = "INSERT INTO assets (asset_id, asset, asset_longname, block_index, type, divisible, description, issuer_id, locked, owner_id, supply) values (
                '{$data->asset_id}',
                '{$asset}',
                '{$data->asset_longname}',
                '{$block_index}',
                '{$data->type}',
                '{$data->divisible}',
                '{$data->description}',
                '{$data->issuer_id}',
                '{$data->locked}',
                '{$data->owner_id}',
                '{$data->supply}')";
            // print $sql;
            $results = $mysqli->query($sql);
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create asset record for ' . $asset);
            }
        }
    } else {
        byeLog('Error while trying to lookup asset record');
    }
}


// Create records in the 'addresses' table and return record id
function createAddress( $address=null ){
    global $mysqli;
    if(!isset($address) || $address=='')
        return 0;
    $address = $mysqli->real_escape_string($address);
    $results = $mysqli->query("SELECT id FROM index_addresses WHERE address='{$address}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $results = $mysqli->query("INSERT INTO index_addresses (`address`) values ('{$address}')");
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create record in index_addresses table');
            }
        }
    } else {
        byeLog('Error while trying to lookup record in index_addresses table');
    }
}


// Create records in the 'index_transactions' table and return record id
function createTransaction( $hash=null ){
    global $mysqli;
    if(!isset($hash) || $hash=='')
        return;
    $hash    = $mysqli->real_escape_string($hash);
    $results = $mysqli->query("SELECT id FROM index_transactions WHERE `hash`='{$hash}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $results = $mysqli->query("INSERT INTO index_transactions (`hash`) values ('{$hash}')");
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create record in index_transactions table');
            }
        }
    } else {
        byeLog('Error while trying to lookup record in index_transactions table');
    }
}


// Create records in the 'transactions' table
function createTransactionHistory( $tx = null ){
    global $mysqli;
    if(!isset($tx) || $tx=='')
        return;
    $tx             = (object) $tx; // Force conversion to object
    $tx_hash_id     = createTransaction($tx->tx_hash);
    $block_hash_id  = createTransaction($tx->block_hash);
    $source_id      = createAddress($tx->source);
    $destination_id = createAddress($tx->destination);
    $btc_amount     = (is_int($tx->btc_amount)) ? $tx->btc_amount : 0;
    // Check if we have an existing record for this tx
    $results = $mysqli->query("SELECT tx_index FROM transactions WHERE `tx_index`='{$tx->tx_index}'");
    if($results){
        if($results->num_rows){
            $sql = "UPDATE 
                        transactions 
                    SET
                        tx_hash_id='{$tx_hash_id}', 
                        block_index='{$tx->block_index}', 
                        block_hash_id='{$block_hash_id}', 
                        block_time='{$tx->block_time}', 
                        source_id='{$source_id}', 
                        destination_id='{$destination_id}', 
                        btc_amount='{$btc_amount}', 
                        fee='{$tx->fee}', 
                        data='{$tx->data}', 
                        supported='{$tx->supported}'
                    WHERE 
                        tx_index='{$tx->tx_index}'";
        } else {
            $sql = "INSERT INTO transactions (tx_index, tx_hash_id, block_index, block_hash_id, block_time, source_id, destination_id, btc_amount, fee, data, supported) values ('{$tx->tx_index}','{$tx_hash_id}', '{$tx->block_index}', '{$block_hash_id}','{$tx->block_time}','{$source_id}','{$destination_id}','{$btc_amount}','{$tx->fee}','{$tx->data}','{$tx->supported}')";
        }
        $results2 = $mysqli->query($sql);
        if(!$results2)
            byeLog('Error while trying to create or update record in transactions table: ' . $sql);
    } else {
        byeLog('Error while trying to lookup record in transactions table');
    }
}


// Create records in the 'messages' table
function createMessage( $message=null ){
    global $mysqli;
    $msg = (object) $message;
    $command       = $mysqli->real_escape_string($msg->command);
    $category      = $mysqli->real_escape_string($msg->category);
    $bindings      = $mysqli->real_escape_string($msg->bindings);
    $block_index   = $mysqli->real_escape_string($msg->block_index);
    $message_index = $mysqli->real_escape_string($msg->message_index);
    $timestamp     = $mysqli->real_escape_string($msg->timestamp);
    $results       = $mysqli->query("SELECT message_index FROM messages WHERE `message_index`='{$message_index}' LIMIT 1");
    if($results){
        if($results->num_rows==0){
            $sql = "INSERT INTO messages (message_index, block_index, command, category, bindings, timestamp) values ('{$message_index}','{$block_index}','{$command}','{$category}','{$bindings}','{$timestamp}')";
        } else {
            $sql = "UPDATE messages SET block_index='{$block_index}', command='{$command}', category='{$category}', bindings='{$bindings}', timestamp='{$timestamp}' WHERE message_index='{$message_index}'";
        }
        $results = $mysqli->query($sql);
        if(!$results){
            byeLog('Error while trying to create or update record in messages table');
        }
    } else {
        byeLog('Error while trying to lookup record in messages table');
    }
}

// Create records in the 'dispenses' table
function createDispense( $block_index=null, $asset=null, $hash=null ){
    global $mysqli;
    // get message_index from messages table for this dispense
    $dispense_hash = $mysqli->real_escape_string($hash);
    $results       = $mysqli->query("SELECT message_index FROM messages WHERE command='insert' AND category='credits' AND block_index='{$block_index}' AND bindings LIKE '%{$dispense_hash}%' AND bindings LIKE '%\"asset\": \"{$asset}\"%' LIMIT 1");
    if($results){
        $row     = $results->fetch_assoc();
        $index   = $row['message_index'];
        // Get the next message after dispense, which will be the dispenser update message, and extract the dispenser transaction hash
        $results = $mysqli->query("SELECT bindings FROM messages WHERE message_index>'{$index}' AND category='dispensers' ORDER BY message_index ASC LIMIT 1");
        if($results){
            $row = $results->fetch_assoc();
            $obj = json_decode($row['bindings']);
            $dispense_tx_id  = createTransaction($dispense_hash);
            $dispenser_tx_id = createTransaction($obj->tx_hash);
            // Check to see if a record already exists
            $results = $mysqli->query("SELECT id FROM dispenses WHERE block_index='{$block_index}' AND dispenser_tx_id='{$dispenser_tx_id}' AND dispense_tx_id='{$dispense_tx_id}'");
            if($results){
                if($results->num_rows==0){
                    $results = $mysqli->query("INSERT INTO dispenses (block_index, dispenser_tx_id, dispense_tx_id) values ('{$block_index}','{$dispenser_tx_id}','{$dispense_tx_id}')");
                    if(!$results)
                        byeLog('Error while trying to create record in dispenses table');
                }
            } else {
                byeLog('Error while trying to lookup record in dispenses table');
            }
        } else {
            byeLog('Error while trying to locate dispenser update record in messages table');
        }
    } else {
        byeLog('Error while trying to locate dispense record in messages table');
    }
}


// Create records in the 'contract_ids' table and return record id
function createContract( $contract=null ){
    global $mysqli;
    if(!isset($contract) || $contract=='')
        return;
    $contract = $mysqli->real_escape_string($contract);
    $results  = $mysqli->query("SELECT id FROM index_contracts WHERE contract='{$contract}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $results = $mysqli->query("INSERT INTO index_contracts (contract) values ('{$contract}')");
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create record in index_contracts table');
            }
        }
    } else {
        byeLog('Error while trying to lookup record in index_contracts table');
    }
}


// Create records in the 'tx_index_types' table and return record id
function createTxType( $type=null ){
    global $mysqli;
    $type    = $mysqli->real_escape_string($type);
    $results = $mysqli->query("SELECT id FROM index_tx_types WHERE type='{$type}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $results = $mysqli->query("INSERT INTO index_tx_types (type) values ('{$type}')");
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create record in index_tx_types table');
            }
        }
    } else {
        byeLog('Error while trying to lookup record in index_tx_types table');
    }
}


// Create records in the 'tx_index' table
function createTxIndex( $tx_index=null, $block_index=null, $tx_type=null, $tx_hash_id=null ){
    global $mysqli;
    $tx_index = $mysqli->real_escape_string($tx_index);
    $type_id  = createTxType($tx_type);
    $results  = $mysqli->query("SELECT type_id FROM index_tx WHERE tx_index='{$tx_index}' LIMIT 1");
    if($results){
        if($results->num_rows==0){
            $results = $mysqli->query("INSERT INTO index_tx (tx_index, block_index, tx_hash_id, type_id) values ('{$tx_index}','{$block_index}','{$tx_hash_id}', '{$type_id}')");
            if(!$results)
                byeLog('Error while trying to create record in index_tx table');
        }
    } else {
        byeLog('Error while trying to lookup record in index_tx table');
    }
}


// Create/Update records in the 'balances' table
function updateAddressBalance( $address=null, $asset_list=null ){
    global $mysqli, $counterparty, $addresses, $assets;
    // Lookup any balance for this address and asset
    $balances = getAddressBalances($address, $asset_list);
    if(count($balances)){
        foreach($balances as $balance){
            $address_id = $addresses[$balance['address']]; // Translate address to address_id
            $asset      = $balance['asset'];
            $asset_id   = $assets[$asset];                 // Translate asset to asset_id
            $quantity   = $balance['quantity'];
            $results    = $mysqli->query("SELECT id FROM balances WHERE address_id='{$address_id}' AND asset_id='{$asset_id}' LIMIT 1");
            if($results){
                if($results->num_rows){
                    // Update asset balance
                    $results = $mysqli->query("UPDATE balances SET quantity='{$quantity}' WHERE address_id='{$address_id}' AND asset_id='{$asset_id}'");
                    if(!$results)
                        byeLog('Error while trying to update balance record for ' . $address . ' - ' . $asset);
                } else {
                    // Create asset balance only if the quantity is greater than 0
                    if($quantity){
                        $results = $mysqli->query("INSERT INTO balances (asset_id, address_id, quantity) values ('{$asset_id}','{$address_id}','{$quantity}')");
                        if(!$results)
                            byeLog('Error while trying to create balance record for ' . $address . ' - ' . $asset);
                    }
                }
            } else {
                byeLog('Error while trying to lookup balance record for ' . $address . ' - ' . $asset);
            }
        }
    }
}

// Handle requesting address balance information for a given address and list of assets
function getAddressBalances($address=null, $asset_list=null){
    global $counterparty;
    $balances = array();
    // Break asset list up into chunks of 500 (API calls with more than 500 assets fail)
    $asset_list   = array_chunk($asset_list, 500);
    foreach($asset_list as $assets){
        // Lookup any balance for this address and asset
        $filters  = array(array('field' => 'address', 'op' => '==', 'value' => $address),
                          array('field' => 'asset',   'op' => 'IN', 'value' => $assets));
        $data = $counterparty->execute('get_balances', array('filters' => $filters, 'filterop' => "AND"));
        if(count($data)){
            $balances = array_merge($balances, $data);
        }
    }
    return $balances;
}


// Handle updating asset with latest XCP price from DEX
function updateAssetPrice( $asset=null ){
    global $mysqli;
    // Lookup asset id 
    $asset   = $mysqli->real_escape_string($asset);
    $results = $mysqli->query("SELECT id, divisible FROM assets WHERE asset='{$asset}'");
    if($results && $results->num_rows){
        $row = $results->fetch_assoc();
        $asset_id  = $row['id'];
        $divisible = ($row['divisible']==1) ? true : false;
    } else {
        byeLog('Error looking up asset id');
    }
    // Bail out on BTC or XCP
    if($asset_id<=1)
        return;
    // Lookup last order match for XCP
    $sql = "SELECT
                m.forward_asset_id,
                m.forward_quantity,
                m.backward_asset_id,
                m.backward_quantity
            FROM
                order_matches m
            WHERE
                ((m.forward_asset_id=2 AND m.backward_asset_id='{$asset_id}') OR
                ( m.forward_asset_id='{$asset_id}' AND m.backward_asset_id=2)) AND
                m.status='completed'
            ORDER BY 
                m.block_index DESC 
            LIMIT 1";
    $results  = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            $data      = $results->fetch_assoc();
            $xcp_amt   = ($data['forward_asset_id']==2) ? $data['forward_quantity'] : $data['backward_quantity'];
            $xxx_amt   = ($data['forward_asset_id']==2) ? $data['backward_quantity'] : $data['forward_quantity'];
            $xcp_qty   = number_format($xcp_amt * 0.00000001,8,'.','');
            $xxx_qty   = ($divisible) ? number_format($xxx_amt * 0.00000001,8,'.','') : number_format($xxx_amt,0,'.','');
            $price     = number_format($xcp_qty / $xxx_qty,8,'.','');
            $price_int = number_format($price * 100000000,0,'.','');
            $results   = $mysqli->query("UPDATE assets SET xcp_price='{$price_int}' WHERE id='{$asset_id}'");
            if(!$results)
                byeLog('Error updating XCP price for asset ' . $asset);
        }
    } else {
        byeLog('Error while trying to lookup asset price');
    }
    $btc_prices = array();
    // Lookup last BTC order match
    $sql = "SELECT
                m.block_index,
                m.forward_asset_id,
                m.forward_quantity,
                m.backward_asset_id,
                m.backward_quantity
            FROM
                order_matches m
            WHERE
                ((m.forward_asset_id=1 AND m.backward_asset_id='{$asset_id}') OR
                ( m.forward_asset_id='{$asset_id}' AND m.backward_asset_id=1)) AND
                m.status='completed'
            ORDER BY 
                m.block_index DESC 
            LIMIT 1";
    $results  = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            $data      = $results->fetch_assoc();
            $btc_amt   = ($data['forward_asset_id']==1) ? $data['forward_quantity'] : $data['backward_quantity'];
            $xxx_amt   = ($data['forward_asset_id']==1) ? $data['backward_quantity'] : $data['forward_quantity'];
            $btc_qty   = number_format($btc_amt * 0.00000001,8,'.','');
            $xxx_qty   = ($divisible) ? number_format($xxx_amt * 0.00000001,8,'.','') : number_format($xxx_amt,0,'.','');
            $price     = number_format($btc_qty / $xxx_qty,8,'.','');
            $price_int = number_format($price * 100000000,0,'.','');
            $btc_prices[$data['block_index']] = $price_int;
        }
    } else {
        byeLog('Error while trying to lookup asset price');
    }
    // Lookup last Dispense
    $sql = "SELECT 
                count(c.event_id) as credits,
                d1.tx_index,
                d1.block_index,
                t.btc_amount,
                d1.dispense_quantity,
                d2.give_quantity,
                a.asset,
                a.divisible,
                d2.satoshirate,
                d2.oracle_address_id,
                d2.tx_index as dispenser_tx_index
            FROM 
                dispenses d1,
                dispensers d2,
                assets a,
                transactions t,
                credits c
            WHERE 
                d1.dispenser_tx_hash_id=d2.tx_hash_id AND
                d1.tx_index=t.tx_index AND
                d1.tx_hash_id=c.event_id AND
                d1.asset_id=a.id AND
                d1.asset_id='{$asset_id}'
            GROUP BY c.event_id 
            ORDER BY 
                d1.block_index DESC
            LIMIT 25";
    // print $sql;
    $results = $mysqli->query($sql);
    $found   = false;
    if($results){
        if($results->num_rows){
            $data      = (object) $results->fetch_assoc();
            // Only update price on first dispense (ignore dispenses of multiple items)
            if(!$found && $data->credits==1){
                $found = true;
                if($data->oracle_address_id){
                    // Oracled Dispensers
                    $quantity   = ($data->divisible==1) ? number_format(($data->dispense_quantity * 0.00000001),8,'.','') : $data->dispense_quantity;
                    $btc_amount = number_format($data->btc_amount * 0.00000001,8,'.','');
                    $price      = number_format($btc_amount / $quantity,8,'.','');
                } else {
                    // Normal Dispensers
                    $quantity   = ($data->divisible==1) ? number_format(($data->give_quantity * 0.00000001),8,'.','') : $data->give_quantity;
                    $btc_amount = number_format($data->satoshirate * 0.00000001,8,'.','');
                    $price      = bcmul($btc_amount, bcdiv(1, $quantity, 8), 8);
                }
                $price_int  = number_format($price * 100000000,0,'.','');
                // Old way of doing things price = asset_quantity / btc_paid
                // Problem with this method is if someone overpays on a btcpay, then that is factored into the price
                // $xxx_qty    = ($data->divisible) ? number_format(($data->dispense_quantity * 0.00000001),8,'.','') : $data->dispense_quantity;
                // $btc_qty    = number_format(($data->btc_amount * 0.00000001),8,'.','');
                // $price      = number_format(($btc_qty / $xxx_qty),8,'.','');
                // $price_int  = number_format($price * 100000000,0,'.','');
                if(!array_key_exists($data->block_index,$btc_prices) || $btc_prices[$data->block_index] < $price_int)
                    $btc_prices[$data->block_index] = $price_int;
            }
        }
    } else {
        byeLog('Error while trying to lookup asset price');
    }
    // Update BTC price to use most recent transaction price (block_index)
    if(count($btc_prices)){
        ksort($btc_prices);
        $price_int = array_pop($btc_prices);
        $results   = $mysqli->query("UPDATE assets SET btc_price='{$price_int}' WHERE id='{$asset_id}'");
        if(!$results)
            byeLog('Error updating BTC price for asset ' . $asset);
    }
}


// Handle looping through a list of DEX markets and creating/updating the market information
function createUpdateMarkets($markets){
    $cnt   = 0;
    $total = count($markets);
    foreach($markets as $market => $value){
        $cnt++;
        list($asset1, $asset2) = explode('|',$market);
        $market_id = createMarket($asset1, $asset2);
        updateMarketInfo($market_id, $cnt, $total);
    }
}

// Handle creating a Decentralized Exchange (DEX) markets record
function createMarket($asset1, $asset2){
    global $mysqli;
    // Check if the market already exists... if not, create it
    $sql = "SELECT 
                m.id
            FROM 
                markets m,
                assets a1,
                assets a2
            WHERE 
                a1.id=m.asset1_id AND
                a2.id=m.asset2_id AND
                ((a1.asset='{$asset1}' AND a2.asset='{$asset2}') OR
                 (a1.asset='{$asset2}' AND a2.asset='{$asset1}'))";
    $results = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $asset1_id = getAssetDatabaseId($asset1);
            $asset2_id = getAssetDatabaseId($asset2); 
            $results   = $mysqli->query("INSERT INTO markets (asset1_id, asset2_id) values ('{$asset1_id}', '{$asset2_id}')");
            if($results && $mysqli->insert_id){
                return $mysqli->insert_id;
            } else {
                byeLog("Error while trying to create market {$asset1} / {$asset2}");
            }
        }
    } else {
        byeLog("Error while trying to check for market {$asset1} / {$asset2}");
    }
}


// Handle looking up the block_index from exactly 24 hours ago
function get24HourBlockIndex(){
    global $mysqli;
    $one_day    = (60 * 60 * 24 * 1); // 60 seconds x 60 minutes x 24 hours x 1 day
    $block_time = time() - $one_day;  // Block time 24 hours ago
    $results = $mysqli->query("SELECT block_index FROM blocks WHERE block_time >= {$block_time} ORDER BY block_index ASC LIMIT 1");
    if($results && $results->num_rows){
        $row = $results->fetch_assoc();
        return $row['block_index'];
    }
    return 0;
}

// Handle looking up block_index for first message
function getFirstMessageBlock(){
    global $mysqli;
    $results = $mysqli->query("SELECT block_index FROM messages ORDER BY message_index ASC LIMIT 1");
    if($results){
        $row = $results->fetch_assoc();
        return $row['block_index'];
    }
}

// Handle looking up block_index for first message
function getLastMessageBlock(){
    global $mysqli;
    $results = $mysqli->query("SELECT block_index FROM messages ORDER BY message_index DESC LIMIT 1");
    if($results){
        $row = $results->fetch_assoc();
        return $row['block_index'];
    }
}


// Handle updating market information
function updateMarketInfo( $market_id, $cnt, $total ){
    global $mysqli, $block_24hr, $debug;

    // Timer to track each market update
    $profile = new Profiler();

    // Define some default values
    $price1_last   = 0; // Asset1 - Last traded price
    $price1_ask    = 0; // Asset1 - Price Sellers are asking
    $price1_bid    = 0; // Asset1 - Price Buyers are paying
    $price1_high   = 0; // Asset1 - 24-hour price high
    $price1_low    = 0; // Asset1 - 24-hour price low
    $price1_24hr   = 0; // Asset1 - Price 24-hours ago
    $price2_last   = 0; // Asset2 - Last traded price
    $price2_ask    = 0; // Asset2 - Price Sellers are asking
    $price2_bid    = 0; // Asset2 - Price Buyers are paying
    $price2_high   = 0; // Asset2 - 24-hour price high
    $price2_low    = 0; // Asset2 - 24-hour price low
    $price2_24hr   = 0; // Asset2 - Price 24-hours ago
    $price_change  = 0; // 24-hour price change (%)
    $asset1_volume = 0; // 24-hour volume (asset1)
    $asset2_volume = 0; // 24-hour volume (asset2)

    // Lookup basic information on this market/assets
    $sql = "SELECT
                a1.asset as asset1,
                a2.asset as asset2,
                a1.divisible as asset1_divisible,
                a2.divisible as asset2_divisible,
                m.asset1_id,
                m.asset2_id
            FROM
                markets m,
                assets a1,
                assets a2
            WHERE 
                a1.id=m.asset1_id AND
                a2.id=m.asset2_id AND
                m.id='{$market_id}'";
    // print $sql;
    $results = $mysqli->query($sql);
    if($results && $results->num_rows){
        $row = $results->fetch_assoc();
        $asset1           = $row['asset1'];
        $asset2           = $row['asset2'];
        $asset1_id        = intval($row['asset1_id']);
        $asset2_id        = intval($row['asset2_id']);
        $asset1_divisible = intval($row['asset1_divisible']);
        $asset2_divisible = intval($row['asset2_divisible']);
    } else {
        byeLog("Error while trying to lookup market info");
    }

    if($debug)
        print "\n[{$cnt} / {$total}] Updating market information for {$asset1} / {$asset2}...";

    // Lookup last trade price
    $sql = "SELECT
            m.tx0_index,
            m.tx1_index,
            m.forward_asset_id,
            m.forward_quantity,
            m.backward_asset_id,
            m.backward_quantity
        FROM 
            order_matches m
        WHERE
            ((m.forward_asset_id='{$asset1_id}' AND m.backward_asset_id='{$asset2_id}') OR
             (m.forward_asset_id='{$asset2_id}' AND m.backward_asset_id='{$asset1_id}')) AND
            m.status='completed'
        ORDER BY tx1_index DESC 
        LIMIT 1";
    // print $sql;
    $results = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            $forward      = ($row['forward_asset_id']==$asset1_id) ? $row['forward_quantity'] : $row['backward_quantity'];
            $backward     = ($row['forward_asset_id']==$asset1_id) ? $row['backward_quantity'] : $row['forward_quantity'];
            $forward_qty  = ($asset1_divisible) ? bcmul($forward, '0.00000001',8) : intval($forward);
            $backward_qty = ($asset2_divisible) ? bcmul($backward, '0.00000001',8) : intval($backward);
            $price1_last  = bcdiv($backward_qty, $forward_qty,8);
            $price2_last  = bcdiv($forward_qty, $backward_qty,8);
        }
    } else {
        byeLog("Error while trying to lookup last trade price for {$asset1} / {$asset2}");
    }

    // Lookup trade price exactly 24-hours ago
    $sql = "SELECT
            m.tx0_index,
            m.tx1_index,
            m.forward_asset_id,
            m.forward_quantity,
            m.backward_asset_id,
            m.backward_quantity
        FROM 
            order_matches m
        WHERE
            ((m.forward_asset_id='{$asset1_id}' AND m.backward_asset_id='{$asset2_id}') OR
             (m.forward_asset_id='{$asset2_id}' AND m.backward_asset_id='{$asset1_id}')) AND
            m.status='completed' AND
            m.block_index<='{$block_24hr}'
        ORDER BY tx1_index DESC 
        LIMIT 1";
    // print $sql;
    $results = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            $forward      = ($row['forward_asset_id']==$asset1_id) ? $row['forward_quantity'] : $row['backward_quantity'];
            $backward     = ($row['forward_asset_id']==$asset1_id) ? $row['backward_quantity'] : $row['forward_quantity'];
            $forward_qty  = ($asset1_divisible) ? bcmul($forward, '0.00000001',8) : intval($forward);
            $backward_qty = ($asset2_divisible) ? bcmul($backward, '0.00000001',8) : intval($backward);
            $price1_24hr  = bcdiv($backward_qty, $forward_qty,8);
            $price2_24hr  = bcdiv($forward_qty, $backward_qty,8);
        }
    } else {
        byeLog("Error while trying to lookup last trade price for {$asset1} / {$asset2}");
    }

    // Lookup 'bid' price
    $sql = "SELECT 
                o.get_quantity,
                o.give_quantity,
                o.tx_index
            FROM 
                orders o
            WHERE
                o.get_asset_id='{$asset1_id}' AND
                o.give_asset_id='{$asset2_id}' AND
                o.status='open'
            ORDER BY o.tx_index";
    // print $sql;
    $results = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            while($row = $results->fetch_assoc()){
                $give_quantity = ($asset2_divisible) ? bcmul($row['give_quantity'], '0.00000001',8) : intval($row['give_quantity']);
                $get_quantity  = ($asset1_divisible) ? bcmul($row['get_quantity'],  '0.00000001',8) : intval($row['get_quantity']);
                $price1         = bcdiv($give_quantity, $get_quantity,8);
                $price2         = bcdiv($get_quantity, $give_quantity,8);
                // print "price1={$price1} price2={$price2} tx={$row['tx_index']}\n";
                if($price1==0||$price2==0)
                    continue;
                if($price1_bid==0) $price1_bid = $price1;
                if($price2_bid==0) $price2_bid = $price2;
                if($price1>$price1_bid) $price1_bid = $price1;
                if($price2>$price2_bid) $price2_bid = $price2;
            }
        }
    } else {
        byeLog("Error while trying to lookup ask price");
    }

    // Lookup 'ask' price
    $sql = "SELECT 
                o.get_quantity,
                o.give_quantity,
                o.tx_index
            FROM 
                orders o
            WHERE
                o.get_asset_id='{$asset2_id}' AND
                o.give_asset_id='{$asset1_id}' AND
                o.status='open'
            ORDER BY o.tx_index";
    // print $sql;
    $results = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            while($row = $results->fetch_assoc()){
                $give_quantity = ($asset1_divisible) ? bcmul($row['give_quantity'], '0.00000001',8) : intval($row['give_quantity']);
                $get_quantity  = ($asset2_divisible) ? bcmul($row['get_quantity'],  '0.00000001',8) : intval($row['get_quantity']);
                $price1        = bcdiv($get_quantity, $give_quantity,8);
                $price2        = bcdiv($give_quantity, $get_quantity,8);
                // print "price1={$price1} price2={$price2} tx={$row['tx_index']}\n";
                if($price1==0||$price2==0)
                    continue;
                if($price1_ask==0) $price1_ask = $price1;
                if($price2_ask==0) $price2_ask = $price2;
                if($price1<$price1_ask) $price1_ask = $price1;
                if($price2<$price2_ask) $price2_ask = $price2;
            }
        }
    } else {
        byeLog("Error while trying to lookup ask price");
    }

    // Lookup all order matches in the last 24-hours
    $sql = "SELECT
            m.tx0_index,
            m.tx1_index,
            m.forward_asset_id,
            m.forward_quantity,
            m.backward_asset_id,
            m.backward_quantity
        FROM 
            order_matches m
        WHERE
            ((m.forward_asset_id='{$asset1_id}' AND m.backward_asset_id='{$asset2_id}') OR
             (m.forward_asset_id='{$asset2_id}' AND m.backward_asset_id='{$asset1_id}')) AND
            m.status='completed' AND
            m.block_index>='{$block_24hr}'
        ORDER BY tx1_index DESC";    
        // print $sql;
    $results = $mysqli->query($sql);
    if($results){
        if($results->num_rows){
            while($row = $results->fetch_assoc()){
                $forward      = ($row['forward_asset_id']==$asset1_id) ? $row['forward_quantity'] : $row['backward_quantity'];
                $backward     = ($row['forward_asset_id']==$asset1_id) ? $row['backward_quantity'] : $row['forward_quantity'];
                $forward_qty  = ($asset1_divisible) ? bcmul($forward, '0.00000001',8) : intval($forward);
                $backward_qty = ($asset2_divisible) ? bcmul($backward, '0.00000001',8) : intval($backward);
                $price1       = bcdiv($backward_qty, $forward_qty,8);
                $price2       = bcdiv($forward_qty, $backward_qty,8);
                if($price1_high==0 && $price1_low==0){
                    $price1_high = $price1_24hr;
                    $price1_low  = $price1_24hr;
                }
                if($price2_high==0 && $price2_low==0){
                    $price2_high = $price2_24hr;
                    $price2_low  = $price2_24hr;
                }
                // 24-hour high
                if($price1 > $price1_high) $price1_high = $price1;
                if($price2 > $price2_high) $price2_high = $price2;
                // 24-hour low
                if($price1 < $price1_low) $price1_low = $price1;
                if($price2 < $price2_low) $price2_low = $price2;
                // 24-hour volumes
                $asset1_volume += $forward_qty;
                $asset2_volume += $backward_qty;
            }
        }
    } else {
        byeLog("Error while trying to lookup 24-hour stats");
    }    


    // Calculate price change percentage
    // $price_change = number_format(((($price1_last - $price1_24hr) / $price1_24hr) * 100), 2, '.','');
    $price1_change = 0.00;
    $price2_change = 0.00;
    if($price1_last > 0 && $price1_24hr > 0)
        $price1_change = bcmul(bcdiv(bcsub($price1_last, $price1_24hr,8), $price1_24hr, 8), '100', 2);
    if($price2_last > 0 && $price2_24hr > 0)
        $price2_change = bcmul(bcdiv(bcsub($price2_last, $price2_24hr,8), $price2_24hr, 8), '100', 2);

    // Pass last trade price forward
    if($price1_high==0) $price1_high = $price1_last;
    if($price2_high==0) $price2_high = $price2_last;
    if($price1_low==0)  $price1_low  = $price1_last;
    if($price2_low==0)  $price2_low  = $price2_last;

    // Convert the amounts from floating point to integers
    $price1_ask_int    = bcmul($price1_ask,    '100000000',0);
    $price1_bid_int    = bcmul($price1_bid,    '100000000',0);
    $price1_high_int   = bcmul($price1_high,   '100000000',0);
    $price1_low_int    = bcmul($price1_low,    '100000000',0);
    $price1_24hr_int   = bcmul($price1_24hr,   '100000000',0);
    $price1_last_int   = bcmul($price1_last,   '100000000',0);
    $price2_ask_int    = bcmul($price2_bid,    '100000000',0); // flip bid = ask
    $price2_bid_int    = bcmul($price2_ask,    '100000000',0); // flip ask = bid
    $price2_high_int   = bcmul($price2_high,   '100000000',0);
    $price2_low_int    = bcmul($price2_low,    '100000000',0);
    $price2_last_int   = bcmul($price2_last,   '100000000',0);
    $price2_24hr_int   = bcmul($price2_24hr,   '100000000',0);
    $price1_change_int = bcmul($price1_change,  '100',0);
    $price2_change_int = bcmul($price2_change,  '100',0);
    $asset1_volume_int = bcmul(number_format($asset1_volume, 8,'.',''), '100000000',0);
    $asset2_volume_int = bcmul(number_format($asset2_volume, 8,'.',''), '100000000',0);

    // Update the market info
    $sql = "UPDATE 
                markets 
            SET 
                price1_ask='{$price1_ask_int}',
                price1_bid='{$price1_bid_int}',
                price1_high='{$price1_high_int}',
                price1_low='{$price1_low_int}',
                price1_last='{$price1_last_int}',
                price1_24hr='{$price1_24hr_int}',
                price2_ask='{$price2_ask_int}',
                price2_bid='{$price2_bid_int}',
                price2_high='{$price2_high_int}',
                price2_low='{$price2_low_int}',
                price2_last='{$price2_last_int}',
                price2_24hr='{$price2_24hr_int}',
                price1_change='{$price1_change_int}',
                price2_change='{$price2_change_int}',
                asset1_volume='{$asset1_volume_int}',
                asset2_volume='{$asset2_volume_int}',
                last_updated=now()
            WHERE 
                id='{$market_id}'";
    // if($debug)
    //     print "{$sql}\n";
    $results = $mysqli->query($sql);
    if(!$results){
        bye('Error when trying to update market information');
    }

    // Report time to process block
    $time = $profile->finish();
    if($debug)
        print " Done [{$time}ms]\n";

    // Print out an update on the current state of the market
    if($debug){
        print "price1_ask    : {$price1_ask}\n";
        print "price1_bid    : {$price1_bid}\n";
        print "price1_high   : {$price1_high}\n";
        print "price1_low    : {$price1_low}\n";
        print "price1_last   : {$price1_last}\n";
        print "price1_24hr   : {$price1_24hr}\n";
        print "price2_ask    : {$price2_ask}\n";
        print "price2_bid    : {$price2_bid}\n";
        print "price2_high   : {$price2_high}\n";
        print "price2_low    : {$price2_low}\n";
        print "price2_last   : {$price2_last}\n";
        print "price2_24hr   : {$price2_24hr}\n";
        print "price1_change : {$price1_change}\n";
        print "price2_change : {$price2_change}\n";
        print "asset1_volume : {$asset1_volume}\n";
        print "asset2_volume : {$asset2_volume}\n";
        // print "---\n";
        // print "price_ask_int     : {$price_ask_int}\n";
        // print "price_bid_int     : {$price_bid_int}\n";
        // print "price_high_int    : {$price_high_int}\n";
        // print "price_low_int     : {$price_low_int}\n";
        // print "price_24hr_int    : {$price_24hr_int}\n";
        // print "price_last_int    : {$price_last_int}\n";
        // print "price_change_int  : {$price_change_int}\n";
        // print "asset1_volume_int : {$asset1_volume_int}\n";
        // print "asset2_volume_int : {$asset2_volume_int}\n";
    }

}



// Handle getting dispensers information, including current pricing 
function getDispenserInfo($tx_hash){
    global $mysqli;
    $whereSql = "t.hash='{$tx_hash}";
    // Handle passing tx_index instead of tx_hash
    if(is_numeric($tx_hash))
        $whereSql = "d.tx_index='{$tx_hash}'";
    // Lookup info on the dispenser
    $sql = "SELECT 
                d.tx_index,
                d.block_index,
                d.give_quantity,
                d.escrow_quantity,
                d.give_remaining,
                d.satoshirate,
                d.status,
                a1.asset,
                a1.asset_longname,
                a1.divisible,
                t.hash as tx_hash,
                a2.address as source,
                b.block_time as timestamp,
                d.oracle_address_id
            FROM 
                dispensers d, 
                blocks b,
                assets a1,
                index_addresses a2,
                index_transactions t
            WHERE 
                b.block_index=d.block_index AND
                t.id=d.tx_hash_id AND
                a1.id=d.asset_id AND
                a2.id=d.source_id AND
                {$whereSql}
            LIMIT 1";
    $results = $mysqli->query($sql);
    if($results && $results->num_rows){
        $row = (object) $results->fetch_assoc();
        // Handle oracled dispensers by looking up oracle info and returning pricing info
        if(isset($row->oracle_address_id)){
            // Get the oracle address
            $results2 = $mysqli->query("SELECT address FROM index_addresses WHERE id={$row->oracle_address_id}");
            if($results2){
                $row2 = (object) $results2->fetch_assoc();
                $row->oracle_address = $row2->address;
            }
            // Get the oracle info
            $results3 = $mysqli->query("SELECT b1.value, b1.text, b1.block_index, b2.block_time FROM broadcasts b1, blocks b2 WHERE b1.block_index=b2.block_index AND b1.source_id='{$row->oracle_address_id}' AND b1.status='valid' ORDER by b1.tx_index DESC LIMIT 1");
            if($results3){
                $row3 = (object) $results3->fetch_assoc();
                $row->oracle_price              = number_format($row3->value,2,'.','');
                $row->oracle_price_last_updated = $row3->block_index;
                $row->oracle_price_block_time   = $row3->block_time;
                $row->fiat_price                = number_format(($row->satoshirate * 0.01),2,'.','');
                $sat_price                      = ($row->oracle_price==0) ? 0 : ceil(((1 / $row->oracle_price) * $row->fiat_price) * 100000000);
                $row->satoshi_price             = strval($sat_price);
                $row->fiat_unit                 = explode('-',$row3->text)[1]; // Extract Fiat from BTC-XXX value
            }
        }
        unset($row->oracle_address_id);
    }
    // var_dump($row);
    return $row;
}


?>