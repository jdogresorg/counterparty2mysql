<?php
/*********************************************************************
 * functions.php - Common functions
 ********************************************************************/

// Handle creating a lockfile and bailing out if lock file already exists (ie, an instance is already running)
function createLockFile($file){
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
function removeLockFile($file){
    $lockFile = ($file!='') ? $file : LOCKFILE;
    if(file_exists($lockFile))
        unlink($lockFile);
}


// Log/Print an error and exit
function byeLog($error, $log){
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
function initDB($hostname, $username, $password, $database, $log){
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
function initCP($hostname, $username, $password, $log){
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


// Convert asset name into a numeric asset_id
// Big thanks to Joe looney <hello@joelooney.org> for pointing me towards his similar javascript function
function getAssetId($asset){
    $id = false;
    if($asset == 'XCP'){
        $id =  1;
    } else if(substr($asset,0,1)=='A'){
        $id = intval(substr($asset,1));
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
function createBlock( $block_index ){
    global $mysqli, $counterparty;
    $data = (object) $counterparty->execute('get_block_info', array('block_index' => $block_index));
    $data->block_hash_id          = createTransaction($data->block_hash);
    $data->previous_block_hash_id = createTransaction($data->previous_block_hash);
    $data->ledger_hash_id         = createTransaction($data->ledger_hash);
    $data->txlist_hash_id         = createTransaction($data->txlist_hash);
    $data->messages_hash_id       = createTransaction($data->messages_hash);
    $results = $mysqli->query("SELECT id FROM blocks WHERE block_index='{$data->block_index}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            $id  = $row['id'];
            $sql = "UPDATE blocks SET
                       block_index            = '{$data->block_index}',
                       block_time             = '{$data->block_time}',
                       block_hash_id          = '{$data->block_hash_id}',
                       previous_block_hash_id = '{$data->previous_block_hash_id}',
                       ledger_hash_id         = '{$data->ledger_hash_id}',
                       txlist_hash_id         = '{$data->txlist_hash_id}',
                       messages_hash_id       = '{$data->messages_hash_id}',
                       difficulty             = '{$data->difficulty}'
                    WHERE
                        id='{$id}'";
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
        byeLog('Error while trying to lookup block record');
    }
}


// Create/Update records in the 'assets' table and return record id
function createAsset( $asset ){
    global $mysqli, $counterparty;
    // Get current information on this asset
    $info = $counterparty->execute('get_asset_info', array('assets' => array($asset)));
    // Create data object using asset info (if any)
    $data                 = (count($info)) ? (object) $info[0] : (object) [];
    $data->asset_id       = getAssetId($asset);
    $data->issuer_id      = createAddress($data->issuer);
    $data->owner_id       = createAddress($data->owner);
    $data->divisible      = ($data->divisible) ? 1 : 0;  // convert to boolean
    $data->locked         = ($data->locked) ? 1 : 0 ;    // convert to boolean
    $data->description    = $mysqli->real_escape_string($data->description);
    $data->asset_longname = $mysqli->real_escape_string($data->asset_longname);
    // Set asset type (1=Named, 2=Numeric, 3=Subasset, 4=Failed issuance)
    $data->type           = (substr($asset,0,1)=='A') ? 2 : 1;
    if($data->asset_longname!='')
        $data->type = 3;
    if(count($info)==0)
        $data->type = 4;
    // Check if this asset already exists
    $results = $mysqli->query("SELECT id FROM assets WHERE asset='{$asset}' LIMIT 1");
    if($results){
        if($results->num_rows){
            // Update asset information
            $row = $results->fetch_assoc();
            $id  = $row['id'];
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
                byeLog('Error while trying to update asset record for ' . $asset);
            }
        } else {
            // Create asset information
            $sql = "INSERT INTO assets (asset_id, asset, asset_longname, type, divisible, description, issuer_id, locked, owner_id, supply) values (
                '{$data->asset_id}',
                '{$asset}',
                '{$data->asset_longname}',
                '{$data->type}',
                '{$data->divisible}',
                '{$data->description}',
                '{$data->issuer_id}',
                '{$data->locked}',
                '{$data->owner_id}',
                '{$data->supply}')";
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
function createAddress( $address ){
    global $mysqli;
    if(!isset($address) || $address=='')
        return;
    $address = $mysqli->real_escape_string($address);
    $results = $mysqli->query("SELECT id FROM addresses WHERE address='{$address}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $results = $mysqli->query("INSERT INTO addresses (`address`) values ('{$address}')");
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create address record');
            }
        }
    } else {
        byeLog('Error while trying to lookup address record');
    }
}


// Create records in the 'transactions' table and return record id
function createTransaction( $hash ){
    global $mysqli;
    if(!isset($hash) || $hash=='')
        return;
    $hash    = $mysqli->real_escape_string($hash);
    $results = $mysqli->query("SELECT id FROM transactions WHERE `hash`='{$hash}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $results = $mysqli->query("INSERT INTO transactions (`hash`) values ('{$hash}')");
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create transaction record');
            }
        }
    } else {
        byeLog('Error while trying to lookup transaction record');
    }
}


// Create records in the 'contract_ids' table and return record id
function createContract( $contract ){
    global $mysqli;
    if(!isset($contract) || $contract=='')
        return;
    $contract = $mysqli->real_escape_string($contract);
    $results  = $mysqli->query("SELECT id FROM contract_ids WHERE contract_id='{$contract}' LIMIT 1");
    if($results){
        if($results->num_rows){
            $row = $results->fetch_assoc();
            return $row['id'];
        } else {
            $results = $mysqli->query("INSERT INTO contract_ids (contract_id) values ('{$contract}')");
            if($results){
                return $mysqli->insert_id;
            } else {
                byeLog('Error while trying to create contract_id record');
            }
        }
    } else {
        byeLog('Error while trying to lookup contract_id record');
    }
}


// Create/Update records in the 'balances' table
function updateAddressBalance( $address, $asset_list ){
    global $mysqli, $counterparty, $addresses, $assets;
    // Lookup any balance for this address and asset
    $filters  = array(array('field' => 'address', 'op' => '==', 'value' => $address),
                      array('field' => 'asset',   'op' => 'IN', 'value' => $asset_list));
    $balances = $counterparty->execute('get_balances', array('filters' => $filters, 'filterop' => "AND"));
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


?>