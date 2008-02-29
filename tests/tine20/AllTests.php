<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test2.0
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tine20_AllTests::main');
}

//require_once 'Addressbook/AllTests.php';
//require_once 'Crm/ControllerTest.php';
//require_once 'Admin/AllTests.php';
require_once 'Tinebase/AllTests.php';
//require_once 'Asterisk/AllTests.php';
//require_once 'Calendar/AllTests.php';


class Tine20_AllTests
{
    public static function main()
	{
		$parameters = array();
		$parameters['configuration'] = CONFIGURATION;
	    PHPUnit_TextUI_TestRunner::run(self::suite(), $parameters);
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine20_AllTests');

		//	 $suite->addTestSuite('Crm_ControllerTest');
		//	$suite->addTest(Asterisk_AllTests::suite());
		//	$suite->addTest(Admin_AllTests::suite());
		$suite->addTest(Tine20_Tinebase_AllTests::suite());
		//	$suite->addTest(Addressbook_AllTests::suite());
		//	$suite->addTest(Calendar_AllTests::suite());
		//	$suite->addTestSuite('Tasks_ControllerTest');
	
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Tine20_AllTests::main') {
    Tine20_AllTests::main();
}