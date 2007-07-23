<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$options = new Zend_Config_Ini('../../config.ini', 'database');
$db = Zend_Db::factory('PDO_MYSQL', $options->toArray());
Zend_Db_Table_Abstract::setDefaultAdapter($db);

$egwBaseNamespace = new Zend_Session_Namespace('egwbase');

#date_default_timezone_set('America/Los_Angeles');
#date_default_timezone_set('Europe/Berlin');

#echo new Zend_Date('2006', Zend_Date::YEAR);
#exit;

#echo $egwBaseNamespace->numberOfPageRequests++;

#$application	= $_REQUEST['application'];
#$function	= $_REQUEST['func'];
#error_log("APPLICATION: $application FUNCTION: $function");
#
#$jsonApp = new $application();
#$jsonApp->$function();

$view = new Zend_View();

if($_REQUEST['application'] == 'addressbook') {
	$view->setScriptPath('Addressbook/views');
	
	echo $view->render('editcontact.php');
} else {
	$view->setScriptPath('Egwbase/views');

	foreach(array('Felamimail', 'Addressbook', 'Asterisk') as $applicationName) {
		$className = "{$applicationName}_Json";
		$application = new $className;
		$applications[] = $application->getMainTree();
		$jsInlucdeFiles[] = $applicationName;
	}

	$view->jsInlucdeFiles = $jsInlucdeFiles;
	$view->applications = $applications;
	$view->title="eGW Test Layout";
	echo $view->render('mainscreen.php');
}

?>