<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * @todo	implement json key check
 *
 */

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();
Zend_Session::start();

$egwBaseNamespace = new Zend_Session_Namespace('egwbase');

if(!isset($egwBaseNamespace->jsonKey)) {
	$egwBaseNamespace->jsonKey = md5(mktime());
}

$options = new Zend_Config_Ini('../../config.ini', 'database');
Zend_Registry::set('dbConfig', $options);

date_default_timezone_set('Europe/Berlin');

$locale = new Zend_Locale();
Zend_Registry::set('locale', $locale);

$db = Zend_Db::factory('PDO_MYSQL', $options->toArray());
Zend_Registry::set('dbAdapter', $db);
Zend_Db_Table_Abstract::setDefaultAdapter($db);

if(isset($egwBaseNamespace->currentAccount)) {
	Zend_Registry::set('currentAccount', $egwBaseNamespace->currentAccount);
}

#date_default_timezone_set('America/Los_Angeles');

#echo new Zend_Date('2006', Zend_Date::YEAR);
#exit;

$auth = Zend_Auth::getInstance();

if($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && !empty($_REQUEST['method'])) {
	//Json request from ExtJS
	
	// is it save to use the jsonKey from $_GET too???
	// can we move this to the Zend_Json_Server???
//	if($_POST['jsonKey'] != $egwBaseNamespace->jsonKey) {
//		error_log('wrong JSON Key sent!!!');
//		die('wrong JSON Key sent!!!');
//	}
	
	$server = new Zend_Json_Server();

	$server->setClass('Egwbase_Json', 'Egwbase');

	if($auth->hasIdentity()) {
		$server->setClass('Addressbook_Json', 'Addressbook');
		//$server->setClass('Asterisk_Json', 'Asterisk');
		//$server->setClass('Felamimail_Json', 'Felamimail');
	}

	$server->handle($_REQUEST);
	
} else {
	// HTTP request
	
	$server = new Egwbase_Http_Server();

	$server->setClass('Egwbase_Http', 'Egwbase');

	if($auth->hasIdentity()) {
		$server->setClass('Addressbook_Http', 'Addressbook');
	} 

	if(empty($_REQUEST['method'])) {
		if($auth->hasIdentity()) {
			$_REQUEST['method'] = 'Egwbase.mainScreen';
		} else {
			$_REQUEST['method'] = 'Egwbase.login';
		}
	}
	
	$server->handle($_REQUEST);
	
}