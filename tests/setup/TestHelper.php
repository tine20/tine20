<?php
/**
 * Tine 2.0
 * 
 * @package     setup tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        refactor setup tests bootstrap
 */

/*
 * Set include path
 */
define('PATH_TO_REAL_DIR', dirname(__FILE__). '/../../tine20');
define('PATH_TO_TINE_LIBRARY', dirname(__FILE__). '/../../tine20/library');
define('PATH_TO_TEST_DIR', dirname(__FILE__));

$path = array(
    PATH_TO_REAL_DIR,
    get_include_path(),
    PATH_TO_TEST_DIR,
    PATH_TO_TINE_LIBRARY
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
require_once 'bootstrap.php';

/*
 * Set white / black lists
 */
$phpUnitVersion = explode(' ',PHPUnit_Runner_Version::getVersionString());
if (version_compare($phpUnitVersion[1], "3.6.0") >= 0) {
    $filter = new PHP_CodeCoverage_Filter();
    $filter->addDirectoryToBlacklist(dirname(__FILE__));
} else if (version_compare($phpUnitVersion[1], "3.5.0") >= 0) {
    PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(dirname(__FILE__));
} else {
    PHPUnit_Util_Filter::addDirectoryToFilter(dirname(__FILE__));
}

// get config
$configData = include('phpunitconfig.inc.php');
$config = new Zend_Config($configData);

$_SERVER['DOCUMENT_ROOT'] = $config->docroot;

Setup_TestServer::getInstance()->initFramework();

Tinebase_Core::set('locale', new Zend_Locale($config->locale));
Tinebase_Core::set('testconfig', $config);
