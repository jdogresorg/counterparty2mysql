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
    // 'A16471286040967332108'
);

// Get list of all assets from orders
$sql = "SELECT 
            a1.asset as forward_asset,
            a2.asset as backward_asset
        FROM 
            order_matches m,
            assets a1,
            assets a2
        WHERE
            a1.id=m.forward_asset_id AND
            a2.id=m.backward_asset_id AND
            m.status='completed'";
$results = $mysqli->query($sql);
if($results){
    while($row = $results->fetch_assoc()){
        if(!in_array($row['forward_asset'], $assets))
            array_push($assets,$row['forward_asset']);
        if(!in_array($row['forward_asset'], $assets))
            array_push($assets,$row['forward_asset']);
    }
}

// Get list of all assets from dispenses
$sql = "SELECT 
            a.asset
        FROM 
            dispenses d,
            assets a
        WHERE
            a.id=d.asset_id";
$results = $mysqli->query($sql);
if($results){
    while($row = $results->fetch_assoc()){
        if(!in_array($row['asset'], $assets))
            array_push($assets,$row['asset']);
    }
}


$total = count($assets);
$cnt   = 0;
sort($assets);
foreach($assets as $idx => $asset){
    $timer = new Profiler();
    $cnt++;
    print "[{$cnt}/{$total}] Updating prices for {$asset}...";
    updateAssetPrice($asset);
    // Report time to process block
    $time = $timer->finish();
    print " Done [{$time}ms]\n";
}
