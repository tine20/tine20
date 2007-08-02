<?php
header('Content-Type: text/html; charset=utf-8');

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
Zend_Db_Table_Abstract::setDefaultAdapter($db);

if(isset($egwBaseNamespace->currentAccount)) {
    Zend_Registry::set('currentAccount', $egwBaseNamespace->currentAccount);
}

#date_default_timezone_set('America/Los_Angeles');

#echo new Zend_Date('2006', Zend_Date::YEAR);
#exit;

$view = new Zend_View();

if(isset($_POST['jsonKey'])) {
	$sendJsonKey = $_POST['jsonKey'];
} else {
	$sendJsonKey = $_GET['jsonKey'];
}

if($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && !empty($_REQUEST['method'])) {
	//json request
	if(isset($_POST['jsonKey'])) {
		$sendJsonKey = $_POST['jsonKey'];
	} else {
		$sendJsonKey = $_GET['jsonKey'];
	}
	
	if($sendJsonKey != $egwBaseNamespace->jsonKey) {
		error_log('wrong JSON Key sent!!!');
		#die('wrong JSON Key sent!!!');
	}
	$server = new Zend_Json_Server();
		
	$server->setClass('Egwbase_Json', 'Egwbase');

	if($egwBaseNamespace->isAutenticated) {
		$server->setClass('Addressbook_Json', 'Addressbook');
		$server->setClass('Asterisk_Json', 'Asterisk');
		$server->setClass('Felamimail_Json', 'Felamimail');
	}

	$server->handle();

} elseif(isset($_REQUEST['getpopup']) && $egwBaseNamespace->isAutenticated) {
    // this does not really belong to here
    $view->setScriptPath('Egwbase/views');
    $view->formData = array();
    
    $list = $locale->getTranslationList('Dateformat');
    $view->formData['config']['dateFormat'] = str_replace(array('dd', 'MMMM', 'MMM','MM','yyyy','yy'), array('d','F','M','m','Y','y'), $list['long']);
    
    $addresses = Addressbook_Contacts::factory(Addressbook_Contacts::SQL);
    if(isset($_REQUEST['contactid']) && $rows = $addresses->find($_REQUEST['contactid'])) {
        $view->formData['values'] = $rows->current()->toArray();
    }
    $view->jsExecute = 'EGWNameSpace.Addressbook.displayContactDialog();';
    
    echo $view->render('popup.php');
} else {
	$view->setScriptPath('Egwbase/views');
	if(!$egwBaseNamespace->isAutenticated) {
		echo $view->render('login.php');
	} else {
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
}

?>