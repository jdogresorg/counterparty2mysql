#!/usr/bin/env php
<?php
/*********************************************************************
 * fix_dropped_balances.php
 *
 * One-time repair for balances that the indexer silently dropped while
 * the paginated-fetch bug was live (getAddressBalances() truncated at
 * the API's 1000-row page limit, so any address holding more than 1000
 * assets could have balances past the first page deleted-but-not-
 * reinserted). See includes/counterparty-v2-api.php::requestAll().
 *
 * Strategy: find every address whose materialized `balances` no longer
 * matches its (credits - debits) ledger, then rebuild that address's
 * balances in full from the (now paginated) API via updateAddressBalances().
 * No raw balance SQL is written - the rebuild path is the same one the
 * live parser uses, so the fix is identical to a normal re-parse.
 *
 * Usage:
 *   php fix_dropped_balances.php --address=1JJP...        # repair one address
 *   php fix_dropped_balances.php --global                 # find + repair all drifted addresses
 *   php fix_dropped_balances.php --global --dry-run       # list drifted addresses, change nothing
 *   php fix_dropped_balances.php --global --limit=500     # cap how many addresses to repair
 *   --testnet                                             # operate on testnet
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::", "address::", "global", "dry-run", "limit::"));
$testnet = isset($args['testnet']);
$runtype = ($testnet) ? 'testnet' : 'mainnet';   // config.php gates DB/CP constants on $runtype
$address = $args['address'] ?? null;
$global  = isset($args['global']);
$dryRun  = isset($args['dry-run']);
$limit   = isset($args['limit']) ? intval($args['limit']) : 0;

if(!$address && !$global){
    fwrite(STDERR, "Refusing to run unbounded. Pass --address=X or --global.\n");
    exit(2);
}

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Helper: how many balance rows the materialized table currently holds for an
// address (so the operator can see rows being restored by the rebuild).
function balanceRowCount($address){
    global $mysqli;
    $addr = $mysqli->real_escape_string($address);
    $sql  = "SELECT COUNT(*) AS c
             FROM balances b
             JOIN index_addresses a ON a.id=b.address_id
             WHERE a.address='{$addr}' AND b.quantity>0";
    $res  = $mysqli->query($sql);
    return ($res && ($row = $res->fetch_assoc())) ? intval($row['c']) : 0;
}

// Build the list of addresses to repair
$addresses = array();

if($address){
    $addresses[$address] = true;
} else {
    // Find every address where the materialized balance for some asset no
    // longer equals (SUM(credits) - SUM(debits)). Anchored on credits because
    // a dropped balance is always backed by ledger credits, so credits is a
    // superset of the affected key space. Excludes UTXO-attached balances
    // (address_id=0) and BTC/XCP sentinel rows handled elsewhere via asset_id>1.
    fwrite(STDERR, "Scanning ledger vs materialized balances for drifted addresses (this is slow)...\n");
    $sql = "
        SELECT DISTINCT addr.address
        FROM (
            SELECT address_id, asset_id, SUM(quantity) AS s
            FROM credits WHERE address_id > 0 GROUP BY address_id, asset_id
        ) c
        LEFT JOIN (
            SELECT address_id, asset_id, SUM(quantity) AS s
            FROM debits WHERE address_id > 0 GROUP BY address_id, asset_id
        ) d ON d.address_id=c.address_id AND d.asset_id=c.asset_id
        LEFT JOIN (
            SELECT address_id, asset_id, SUM(quantity) AS s
            FROM balances WHERE address_id > 0 GROUP BY address_id, asset_id
        ) b ON b.address_id=c.address_id AND b.asset_id=c.asset_id
        JOIN index_addresses addr ON addr.id=c.address_id
        WHERE c.asset_id > 1
          AND (c.s - COALESCE(d.s,0)) != COALESCE(b.s,0)
    ";
    $t0 = microtime(true);
    $results = $mysqli->query($sql);
    fwrite(STDERR, sprintf("Scan took %.1fs\n", microtime(true) - $t0));
    if(!$results){
        fwrite(STDERR, "SQL error: " . $mysqli->error . "\n");
        exit(1);
    }
    while($row = $results->fetch_assoc())
        $addresses[$row['address']] = true;
}

ksort($addresses);
$total = count($addresses);

if($total == 0){
    print "No drifted addresses found. Nothing to repair.\n";
    exit(0);
}

print ($dryRun ? "Found " : "Repairing ") . "{$total} address(es)" . ($limit ? " (limited to {$limit})" : "") . ":\n";

$cnt = 0;
foreach($addresses as $addr => $value){
    if($limit && $cnt >= $limit)
        break;
    $cnt++;

    // Ignore any multi-sig addresses (address1-address2)
    if(str_contains($addr, '-')){
        print "[{$cnt}/{$total}] {$addr} - skipping multisig\n";
        continue;
    }

    if($dryRun){
        print "[{$cnt}/{$total}] {$addr} (rows now: " . balanceRowCount($addr) . ")\n";
        continue;
    }

    $before = balanceRowCount($addr);
    // Full rebuild: passing no asset list deletes ALL balances for the address
    // (address- and utxo-attached) and reinserts the complete set from the API.
    updateAddressBalances($addr);
    $after = balanceRowCount($addr);
    printf("[%d/%d] %s  rows %d -> %d%s\n", $cnt, $total, $addr, $before, $after,
        ($after > $before ? "  (restored " . ($after - $before) . ")" : ""));
}

print "Done.\n";
