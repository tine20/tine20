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
 */
error_reporting( E_ALL | E_STRICT );


/*
 * Set white / black lists
 */
PHPUnit_Util_Filter::addDirectoryToFilter(dirname(__FILE__));

/*
 * Set include path
 */
 
define('PATH_TO_REAL_DIR', dirname(__FILE__). '/../tine20');
define('PATH_TO_TEST_DIR', dirname(__FILE__));

$path = array(
    PATH_TO_REAL_DIR,
    get_include_path(),
	PATH_TO_TEST_DIR,
);
	
set_include_path(implode(PATH_SEPARATOR, $path));

/*
 * Set parameters  for logging (call via browser)
 */
define('CONFIGURATION', PATH_TO_TEST_DIR."/conf.xml");

/*
 * Set up basic tine 2.0 environment
 */
$_SERVER['DOCUMENT_ROOT'] = '/Applications/xampp/htdocs';
require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();
$tinebaseController = Tinebase_Controller::getInstance();
if (!$tinebaseController->login('tine20admin', 'lars', '127.0.0.1')){
    throw new Exception("could't login, user session required for tests! \n");
}

//Zend_Registry::set('logger', new Zend_Log(new Zend_Log_Writer_Null));
 
