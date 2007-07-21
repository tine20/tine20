<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$options = new Zend_Config_Ini('../../config.ini', 'database');
$db = Zend_Db::factory('PDO_MYSQL', $options->toArray());
Zend_Db_Table_Abstract::setDefaultAdapter($db);


$snom = new Asterisk_Json();
$snom->getData();

		
?>