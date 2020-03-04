#!/usr/bin/env php
<?php
/*********************************************************************
 * alert.php 
 * 
 * Handle monitoring DB and sending alert if no blocks for the last hour 
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

// Parse in the command line args and set some flags based on them
$args    = getopt("", array("testnet::"));
$testnet = (isset($args['testnet'])) ? true : false;
$runtype = ($testnet) ? 'testnet' : 'mainnet';

// Load config (only after runtype is defined)
require_once(__DIR__ . '/../includes/config.php');

// Email and text alert info
$email   = "user@email.com"; // Email address
$sms     = "sms@email.com"; // SMS Email address
$subject = "Counterparty2mysql Alert";
$message = "No new blocks on {$runtype} in the last hour";

// Initialize the database
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);

// Check how many blocks we have parsed in the last hour
$sql = "SELECT
            count(*) as cnt
        FROM
            blocks
        WHERE
            block_time >= UNIX_TIMESTAMP(DATE_SUB(now(), INTERVAL 1 HOUR))";
$results = $mysqli->query($sql);
if($results){
    $row = $results->fetch_assoc();
    $cnt = $row['cnt'];
    // If we have no records, send alert
    if($cnt==0){
        if(strlen($sms))
            $email .= ', ' . $sms;
        $message = wordwrap($message, 70, "\r\n");
        mail($email, $subject, $message);
    }
}

