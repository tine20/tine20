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

//$locale = new Zend_Locale('fr_BE');
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

$view = new Zend_View();

if(isset($_POST['jsonKey'])) {
	$sendJsonKey = $_POST['jsonKey'];
} else {
	$sendJsonKey = $_GET['jsonKey'];
}

$auth = Zend_Auth::getInstance();
if($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && !empty($_REQUEST['method'])) {
	//json request
	if(isset($_POST['jsonKey'])) {
		$sendJsonKey = $_POST['jsonKey'];
	} else {
		$sendJsonKey = $_GET['jsonKey'];
	}
	
	if($sendJsonKey != $egwBaseNamespace->jsonKey) {
		#error_log('wrong JSON Key sent!!!');
		#die('wrong JSON Key sent!!!');
	}
	$server = new Zend_Json_Server();
		
	$server->setClass('Egwbase_Json', 'Egwbase');
	
	if($auth->hasIdentity()) {
		$server->setClass('Addressbook_Json', 'Addressbook');
		$server->setClass('Asterisk_Json', 'Asterisk');
		$server->setClass('Felamimail_Json', 'Felamimail');
	}

	$server->handle();

} elseif(isset($_REQUEST['getpopup']) && $auth->hasIdentity()) {
    // this does not really belong to here
    $view->setScriptPath('Egwbase/views');
    $view->formData = array();
    
    $list = $locale->getTranslationList('Dateformat');
    $view->formData['config']['dateFormat'] = str_replace(array('dd', 'MMMM', 'MMM','MM','yyyy','yy'), array('d','F','M','m','Y','y'), $list['long']);
    
	$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'-min.js');
	
    $currentAccount = Zend_Registry::get('currentAccount');
    $egwbaseAcl = Egwbase_Acl::getInstance();
    
    $acl = $egwbaseAcl->getGrantors($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);
    
    foreach($acl as $value) {
    	$view->formData['config']['addressbooks'][] = array($value, $value);
    }
        
    $addresses = Addressbook_Backend::factory(Addressbook_Backend::SQL);
    if(isset($_REQUEST['contactid']) && $contact = $addresses->getContactById($_REQUEST['contactid'])) {
        $view->formData['values'] = $contact->toArray();
    }
	if($_REQUEST['getpopup'] == 'addressbook.editcontact') {
		$view->jsExecute = 'EGWNameSpace.Addressbook.displayContactDialog();';
	} elseif($_REQUEST['getpopup'] == 'addressbook.editlist') {
		$view->jsExecute = 'EGWNameSpace.Addressbook.displayListDialog();';
	}
    
    echo $view->render('popup.php');
} else {
    $view->setScriptPath('Egwbase/views');

    if($auth->hasIdentity()) {
    	foreach(array('Felamimail', 'Addressbook', 'Asterisk') as $applicationName) {
    	    $className = "{$applicationName}_Json";
    	    $application = new $className;
    	    $applications[] = $application->getMainTree();
    	    $jsIncludeFiles[] = $applicationName .'/Js/'. $applicationName .'.js';
        }
        
		
        $view->jsIncludeFiles = $jsIncludeFiles;
        $view->applications = $applications;
        $view->title="eGW Test Layout";
        echo $view->render('mainscreen.php');
    } else {
        echo $view->render('login.php');
    }
}

?>