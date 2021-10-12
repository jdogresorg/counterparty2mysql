#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_address_balances.php 
 * 
 * Handles updating address balances for a given address
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

$addresses = array('1Pk5iSNoUDcTJiHLJZ4EuoG6nas6UB4NC2');

foreach($addresses as $address){

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
        // Update balance
        $results = $mysqli->query("UPDATE balances SET quantity='{$info->quantity}' WHERE address_id='{$address_id}' AND asset_id='{$asset_id}'");
        if(!$results)
            bye('Error while trying to update balance record for ' . $address . ' - ' . $info->asset);
    }
}
