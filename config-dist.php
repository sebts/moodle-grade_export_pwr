<?PHP
unset($PWR);  // Ignore this line
global $PWR;  // This is necessary here for PHPUnit execution
$PWR = new stdClass();

//=========================================================================
// 1. DATABASE SETUP
//=========================================================================
// First, you need to configure the database where all Power campus data //
// will be stored.  This database must already have been created         //
// and a username/password created to access it.                         //

$PWR->dbhost    = ''; // eg 'localhost' or 'db.isp.com' or IP
$PWR->dbname    = ''; // database name, eg Campus
$PWR->dbuser    = ''; // your database username
$PWR->dbpass    = ''; // your database password