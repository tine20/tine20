<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @version     $Id: SchedulerTest.php 4754 2008-09-30 13:34:35Z g.ciyiltepe@metaways.de $
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Scheduler_SchedulerTest::main');
}

/**
 * Test class for Scheduler Test
 */
class Tinebase_Scheduler_SchedulerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up unit tests.
     */
    public function setUp()
    {
    }
    
    /**
     * tears down the fixture
     */
    public function tearDown()
    {
        $scheduler = Tinebase_Core::getScheduler();
        $db = $scheduler->getBackend()->getDbAdapter();
        $db->delete(SQL_TABLE_PREFIX.'scheduler');
    }
    
    /**
     * Tests if the 'Db' backend is functional.
     */
    public function testCanUseDbBackend()
    {
        $scheduler = Tinebase_Core::getScheduler();
        $backend = $scheduler->getBackend();
        $tasks = $backend->loadQueue();
        $this->assertTrue(is_array($tasks));
    }
    
    /**
     * Tests if a task can be saved.
     */
    public function testSaveTask()
    {
        $request = new Zend_Controller_Request_Http(); 
        $request->setControllerName('Tinebase_Alarm');
        $request->setActionName('sendPendingAlarms');
        $request->setParam('eventName', 'Tinebase_Event_Async_Minutely');
        
        $task = Zend_Scheduler_Task::getTask()
            ->setMonths("Jan-Dec")
            ->setWeekdays("Sun-Sat")
            ->setDays("1-31")
            ->setHours("0-23")
            ->setMinutes("0/1")
            ->setRequest($request);
        
        $scheduler = Tinebase_Core::getScheduler();
        $db = $scheduler->getBackend()->getDbAdapter();
        $db->delete(SQL_TABLE_PREFIX.'scheduler');
        
        $scheduler->addTask('Tinebase_Alarm_Test', $task);
        $scheduler->saveTask();
        
        $backend = $scheduler->getBackend();
        $tasks = $backend->loadQueue();
        $this->assertTrue(count($tasks) == 1);
    }
    
    /**
     * 
     */
    public function testCanRunTask()
    {
        $this->testSaveTask();
        
        $scheduler = Tinebase_Core::getScheduler();
        $scheduler->run();
    }
}
