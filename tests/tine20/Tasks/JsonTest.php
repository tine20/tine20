<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tasks_JsonTest
 */
class Tasks_JsonTest extends TestCase
{
    /**
     * Backend
     *
     * @var Tasks_Frontend_Json
     */
    protected $_backend;
    
    /**
     * smtp config array
     * 
     * @var array
     */
    protected $_smtpConfig = array();

    /**
     * smtp config changed
     * 
     * @var array
     */
    protected $_smtpConfigChanged = FALSE;

    /**
     * smtp transport
     * 
     * @var Zend_Mail_Transport_Abstract
     */
    protected $_smtpTransport = NULL;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_backend = new Tasks_Frontend_Json();
        $this->_smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        $this->_smtpTransport = Tinebase_Smtp::getDefaultTransport();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if ($this->_smtpConfigChanged) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $this->_smtpConfig);
            Tinebase_Smtp::setDefaultTransport($this->_smtpTransport);
        }

        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, false);

        parent::tearDown();
    }
    
    /**
     * test creation of a task
     *
     */
    public function testCreateTask()
    {
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask($task->toArray());
        
        $this->assertEquals($task['summary'], $returned['summary']);
        $this->assertNotNull($returned['id']);
        
        // test getTask($contextId) as well
        $returnedGet = $this->_backend->getTask($returned['id']);
        $this->assertEquals($task['summary'], $returnedGet['summary']);
        
        $returnedGet = $this->_backend->getTask($returned['id'], '0', '');
        $this->assertEquals($task['summary'], $returnedGet['summary']);
        
        $this->_backend->deleteTasks(array($returned['id']));
    }
    

    /**
     * test create task with alarm
     *
     */
    public function testCreateTaskWithAlarmTime()
    {
        $task = $this->_getTaskWithAlarm(array(
            'alarm_time'        => Tinebase_DateTime::now(),
            'minutes_before'    => 'custom',
        ));
        
        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        
        $this->_checkAlarm($persistentTaskData);
    }
    
    /**
     * test create task with alarm
     */
    public function testCreateTaskWithAlarm()
    {
        $task = $this->_getTaskWithAlarm();
        
        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        
        $this->_checkAlarm($loadedTaskData);
        $this->_sendAlarm();
        
        // check alarm status
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_SUCCESS, $loadedTaskData['alarms'][0]['sent_status']);

        // try to save task without due (alarm should be removed)
        unset($task->due);
        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $this->assertTrue(isset($persistentTaskData['alarms']));
        $this->assertEquals(0, count($persistentTaskData['alarms']));
    }
    
    /**
     * test alarm sending failure (with wrong stmp user/password)
     *
     * @group nogitlabci
     * gitlabci: should not send message with wrong pw - maybe smtp server is not configured correctly? ... Expected: failure, Actual: success
     */
    public function testAlarmSendingFailure()
    {
        if (empty($this->_smtpConfig)) {
             $this->markTestSkipped('No SMTP config found.');
        }
        
        // send old alarms first
        $this->_sendAlarm();

        $task = $this->_getTaskWithAlarm();
        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        
        // set wrong smtp user/password
        $wrongCredentialsConfig = $this->_smtpConfig;
        $wrongCredentialsConfig['username'] = $this->_getEmailAddress();
        $wrongCredentialsConfig['password'] = 'wrongpw';
        Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $wrongCredentialsConfig);
        $this->_smtpConfigChanged = TRUE;
        Tinebase_Smtp::setDefaultTransport(NULL);
        $this->_sendAlarm();
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_FAILURE, $loadedTaskData['alarms'][0]['sent_status'],
            'should not send message with wrong pw - maybe smtp server is not configured correctly?');
        $this->assertContains('5.7.8 Error: authentication failed', $loadedTaskData['alarms'][0]['sent_message'],
            'got: ' . $loadedTaskData['alarms'][0]['sent_message']);
    }
    
    /**
     * send alarm via scheduler
     */
    protected function _sendAlarm()
    {
        $scheduler = Tinebase_Core::getScheduler();
        /** @var Tinebase_Model_SchedulerTask $task */
        $task = $scheduler->getBackend()->getByProperty('Tinebase_Alarm', 'name');
        $task->config->run();
    }

    /**
     * create scheduler task
     * 
     * @return Tinebase_Scheduler_Task
     */
    protected function _createTask()
    {
        $request = new Zend_Controller_Request_Http();
        $request->setControllerName('Tinebase_Alarm');
        $request->setActionName('sendPendingAlarms');
        $request->setParam('eventName', 'Tinebase_Event_Async_Minutely');
        
        $task = new Tinebase_Scheduler_Task();
        $task->setMonths("Jan-Dec");
        $task->setWeekdays("Sun-Sat");
        $task->setDays("1-31");
        $task->setHours("0-23");
        $task->setMinutes("0/1");
        $task->setRequest($request);
        return $task;
    }
    
    /**
     * check alarm of task
     * 
     * @param array $_taskData
     */
    protected function _checkAlarm($_taskData)
    {
        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($_taskData['alarms']));
        $this->assertEquals('Tasks_Model_Task', $_taskData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $_taskData['alarms'][0]['sent_status']);
        $this->assertTrue((isset($_taskData['alarms'][0]['minutes_before']) || array_key_exists('minutes_before', $_taskData['alarms'][0])), 'minutes_before is missing');
    }
    
    /**
     * test create task with automatic alarm
     *
     */
    public function testCreateTaskWithAutomaticAlarm()
    {
        $task = $this->_getTask();
        
        // set config for automatic alarms
        Tasks_Config::getInstance()->set(
            Tinebase_Config::AUTOMATICALARM,
            array(
                2*24*60,    // 2 days before
                //0           // 0 minutes before
            )
        );
        
        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        
        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($loadedTaskData['alarms']));
        $this->assertEquals('Tasks_Model_Task', $loadedTaskData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedTaskData['alarms'][0]['sent_status']);
        $this->assertTrue((isset($loadedTaskData['alarms'][0]['minutes_before']) || array_key_exists('minutes_before', $loadedTaskData['alarms'][0])), 'minutes_before is missing');
        $this->assertEquals(2*24*60, $loadedTaskData['alarms'][0]['minutes_before']);

       // reset automatic alarms config
        Tasks_Config::getInstance()->set(
            Tinebase_Config::AUTOMATICALARM,
            array()
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
        
        $returned = $this->_backend->saveTask($task->toArray());
        $returned['summary'] = 'new summary';
        
        $updated = $this->_backend->saveTask($returned);
        $this->assertEquals($returned['summary'], $updated['summary']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteTasks(array($returned['id']));
    }
    
    /**
     * try to search for tasks
     *
     */
    public function testSearchTasks()    
    {
        // create task
        $task = $this->_getTask();
        $task = $this->_backend->saveTask($task->toArray());
        
        // search tasks
        $tasks = $this->_backend->searchTasks($this->_getFilter(), $this->_getPaging());
        
        // check
        $count = $tasks['totalcount'];
        $this->assertGreaterThan(0, $count);
        
        // delete task
        $this->_backend->deleteTasks(array($task['id']));

        // search and check again
        $tasks = $this->_backend->searchTasks($this->_getFilter(), $this->_getPaging());
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
        $returned = $this->_backend->saveTask($task->toArray());
        
        $test_container = $this->_backend->getDefaultContainer();
        $this->assertEquals($returned['container_id']['type'], 'personal');
        
        $application_id_1 = $test_container['application_id'];
        $application_id_2 = Tinebase_Application::getInstance()->getApplicationByName($application)->toArray();
        $application_id_2 = $application_id_2['id'];
        
        $this->assertEquals($application_id_1, $application_id_2);
        
        $this->_backend->deleteTasks(array($returned['id']));
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
        $returned = $this->_backend->saveTask($task->toArray());
        $taskId = $returned['id'];
        
        // check search tasks- organizer exists
        $tasks = $this->_backend->searchTasks($this->_getFilter(), $this->_getPaging());
        $this->assertEquals(1, $tasks['totalcount'], 'more (or less) than one tasks found');
        $this->assertEquals($tasks['results'][0]['organizer']['accountId'], $organizerId);

        // check get single task - organizer exists
        $task = $this->_backend->getTask($taskId);
        $this->assertEquals($task['organizer']['accountId'], $organizerId);

        Tinebase_User::getInstance()->deleteUser($organizerId);

        // test seach search tasks - organizer is deleted
        $tasks = $this->_backend->searchTasks($this->_getFilter(), $this->_getPaging());
        $this->assertEquals(1, $tasks['totalcount'], 'more (or less) than one tasks found');

        $organizer = $tasks['results'][0]['organizer'];
        $this->assertTrue(is_array($organizer), 'organizer not resolved: ' . print_r($tasks['results'][0], TRUE));
        $this->assertEquals($organizer['accountDisplayName'], Tinebase_User::getInstance()->getNonExistentUser()->accountDisplayName,
            'accountDisplayName not found in organizer: ' . print_r($organizer, TRUE));

        // test get single task - organizer is deleted
        $task = $this->_backend->getTask($taskId);
        $this->assertEquals($task['organizer']['accountDisplayName'], Tinebase_User::getInstance()->getNonExistentUser()->accountDisplayName);
    }
    
    /**
     * Create and save dummy user object
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _createUser()
    {
        try {
            $user = Tinebase_User::getInstance()->getUserByLoginName('creator');
        } catch (Tinebase_Exception_NotFound $tenf) {
            $user = new Tinebase_Model_FullUser(array(
                'accountLoginName'      => 'creator',
                'accountStatus'         => 'enabled',
                'accountExpires'        => NULL,
                'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
                'accountLastName'       => 'Tine 2.0',
                'accountFirstName'      => 'Creator',
                'accountEmailAddress'   => 'phpunit@metaways.de'
            ));
            $user = Tinebase_User::getInstance()->addUser($user);
        }
        
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
            'due'           => new Tinebase_DateTime("now", Tinebase_Core::getUserTimezone()),
            'organizer'     => Tinebase_Core::getUser()->getId(),
        ));
    }

    /**
     * get task record
     *
     * @param $_alarmData alarm settings
     * @return Tasks_Model_Task
     */
    protected function _getTaskWithAlarm($_alarmData = NULL)
    {
        $task = new Tasks_Model_Task(array(
            'summary'       => 'minimal task with alarm by PHPUnit::Tasks_ControllerTest',
            'due'           => new Tinebase_DateTime()
        ));
        $alarmData = ($_alarmData !== NULL) ? $_alarmData : array(
            'minutes_before'    => 0
        );
        $task->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array($alarmData), TRUE);
        return $task;
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

    /**
     * test advanced search
     *
     * @see 0011492: activate advanced search (search in lead relations)
     */
    public function testAdvancedSearch()
    {
        // create task with lead relation
        $crmTests = new Crm_JsonTest();
        $crmTests->saveLead();

        // activate advanced search
        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, true);

        // search in lead
        $result = $this->_backend->searchTasks(array(array(
            'field' => 'query', 'operator' => 'contains', 'value' => 'PHPUnit LEAD'
        )), array());
        $this->assertEquals(1, $result['totalcount']);
    }
}
