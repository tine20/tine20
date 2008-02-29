<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  tinebase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tine20_Tinebase_AllTests::main');
}


//require_once 'Tinebase/Application.php';
//require_once 'ApplicationTest.php';
//require_once 'AuthTest.php';
//require_once 'ControllerTest.php';
//require_once 'JsonTest.php';
//require_once 'LinksTest.php';
require_once 'Record/RecordTest.php';
//require_once 'Record/ContainerTest.php';



class Tine20_Tinebase_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine20_Tinebase_AllTests');

        //$suite->addTestSuite('Addressbook_ControllerTest');
		//$suite->addTestSuite('Crm_ControllerTest');
		//$suite->addTestSuite('Admin_ControllerTest');
		//	$suite->addTestSuite('Tine20_Tinebase_Record_AbstractRecordTest');
			//$suite->addTestSuite('Tine20_Tinebase_Record_ContainerTest');
			
			$suite->addTestSuite('Tine20_Tinebase_Record_RecordTest');
			
	//	$suite->addTest(ControllerTest::suite());
	//	$suite->addTestSuite('Tasks_ControllerTest');
	 	/*
	    
	   $suite->addTest(Zend_XmlRpc_AllTests::suite());
		*/
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Tine20_Tinebase_AllTests::main') {
    Tine20_Tinebase_AllTests::main();
}
