<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Thomas Pawassarat <tomp@topanet.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tasks_Convert_Task_VCalendar_EMClient
 */
class Tasks_Convert_Task_VCalendar_EMClientTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Tasks WebDAV EM Client Task Tests');
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
        $task = $this->_convertHelper(dirname(__FILE__) . '/../../../Import/files/emtask.ics');
        #var_dump($task->toArray());
        
        $this->assertEquals(Tasks_Model_Task::CLASS_PUBLIC,      $task->class);
        $this->assertEquals('IN-PROCESS',                        $task->status);
        $this->assertEquals('Betreff',                          $task->summary);
        $this->assertEquals("2013-10-15 11:00:00",               (string)$task->due);
        $this->assertEquals("2013-10-15 10:00:00",               (string)$task->dtstart);
        $this->assertEquals("Beschreibung\nmit\nUmbruch", $task->description);

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
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_EMCLIENT);
        $task = $converter->toTine20Model($vcalendar);
        
        return $task;
    }
    
    /**
     * test converting VTODO with status
     */
    public function testConvertToTine20ModelWithStatus()
    {
        #$this->markTestSkipped();
        $vcalendar = Tasks_Frontend_WebDAV_TaskTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/emtask.ics', 'r');
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_EMCLIENT);
        
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
        $vcalendarStream = Tasks_Frontend_WebDAV_TaskTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/emtask.ics', 'r');
    
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_EMCLIENT);
    
        $task = $converter->toTine20Model($vcalendarStream);
    
        #var_dump($task->toArray());
    
        $this->assertEquals(Tasks_Model_Task::CLASS_PRIVATE, $task->class);
        $this->assertEquals('Ort',                               $task->location);
        $this->assertEquals('Europe/Berlin',                     $task->originator_tz);
        $this->assertEquals("2011-10-04 10:00:00",               (string)$task->dtend);
        $this->assertEquals("2011-10-04 08:00:00",               (string)$task->dtstart);

        return $task;
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
        
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_EMCLIENT);
        
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
    }
}