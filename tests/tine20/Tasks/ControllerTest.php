<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Relations
 */
class Tasks_ControllerTest extends PHPUnit_Framework_TestCase //Tinebase_AbstractControllerTest
{   
	/**
     * @var array test Task 1 data
     */
    protected $_testTask1;
    
	/**
     * @var Tasks_Model_Task persistant (readout from db after persistant creation) test Task 1
     */
    protected $_persistantTestTask1;
    
    /**
     * application name of the controller to test
     *
     * @var string
     */
    protected $_appName = 'Tasks';
    
    /**
     * Name of the model(s) this controller handels
     *
     * @var array
     */
    protected $_modelNames = array('Tasks_Model_Task' => 'Task');
    
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tasks_ControllerTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_controller = Tasks_Controller_Task::getInstance();
        $this->_minimalDatas = array('Task' => array(
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
        ));
        
        $this->_testTask1 = new Tasks_Model_Task(array(
            // tine record fields
            'container_id'         => NULL,
            'created_by'           => 6,
            'creation_time'        => Tinebase_DateTime::now(),
            'is_deleted'           => 0,
            'deleted_time'         => NULL,
            'deleted_by'           => NULL,
            // task only fields
            'percent'              => 70,
            'completed'            => NULL,
            'due'                  => Tinebase_DateTime::now()->addMonth(1),
            // ical common fields
            //'class_id'             => 2,
            'description'          => str_pad('',1000,'.'),
            'geo'                  => 0.2345,
            'location'             => 'here and there',
            'organizer'            => 4,
            'priority'             => 2,
            //'status'               => 'NEEDS-ACTION',
            'summary'              => 'our first test task',
            'url'                  => 'http://www.testtask.com',
        ),true, false);
        $this->_testTask1->convertDates = true;
        
        $this->_persistantTestTask1 = $this->_controller->create($this->_testTask1);
        
        //parent::setUp();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        //parent::tearDown();
    }

    /**
     * tests if completed gets deleted when status is open
     *
     */
    public function testCompletedNULL()
    {
        $task = new Tasks_Model_Task($this->_minimalDatas['Task']);
        $task->status = $this->_getStatus()->getId();
        $task->completed = Tinebase_DateTime::now();
        
        $pTask = $this->_controller->create($task);
        $this->assertNull($pTask->completed);
        
        $this->_controller->delete($pTask->getId());
    }
    
    public function testCompletedViaStatus()
    {
        $task = new Tasks_Model_Task($this->_minimalDatas['Task']);
        $task->status = $this->_getStatus(false)->getId();
        //$task->completed = Tinebase_DateTime::now();
        
        $pTask = $this->_controller->create($task);
        $this->assertTrue($pTask->completed instanceof DateTime);
        
        $this->_controller->delete($pTask->getId());
    }
    
    /**
     * returns a status which is defined as open state
     *
     * @return Tasks_Model_Status
     */
    protected function _getStatus($_open=true)
    {
        foreach (Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records as $idx => $status) {
            if (! ($status->is_open xor $_open)) {
                return $status;
            }
        }
    }
    
    /**
     * test basic update function
     *
     */
    public function testUpdateTask()
    {
        $nowTs = Tinebase_DateTime::now()->getTimestamp();
        $task = clone $this->_persistantTestTask1;
        
        $task->summary = 'Update of test task 1';
        $utask = $this->_controller->update($task);
        
        foreach ($task as $field => $value) {
            switch ($field) {
                case 'last_modified_time':
                    $this->assertGreaterThanOrEqual($nowTs, $utask->last_modified_time->getTimestamp(),'', 1);
                    break;
                case 'last_modified_by':
                    $this->assertEquals(Zend_Registry::get('currentAccount')->getId(), $utask->last_modified_by);
                    break;
                case 'notes':
                	break;
                default:
                    $this->assertEquals($value, $utask->$field, "field $field not equal.");
            }
        }
        return $utask;
    }
    
    public function testNonConcurrentUpdate()
    {
        $utask = $this->testUpdateTask();
        
        sleep(1);
        $nonConflictTask = clone $utask;
        $nonConflictTask->summary = 'Second Update of test task 1';
        return $this->_controller->update($nonConflictTask);
    }
    
    public function testConcurrencyResolveableSameValue() {
        $utask = $this->testUpdateTask();
        
        sleep(1);
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        
        return $this->_controller->update($resolvableConcurrencyTask);
    }
    
    public function testConcurrencyResolveableOtherField() {
        $utask = $this->testUpdateTask();
        
        sleep(1);
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        $this->_controller->update($resolvableConcurrencyTask);
        
        sleep(1);
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->description = 'other field';
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        $this->_controller->update($resolvableConcurrencyTask);
    }

    public function testConcurrencyDateTime()
    {
        $utask = $this->testUpdateTask();
        
        sleep(1);
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        $this->_controller->update($resolvableConcurrencyTask);
        
        sleep(1);
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->due = $resolvableConcurrencyTask->due->addMonth(1);
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        $this->_controller->update($resolvableConcurrencyTask);
    }
    
    /**
     * test if non resolvable concurrency problem gets detected
     *
     */
    public function testConcurrencyFail()
    {
        $utask = $this->testUpdateTask();
        
        sleep(1);
        $conflictTask = clone $utask;
        $conflictTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $conflictTask->summary = 'Non resolvable conflict';
        $this->setExpectedException('Tinebase_Timemachine_Exception_ConcurrencyConflict');
        $this->_controller->update($conflictTask);
    }
    
    /**
     * 2009-07-14 concurrency management on newly created records 
     */
    public function testConcurrencyFromCreatedTask()
    {
    	$utask = $this->testUpdateTask();
    	sleep(1);
    	
    	$ctask = clone $this->_persistantTestTask1;
    	$ctask->description = 'testConcurrencyFromCreatedTask';
    	
    	$u2task = $this->_controller->update($ctask);
    }
}
