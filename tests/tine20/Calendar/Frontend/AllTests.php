<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Frontend_AllTests::main');
}

class Calendar_Frontend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar All Frontend Tests');
        $suite->addTest(Calendar_Frontend_WebDAV_AllTests::suite());
        $suite->addTestSuite('Calendar_Frontend_CalDAVTest');
        $suite->addTestSuite('Calendar_Frontend_iMIPTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Calendar_Frontend_AllTests::main') {
    Calendar_Frontend_AllTests::main();
}
