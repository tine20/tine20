<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Controller_MSEventFacadeTest::main');
}

/**
 * Test class for Calendar_Controller_MSEventFacade
 * 
 * @package     Calendar
 */
class Calendar_Controller_MSEventFacadeTest extends Calendar_TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->_uit = Calendar_Controller_MSEventFacade::getInstance();
    }
    
    public function getTestEvent()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $persistentException = clone $event;
        $persistentException->recurid = clone $persistentException->dtstart;
        $persistentException->recurid->addDay(1);
        $persistentException->dtstart->addDay(1)->addHour(2);
        $persistentException->dtend->addDay(1)->addHour(2);
        $persistentException->summary = 'exception';
        $exceptions->addRecord($persistentException);
        
        $deletedInstance = clone $event;
        $deletedInstance->dtstart->addDay(2);
        $deletedInstance->dtend->addDay(2);
        $deletedInstance->recurid = clone $deletedInstance->dtstart;
        $deletedInstance->is_deleted = TRUE;
        $exceptions->addRecord($deletedInstance);
        
        $event->exdate = $exceptions;
        return $event;
    }
    
    public function testCreate()
    {
        $event = $this->getTestEvent();
        
        $persistentEvent = $this->_uit->create($event);
        
        $this->_assertTestEvent($persistentEvent);
    }
    
    public function testGet()
    {
        $event = $this->getTestEvent();
        
        $persistentEvent = $this->_uit->create($event);
        $persistentEvent = $this->_uit->get($persistentEvent->getId());
        
        $this->_assertTestEvent($persistentEvent);
    }
    
    public function testSearch()
    {
        $this->testCreate();
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_testCalendars->getId()),
        )));
        
        $this->assertEquals(1, $events->count());
        $this->_assertTestEvent($events->getFirstRecord());
    }
    
    protected function _assertTestEvent($persistentEvent)
    {
        $this->assertEquals(2, $persistentEvent->exdate->count());
        
        $persistException = $persistentEvent->exdate->filter('is_deleted', 0)->getFirstRecord();
        $this->assertEquals('2009-03-26 08:00:00', $persistException->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-26 06:00:00', $persistException->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('exception', $persistException->summary);
        
        $deletedInstance = $persistentEvent->exdate->filter('is_deleted', 1)->getFirstRecord();
        $this->assertEquals('2009-03-27 06:00:00', $deletedInstance->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
}

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_MSEventFacadeTest::main') {
    Calendar_Controller_MSEventFacadeTest::main();
}