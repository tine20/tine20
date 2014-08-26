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
     * @var Calendar_Import_CalDav_Client
     */
    protected $_uit = null;
    
    /**
     * lazy init of uit
     *
     * @return Calendar_Import_CalDav_Client
     */
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $caldavClientOptions = array(
                'baseUri' => 'localhost',
                'userName' => Tinebase_Core::getUser()->accountLoginName,
                'password' => Zend_Registry::get('testConfig')->password, // TODO use credential cache?
            );
            $this->_uit = new Calendar_Import_CalDAV_ClientMock($caldavClientOptions, 'MacOSX');
            $this->_uit->setVerifyPeer(false);
        }
        
        return $this->_uit;
    }
    
    /**
     * test import of a single container/calendar of current user
     * 
     * @todo uuid needs to be changed as we need different uuids for tasks + events
     */
    public function testImportCalendars()
    {
        $this->_getUit()->importAllCalendars();
        $calendarUuid = sha1('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/');
        $importedCalendar = Tinebase_Container::getInstance()->getByProperty($calendarUuid, 'uuid');
        
        $this->assertEquals('calendar', $importedCalendar->name);
        $this->assertEquals('Calendar_Model_Event', $importedCalendar->model, print_r($importedCalendar->toArray(), true));
        $this->assertEquals( Tinebase_Core::getUser()->getId(), $importedCalendar->owner_id, print_r($importedCalendar->toArray(), true));
    }
    
    public function testImportEvents()
    {
        $this->markTestIncomplete('TODO: finish test');
        $this->_getUit()->importAllCalendarData();
        // @todo add assertions
    }
    
    public function testUpdateEvents()
    {
        $this->markTestIncomplete('TODO: finish test');
        $this->testImport();
        // @todo change some events
        $this->_getUit()->updateAllCalendarData();
        // @todo add assertions
    }
}
