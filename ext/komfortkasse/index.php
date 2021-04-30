<?php
/**
 * Komfortkasse
 * routing
 *
 * @version 1.0.1-xtc4/xct3
 */

ini_set('default_charset', 'utf-8');

//  error_reporting(E_ALL);
//  ini_set('display_errors', '1');

require_once ('../../includes/configure.php');
require_once (DIR_FS_CATALOG . DIR_WS_INCLUDES . 'filenames.php');
require_once (DIR_FS_CATALOG . DIR_WS_INCLUDES . 'database_tables.php');
require_once (DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'database.php');
require (DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'general.php');
include_once 'Komfortkasse.php';

tep_db_connect() or die('Unable to connect to database server!');

$action = Komfortkasse_Config::getRequestParameter('action');

$kk = new Komfortkasse();
$kk->$action();
//call_user_method($action, );

?>