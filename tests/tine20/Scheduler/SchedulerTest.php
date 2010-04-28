<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @version     $Id: ControllerTest.php 4754 2008-09-30 13:34:35Z g.ciyiltepe@metaways.de $
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Scheduler_SchedulerTest::main');
}

/**
 * Test class for Scheduler Test
 */
class Scheduler_SchedulerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up unit tests.
     */
    public function setUp()
    {
        date_default_timezone_set('US/Pacific');
    }

    /**
     * Tests if a task can be added.
     */
    public function testCanAddTask()
    {
        $scheduler = new Zend_Scheduler();

        $task = new Zend_Scheduler_Task();
        $scheduler->addTask('test', $task);

        $this->assertTrue($scheduler->hasTask('test'), 'Task could not be added');
    }

    /**
     * Tests if more than one task can be added.
     */
    public function testCanAddTasks()
    {
        $scheduler = new Zend_Scheduler();

        $tasks = array(
            'test1' => new Zend_Scheduler_Task(),
            'test2' => new Zend_Scheduler_Task()
        );
        $scheduler->addTasks($tasks);

        $this->assertTrue($scheduler->hasTask('test1'), 'Tasks could not be added');
        $this->assertTrue($scheduler->hasTask('test2'), 'Second task could not be added');
    }

    /**
     * Tests if tasks can be added from a Zend_Config file.
     */
    public function testCanAddConfig()
    {
        $scheduler = new Zend_Scheduler();

        $filename = dirname(__FILE__) . '/tasks.ini';
        $scheduler->addConfig(new Zend_Config_Ini($filename, 'tasks'), 'tasks');

        $this->assertTrue($scheduler->hasTask('test1'), 'Tasks could not be added');
        $this->assertTrue($scheduler->hasTask('test2'), 'Second task could not be added');
    }

    /**
     * Tests if a task can be removed.
     */
    public function testCanRemoveTask()
    {
        $scheduler = new Zend_Scheduler();

        $task = new Zend_Scheduler_Task();
        $scheduler->addTask('test', $task);
        $scheduler->removeTask('test');

        $this->assertFalse($scheduler->hasTask('test'), 'Task could not be removed');
    }

    /**
     * Tests to ensure that the scheduler cannot be serialized.
     *
     * This test is performed because serializing the scheduler depends on the 
     * serialization of the controller, which is impractical.
     */
    public function testCannotSerializeScheduler()
    {
        $scheduler = new Zend_Scheduler();

        try {
            serialize($scheduler);
        } catch (Zend_Scheduler_Exception $e) {
            return true;
        }

        $this->fail('Did not prevent serialization of scheduler');
    }

    /**
     * Tests if a task can interpret basic rules.
     */
    public function testCanInterpretBasicRules()
    {
        $task = new Zend_Scheduler_Task();
        $task->setTime(mktime(23, 59, 59, 12, 31, 2006));

        $task->setMonths('December');
        $this->assertTrue($task->isScheduled(), 'Month rule was not interpreted correctly');

        $task->setDays('31');
        $this->assertTrue($task->isScheduled(), 'Day rule was not interpreted correctly');

        $task->setDays('last');
        $this->assertTrue($task->isScheduled(), "Keyword 'last' was not interpreted correctly");

        $task->setWeekdays('Sunday');
        $this->assertTrue($task->isScheduled(), 'Weekday rule was not interpreted correctly');

        $task->setHours('23');
        $this->assertTrue($task->isScheduled(), 'Hour rule was not interpreted correctly');

        $task->setMinutes('59');
        $this->assertTrue($task->isScheduled(), 'Minute rule was not interpreted correctly');
    }

    /**
     * Tests if a task can interpret the 'earliest run' rule.
     */
    public function testCanInterpretEarliestRunRule()
    {
        $task = new Zend_Scheduler_Task();
        $task->setTime(mktime(23, 59, 59, 12, 31, 2006));

        $task->setEarliestRun('2007-01-01T00:00:00');
        $this->assertFalse($task->isScheduled(), 'Earliest run rule was not interpreted correctly');

        $task->setEarliestRun('2006-01-01T00:00:00');
        $this->assertTrue($task->isScheduled(), 'Earliest run rule was not interpreted correctly');
    }

    /**
     * Tests if a task can interpret the 'latest run' rule.
     */
    public function testCanInterpretLatestRunRule()
    {
        $task = new Zend_Scheduler_Task();
        $task->setTime(mktime(23, 59, 59, 12, 31, 2006));

        $task->setLatestRun('2006-01-01T00:00:00');
        $this->assertFalse($task->isScheduled(), 'Latest run rule was not interpreted correctly');

        $task->setLatestRun('2007-01-01T00:00:00');
        $this->assertTrue($task->isScheduled(), 'Latest run rule was not interpreted correctly');
    }

    /**
     * Tests if a task can interpret rules with ranges, incuding those that 
     * wrap from the maximum value to the minimum value.
     */
    public function testCanInterpretRangeRules()
    {
        $task = new Zend_Scheduler_Task();
        $task->setTime(mktime(23, 59, 59, 12, 31, 2006));

        $task->setMonths('October-December');
        $this->assertTrue($task->isScheduled(), 'Standard month range rule was not interpreted correctly');

        $task->setMonths('November-February');
        $this->assertTrue($task->isScheduled(), 'Wrap-around month range rule was not interpreted correctly');

        $task->setDays('25-31');
        $this->assertTrue($task->isScheduled(), 'Day range rule was not interpreted correctly');

        $task->setDays('25-3');
        $this->assertTrue($task->isScheduled(), 'Wrap-around day range rule was not interpreted correctly');

        $task->setDays('25-last');
        $this->assertTrue($task->isScheduled(), "Day range rule using keyword 'last' was not interpreted correctly");

        $task->setDays('last-3');
        $this->assertTrue($task->isScheduled(), "Wrap-around day range rule using keyword 'last' was not interpreted correctly");

        $task->setWeekdays('Sunday-Wednesday');
        $this->assertTrue($task->isScheduled(), 'Weekday range rule was not interpreted correctly');

        $task->setWeekdays('Friday-Wednesday');
        $this->assertTrue($task->isScheduled(), 'Wrap-around weekday range rule was not interpreted correctly');

        $task->setHours('18-23');
        $this->assertTrue($task->isScheduled(), 'Hour range rule was not interpreted correctly');

        $task->setHours('18-5');
        $this->assertTrue($task->isScheduled(), 'Wrap-around hour range rule was not interpreted correctly');

        $task->setMinutes('30-59');
        $this->assertTrue($task->isScheduled(), 'Minute range rule was not interpreted correctly');

        $task->setMinutes('50-10');
        $this->assertTrue($task->isScheduled(), 'Wrap-around minute range rule was not interpreted correctly');
    }

    /**
     * Tests if a task can interpret incremental step rules.
     */
    public function testCanInterpretStepRules()
    {
        $task = new Zend_Scheduler_Task();
        $task->setTime(mktime(23, 59, 59, 12, 31, 2006));

        $task->setDays('3/2');
        $this->assertTrue($task->isScheduled(), 'Day step rule was not interpreted correctly');

        $task->setHours('5/3');
        $this->assertTrue($task->isScheduled(), 'Hour step rule was not interpreted correctly');

        $task->setMinutes('5/9');
        $this->assertTrue($task->isScheduled(), 'Minute step rule was not interpreted correctly');
    }

    /**
     * Tests if a task gives an error when trying to use step rules in ways 
     * which are not permitted.
     *
     * This test applies to months.
     */
    public function testCannotInterpretInvalidStepRules1()
    {
        $task = new Zend_Scheduler_Task();
        $task->setTime(mktime(23, 59, 59, 12, 31, 2006));

        $task->setMonths('January/3');

        try {
            $task->isScheduled();
        } catch (Zend_Scheduler_Exception $e) {
            return true;
        }

        $this->fail('Did not prevent invalid month step rule');
    }

    /**
     * Tests if a task gives an error when trying to use step rules in ways 
     * which are not permitted.
     *
     * This test applies to days of the week ('weekdays').
     */
    public function testCannotInterpretInvalidStepRules2()
    {
        $task = new Zend_Scheduler_Task();
        $task->setTime(mktime(23, 59, 59, 12, 31, 2006));

        $task->setWeekdays('Monday/3');

        try {
            $task->isScheduled();
        } catch (Zend_Scheduler_Exception $e) {
            return true;
        }

        $this->fail('Did not prevent invalid weekday step rule');
    }

    /**
     * Tests if a task can be successfully dispatched.
     *
     * @see Zend_Controller_Front_Mock
     */
    public function testCanDispatchTask()
    {
        $controller = Zend_Controller_Front::getInstance();
        $controller->setControllerDirectory('controllers');
        $controller->returnResponse(true);
        $controller->throwExceptions(true);

        $scheduler = new Zend_Scheduler();

        $task = new Zend_Scheduler_Task();
        $task->setFrontController($controller);
        $task->setRequest('/');
        $scheduler->addTask('test', $task);

        $responses = $scheduler->run();

        $this->assertTrue(isset($responses['test']), 'Received empty response');
    }

    /**
     * Tests if a valid (i.e., included) backend can be loaded.
     */
    public function testCanLoadValidBackend()
    {
        $scheduler = new Zend_Scheduler();

        try {
            $scheduler->setBackend('File');
        } catch (Exception $e) {
            $this->fail('Denied the use of a valid backend');
        }
    }

    /**
     * Tests if the 'File' backend is functional.
     */
    public function testCanUseFileBackend()
    {
        $queue   = dirname(__FILE__) . '/task.queue';
        $backend = new Zend_Scheduler_Backend_File(array('filename' => $queue));
        $this->_canUseBackend($backend);
        unset($queue);
    }

    
    /**
     * Tests if the 'Db' backend is functional.
     */
    public function testCanUseDbBackend()
    {
        $backend = new Zend_Scheduler_Backend_Db(array(
                'DbAdapter' => Tinebase_Core::getDb(),
                'tableName' => 'tine20_scheduler',
                'taskClass' => 'Tinebase_Scheduler_Task'
        ));
        $tasks = $backend->loadQueue();
        $taskCount = (count($tasks) > 0);
        $this->assertTrue($taskCount);
    }
    
    /**
     * Tests any given backend.
     *
     * @param Zend_Scheduler $scheduler
     * @param Zend_Scheduler_Backend_Abstract $backend
     */
    protected function _canUseBackend(Zend_Scheduler_Backend_Abstract $backend)
    {
        $scheduler = new Zend_Scheduler($backend);
        
        try {            
            $tasks = $backend->loadQueue();
        } catch (Zend_Scheduler_Exception $e) {
            $this->fail('Could not load task queue');
        }
        
        $taskCount = (count($tasks) > 0);
        $this->assertTrue($taskCount);

    }
}
