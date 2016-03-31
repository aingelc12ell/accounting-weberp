<?php
#modifications
defined('DS') or define('DS',DIRECTORY_SEPARATOR);
define('ROOT_DIR',dirname(dirname(__FILE__)).DS);
define('ROOT_DIR_MOD',dirname(__FILE__).DS);

#system-wide
define('SYSTEM_VERSION','3.0.3');

#dependencies
require_once(ROOT_DIR_MOD.'tools'.DS.'index.php');

$config = dirname(__FILE__).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.strtolower($_SERVER['SERVER_NAME']).'.php';

if(!file_exists($config)){
    die('Configuration file: '.$config.' is not found');
}
require_once($config);