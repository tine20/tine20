<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Calendar_Import_CalDAV
 */
class Calendar_Import_CalDAVTest extends Calendar_TestCase
{
    /**
     * unit in test
     *
     * @var Calendar_Import_CalDAV_ClientMock
     */
    protected $_uit = null;
    
    /**
     * lazy init of uit
     *
     * @return Calendar_Import_CalDAV_ClientMock
     */
    protected function _getUit()
    {
        $testCredentials = TestServer::getInstance()->getTestCredentials();
        if ($this->_uit === null) {
            $caldavClientOptions = array(
                'baseUri' => 'localhost',
                'userName' => Tinebase_Core::getUser()->accountLoginName,
                'password' => $testCredentials['password'],
            );
            $this->_uit = new Calendar_Import_CalDAV_ClientMock($caldavClientOptions, 'MacOSX');
            $this->_uit->setVerifyPeer(false);
        }
        
        return $this->_uit;
    }
    
    /**
     * test import of a single container/calendar of current user
     */
    public function testImportCalendars()
    {
        $this->_getUit()->importAllCalendars();
        
        $importedCalendar = $this->_getImportCalendar();
        
        $this->assertEquals('calendar', $importedCalendar->name);
        $this->assertEquals('#711A76', $importedCalendar->color);
        $this->assertEquals('Calendar_Model_Event', $importedCalendar->model, print_r($importedCalendar->toArray(), true));
        $this->assertEquals( Tinebase_Core::getUser()->getId(), $importedCalendar->owner_id, print_r($importedCalendar->toArray(), true));
    }
    
    /**
     * fetch import calendar
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getImportCalendar()
    {
        $calendarUuid = sha1('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/');
        return Tinebase_Container::getInstance()->getByProperty($calendarUuid, 'uuid');
    }
    
    /**
     * test import of events
     */
    public function testImportEvents()
    {
        $this->testImportCalendars();
        $this->_getUit()->importAllCalendarData();
        
        $importedCalendar = $this->_getImportCalendar();
        
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => array($importedCalendar->getId()))
        )));
        $this->assertEquals(3, count($events));
        $this->assertTrue(array(
            '"bcc36c611f0b60bfee64b4d42e44aa1d"',
            '"8b89914690ad7290fa9a2dc1da490489"',
            '"0b3621a20e9045d8679075db57e881dd"'
        ) == $events->etag);
    }
    
    /**
     * test update of events
     */
    public function testUpdateEvents()
    {
        $this->testImportEvents();
        
        $importedCalendar = $this->_getImportCalendar();
        
        $tine20Event = $this->_getEvent();
        $tine20Event->container_id = $importedCalendar->getId();
        Calendar_Controller_Event::getInstance()->create($tine20Event);
        
        $this->_getUit()->updateServerEvents();
        
        $this->_getUit()->updateAllCalendarData();

        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => array($importedCalendar->getId()))
        )));
        $this->assertEquals(4, count($events));
        $expectedEtags = array(
            '"bcc36c611f0b60bfee64b4d42e44aa1d"', // unchanged
            '"-1030341843%40citrixonlinecom"',    // added on server
            '"aa3621a20e9045d8679075db57e881dd"', // updated
            null,                                 // added on client (tine20)
        );
        sort($expectedEtags);
        $currentEtags = $events->etag;
        sort($currentEtags);
        $this->assertEquals($expectedEtags, $currentEtags, 'etag mismatch');
        
        $updatedEvent = $events->filter('etag', '"aa3621a20e9045d8679075db57e881dd"')->getFirstRecord();
        $this->assertEquals('test update', $updatedEvent->summary);
    }
}
