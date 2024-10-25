<?php
/*********************************************************************
 * config.php - Config info / credentials
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

/* Log, lock, and state file config */
define("LOCKFILE", '/var/tmp/dogeparty2mysql-' . $runtype . '.lock');
define("LASTFILE", '/var/tmp/dogeparty2mysql-' . $runtype . '.last-block');
define("ERRORLOG", '/var/tmp/dogeparty2mysql-' . $runtype . '.errors');

/* Mainnet config */
if($runtype=='mainnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Dogeparty");
    define("DP_HOST", "http://127.0.0.1:14005/api/");
    define("DP_USER", "dogeparty_username");
    define("DP_PASS", "dogeparty_password");
}

/* Testnet config */
if($runtype=='testnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Dogeparty_Testnet");
    define("DP_HOST", "http://127.0.0.1:14005/api/");
    define("DP_USER", "dogeparty_username");
    define("DP_PASS", "dogeparty_password");
}

/* Regtest config */
if($runtype=='regtest'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Dogeparty_Regtest");
    define("DP_HOST", "http://127.0.0.1:44005/api/");
    define("DP_USER", "dogeparty_username");
    define("DP_PASS", "dogeparty_password");
}

// Require various libraries
require_once('jsonRPC/Client.php');
require_once('functions.php');
require_once('profiler.php');

// Start runtime clock
$runtime = new Profiler();

?>
