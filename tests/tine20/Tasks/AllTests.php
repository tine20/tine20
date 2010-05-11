<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        add controller tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tasks_AllTests::main');
}

class Tasks_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tasks All Tests');
        $suite->addTest(Tasks_Backend_AllTests::suite());
        $suite->addTestSuite('Tasks_ControllerTest');
        $suite->addTestSuite('Tasks_Model_TaskFilterTest');
        $suite->addTestSuite('Tasks_JsonTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Tasks_AllTests::main') {
    Tasks_AllTests::main();
}
