<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tasks_Frontend_WebDAV_Task
 */
class Tasks_Frontend_WebDAV_TaskTest extends Tasks_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Tasks WebDAV Task Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
        )));
        $this->objects['sharedContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
        )));
        
        $prefs = new Tasks_Preference();
        $prefs->setValue(Tasks_Preference::DEFAULTTASKLIST, $this->objects['initialContainer']->getId());
        
        $_SERVER['REQUEST_URI'] = 'lars';
    }

    /**
     * test create task
     * 
     * @return Tasks_Frontend_WebDAV_Task
     */
    public function testCreateTask()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $task = Tasks_Frontend_WebDAV_Task::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $record = $task->getRecord();

        $this->assertEquals('New Task', $record->summary, 'summary');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $record->organizer, 'organizer');
        
        return $task;
    }
    
    /**
     * test create task
     * 
     * @return Tasks_Frontend_WebDAV_Task
     */
    public function testCreateMinimalTask()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning-minimal.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $task = Tasks_Frontend_WebDAV_Task::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $record = $task->getRecord();

        $this->assertEquals('New Task', $record->summary);
        
        return $task;
    }
    
    /**
     * create an event which already exists on the server
     * - this happen when the client moves an event to another calendar -> see testMove*
     * - or when an client processes an iMIP which is not already loaded by CalDAV
     */
    public function testCreateTaskWhichExistsAlready()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $oldUserAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $existingTask = $this->testCreateTask();
        $existingRecord = $existingTask->getRecord();
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $task = Tasks_Frontend_WebDAV_Task::create($this->objects['initialContainer'], $existingTask->getRecord()->uid . '.ics', $vcalendar);
        
        if (isset($oldUserAgent)) {
            $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent;
        }
        
        $record = $task->getRecord();
        
        $this->assertEquals($existingRecord->getId(), $record->getId(), 'event got duplicated');
    }
    
    /**
     * test create repeating event
     *
     * @return Tasks_Frontend_WebDAV_Task
     */
    public function testCreateRepeatingTask()
    {
        $this->markTestIncomplete('repeating tasks are not yet supported');
        
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
    
        $vcalendarStream = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_daily.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $task = Tasks_Frontend_WebDAV_Task::create($this->objects['initialContainer'], "$id.ics", $vcalendarStream);
        $this->_checkExdate($task);
    
        return $task;
    }
    
    /**
     * test get vcard
     * @depends testCreateTask
     */
    public function testGetVCalendar()
    {
        $task = $this->testCreateTask();
        
        $vcalendar = stream_get_contents($task->get());
        
        //var_dump($vcalendar);
        
        $this->assertContains('SUMMARY:New Task', $vcalendar);
        $this->assertContains('ORGANIZER;CN=', $vcalendar);
    }
    
    /**
     * test updating existing event
     */
    public function testPutTaskFromThunderbird()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $task = $this->testCreateTask();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $task->put($vcalendarStream);
        
        $record = $task->getRecord();
        
        $this->assertEquals('New Task', $record->summary);
    }
    
    /**
     * test updating existing task
     */
    public function testPutTaskFromMacOsX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $task = $this->testCreateTask();
    
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/apple-reminder-minimal.ics', 'r');
    
        $task->put($vcalendarStream);
    
        $record = $task->getRecord();
    
        $this->assertEquals('high priority reminder', $record->summary);
    }
    
    /**
     * test updating existing event
     */
    public function testPutTaskFromGenericClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $task = $this->testCreateTask();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $task->put($vcalendarStream);
        
        $record = $task->getRecord();
        
        $this->assertEquals('New Task', $record->summary);
    }
    
    public function testPutTaskMultipleAlarms()
    {
        $this->markTestIncomplete('add VCalendar with multiple alarms');
        
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $task = $this->testCreateTask();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/event_with_multiple_alarm.ics', 'r');
        
        $task->put($vcalendarStream);
        
        $record = $task->getRecord();
        
        $this->assertEquals('3', count($record->alarms));
    }
    
    /**
     * test get name of vcard
     */
    public function testGetNameOfTask()
    {
        $task = $this->testCreateTask();
        
        $record = $task->getRecord();
        
        $this->assertEquals($task->getName(), $record->getId() . '.ics');
    }
    
    /**
     * move event orig container shared -> personal
     */
    public function testMoveOriginTask()
    {
        $this->markTestIncomplete('move logic has to be reviewed');
        
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $task = Tasks_Frontend_WebDAV_Task::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        // move event (origin container)
        Tasks_Frontend_WebDAV_Task::create($this->objects['initialContainer'], "$id.ics", stream_get_contents($task->get()));
        $oldEvent = new Tasks_Frontend_WebDAV_Task($this->objects['sharedContainer'], "$id.ics");
        $oldEvent->delete();
        
        $loadedEvent = new Tasks_Frontend_WebDAV_Task($this->objects['initialContainer'], "$id.ics");
        $this->assertEquals($this->objects['initialContainer']->getId(), $loadedEvent->getRecord()->container_id, 'origin container not updated');
        
    }
    
    /**
     * return vcalendar as string and replace organizers email address with emailaddess of current user
     * 
     * @param string $_filename  file to open
     * @return string
     */
    public static function getVCalendar($_filename)
    {
        $vcalendar = file_get_contents($_filename);
        
        $unittestUserEmail = Tinebase_Core::getUser()->accountEmailAddress;
        $vcalendar = preg_replace(
            array(
                '/l.kneschke@metaway\n s.de/',
                '/unittest@\n tine20.org/',
                '/un\n ittest@tine20.org/',
                '/unittest@tine20.org/',
                '/unittest@ti\n ne20.org/',
                '/pwulf\n @tine20.org/',
            ), 
            array(
                $unittestUserEmail,
                $unittestUserEmail,
                $unittestUserEmail,
                $unittestUserEmail,
                $unittestUserEmail,
                Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'))->accountEmailAddress,
            ), 
            $vcalendar
        );
        
        return $vcalendar;
    }
}
