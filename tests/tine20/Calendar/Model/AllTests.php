<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Model_AllTests::main');
}

class Calendar_Model_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar All Model Tests');
        $suite->addTestSuite('Calendar_Model_AttenderFilterTests');
        $suite->addTestSuite('Calendar_Model_AttenderTests');
        $suite->addTestSuite('Calendar_Model_EventTests');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Calendar_Model_AllTests::main') {
    Calendar_Model_AllTests::main();
}
