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
 * Set white / black lists
 */
PHPUnit_Util_Filter::addDirectoryToFilter(dirname(__FILE__));

/*
 * Set include path
 */
 
define('PATH_TO_REAL_DIR', dirname(__FILE__). '/../../tine20');
define('PATH_TO_TEST_DIR', dirname(__FILE__));

$path = array(
    PATH_TO_REAL_DIR,
    get_include_path(),
	PATH_TO_TEST_DIR,
);
        
set_include_path(implode(PATH_SEPARATOR, $path));

/*
 * Set parameters  for logging (call via browser)
 * 
 * @todo put that in config.inc as well or remove that?
 */
define('CONFIGURATION', PATH_TO_TEST_DIR."/conf.xml");

/*
 * Set up basic tine 2.0 environment
 */
require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

// get config
if(file_exists(dirname(__FILE__) . '/config.inc.php')) {
    $config = new Zend_Config(require dirname(__FILE__) . '/config.inc.php');
} else {
    throw new Exception("Couldn't find config.inc.php! \n");
}

$_SERVER['DOCUMENT_ROOT'] = $config->docroot;    

$tinebaseController = TestController::getInstance();
$tinebaseController->initFramework();
Zend_Registry::set('locale', new Zend_Locale($config->locale));

if (!$tinebaseController->login($config->username, $config->password, $config->ip)){
    throw new Exception("Couldn't login, user session required for tests! \n");
}

 
