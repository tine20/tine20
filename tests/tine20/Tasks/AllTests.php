<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * All Tasks tests
 * 
 * @package     Tasks
 */
class Tasks_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('All Tasks tests');
        
        $suite->addTest(Tasks_Backend_AllTests::suite());
        $suite->addTest(Tasks_Convert_Task_VCalendar_AllTests::suite());
        $suite->addTest(Tasks_Frontend_AllTests::suite());
        
        $suite->addTestSuite('Tasks_ControllerTest');
        $suite->addTestSuite('Tasks_Model_TaskFilterTest');
        $suite->addTestSuite('Tasks_JsonTest');
        
        return $suite;
    }
}
