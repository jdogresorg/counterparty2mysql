#!/usr/bin/env php
<?php
/*********************************************************************
 * verify_balances.php
 *
 * Independent sanity check that the materialized `balances` table agrees
 * with the authoritative counterparty-core API. Intended to be run after
 * fix_dropped_balances.php to confirm repaired addresses now match chain.
 *
 * For each address it compares the ADDRESS-attached holdings (utxo = null)
 * per asset: chain quantity (from the paginated API) vs the quantity stored
 * in our balances table. Any per-asset difference is reported. This directly
 * catches the dropped-balance class of bug (e.g. XCP reading 0 when the chain
 * shows a real balance).
 *
 * Usage:
 *   php verify_balances.php --address=1JJP...      # verify one address
 *   php verify_balances.php --sample=25            # verify the 25 addresses holding the most assets
 *   php verify_balances.php --sample=25 --verbose  # also print addresses that match
 *   --testnet                                      # operate on testnet
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

$args    = getopt("", array("testnet::", "address::", "sample::", "verbose"));
$testnet = isset($args['testnet']);
$runtype = ($testnet) ? 'testnet' : 'mainnet';   // config.php gates DB/CP constants on $runtype
$address = $args['address'] ?? null;
$sample  = isset($args['sample']) ? intval($args['sample']) : 0;
$verbose = isset($args['verbose']);

if(!$address && !$sample){
    fwrite(STDERR, "Pass --address=X or --sample=N.\n");
    exit(2);
}

require_once(__DIR__ . '/../includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Compare two non-negative decimal integer strings without bcmath/float
// (quantities are raw satoshis and can exceed the 64-bit signed range).
// Returns -1, 0, or 1.
function intStrCmp($a, $b){
    $a = ltrim((string)$a, '0'); $b = ltrim((string)$b, '0');
    if($a === '') $a = '0';
    if($b === '') $b = '0';
    if(strlen($a) !== strlen($b))
        return (strlen($a) < strlen($b)) ? -1 : 1;
    return strcmp($a, $b);
}

// Return the address-attached holdings recorded in OUR balances table,
// keyed by asset name => quantity (raw satoshis).
function dbHoldings($address){
    global $mysqli;
    $addr = $mysqli->real_escape_string($address);
    $sql  = "SELECT a.asset, b.quantity
             FROM balances b
             JOIN assets a            ON a.id=b.asset_id
             JOIN index_addresses ad  ON ad.id=b.address_id
             WHERE ad.address='{$addr}' AND b.utxo_id=0 AND b.quantity>0";
    $out  = array();
    $res  = $mysqli->query($sql);
    if($res)
        while($row = $res->fetch_assoc())
            $out[$row['asset']] = $row['quantity'];
    return $out;
}

// Return the address-attached holdings the chain reports for this address,
// keyed by asset name => quantity (raw satoshis). Uses the paginated API.
function chainHoldings($address){
    global $counterparty;
    $data = $counterparty->getAddressBalances($address);
    $out  = array();
    if(is_array($data)){
        foreach($data as $bal){
            // Only address-attached balances (skip UTXO-attached)
            if(isset($bal->utxo) && !is_null($bal->utxo))
                continue;
            // One address-attached row per asset, so assign directly
            $out[$bal->asset] = (string)$bal->quantity;
        }
    }
    return $out;
}

// Build the list of addresses to verify
$addresses = array();
if($address){
    $addresses[] = $address;
} else {
    // Sample the addresses holding the most assets - the population the
    // truncation bug could affect (>1000 assets), most likely to surface
    // any remaining drift.
    $sample = max(1, $sample);
    $sql = "SELECT ad.address, COUNT(*) AS c
            FROM balances b
            JOIN index_addresses ad ON ad.id=b.address_id
            WHERE b.address_id>0 AND b.utxo_id=0 AND b.quantity>0
            GROUP BY b.address_id
            ORDER BY c DESC
            LIMIT {$sample}";
    $res = $mysqli->query($sql);
    if($res)
        while($row = $res->fetch_assoc())
            $addresses[] = $row['address'];
}

$total    = count($addresses);
$okCount  = 0;
$badCount = 0;
print "Verifying {$total} address(es) against chain...\n";

foreach($addresses as $i => $addr){
    $n = $i + 1;
    if(str_contains($addr, '-')){
        print "[{$n}/{$total}] {$addr} - skipping multisig\n";
        continue;
    }

    $chain = chainHoldings($addr);
    $db    = dbHoldings($addr);

    // Compare the union of asset keys from both sides
    $assets    = array_unique(array_merge(array_keys($chain), array_keys($db)));
    $mismatches = array();
    foreach($assets as $asset){
        $c = (string)($chain[$asset] ?? '0');
        $d = (string)($db[$asset]    ?? '0');
        if(intStrCmp($c, $d) !== 0)
            $mismatches[] = sprintf("    %-22s chain=%s  db=%s", $asset, $c, $d);
    }

    if(count($mismatches)){
        $badCount++;
        printf("[%d/%d] %s  MISMATCH (%d asset(s), chain=%d / db=%d total assets)\n",
            $n, $total, $addr, count($mismatches), count($chain), count($db));
        foreach($mismatches as $m)
            print $m . "\n";
    } else {
        $okCount++;
        if($verbose)
            printf("[%d/%d] %s  OK (%d assets)\n", $n, $total, $addr, count($chain));
    }
}

print "\n";
printf("Result: %d OK, %d MISMATCH out of %d verified.\n", $okCount, $badCount, $total);
exit($badCount > 0 ? 1 : 0);
