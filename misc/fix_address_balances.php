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

$total = count($addresses);
print "Updating {$total} addresses...\n";

// Loop through addresses and update each
foreach($addresses as $address){
    $cnt++;
    print "[{$cnt} / {$total}] Updating balances for {$address}...\n";

    // Ignore any multi-sig addresses (address1-address2)
    if(str_contains($address, '-'))
        continue;

    updateAddressBalances($address);
}