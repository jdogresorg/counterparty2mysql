<?php
/*********************************************************************
 * config.php - Config info / credentials
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

/* Mainnet config */
if($runtype=='mainnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Unoparty");
    define("CP_HOST", "http://127.0.0.1:4120/api/");
    define("CP_USER", "unoparty_username");
    define("CP_PASS", "unoparty_password");
}

/* Testnet config */
if($runtype=='testnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "mysql_username");
    define("DB_PASS", "mysql_password");
    define("DB_DATA", "Unoparty_Testnet");
    define("CP_HOST", "http://127.0.0.1:14120/api/");
    define("CP_USER", "unoparty_username");
    define("CP_PASS", "unoparty_password");
}


// Require various libraries
require_once('jsonRPC/Client.php');
require_once('functions.php');
require_once('profiler.php');

// Start runtime clock
$runtime = new Profiler();

?>
