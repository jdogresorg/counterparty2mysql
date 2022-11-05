#!/usr/bin/env php
<?php
/*********************************************************************
 * update_asset_prices.php 
 * 
 * Handles updating asset prices
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);

$assets = array(
    'XCP',
    // 'A10036924099664623395'
);

$results = $mysqli->query("SELECT asset FROM assets WHERE btc_price IS NULL ORDER BY asset");
if($results){
    while($row = $results->fetch_assoc()){
        array_push($assets,$row['asset']);
    }
}

foreach($assets as $asset){
    print "Updating prices for {$asset}...\n";
    updateAssetPrice($asset);
}
