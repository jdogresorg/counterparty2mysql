#!/usr/bin/env php
<?php
/*********************************************************************
 * update_market_info.php 
 * 
 * Script to handle creating/updating counterparty DEX market pairs
 * Note: BCMath is used extensively to prevent rounding issues
 * 
 * Command line arguments :
 * --testnet    Load data from testnet
 * --block=#    Load data for given block
 * --single     Load single block
 * --queue      Queue the market updates and process all at once
 * --update     Update all existing markets with current information
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::", "block::", "single::", "queue::","update::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';
$block   = (is_numeric($args['block'])) ? intval($args['block']) : false;
$single  = (isset($args['single'])) ? true : false;  
$queue   = (isset($args['queue'])) ? true : false;  
$update  = (isset($args['update'])) ? true : false;  
$debug   = true;

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database connection
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);

// Get first block index from messages table
if(!$block)
    $block = getFirstMessageBlock();

// If not parsing a single block, get the highest block index we have parsed so far
$current = ($single) ? $block : getLastMessageBlock();

// array of markets
$markets = array(); 

// Get the block_index 24 hours ago
$block_24hr = get24HourBlockIndex();

// Handle getting a list of all of the existing markets
if($update){
    $sql = "SELECT
        a1.asset as asset1,
        a2.asset as asset2
    FROM 
        markets m,
        assets a1,
        assets a2
    WHERE
        a1.id=m.asset1_id AND
        a2.id=m.asset2_id";
    $results = $mysqli->query($sql);
    if($results){
        while($row = $results->fetch_assoc()){
            $row = (object) $row;
            if(!$markets[$row->asset2 . '|' . $row->asset1])
                $markets[$row->asset1 . '|' . $row->asset2] = 1;
        }
    } else {
        bye('Error while looking up all markets');
    }
} else {
    // Loop through blocks and process them
    while($block <= $current){
        $timer = new Profiler();
        print "processing block {$block}...";

        // Get any order messages for this block
        $messages = [];
        $results = $mysqli->query("SELECT * FROM messages WHERE block_index='{$block}' AND category='orders'");
        if($results && $results->num_rows>0){
            while($row = $results->fetch_assoc())
                array_push($messages, $row);
        } 

        // Reset the markets array in between blocks
        if(!$queue)
            $markets = array(); 

        // Loop through messages and detect any DEX market changes
        foreach($messages as $message){
            $msg = (object) $message;
            $obj = json_decode($msg->bindings);
            $market = false;
            if($msg->category='orders'){
                $sql = "SELECT
                            a1.asset as asset1,
                            a2.asset as asset2
                        FROM
                            orders o,
                            assets a1,
                            assets a2,
                            index_transactions t
                        WHERE
                            t.id=o.tx_hash_id AND
                            a1.id=o.give_asset_id AND
                            a2.id=o.get_asset_id AND
                            t.hash='{$obj->tx_hash}'";
                $results = $mysqli->query($sql);
                if($results){
                    if($results->num_rows){
                        $row = (object) $results->fetch_assoc();
                        if(!$markets[$row->asset2 . '|' . $row->asset1])
                            $markets[$row->asset1 . '|' . $row->asset2] = 1;
                    }
                }
            }
        }

        // If we have any market changes, update the markets
        if(!$queue && count($markets))
            createUpdateMarkets($markets);

        // Report time to process block
        $time = $timer->finish();
        print " Done [{$time}ms]\n";

        // Bail out of user only wants to parse a single block
        if($single){
            print "detected single block... bailing out\n";
            break;
        }

        // Increase block before next loop
        $block++;
    }
}


// Loop through any markets and update them 
if(($queue || $update) && count($markets)){
    print "Updating " . count($markets) . " Markets...\n";
    createUpdateMarkets($markets);
}


