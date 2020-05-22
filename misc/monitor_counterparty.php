#!/usr/bin/env php
<?php
/*********************************************************************
 * monitor-counterparty.php 
 * 
 * Script to handle monitoring a Counterparty API endpoint for the 
 * 'Counterparty database is behind backend.' error message and then
 * handle restarting the API since parsing blocks is lagging behind
 *
 * Command line arguments :
 * --testnet    Load data from testnet
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

// Parse in the command line args and set some flags based on them
$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';
$server  = ($testnet) ? 'counterparty-testnet' : 'counterparty';
$timeout = 30000; // Curl timeout (milliseconds)
$sleep   = 60;    // Time to wait between uptime checks (seconds)
$errors  = 0;     // Error counter
$restart = 1;     // Indicates if we have restarted the server recently
                  // Used to ensure the API comes back up fully before additional restarts

// Load config (only after runtype is defined)
require_once(__DIR__ . '/../includes/config.php');

// Ensure that we have sudo privileges
$sudo = exec("sudo uptime| grep load | wc -l");
if($sudo=="0"){
    print "This script requires root access via sudo!\n";
    exit;
}

// Try to connect to the Counterparty API and run get_running_info
// Status : 0=Down, 1=UP, 2=Lagging
function check_counterparty_status(){
    $status = 0;
    $ch   = curl_init();
    $data = array(
        'id'      => mt_rand(),
        'jsonrpc' => '2.0',
        'method'  => 'get_running_info'
    );
    curl_setopt($ch, CURLOPT_URL, CP_HOST);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JSON-RPC PHP Client');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_USERPWD, CP_USER . ':' . CP_PASS);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
    $result = curl_exec($ch);
    $data   = json_decode($result);
    if(isset($data)){
        if($data->message=='Server error' && $data->data=='Counterparty database is behind backend.'){
            $status = 2;
        } else {
            $status = 1;
        }
    }
    return $status;
}

// Simple function to get a timestamp
function get_timestamp(){
    return exec("date '+%Y-%m-%d %T'");
}

// Loop forever and check the status of the API
while(1){
    print "[" . get_timestamp() . "] Checking {$server} API status...";
    $status = check_counterparty_status();
    if($status==1){
        print "UP\n";
        $errors  = 0;
        $restart = 0; // Reset flag so we start doing actual checks
    } else {
        $errors++;
        if($status==0)
            print "DOWN\n";
        if($status==2){
            if($restart){
                print "CATCHING UP\n";
            } else {
                print "LAGGING\n";
            }
        }
        // If we detected at least 3 errors, fix by restarting counterparty service
        if($errors>=3 && !$restart){
            $errors  = 0;
            $restart = 1;
            print "[" . get_timestamp() . "] Restarting {$server}...\n";
            echo shell_exec("fednode restart {$server}");
        }
    }
    // Run sudo -v to keep password authentication from timing out
    $sudo = exec("sudo -v");
    // print "[" . get_timestamp() . "] Sleeping {$sleep} seconds before next check...\n";
    sleep($sleep);
}

