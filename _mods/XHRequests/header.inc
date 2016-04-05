<?php
include_once((dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'config.php');

/**
* Extract Trial Balance Entries for this particular AccountGroup
*/

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 1 Jan 2000 01:01:00 GMT');
header('Content-Type: application/json');

session_start();

#include(ROOT_DIR . 'includes/ConnectDB.inc');
include_once (ROOT_DIR . 'includes/ConnectDB_' . $DBType . '.inc');
include_once (ROOT_DIR . 'includes/DateFunctions.inc');
include_once (ROOT_DIR.'includes/SQL_CommonFunctions.inc');
include_once (ROOT_DIR.'includes/AccountSectionsDef.inc');
# check if user is allowed?