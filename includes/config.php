<?php
/*********************************************************************
 * config.php - Config info / credentials
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

// Mainnet Config
define("MAIN_DB_HOST", "localhost");
define("MAIN_DB_USER", "mysql_username");
define("MAIN_DB_PASS", "mysql_password");
define("MAIN_DB_DATA", "Counterparty");
define("MAIN_CP_HOST", "http://127.0.0.1:4000/api/");
define("MAIN_CP_USER", "counterparty_username");
define("MAIN_CP_PASS", "counterparty_password");

// Testnet Config
define("TEST_DB_HOST", "localhost");
define("TEST_DB_USER", "mysql_username");
define("TEST_DB_PASS", "mysql_password");
define("TEST_DB_DATA", "Counterparty_Testnet");
define("TEST_CP_HOST", "http://127.0.0.1:14000/api/");
define("TEST_CP_USER", "counterparty_username");
define("TEST_CP_PASS", "counterparty_password");

// Require misc libraries
require_once('jsonRPC/Client.php');
require_once('functions.php');
require_once('profiler.php');

// Start runtime clock
$runtime = new Profiler();

?>
