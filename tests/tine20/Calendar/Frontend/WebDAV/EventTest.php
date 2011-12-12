<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Frontend_WebDAV_EventTest::main');
}

/**
 * Test class for Calendar_Frontend_WebDAV_Event
 */
class Calendar_Frontend_WebDAV_EventTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV Event Tests');
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
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        $this->objects['containerToDelete'][] = $this->objects['initialContainer'];
        
        $this->objects['eventsToDelete'] = array();
        
        $_SERVER['REQUEST_URI'] = 'lars';
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['eventsToDelete'] as $event) {
            $event->delete();
        }
        
        foreach ($this->objects['containerToDelete'] as $containerId) {
            $containerId = $containerId instanceof Tinebase_Model_Container ? $containerId->getId() : $containerId;
            
            try {
                Tinebase_Container::getInstance()->deleteContainer($containerId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
    }
    
    /**
     * test create contact
     * 
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEvent()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $this->objects['eventsToDelete'][] = $event;
        
        $record = $event->getRecord();

        $this->assertEquals('New Event', $record->summary);
        
        return $event;
    }
        
    /**
     * test create repeating event
     *
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateRepeatingEvent()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
    
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_daily.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendarStream);
    
        $this->objects['eventsToDelete'][] = $event;
    
        $record = $event->getRecord();
        #var_dump($record->exdate->toArray());
        $this->assertEquals('New Event', $record->summary);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $record->exdate[0]->organizer);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $record->exdate[0]->attendee[0]->user_id);
        return $event;
    }
    
    /**
     * test get vcard
     * @depends testCreateEvent
     */
    public function testGetVCalendar()
    {
        $event = $this->testCreateEvent();
        
        $vcalendar = stream_get_contents($event->get());
        
        //var_dump($vcalendar);
        
        $this->assertContains('SUMMARY:New Event', $vcalendar);
        $this->assertContains('ORGANIZER;CN=', $vcalendar);
    }
    
    /**
     * test get vcard
     */
    public function testGetRepeatingVCalendar()
    {
        $event = $this->testCreateRepeatingEvent();
    
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $event->getRecord()->getId());
        
        $vcalendar = stream_get_contents($event->get());
        #var_dump($vcalendar);
        $this->assertContains('SUMMARY:New Event', $vcalendar);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111005T080000Z', $vcalendar);
        $this->assertContains('RECURRENCE-ID;VALUE=DATE-TIME;TZID=Europe/Berlin:20111008T100000', $vcalendar);
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventFromThunderbird()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $event = $this->testCreateEvent();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        #var_dump($record->attendee[0]->toArray());
        $this->assertEquals('New Event', $record->summary);
        $this->assertTrue(! empty($record->attendee[0]["status_authkey"]));
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventFromMacOsX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $event = $this->testCreateEvent();
    
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
    
        $event->put($vcalendarStream);
    
        $record = $event->getRecord();
    
        $this->assertEquals('New Event', $record->summary);
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventFromGenericClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $event = $this->testCreateEvent();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('New Event', $record->summary);
    }
    
    /**
     * test get name of vcard
     */
    public function testGetNameOfEvent()
    {
        $event = $this->testCreateEvent();
        
        $record = $event->getRecord();
        
        $this->assertEquals($event->getName(), $record->getId() . '.ics');
    }
    
    /**
     * return vcalendar as string and replace organizers email address with emailaddess of current user
     * 
     * @param string $_filename  file to open
     * @return string
     */
    protected function _getVCalendar($_filename)
    {
        $vcalendar = file_get_contents($_filename);
        
        $vcalendar = preg_replace('/l.kneschke@metaway\n s.de/', Tinebase_Core::getUser()->accountEmailAddress, $vcalendar);
        
        return $vcalendar;
    }
}
