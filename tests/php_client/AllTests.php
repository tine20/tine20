<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  php_client
 * @license     yet unknown
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

/**
 * Test helper
 */
require_once 'TestHelper.php';

class AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 PHP Client');
        $suite->addTestSuite('Tinebase_LoginTest');
        $suite->addTestSuite('Addressbook_ServiceTest');
        $suite->addTestSuite('Tinebase_LogoutTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}