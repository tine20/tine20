<?php
require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$options = new Zend_Config_Ini('../../config.ini', 'database');
$db = Zend_Db::factory('PDO_MYSQL', $options->toArray());
Zend_Db_Table_Abstract::setDefaultAdapter($db);

//$egwAPISession = new Zend_Session_Namespace('egwapi');

$application	= $_REQUEST['application'];
$function	= $_REQUEST['func'];
error_log("APPLICATION: $application FUNCTION: $function");

$jsonApp = new $application();
$jsonApp->$function();

?>