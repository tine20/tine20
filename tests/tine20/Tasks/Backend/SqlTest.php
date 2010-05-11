<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * Test class for Tinebase_User
 * @todo move concurrency tests to controller test!
 */
class Tasks_Backend_SqlTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Tasks_Backend_Sql SQL Backend in test
	 */
	protected $_backend;
	
	/**
	 * @var array test Task 1 data
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
	    $user = Tinebase_Core::getUser();
        $container = $user->getPersonalContainer('Tasks', $user, Tinebase_Model_Grants::GRANT_ADMIN);
        $this->container_id = $container[0]->getId();
        
		$this->_backend = new Tasks_Backend_Sql();
        
		$this->_testTask1 = new Tasks_Model_Task(array(
            // tine record fields
	        'container_id'         => $this->container_id,
	        'created_by'           => 6,
	        'creation_time'        => '2009-03-31 17:35:00',
	        'is_deleted'           => 0,
	        'deleted_time'         => NULL,
	        'deleted_by'           => NULL,
	        // task only fields
	        'percent'              => 70,
	        'completed'            => NULL,
	        'due'                  => '2009-04-30 17:35:00',
	        // ical common fields
	        //'class_id'             => 2,
	        'description'          => str_pad('',1000,'.'),
	        'geo'                  => 0.2345,
	        'location'             => 'here and there',
	        'organizer'            => Tinebase_Core::getUser()->getId(),
	        'priority'             => 2,
	        //'status_id'            => 2,
	        'summary'              => 'our fist test task',
	        'url'                  => 'http://www.testtask.com',
        ));
        
        $this->_persistantTestTask1 = $this->_backend->create($this->_testTask1);
	}
	
	/**
	 * remove stuff from db
	 *
	 */
	public function tearDown()
	{
		// NOTE: cascading delete of dependend stuff due to sql schema
        $db = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks'));
        $db->delete($db->getAdapter()->quoteInto('id = ?', $this->_persistantTestTask1->getId() ));
        Tinebase_Timemachine_ModificationLogTest::purgeLogs($this->_persistantTestTask1->getId());
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
    
    public function testCreateMinimalTask()
    {
        $summary = 'minimal task by phpunit';
        
        $task = new Tasks_Model_Task(array(
            'summary'       => $summary,
            'container_id'  => $this->container_id,
        ));
        $persitantTask = $this->_backend->create($task);
        
        $pagination = new Tasks_Model_Pagination();
        $filter = new Tasks_Model_TaskFilter(array(
            array('field' => 'summary',      'operator' => 'contains', 'value' => $summary),
            array('field' => 'container_id', 'operator' => 'equals',   'value' => $task->container_id)
        ));

        $tasks = $this->_backend->search($filter, $pagination);
        $this->assertEquals(1, count($tasks));
        
        $db = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks'));
        $db->delete("summary LIKE '$summary'" );
        Tinebase_Timemachine_ModificationLogTest::purgeLogs($persitantTask->getId());
        
    }
    
    /**
     * Note: delete sets is_deleted and does not delete records 
     * from table!
     */
    public function testSearchDeletedTask()
    {
    	$testId = $this->_persistantTestTask1->getId();
        $this->_backend->delete($testId);
        
        $filter = new Tasks_Model_TaskFilter(array(
            array('field' => 'summary',      'operator' => 'contains', 'value' => 'our fist test task'),
            array('field' => 'container_id', 'operator' => 'equals',   'value' => $this->_persistantTestTask1->container_id)
        ));
        
        $pagination = new Tasks_Model_Pagination();
        $tasks = $this->_backend->search($filter, $pagination);
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
        $this->_backend->delete($testId);
        try {
        	$task = $this->_backend->get($testId);
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
     * search for a task and test searchCount
     *
     */
    public function testSearchTask()
    {
        //create a unique search criteria
        $this->_persistantTestTask1->summary = $this->_persistantTestTask1->getId();
        $this->_backend->update($this->_persistantTestTask1);
        
        $pagination = new Tasks_Model_Pagination();
        
        $filter = new Tasks_Model_TaskFilter(array(
            array('field' => 'query',        'operator' => 'contains', 'value' => $this->_persistantTestTask1->getId()),
            array('field' => 'container_id', 'operator' => 'equals',   'value' => $this->_persistantTestTask1->container_id)
        ));
        
        $tasks = $this->_backend->search($filter, $pagination);
        
        $this->assertEquals(1, count($tasks));
        
        // test search count
        $count = $this->_backend->searchCount($filter);
        $this->assertEquals(1, $count);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tasks_Backend_SqlTest::main') {
    Tasks_Backend_SqlTest::main();
}
