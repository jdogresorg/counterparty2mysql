#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_address_balances.php 
 * 
 * Handles updating address balances for a given address
 * --testnet    Load data from testnet
 * --block=#    Load addresses after a given block
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::","block::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';
$block   = (isset($args['block'])) ? $args['block'] : false;  

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);


// Define list of addresses
$addresses = array();

// Add a specific address to the address list to fix
// array_push($addresses, '1GJYJoRqR16AtCUFDkajtioAvXMCoZAN9g');

// Build out a list of addresses used in blocks
if($block){
    print "Getting list of addresses...\n";
    // Get highest block_index
    $sql = "SELECT
                DISTINCT(a.address) as address
            FROM
                credits c,
                index_addresses a
            WHERE
                a.id=c.address_id AND
                c.block_index>='{$block}'
            UNION
            SELECT
                DISTINCT(a.address) as address
            FROM
                debits d,
                index_addresses a
            WHERE
                a.id=d.address_id AND
                d.block_index>='{$block}'";
    // print $sql;
    $results = $mysqli->query($sql);
    if($results){
        while($row = $results->fetch_assoc()){
            if(!in_array($row['address'],$addresses))
                array_push($addresses,$row['address']);
        }
    } else {
        bye('Error looking up addresses associated with blocks');
    }
}

$cnt = count($addresses);
print "Updating {$cnt} addresses...\n";

foreach($addresses as $address){

    print "Updating balances for {$address}...\n";
    // Lookup the address_id
    $results = $mysqli->query("SELECT id FROM index_addresses WHERE address='{$address}'");
    if($results && $results->num_rows==1){
        $address_id = $results->fetch_assoc()['id'];
    } else {
        bye('Error looking up address id');
    }

    // Lookup any balance for this address and asset
    $filters  = array(array('field' => 'address', 'op' => '==', 'value' => $address));
    $offset   = 0;
    $data     = $counterparty->execute('get_balances', array('filters' => $filters));
    $balances = $data;
    
    // Loop until we get all balances
    while(count($data)==1000){
        $data     = $counterparty->execute('get_balances', array('filters' => $filters, 'offset' => count($balances)));
        $balances = array_merge($balances, $data);
    }

    // Loop through balances
    foreach($balances as $info){
        $info    = (object) $info;
        // Lookup asset id
        $results = $mysqli->query("SELECT id FROM assets WHERE asset='{$info->asset}'");
        if($results && $results->num_rows==1){
            $asset_id = $results->fetch_assoc()['id'];
        } else {
            bye('Error looking up asset id');
        }
        // Check if a balance record already exists for this asset
        $results = $mysqli->query("SELECT id FROM balances WHERE address_id='{$address_id}' AND asset_id='{$asset_id}'");
        if($results){
            if($results->num_rows){
                $sql = "UPDATE balances SET quantity='{$info->quantity}' WHERE address_id='{$address_id}' AND asset_id='{$asset_id}'";
            } else {
                $sql = "INSERT INTO balances (quantity, address_id, asset_id) values ('{$info->quantity}', '{$address_id}', '{$asset_id}')";
            }
            $results = $mysqli->query($sql);
            if(!$results)
                bye('Error while trying to update balance record for ' . $address . ' - ' . $info->asset);
        } else {
            bye('Error while trying to check for balance record for ' . $address . ' - ' . $info->asset);
        }

    }
}
