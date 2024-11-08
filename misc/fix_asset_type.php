#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_asset_type.php 
 * 
 * Handles correctly setting asset type
 * 1=Named, 2=Numeric, 3=Subasset, 4=Failed issuance, 5=Numeric Subasset
 * --testnet    Load data from testnet
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';
$block   = (isset($args['block'])) ? $args['block'] : false;  

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);


// Get list of all assets
print "Getting list of assets...\n";
// Get highest block_index
$sql = "SELECT
            type,
            asset,
            asset_longname
        FROM
            assets";
print $sql;
$results = $mysqli->query($sql);
if($results){
    $count = 0;
    $fixed = 0;
    $total = $results->num_rows;
    while($row = $results->fetch_assoc()){
        $count++;
        // Determine asset type
        $asset          = $row['asset'];
        $asset_longname = $row['asset_longname'];
        $type           = (substr($asset,0,1)=='A') ? 2 : 1;
        if(isset($asset_longname) && $asset_longname!='')
            $type = (substr($asset_longname,0,1)=='A') ? 5 : 3;
        if($type!=$row['type']){
            $fixed++;
            $sql = "UPDATE assets SET type='{$type}' WHERE asset='{$asset}'";
            $results2 = $mysqli->query($sql);
        }
        print "[{$count} / {$fixed} / {$total}] Processing asset {$asset} - {$asset_longname}...\n";
    }
} else {
    bye('Error looking up asset list');
}

