<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_AllTests::main');
}

/**
 * Tests for calendar app
 * 
 * @package     Calendar
 */
class Calendar_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar All Tests');
        $suite->addTestSuite('Calendar_Model_AttenderFilterTests');
        $suite->addTestSuite('Calendar_RruleTests');
        $suite->addTestSuite('Calendar_Backend_SqlTests');
        $suite->addTestSuite('Calendar_Controller_EventTests');
        $suite->addTestSuite('Calendar_Controller_EventGrantsTests');
        $suite->addTestSuite('Calendar_Controller_EventNotificationsTests');
        $suite->addTestSuite('Calendar_JsonTests');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Calendar_AllTests::main') {
    Calendar_AllTests::main();
}
