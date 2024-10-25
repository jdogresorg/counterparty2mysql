#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_block_hashes.php
 * 
 * Script to loop through all blocks and validate / fix block hashes
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

// Lookup all Blocks and hashes
$sql = "SELECT
            b.block_index,
            t1.hash as block_hash,
            t2.hash as ledger_hash,
            t3.hash as txlist_hash,
            t4.hash as messages_hash
        FROM
            blocks b,
            index_transactions t1,
            index_transactions t2,
            index_transactions t3,
            index_transactions t4
        WHERE
            t1.id=b.block_hash_id AND        
            t2.id=b.ledger_hash_id AND
            t3.id=b.txlist_hash_id AND
            t4.id=b.messages_hash_id 
        ORDER BY b.block_index DESC LIMIT 1";
// print $sql;
$results = $mysqli->query($sql);
if($results && $results->num_rows){
    $cnt    = 0;
    $errors = 0;
    $fixed  = 0;
    while($row = $results->fetch_assoc()){
        $cnt++;
        $block_index = (int) $row['block_index'];
        print "[{$cnt} / {$errors} / ${fixed}] checking current hashes of {$block_index}...\n";
        var_dump($counterparty);
        $data = $counterparty->execute('get_block_info', array('block_index' => $block_index));

        if($row['block_hash']!=$data['block_hash'] || $row['txlist_hash']!=$data['txlist_hash'] || $row['ledger_hash']!=$data['ledger_hash'] || $row['messages_hash']!=$data['messages_hash']){
            $errors++;
            $block_hash_id    = createTransaction($data['block_hash']);
            $txlist_hash_id   = createTransaction($data['txlist_hash']);
            $ledger_hash_id   = createTransaction($data['ledger_hash']);
            $messages_hash_id = createTransaction($data['messages_hash']);
            print "[{$cnt} / {$errors} / ${fixed}] Fixing block #{$block_index}\n";
            $sql = "UPDATE 
                        blocks 
                    SET 
                        block_hash_id={$block_hash_id},
                        txlist_hash_id={$txlist_hash_id},
                        ledger_hash_id={$ledger_hash_id},
                        messages_hash_id={$messavges_hash_id}
                    WHERE 
                        block_index={$block_index}";
            $results2 = $mysqli->query($sql);
            if($results2){
                $fixed++;
            } else {
                bye('Error while trying to update block hashes');
            }
        }
    }
} else {
    bye('Error while trying to lookup list of blocks');
}

print "Checked {$cnt} blocks, encountered {$errors} errors, and fixed {$fixed} of those errors\n";