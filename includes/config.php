<?php
/*********************************************************************
 * config.php - Config info / credentials
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

/* Log, lock, and state file config */
define("LOCKFILE", '/var/tmp/counterparty2mysql-cp20-' . $runtype . '.lock');
define("LASTFILE", '/var/tmp/counterparty2mysql-cp20-' . $runtype . '.last-block');
define("ERRORLOG", '/var/tmp/counterparty2mysql-cp20-' . $runtype . '.errors');

/* Mainnet config */
if($runtype=='mainnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Counterparty");
    define("CP_HOST", "http://127.0.0.1:4000/api/");
    define("CP_USER", "counterparty_username");
    define("CP_PASS", "counterparty_password");
}

/* Testnet config */
if($runtype=='testnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Counterparty_Testnet");
    define("CP_HOST", "http://127.0.0.1:14000/api/");
    define("CP_USER", "counterparty_username");
    define("CP_PASS", "counterparty_password");
}

/* Regtest config */
if($runtype=='regtest'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Counterparty_Regtest");
    define("CP_HOST", "http://127.0.0.1:44000/api/");
    define("CP_USER", "counterparty_username");
    define("CP_PASS", "counterparty_password");
}

// Require various libraries
require_once('counterparty-v2-api.php');
require_once('functions.php');
require_once('profiler.php');

// Start runtime clock
$runtime = new Profiler();

?>