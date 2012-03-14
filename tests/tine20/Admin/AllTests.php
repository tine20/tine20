<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        add more test suites
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Admin_AllTests::main');
}

class Admin_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Admin All Tests');
        $suite->addTestSuite('Admin_ControllerTest');
        $suite->addTestSuite('Admin_JsonTest');
        $suite->addTestSuite('Admin_CliTest');
        $suite->addTestSuite('Admin_Acl_RightsTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Admin_AllTests::main') {
    Admin_AllTests::main();
}
