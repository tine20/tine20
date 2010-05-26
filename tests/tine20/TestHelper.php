<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id$
 */

/*
 * Set error reporting 
 * 
 * @todo put that in config.inc as well?
 */
error_reporting( E_ALL | E_STRICT );

/*
 * Set include path
 */
 
define('PATH_TO_REAL_DIR', dirname(__FILE__). '/../../tine20');
define('PATH_TO_TINE_LIBRARY', dirname(__FILE__). '/../../tine20/library');
define('PATH_TO_TEST_DIR', dirname(__FILE__));

/*
 * Set white / black lists
 */
PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_TEST_DIR);
PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_TINE_LIBRARY);
PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_REAL_DIR.'/Setup');
PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_REAL_DIR.'/Zend');

$path = array(
    PATH_TO_REAL_DIR,
    PATH_TO_TEST_DIR,
	PATH_TO_TINE_LIBRARY,
    get_include_path(),
);
        
set_include_path(implode(PATH_SEPARATOR, $path));

/**
 * Set up basic tine 2.0 environment
 */
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);


// get config
$configData = include('phpunitconfig.inc.php');
if($configData === false) {
    $configData = include('config.inc.php');
}
if($configData === false) {
    die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
}
$config = new Zend_Config($configData);

Zend_Registry::set('testConfig', $config);

$_SERVER['DOCUMENT_ROOT'] = $config->docroot;

// set default test mailer
Tinebase_Smtp::setDefaultTransport(new Zend_Mail_Transport_Array());

// set max execution time
Tinebase_Core::setExecutionLifeTime(1200);

// finally init base framework
TestServer::getInstance()->initFramework();

Zend_Registry::set('locale', new Zend_Locale($config->locale));

$tinebaseController = Tinebase_Controller::getInstance();
if (!$tinebaseController->login($config->username, $config->password, $config->ip)){
    throw new Exception("Couldn't login, user session required for tests! \n");
}

 
