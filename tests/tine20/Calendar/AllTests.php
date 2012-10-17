<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        $suite->addTest(Calendar_Frontend_AllTests::suite());
        $suite->addTest(Calendar_Model_AllTests::suite());
        $suite->addTestSuite('Calendar_RruleTests');
        $suite->addTestSuite('Calendar_Backend_SqlTest');
        $suite->addTestSuite('Calendar_Controller_EventTests');
        $suite->addTestSuite('Calendar_Controller_ResourceTest');
        $suite->addTestSuite('Calendar_Controller_EventGrantsTests');
        $suite->addTestSuite('Calendar_Controller_EventNotificationsTests');
        $suite->addTestSuite('Calendar_Controller_RecurTest');
        $suite->addTestSuite('Calendar_Controller_MSEventFacadeTest');
        $suite->addTestSuite('Calendar_JsonTests');
        $suite->addTestSuite('Calendar_Import_ICalTest');
        $suite->addTestSuite('Calendar_Export_ICalTest');
        $suite->addTestSuite('Calendar_Convert_Event_VCalendar_AllTests');
        $suite->addTestSuite('Calendar_Setup_DemoDataTests');
        
        return $suite;
    }
}

