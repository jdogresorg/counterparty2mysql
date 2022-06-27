#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_issuance_descriptions.php
 * 
 * Script to fix issuances
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

// Lookup all open dispensers
$sql = "SELECT
            a.asset, 
            i.asset_id,
            i.tx_index,
            i.block_index,
            t.hash as tx_hash,
            i.description
        FROM
            issuances i,
            index_transactions t,
            assets a
        WHERE
            i.tx_hash_id = t.id AND
            i.asset_id = a.id
        ORDER BY i.tx_index DESC";
print $sql . "\n";
$results = $mysqli->query($sql);
if($results && $results->num_rows){
    $cnt    = 0;
    $errors = 0;
    $fixed  = 0;
    while($row = $results->fetch_assoc()){
        $cnt++;
        print "[{$cnt} / {$errors} / ${fixed}] checking issuance tx #{$row['tx_index']}...\n";
        $filters = array(
                        array(  'field' => 'tx_index', 
                                'op'    => '==', 
                                'value' => $row['tx_index']),
                        // array(  'field' => 'block_index', 
                        //         'op'    => '==', 
                        //         'value' => $block)
                    );
        $result = $counterparty->execute('get_issuances', array('filters' => $filters));
        foreach($result as $data){
            if($row['description']!=$data['description'] && $row['asset']==$data['asset']){
                $errors++;
                // Remove all characters except alphanumerics, spaces, and characters valid in urls (:/?=;)
                // Fixes issue where special characters in description break SQL queries (temp fix)
                $desc = preg_replace("/[^[:alnum:][:space:]\:\/\.\?\=\&\;]/u", '', $data['description']);
                // print "orig \t: {$data['description']}\n";
                // print "final \t: {$desc}\n";
                // print "[{$cnt} / {$errors} / ${fixed}] Fixing issuance {$row['tx_index']}\n";
                $description = $mysqli->real_escape_string($desc);
                $sql = "UPDATE issuances SET description='{$description}' WHERE asset_id='{$row['asset_id']}' AND tx_index={$row['tx_index']}";
                print $sql . "\n";
                $results2 = $mysqli->query($sql);
                if($results2){
                    $fixed++;
                } else {
                    bye('Error while trying to update issuance description');
                }
            }
        }
    }
} else {
    bye('Error while trying to lookup list of issuances');
}

print "Checked {$cnt} issuances, encountered {$errors} errors, and fixed {$fixed} of those errors\n";