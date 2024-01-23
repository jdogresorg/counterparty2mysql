#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_asset_descriptions.php
 * 
 * Script to fix issuance descriptions
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

require_once(__DIR__ . '/../../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Lookup all assets
$sql = "SELECT
            a.asset,
            a.description
        FROM
            assets a
        ORDER BY a.asset";
print $sql . "\n";
$results = $mysqli->query($sql);
if($results && $results->num_rows){
    $cnt    = 0;
    $errors = 0;
    $fixed  = 0;
    while($row = $results->fetch_assoc()){
        $cnt++;
        print "[{$cnt} / {$errors} / ${fixed}] checking asset {$row['asset']}...\n";

        $result = $counterparty->execute('get_asset_info', array('asset' => $row['asset']));
        foreach($result as $data){
            if($row['description']!=$data['description'] && $row['asset']==$data['asset']){
                $errors++;
                // Replace 4-byte UTF-8 characters (fixes issue with breaking SQL queries) 
                $desc = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $data['description']);
                // print "orig \t: {$data['description']}\n";
                // print "final \t: {$desc}\n";
                // print "[{$cnt} / {$errors} / ${fixed}] Fixing issuance {$row['tx_index']}\n";
                $description = $mysqli->real_escape_string($desc);
                $sql = "UPDATE assets SET description='{$description}' WHERE asset='{$row['asset']}'";
                print $sql . "\n";
                $results2 = $mysqli->query($sql);
                if($results2){
                    $fixed++;
                } else {
                    bye('Error while trying to update asset description');
                }
            }
        }
    }
} else {
    bye('Error while trying to lookup list of assets');
}

print "Checked {$cnt} assets, encountered {$errors} errors, and fixed {$fixed} of those errors\n";