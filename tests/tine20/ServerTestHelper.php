<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
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

if (!defined('PATH_TO_REAL_DIR')) {
    define('PATH_TO_REAL_DIR', dirname(__FILE__). '/../../tine20');
    define('PATH_TO_TINE_LIBRARY', dirname(__FILE__). '/../../tine20/library');
    define('PATH_TO_TEST_DIR', dirname(__FILE__));
}

/*
 * Set white / black lists
 */
$phpUnitVersion = explode(' ',PHPUnit_Runner_Version::getVersionString());
if (version_compare($phpUnitVersion[1], "3.6.0") >= 0) {
    $filter = new PHP_CodeCoverage_Filter();
    $filter->addDirectoryToBlacklist(PATH_TO_TEST_DIR);
    $filter->addDirectoryToBlacklist(PATH_TO_TINE_LIBRARY);
    $filter->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Setup');
    $filter->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Zend');
} else if (version_compare($phpUnitVersion[1], "3.5.0") >= 0) {
    PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_TEST_DIR);
    PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_TINE_LIBRARY);
    PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Setup');
    PHP_CodeCoverage_Filter::getInstance()->addDirectoryToBlacklist(PATH_TO_REAL_DIR.'/Zend');
} else {
    PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_TEST_DIR);
    PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_TINE_LIBRARY);
    PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_REAL_DIR.'/Setup');
    PHPUnit_Util_Filter::addDirectoryToFilter(PATH_TO_REAL_DIR.'/Zend');
}

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
Tinebase_Autoloader::initialize($autoloader);
