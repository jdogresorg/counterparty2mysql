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


// Convert asset name into a numeric asset_id
// Big thanks to Joe looney <hello@joelooney.org> for pointing me towards his similar javascript function
function getAssetId($asset=null){
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
    $description          = substr($data->description,0,250); // Truncate to 250 chars
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


// Create records in the 'transactions' table and return record id
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
function createTxIndex( $tx_index=null, $tx_type=null, $tx_hash_id=null ){
    global $mysqli;
    $tx_index = $mysqli->real_escape_string($tx_index);
    $type_id  = createTxType($tx_type);
    $results  = $mysqli->query("SELECT type_id FROM index_tx WHERE tx_index='{$tx_index}' LIMIT 1");
    if($results){
        if($results->num_rows==0){
            $results = $mysqli->query("INSERT INTO index_tx (tx_index, tx_hash_id, type_id) values ('{$tx_index}','{$tx_hash_id}', '{$type_id}')");
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
    if($asset_id<=2)
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
                m.tx1_index DESC 
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
}

?>