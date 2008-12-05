<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tasks_JsonTest::main();
}

/**
 * Test class for Tasks_JsonTest
 */
class Tasks_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Tasks_Frontend_Json
     */
    protected $_backend;
    
    /**
     * main function
     *
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tasks_JsonTest');
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
        $this->_backend = new Tasks_Frontend_Json();  
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    /**
     * test creation of a task
     *
     */
    public function testCreateTask()
    {
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        
        $this->assertEquals($task['summary'], $returned['summary']);
        $this->assertNotNull($returned['id']);
        
        // test getTask($contextId) as well
        $returnedGet = $this->_backend->getTask($returned['id']);
        $this->assertEquals($task['summary'], $returnedGet['summary']);
        
        $returnedGet = $this->_backend->getTask($returned['id'], '0', '');
        $this->assertEquals($task['summary'], $returnedGet['summary']);
        
        $returnedGet = $this->_backend->getTask('');
        $this->assertEquals($returnedGet['organizer']['accountDisplayName'], 'Tine 2.0 Admin Account');
        $this->assertEquals($returnedGet['container_id']['type'], 'personal');
        
        $this->_backend->deleteTasks(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of a task
     *
     */
    public function testUpdateTask()
    {
        $task = $this->_getTask();
        
        $returned = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        $returned['summary'] = 'new summary';
        
        $updated = $this->_backend->saveTask(Zend_Json::encode($returned));
        $this->assertEquals($returned['summary'], $updated['summary']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteTasks(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * try to search for tasks
     *
     */
    public function testSearchTasks()    
    {
        // create task
        $task = $this->_getTask();
        $task = Tasks_Controller_Task::getInstance()->create($task);
        
        // search tasks
        $tasks = $this->_backend->searchTasks(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        
        // check
        $this->assertEquals(1, $tasks['totalcount']);
        
        // delete task
        // Tasks_Controller_Task::getInstance()->delete($task->getId());
        $this->_backend->deleteTasks(Zend_Json::encode(array($task->getId())));

        // search and check again
        $tasks = $this->_backend->searchTasks(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals(0, $tasks['totalcount']);
    }
    
    /**
     * test create default container
     *
     */
    public function testDefaultContainer()
    {
        $application = 'Tasks';
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        
        $test_container = $this->_backend->getDefaultContainer();
        $this->assertEquals($returned['container_id']['type'], 'personal');
        
        $application_id_1 = $test_container['application_id'];
        $application_id_2 = Tinebase_Application::getInstance()->getApplicationByName($application)->toArray();
        $application_id_2 = $application_id_2['id'];
        
        $this->assertEquals($application_id_1, $application_id_2);
        
        $this->_backend->deleteTasks(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test get status
     *
     */
    public function testGetStatus()
    {
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        $status = $this->_backend->getAllStatus();
        
        $this->assertGreaterThan(0, count($status));
        $this->assertNotEquals('', $status[0]['status_name']);
        
        $this->_backend->deleteTasks(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test get registry data
     *
     */
    public function testGetRegistryData()
    {
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        $regData = $this->_backend->getRegistryData();
        
        $this->assertGreaterThan(0, count($regData['AllStatus']));
        $this->assertNotEquals('', $regData['AllStatus'][0]['status_name']);
        
        $this->_backend->deleteTasks(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * get task record
     *
     * @return Tasks_Model_Task
     * 
     * @todo add task to objects
     */
    protected function _getTask()
    {
        return new Tasks_Model_Task(array(
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
        ));
    }

    /**
     * get filter for task search
     *
     * @return Tasks_Model_Task
     */
    protected function _getFilter()
    {
        // define filter
        return array(
            array('field' => 'containerType', 'operator' => 'equals', 'value' => 'all'),
            array('field' => 'query'        , 'operator' => 'equals', 'value' => 'minimal task by PHPUnit'),
        );
    }
    
    /**
     * get default paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        // define paging
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'summary',
            'dir' => 'ASC',
        );
    }
}

