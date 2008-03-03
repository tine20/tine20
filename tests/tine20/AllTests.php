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
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tine20_AllTests::main');
}
class Tine20_AllTests
{
    
    public static function main()
	{
	    PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 All Tests');
        $suite->addTestSuite('Tine20_Tinebase_AllTests');
        //  $suite->addTestSuite('Crm_ControllerTest');
        //	$suite->addTest(Asterisk_AllTests::suite());
        //	$suite->addTest(Admin_AllTests::suite());
        //	$suite->addTest(Addressbook_AllTests::suite());
        //	$suite->addTest(Calendar_AllTests::suite());
        //	$suite->addTestSuite('Tasks_ControllerTest');
        return $suite;
    }
}
if (PHPUnit_MAIN_METHOD == 'Tine20_AllTests::main') {
    Tine20_AllTests::main();
}