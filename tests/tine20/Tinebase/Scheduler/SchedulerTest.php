<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Scheduler Test
 */
class Tinebase_Scheduler_SchedulerTest extends PHPUnit_Framework_TestCase
{
    /**
     * the scheduler
     * 
     * @var Zend_Scheduler
     */
    protected $_scheduler = NULL;
    
    /**
     * Sets up unit tests.
     */
    public function setUp()
    {
        $this->_scheduler = Tinebase_Core::getScheduler();
    }
    
    /**
     * tears down the fixture
     */
    public function tearDown()
    {
        // remove all tasks
        Tinebase_Core::getScheduler()->getBackend()->saveQueue();
        
        // init default tasks
        $setup = new Tinebase_Setup_Initialize();
        $setup->initTinebaseScheduler();
    }
    
    /**
     * Tests if the 'Db' backend is functional.
     */
    public function testCanUseDbBackend()
    {
        $backend = $this->_scheduler->getBackend();
        $tasks = $backend->loadQueue();
        $this->assertTrue(is_array($tasks));
    }

    /**
     * testClearQueue
     */
    public function testClearQueue()
    {
        $backend = $this->_scheduler->getBackend();
        $backend->clearQueue();
        
        $tasks = $backend->loadQueue();
        $this->assertEquals(0, count($tasks));
        $this->_scheduler->removeAllTasks();
        $this->assertFalse($this->_scheduler->hasTask('Tinebase_Alarm'));
    }
    
    /**
     * Tests if a task can be saved.
     */
    public function testSaveTask()
    {
        $this->testClearQueue();
        
        $request = new Zend_Controller_Request_Simple(); 
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
        
        $this->_scheduler->addTask('Tinebase_Alarm_Test', $task);
        $this->_scheduler->saveTask();
        
        $tasks = $this->_scheduler->getBackend()->loadQueue();
        $this->assertEquals(1, count($tasks));
    }
    
    /**
     * can run task
     */
    public function testCanRunTask()
    {
        $this->testSaveTask();
        $result = $this->_scheduler->run();
        $this->assertGreaterThan(0, count($result));
    }
}
