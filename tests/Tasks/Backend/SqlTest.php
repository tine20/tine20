<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tasks_Backend_SqlTest::main');
}

/**
 * Test class for Tinebase_Account
 */
class Tasks_Backend_SqlTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Tasks_Backend_Sql SQL Backend in test
	 */
	protected $_backend;
	/**
	 * @var Tasks_Model_Task test Task 1
	 */
	protected $_testTask1;
	/**
     * @var Tasks_Model_Task persistant (readout from db after persistant creation) test Task 1
     */
	protected $_persistantTestTask1;
	/**
	 * As the backend does not depend on the container classes, we define
	 * two static containers here.
	 * Moreover we generate static initial datas for two tasks which act 
	 * as our test rabits.
	 */
	public function setUp()
	{
		$this->_backend = new Tasks_Backend_Sql();
        
		$this->_testTask1 = new Tasks_Model_Task(array(
            // tine record fields
	        'container_id'         => 5,
	        'created_by'           => 6,
	        'creation_time'        => Zend_Date::now(),
	        'is_deleted'           => 0,
	        'deleted_time'         => NULL,
	        'deleted_by'           => NULL,
	        // task only fields
	        'percent'              => 70,
	        'completed'            => NULL,
	        'due'                  => Zend_Date::now()->addMonth(1),
	        // ical common fields
	        'class_id'             => 1,
	        'description'          => str_pad('',1000,'.'),
	        'geo'                  => 0.2345,
	        'location'             => 'here and there',
	        'organizer'            => 4,
	        'priority'             => 2,
	        'status_id'            => 1,
	        'summary'              => 'our fist test task',
	        'url'                  => 'http://www.testtask.com',
        ),true, false);
        $this->_persistantTestTask1 = $this->_backend->createTask($this->_testTask1);
	}
	public function tearDown()
	{
		// NOTE: cascading delete of dependend stuff due to sql schema
        $db = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks'));
        $db->delete($db->getAdapter()->quoteInto('id = ?', $this->_persistantTestTask1->getId() ));
	}
	/**
	 * If $this->_testTask1 and $this->_persistantTestTask1 are equal, 
	 * creation and single readout of task must have been successfull
	 */
    public function testCreateTask()
    {
    	foreach ($this->_testTask1 as $field => $value) {
    		$pvalue = $this->_persistantTestTask1->$field;
    		$this->assertEquals($value, $pvalue, "$field shoud be $value but is $pvalue");
    	}
    }
    /**
     * Note: delete sets is_deleted and does not delete records 
     * from table!
     */
    public function testSearchDeletedTask()
    {
    	$testId = $this->_persistantTestTask1->getId();
        $this->_backend->deleteTask($testId);
        $filter = new Tasks_Model_Filter();
        $filter->query = 'our fist test task';
        $pagination = new Tasks_Model_Pagination();
        $tasks = $this->_backend->searchTasks($filter, $pagination);
        foreach ($tasks as $task) {
        	$this->assertNotEquals($testId, $task->getId());
        }
    }
    /**
     * Note: delete sets is_deleted and does not delete records 
     * from table!
     */
    public function testGetDeletedTask()
    {
        $testId = $this->_persistantTestTask1->getId();
        $this->_backend->deleteTask($testId);
        try {
        	$task = $this->_backend->getTask($testId);
        	// this point should not be reached!
        	if($task->is_deleted) {
        		$this->fail('Entry getable although it\'s maked as deleted!');
        	} else {
        		$this->fail('Failed to delete (set is_deleted).');
        	}
        } catch (Exception $e) {
        	$this->assertTrue(true);
        }
    }
    /**
     * test basic update function
     */
    public function testUpdateTask()
    {
    	$nowTs = Zend_Date::now()->getTimestamp();
    	$task = clone $this->_persistantTestTask1;
    	$task->summary = 'Update of test task 1';
    	$task->due->addWeek(1);
    	$utask = $this->_backend->updateTask($task);
    	//$this->assertEquals($task, $utask);
    	foreach ($task as $field => $value) {
    		switch ($field) {
    			case 'last_modified_time':
    			    $this->assertEquals($nowTs, $utask->last_modified_time->getTimestamp(),'', 1);
    			    break;
    			case 'last_modified_by':
    				$this->assertEquals(Zend_Registry::get('currentAccount')->getId(), $utask->last_modified_by);
    				break;
    			default:
    				$this->assertEquals($value, $utask->$field);
    		}
    	}
    }
    /**
     * test if non resolvable concurrency problem gets detected
     */
    public function testConcurrency()
    {
        sleep(1);
        $task = clone $this->_persistantTestTask1;
        $task->summary = 'First Update of test task 1';
        $utask = $this->_backend->updateTask($task);
        
        sleep(1);
        $utask->summary = 'Second Update of test task 1';
        $this->_backend->updateTask($utask);
        
        sleep(1);
        $conflictTask = clone $utask;
        $conflictTask->summary = 'Non resolvable conflict';
        try {
            $this->_backend->updateTask($conflictTask);
        	$this->fail('Not detected concurrency conflict');
        } catch (Exception $e) {
        	$this->assertType('Tinebase_Timemachine_Exception_ConcurrencyConflict', $e);
        }
        
        
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tasks_Backend_SqlTest::main') {
    Tasks_Backend_SqlTest::main();
}
