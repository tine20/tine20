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
        $this->assertEquals(Tinebase_Core::getUser()->accountFirstName, $returnedGet['organizer']['accountFirstName']);
        $this->assertEquals('personal', $returnedGet['container_id']['type']);
        
        $this->_backend->deleteTasks(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test create task with alarm
     *
     */
    public function testCreateTaskWithAlarm()
    {
        $task = $this->_getTaskWithAlarm();
        
        $persistentTaskData = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        
        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($loadedTaskData['alarms']));
        $this->assertEquals('Tasks_Model_Task', $loadedTaskData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedTaskData['alarms'][0]['sent_status']);
        $this->assertTrue(array_key_exists('minutes_before', $loadedTaskData['alarms'][0]), 'minutes_before is missing');
        
        // try to send alarm
        if (isset(Tinebase_Core::getConfig()->smtp)) {
            $event = new Tinebase_Event_Async_Minutely();
            Tinebase_Event::fireEvent($event);
            
            // check alarm status
            $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
            $this->assertEquals(Tinebase_Model_Alarm::STATUS_SUCCESS, $loadedTaskData['alarms'][0]['sent_status']);
        }

        // try to save task without due (alarm should be removed)
        unset($task->due);
        $persistentTaskData = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        $this->assertEquals(0, count($persistentTaskData['alarms']));
    }

    /**
     * test create task with automatic alarm
     *
     */
    public function testCreateTaskWithAutomaticAlarm()
    {
        $task = $this->_getTask();
        
        // set config for automatic alarms
        Tinebase_Config::getInstance()->setConfigForApplication(
            Tinebase_Model_Config::AUTOMATICALARM,
            Zend_Json::encode(array(
                2*24*60,    // 2 days before
                //0           // 0 minutes before
            )),
            'Tasks'
        );
        
        $persistentTaskData = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        
        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($loadedTaskData['alarms']));
        $this->assertEquals('Tasks_Model_Task', $loadedTaskData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedTaskData['alarms'][0]['sent_status']);
        $this->assertTrue(array_key_exists('minutes_before', $loadedTaskData['alarms'][0]), 'minutes_before is missing');
        $this->assertEquals(2*24*60, $loadedTaskData['alarms'][0]['minutes_before']);

        // reset automatic alarms config
        Tinebase_Config::getInstance()->setConfigForApplication(
            Tinebase_Model_Config::AUTOMATICALARM,
            Zend_Json::encode(array()),
            'Tasks'
        );
        
        $this->_backend->deleteTasks($persistentTaskData['id']);
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
        $count = $tasks['totalcount'];
        $this->assertGreaterThan(0, $count);
        
        // delete task
        $this->_backend->deleteTasks(Zend_Json::encode(array($task->getId())));

        // search and check again
        $tasks = $this->_backend->searchTasks(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($count - 1, $tasks['totalcount']);
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
     * test delete organizer of task (and then search task and retrieve single task) 
     * 
     */
    public function testDeleteOrganizer()
    {       
        $organizer = $this->_createUser();
        $organizerId = $organizer->getId();
        
        $task = $this->_getTask();
        $task->organizer = $organizer;      
        $returned = $this->_backend->saveTask(Zend_Json::encode($task->toArray()));
        $taskId = $returned['id'];
               
        // check search tasks- organizer exists
        $tasks = $this->_backend->searchTasks(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals(1, $tasks['totalcount'], 'more (or less) than one tasks found');
        $this->assertEquals($tasks['results'][0]['organizer']['accountId'], $organizerId);

        // check get single task - organizer exists
        $task = $this->_backend->getTask($taskId);
        $this->assertEquals($task['organizer']['accountId'], $organizerId);

        // delete user
        Tinebase_User::getInstance()->deleteUser($organizerId);       

        // test seach search tasks - organizer is deleted
        $tasks = $this->_backend->searchTasks(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals(1, $tasks['totalcount'], 'more (or less) than one tasks found');

        $this->assertEquals($tasks['results'][0]['organizer']['accountDisplayName'], Tinebase_User::getInstance()->getNonExistentUser()->accountDisplayName);

        // test get single task - organizer is deleted
        $task = $this->_backend->getTask($taskId);
        $this->assertEquals($task['organizer']['accountDisplayName'], Tinebase_User::getInstance()->getNonExistentUser()->accountDisplayName);
        
        //Cleanup test objects
        $this->_backend->deleteTasks(Zend_Json::encode(array($taskId)));
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
     * Create and save dummy user object
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _createUser()
    {
        $user = new Tinebase_Model_FullUser(array(
//            'accountId'             => 100,
            'accountLoginName'      => 'creator',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'Creator',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        ));
        Tinebase_User::getInstance()->addUser($user);
        
        return $user;
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
            'due'           => new Zend_Date()
        ));
    }

    /**
     * get task record
     *
     * @return Tasks_Model_Task
     */
    protected function _getTaskWithAlarm()
    {
        $task = new Tasks_Model_Task(array(
            'summary'       => 'minimal task with alarm by PHPUnit::Tasks_ControllerTest',
            'due'           => new Zend_Date()
        ));
        $task->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            array(
                'minutes_before'    => 0
            ),
        ), TRUE);
        return $task;
    }
    
    /**
     * get filter for task search
     *
     * @return Tasks_Model_Task
     */
    protected function _getFilter()
    {
        $date = new Zend_Date();
        
        // define filter
        return array(
            array('field' => 'container_id', 'operator' => 'specialNode', 'value' => 'all'),
            array('field' => 'summary'     , 'operator' => 'contains',    'value' => 'minimal task by PHPUnit'),
            array('field' => 'due'         , 'operator' => 'within',      'value' => 'dayThis'),
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

