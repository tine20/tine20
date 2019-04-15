<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tasks_Convert_Task_VCalendar_Generic
 */
class Tasks_Convert_Task_VCalendar_GenericTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Tasks WebDAV Generic Task Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * test converting vcard from lighting to Tasks_Model_Task
     * 
     * @return Tasks_Model_Task
     */
    public function testConvertToTine20Model()
    {
        $task = $this->_convertHelper(dirname(__FILE__) . '/../../../Import/files/lightning.ics');
        #var_dump($task->toArray());
        
        $this->assertEquals(Tasks_Model_Task::CLASS_PUBLIC,      $task->class);
        $this->assertEquals(Tasks_Model_Priority::NORMAL,        $task->priority);
        $this->assertEquals('IN-PROCESS',                        $task->status);
        $this->assertEquals('New Task',                          $task->summary);
        $this->assertEquals("2013-07-14 16:00:00",               (string)$task->due);
        $this->assertEquals("2013-07-14 10:00:00",               (string)$task->dtstart);
        #$this->assertEquals("2013-07-14 08:45:00",               (string)$task->alarms[0]->alarm_time);
        $this->assertEquals("75",                                (string)$task->alarms[0]->minutes_before);
        $this->assertEquals("This is a descpription\nwith a linebreak and a ; , and : a", $task->description);
        $this->assertEquals(1, count($task->alarms));
        $this->assertContains('CATEGORY 1',                      $task->tags->name);
        $this->assertContains('CATEGORY 2',                      $task->tags->name);
        
        return $task;
    }
    
    /**
     * convert helper
     * 
     * @param string $filename
     * @return Tasks_Model_Task
     */
    protected function _convertHelper($filename)
    {
        $vcalendar = Tasks_Frontend_WebDAV_TaskTest::getVCalendar($filename);
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        $task = $converter->toTine20Model($vcalendar);
        
        return $task;
    }
    
    /**
     * test converting VTODO with status
     */
    public function testConvertToTine20ModelWithStatus()
    {
        $vcalendar = Tasks_Frontend_WebDAV_TaskTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        
        $vcalendar = str_replace('STATUS:IN-PROCESS', 'STATUS:NEEDS-ACTION', $vcalendar);
        $task = $converter->toTine20Model($vcalendar);
        $this->assertEquals('NEEDS-ACTION', $task->status);
        return;
        $vcalendar = str_replace('STATUS:CONFIRMED', 'STATUS:TENTATIVE', $vcalendar);
        $task = $converter->toTine20Model($vcalendar);
        $this->assertEquals(Tasks_Model_Task::STATUS_TENTATIVE, $task->status);
        
        $vcalendar = str_replace('STATUS:TENTATIVE', 'STATUS:CANCELED', $vcalendar);
        $task = $converter->toTine20Model($vcalendar);
        $this->assertEquals(Tasks_Model_Task::STATUS_CANCELED, $task->status);
    }
    
    /**
     * test converting vcard from sogo connector to Tasks_Model_Task
     * @return Tasks_Model_Task
     */
    public function _testConvertFromIcalToTine20Model()
    {
        $vcalendarStream = Tasks_Frontend_WebDAV_TaskTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
    
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
    
        $task = $converter->toTine20Model($vcalendarStream);
    
        #var_dump($task->toArray());
    
        $this->assertEquals(Tasks_Model_Task::CLASS_PRIVATE, $task->class);
        $this->assertEquals('Hamburg',                           $task->location);
        $this->assertEquals('Europe/Berlin',                     $task->originator_tz);
        $this->assertEquals("2011-10-04 10:00:00",               (string)$task->dtend);
        $this->assertEquals("2011-10-04 08:00:00",               (string)$task->dtstart);
        $this->assertEquals("2011-10-04 06:45:00",               (string)$task->alarms[0]->alarm_time);
        $this->assertEquals("75",                                (string)$task->alarms[0]->minutes_before);
    
        return $task;
    }
    
    /**
     * test converting vcard from sogo connector to Tasks_Model_Task 
     */
    public function testConvertToTine20ModelWithUpdate()
    {
        $this->markTestSkipped();
        
        $vcalendarStream = Tasks_Frontend_WebDAV_TaskTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        
        $task = $converter->toTine20Model($vcalendarStream);
        
        // status_authkey must be kept after second convert
        $task->attendee[0]->quantity = 10;
        
        $vcalendar = Tasks_Frontend_WebDAV_TaskTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics');
        // remove alarm part from vcalendar
        $vcalendar = preg_replace('/BEGIN:VALARM.*END:VALARM(\n|\r\n)/s', null, $vcalendar);
        
        $task = $converter->toTine20Model($vcalendar, $task);
        
        $this->assertEquals(10, $task->attendee[0]->quantity);
        $this->assertTrue($task->alarms instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(0, count($task->alarms));
    }    

    /**
     * @depends testConvertToTine20Model
     */
    public function testConvertFromTine20Model()
    {
        $task = $this->testConvertToTine20Model();
        $task->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $task->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $task->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        
        $vcalendar = $converter->fromTine20Model($task)->serialize();
        // var_dump($vcalendar);
        // required fields
        $this->assertContains('VERSION:2.0',                                    $vcalendar, $vcalendar);
        $this->assertContains('PRODID:-//tine20.com//Tine 2.0 Tasks V',         $vcalendar, $vcalendar);
        $this->assertContains('CREATED:20111111T111100Z',       $vcalendar, $vcalendar);
        $this->assertContains('LAST-MODIFIED:20111111T121200Z', $vcalendar, $vcalendar);
        $this->assertContains('DTSTAMP:',                       $vcalendar, $vcalendar);
        $this->assertContains('TZID:Europe/Berlin',               $vcalendar, $vcalendar);
        $this->assertContains('UID:' . $task->uid,                $vcalendar, $vcalendar);
        $this->assertContains('LOCATION:' . $task->location,      $vcalendar, $vcalendar);
        $this->assertContains('CLASS:PUBLIC',                    $vcalendar, $vcalendar);
        $this->assertContains('TZOFFSETFROM:+0100',  $vcalendar, $vcalendar);
        $this->assertContains('TZOFFSETTO:+0200',    $vcalendar, $vcalendar);
        $this->assertContains('TZNAME:CEST',         $vcalendar, $vcalendar);
        $this->assertContains('TZOFFSETFROM:+0200',  $vcalendar, $vcalendar);
        $this->assertContains('TZOFFSETTO:+0100',    $vcalendar, $vcalendar);
        $this->assertContains('TZNAME:CET',          $vcalendar, $vcalendar);
        $this->assertContains('CATEGORIES:CATEGORY 1,CATEGORY 2', $vcalendar, $vcalendar);
    }
    
    public function testConvertFromTine20ModelWithCustomAlarm()
    {
        $task = $this->testConvertToTine20Model();
        $task->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $task->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $task->organizer          = Tinebase_Core::getUser()->contact_id;
        
        $task->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(array(
            'model'            => 'Tasks_Model_Task',
            'alarm_time'       => '2011-10-04 07:10:00',
            'minutes_before'   => Tinebase_Model_Alarm::OPTION_CUSTOM
        )));
        
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($task)->serialize();
        #var_dump($vevent);
        $this->assertContains('TRIGGER;VALUE=DATE-TIME:20111004T071000Z',        $vevent, $vevent);
    }
    
    public function testConvertFromTine20ModelWithStatus()
    {
        $this->markTestIncomplete();
        
        $task = $this->testConvertToTine20Model();
        $task->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $task->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $task->organizer          = Tinebase_Core::getUser()->contact_id;
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        
        $task->status = Tasks_Model_Task::STATUS_CONFIRMED;
        $vevent = $converter->fromTine20Model($task)->serialize();
        #var_dump($vevent);
        $this->assertContains('STATUS:CONFIRMED',        $vevent, $vevent);
        
        $task->is_deleted = 1;
        $vevent = $converter->fromTine20Model($task)->serialize();
        #var_dump($vevent);
        $this->assertContains('STATUS:CANCELED',        $vevent, $vevent);
    }
    
    public function testConvertToTine20ModelWithCustomAlarm()
    {
        $this->markTestSkipped();
        $vcalendar = Tasks_Frontend_WebDAV_TaskTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/event_with_custom_alarm.ics');
        
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        
        $task = $converter->toTine20Model($vcalendar);
        
        $this->assertTrue($task->alarms instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(1, count($task->alarms));
        
        $alarm = $task->alarms->getFirstRecord();
        
        $this->assertEquals(Tinebase_Model_Alarm::OPTION_CUSTOM, $alarm->minutes_before);
        $this->assertEquals('2012-02-14 17:00:00', $alarm->alarm_time->toString());
    }
}
