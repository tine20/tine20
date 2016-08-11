<?php
/**
 * Expresso Lite
 * Bootstrap for functional tests. It defines constants for the class
 * paths, sets up the class loader and sets PHPUnit to use shared
 * selenium session for tests
 *
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define('SRC_PATH', dirname(__FILE__).'/../../src/');
define('TEST_ROOT_PATH', dirname(__FILE__) . '/');

require_once (SRC_PATH.'api/SplClassLoader.php');

$testConfPhp = TEST_ROOT_PATH.'test_conf.php';
if (!file_exists($testConfPhp)) {
   die("Could not find test_conf.php file. Please create it using test_conf.php.dist as a template and run the tests again.\n");
}

require_once ($testConfPhp);

$classLoader = new SplClassLoader('ExpressoLiteTest', TEST_ROOT_PATH);
$classLoader->register();

\PHPUnit_Extensions_SeleniumTestCase::shareSession(true);
\PHPUnit_Extensions_Selenium2TestCase::shareSession(true);
