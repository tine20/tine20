<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Tinebase
 */
class Calendar_Frontend_CalDAV_BackendTest extends Calendar_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * @var Calendar_Frontend_CalDAV_Backend
     */
    protected $_uit;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 CalDAV backend tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    public function setUp()
    {
        $this->_uit = new Calendar_Frontend_CalDAV_Backend();
        parent::setUp();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    public function tearDown()
    {

    }
    
    public function testGetCalendarsForUser()
    {
        $DAVuserCalendars = $this->_uit->getCalendarsForUser('principals/' . Tinebase_Core::getUser()->accountLoginName);
        $INTuserCalendars = Tinebase_Core::getUser()->getPersonalContainer('Calendar', Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_READ);
        
        $this->assertEquals(count($INTuserCalendars), count($DAVuserCalendars));
        
        foreach ($DAVuserCalendars as $DAVCalendar) {
            $intCalendar = $INTuserCalendars[$INTuserCalendars->getIndexById($DAVCalendar['id'])];
            $this->assertEquals($intCalendar->name, $DAVCalendar['{DAV:}displayname']);
        }
    }
    
    public function testGetCalendarObjects()
    {
        $event = $this->_getEvent();
        $persisentEvent = Calendar_Controller_Event::getInstance()->create($event);
        
        $DAVEvents = $this->_uit->getCalendarObjects($this->_testCalendar->getId());
        
        $this->assertEquals(1, count($DAVEvents));
        $this->assertEquals($persisentEvent->creation_time->getTimestamp(), $DAVEvents[0]['lastmodified']);
        $this->assertEquals(1, preg_match("/SUMMARY:{$event->summary}\r\n/", $DAVEvents[0]['calendardata']), 'SUMMARY not correct');
    }
}       
