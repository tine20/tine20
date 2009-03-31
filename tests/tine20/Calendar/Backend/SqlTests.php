<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Backend_SqlTests::main');
}

/**
 * Test class for Calendar_Backend_Sql
 * 
 * @package     Calendar
 */
class Calendar_Backend_SqlTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Calendar_Backend_Sql SQL Backend in test
     */
    protected $_backend;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_testCalendar;
    
    public function setUp()
    {
        $this->_backend = new Calendar_Backend_Sql();
        $this->_testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'        => $this->_backend->getType(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));
    }
    
    public function tearDown()
    {
        $eventIds = $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )), new Tinebase_Model_Pagination(array()), true);
        
        foreach ($eventIds as $eventId) {
        	$this->_backend->delete($eventId);
        }
        
        Tinebase_Container::getInstance()->deleteContainer($this->_testCalendar);
    }
    
    public function testCreateEvent()
    {
        $event = $this->_getEvent();
        $persistentEvent = $this->_backend->create($event);
                
        $this->assertEquals($persistentEvent->summary, $event->summary);
    }
    
    public function testUpdateEvent()
    {
        $event = $this->_getEvent();
        $persistentEvent = $this->_backend->create($event);
        
        $persistentEvent->dtstart->addHour(3);
        $persistentEvent->dtend->addHour(3);
        $persistentEvent->summary = 'Robert Lembke:';
        $persistentEvent->description = 'Wer spät zu Bett geht und früh heraus muß, weiß, woher das Wort Morgengrauen kommt';
        
        $updatedEvent = $this->_backend->update($persistentEvent);
        
        $this->assertEquals($persistentEvent->summary, $updatedEvent->summary);
        $this->assertTrue($persistentEvent->dtstart->equals($updatedEvent->dtstart));
    }
    
    public function testGetEvent()
    {
        $event = $this->_getEvent();
        $persistentEvent = $this->_backend->create($event);
        
        $loadedEvent = $this->_backend->get($persistentEvent->getId());
        
        $this->assertEquals($loadedEvent->summary, $event->summary);
    }
    
    public function testDeleteEvent()
    {
        $event = $this->_getEvent();
        $persistentEvent = $this->_backend->create($event);
        
        $this->_backend->delete($persistentEvent->getId());
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $loadedEvent = $this->_backend->get($persistentEvent->getId());
    }
    
    public function testSearchEvents()
    {
        $from = '2009-04-03 00:00:00';
        $until = '2009-04-10 23:59:59';
        
        $events = new Tinebase_Record_RecordSet('Calendar_Model_Event', array(
            array(
                'dtstart'      => '2009-04-02 22:00:00',
                'dtend'        => '2009-04-02 23:59:59',
                'summary'      => 'non recur event ending before search period => should _not_ be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-04-02 23:30:00',
                'dtend'        => '2009-04-03 00:30:00',
                'summary'      => 'non recur event ending within search period => should be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-04-06 12:00:00',
                'dtend'        => '2009-04-07 12:00:00',
                'summary'      => 'non recur event completly within search period => should be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-04-10 23:30:00',
                'dtend'        => '2009-04-11 00:30:00',
                'summary'      => 'non recur event starting within search period => should be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-04-11 00:00:00',
                'dtend'        => '2009-04-11 02:00:00',
                'summary'      => 'non recur event starting after search period => should _not_ be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-03-27 22:00:00',
                'dtend'        => '2009-03-27 23:59:59',
                'rrule'        => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-02 23:59:59',
                'summary'      => 'recur event ending before search period => should _not_ be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-03-27 22:00:00',
                'dtend'        => '2009-03-27 23:59:59',
                'rrule'        => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-05 23:59:59',
                'summary'      => 'recur event ending within search period => should be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-04-03 22:00:00',
                'dtend'        => '2009-04-03 23:59:59',
                'rrule'        => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-06 23:59:59',
                'summary'      => 'recur event completly within search period => should be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-04-03 22:00:00',
                'dtend'        => '2009-04-03 23:59:59',
                'rrule'        => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-12 23:59:59',
                'summary'      => 'recur event starting within search period => should be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            ),
            array(
                'dtstart'      => '2009-04-11 00:00:00',
                'dtend'        => '2009-04-11 02:00:00',
                'rrule'        => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-15 02:00:00',
                'summary'      => 'recur event starting after search period => should _not_ be found',
                'container_id' => $this->_testCalendar->getId(),
                'organizer'    => Tinebase_Core::getUser()->getId(),
                'uid'          => Calendar_Model_Event::generateUID(),
            )
        ));
        
        foreach ($events as $event) {
            $this->_backend->create($event);
        }
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period'      , 'operator' => 'within', 'value' => array(
                'from'  => $from,
                'until' => $until
            )),
        ));
        
        $eventsFound = $this->_backend->search($filter, new Tinebase_Model_Pagination());
        
        $eventsFoundIds = $eventsFound->getArrayOfIds();
        
        foreach ($events as $event) {
            $eventId = $event->getId();
            if (strpos($event->summary, '_not_') === false) {
                $this->assertTrue(in_array($eventId, $eventsFoundIds), 'The following event is missing in the search result :' . print_r($event->toArray(), true));
            } else {
                $this->assertFalse(in_array($eventId, $eventsFoundIds), 'The following event is in the search result, but should not be :' . print_r($event->toArray(), true));
            }
        }
    }
    
    /**
     * test searching of direct events
     * 
     * Direct events are those, which duration (events dtstart -> dtend)
     *   reaches in the seached period.
     * 
     * We add tree events and search from the middle for the first to the middle 
     * of the last. All tree events should be found
     *
    public function testSearchDirectEvents()
    {
        $persistentEventIds = array();
        
        $event1 = $this->_getEvent();
        $persistentEventIds[] = $this->_backend->create($event1)->getId();
        
        $event2 = $this->_getEvent();
        $event2->dtstart->addDay(1);
        $persistentEventIds[] = $this->_backend->create($event2)->getId();
        
        $event3 = $this->_getEvent();
        $event3->dtstart->addDay(2);
        $persistentEventIds[] = $this->_backend->create($event3)->getId();
        
        $from = $event1->dtstart->addMinute(7)->get(Calendar_Model_Event::ISO8601LONG);
        $until = $event3->dtstart->addMinute(-7)->get(Calendar_Model_Event::ISO8601LONG);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $event1->container_id),
            array('field' => 'period'      , 'operator' => 'within', 'value' => array(
                'from'  => $from,
                'until' => $until
            )),
        ));
        
        $events = $this->_backend->searchDirectEvents($filter, new Tinebase_Model_Pagination());
        
        $this->assertEquals(3, count($events));
        
        foreach ($persistentEventIds as $id) {
            $this->_backend->delete($id);
        }
    }
    */
    
    /**
     * test search of recuring base events
     * 
     * Recur Base events are those recuring events which potentially could have
     *   recurances in the searched period
     *
     *
    public function testSearchRecurBaseEvnets()
    {
        
        $from = '2009-04-03 00:00:00';
        $until = '2009-04-10 23:59:59';
        
        $persistentEventIds = array();
        
        // should not be found
        $event1 = $this->_getEvent();
        $event1->rrule = "FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 06:15:00";
        $persistentEventIds[] = $this->_backend->create($event1)->getId();
        
        // should be found as its rrule until reaches in searched period
        $event2 = $this->_getEvent();
        $event2->dtstart->addWeek(1); // 2009-04-01 06:00:00
        $event2->dtend->addWeek(1);   // 2009-04-01 06:15:00
        $event1->rrule = "FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-07 06:15:00";
        $persistentEventIds[] = $this->_backend->create($event2)->getId();
        
        // should be found, as it starts in searched period
        $event3 = $this->_getEvent();
        $event3->dtstart->addWeek(2); // 2009-04-08 06:00:00
        $event3->dtend->addWeek(2);   // 2009-04-08 06:15:00
        $event1->rrule = "FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-14 06:15:00";
        
        $persistentEventIds[] = $this->_backend->create($event3)->getId();
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $event1->container_id),
            array('field' => 'period'      , 'operator' => 'within', 'value' => array(
                'from'  => $from,
                'until' => $until
            )),
        ));
        
        $events = $this->_backend->searchRecurBaseEvents($filter, new Tinebase_Model_Pagination());
        
        $this->assertEquals(2, count($events));
                
        foreach ($persistentEventIds as $id) {
            $this->_backend->delete($id);
        }
        
    }
    */
    
    public function testExDate()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=WEEKLY;INTERVAL=1;UNTIL=2009-05-20 23:59:59';
        $event->exdate = array(
            new Zend_Date('2009-04-29 06:00:00'),
            new Zend_Date('2009-05-06 06:00:00'),
        );
        
        $persistentEvent = $this->_backend->create($event);
        
        $this->assertEquals(2, count($persistentEvent->exdate), 'We put in two exdates, we should get out two exdates!');
        foreach ($persistentEvent->exdate as $exdate) {
        	$this->assertTrue($exdate->equals($event->exdate[0]) || $exdate->equals($event->exdate[1]), 'exdates mismatch');
        }
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     */
    protected function _getEvent()
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Wakeup',
            'dtstart'     => '2009-03-25 06:00:00',
            'dtend'       => '2009-03-25 06:15:00',
            'description' => 'Earyly to bed and early to rise, makes a men helthy, welthy and wise',
        
            'container_id' => $this->_testCalendar->getId(),
            'organizer'    => Tinebase_Core::getUser()->getId(),
            'uid'          => Calendar_Model_Event::generateUID(),
        ));
        
        
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Backend_SqlTests::main') {
    Calendar_Backend_SqlTests::main();
}
