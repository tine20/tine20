<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Json Frontend
 * 
 * @package     Calendar
 */
class Calendar_JsonTests extends Calendar_TestCase
{
    /**
     * Calendar Json Object
     *
     * @var Calendar_Frontend_Json
     */
    protected $_uit = null;
    
    /**
     * (non-PHPdoc)
     * @see Calendar/Calendar_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
        $this->_uit = new Calendar_Frontend_Json();
    }
    
    /**
     * testGetRegistryData
     */
    public function testGetRegistryData()
    {
        $registryData = $this->_uit->getRegistryData();
        
        $this->assertTrue(is_array($registryData['defaultContainer']['account_grants']));
    }
    
    /**
     * testCreateEvent
     */
    public function testCreateEvent()
    {
        $scleverDisplayContainerId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $this->_personas['sclever']->getId());
        $contentSeqBefore = Tinebase_Container::getInstance()->getContentSequence($scleverDisplayContainerId);
        
        $eventData = $this->_getEvent()->toArray();
        
        $tag = Tinebase_Tags::getInstance()->createTag(new Tinebase_Model_Tag(array(
            'name' => 'phpunit-' . substr(Tinebase_Record_Abstract::generateUID(), 0, 10),
            'type' => Tinebase_Model_Tag::TYPE_PERSONAL
        )));
        $eventData['tags'] = array($tag->toArray());
        
        $note = new Tinebase_Model_Note(array(
            'note'         => 'very important note!',
            'note_type_id' => Tinebase_Notes::getInstance()->getNoteTypes()->getFirstRecord()->getId(),
        ));
        $eventData['notes'] = array($note->toArray());
        
        $persistentEventData = $this->_uit->saveEvent($eventData);
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        
        $this->_assertJsonEvent($eventData, $loadedEventData, 'failed to create/load event');
        
        $contentSeqAfter = Tinebase_Container::getInstance()->getContentSequence($scleverDisplayContainerId);
        $this->assertEquals($contentSeqBefore[$scleverDisplayContainerId] + 1, $contentSeqAfter[$scleverDisplayContainerId],
        	'content sequence of display container should be increased by 1:' . print_r($contentSeqAfter, TRUE));
        $this->assertEquals($contentSeqAfter[$scleverDisplayContainerId], Tinebase_Container::getInstance()->get($scleverDisplayContainerId)->content_seq);
        
        return $loadedEventData;
    }

    /**
    * testCreateEventWithNonExistantAttender
    */
    public function testCreateEventWithNonExistantAttender()
    {
        $testEmail = 'unittestnotexists@example.org';
        $eventData = $this->_getEvent()->toArray();
        $eventData['attendee'][] = array(
            'user_id'        => $testEmail,
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
            'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
        );
        
        $persistentEventData = $this->_uit->saveEvent($eventData);
        $found = FALSE;
        foreach ($persistentEventData['attendee'] as $attender) {
            if ($attender['user_id']['email'] === $testEmail) {
                $this->assertEquals($testEmail, $attender['user_id']['n_fn']);
                $found = TRUE;
            }
        }
        $this->assertTrue($found);
    }
    
    /**
     * test create event with alarm
     *
     * @todo add testUpdateEventWithAlarm
     */
    public function testCreateEventWithAlarm()
    {
        $eventData = $this->_getEventWithAlarm()->toArray();
        $persistentEventData = $this->_uit->saveEvent($eventData);
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        
        //print_r($loadedEventData);
        
        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($loadedEventData['alarms']));
        $this->assertEquals('Calendar_Model_Event', $loadedEventData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedEventData['alarms'][0]['sent_status']);
        $this->assertTrue(array_key_exists('minutes_before', $loadedEventData['alarms'][0]), 'minutes_before is missing');
        
        $scheduler = Tinebase_Core::getScheduler();
        $scheduler->addTask('Tinebase_Alarm', $this->createTask());
        $scheduler->run();
        
        // check alarm status
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_SUCCESS, $loadedEventData['alarms'][0]['sent_status']);
    }
    
    /**
     * createTask
     */
    public function createTask()
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
     * testUpdateEvent
     */
    public function testUpdateEvent()
    {
        $event = new Calendar_Model_Event($this->testCreateEvent(), true);
        $event->dtstart->addHour(5);
        $event->dtend->addHour(5);
        $event->description = 'are you kidding?';
        
        
        $eventData = $event->toArray();
        foreach ($eventData['attendee'] as $key => $attenderData) {
            if ($eventData['attendee'][$key]['user_id'] != $this->_testUserContact->getId()) {
                unset($eventData['attendee'][$key]);
            }
        }
        
        $updatedEventData = $this->_uit->saveEvent($eventData);
        
        $this->_assertJsonEvent($eventData, $updatedEventData, 'failed to update event');
        
        return $updatedEventData;
    }
    
    /**
     * testDeleteEvent
     */
    public function testDeleteEvent() {
        $eventData = $this->testCreateEvent();
        
        $this->_uit->deleteEvents(array($eventData['id']));
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_uit->getEvent($eventData['id']);
    }
    
    /**
     * testSearchEvents
     */
    public function testSearchEvents()
    {
        $eventData = $this->testCreateEvent();
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $resultEventData = $searchResultData['results'][0];
        
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to search event');
        return $searchResultData;
    }

    /**
     * testSearchEvents with period filter
     * 
     * @todo add an event that is in result set of Calendar_Controller_Event::search() 
     *       but should be removed in Calendar_Frontend_Json::_multipleRecordsToJson()
     */
    public function testSearchEventsWithPeriodFilter()
    {
        $eventData = $this->testCreateRecurEvent();
        
        $filter = array(
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => '2009-03-25 00:00:00',
                'until' => '2009-03-25 23:59:59',
            )),
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $resultEventData = $searchResultData['results'][0];
        
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to search event');
    }

    /**
     * search event with alarm
     *
     */
    public function testSearchEventsWithAlarm()
    {
        $eventData = $this->_getEventWithAlarm()->toArray();
        $persistentEventData = $this->_uit->saveEvent($eventData);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $resultEventData = $searchResultData['results'][0];
        
        $this->_assertJsonEvent($persistentEventData, $resultEventData, 'failed to search event with alarm');
    }
    
    /**
     * testSetAttenderStatus
     */
    public function testSetAttenderStatus()
    {
        $eventData = $this->testCreateEvent();
        $numAttendee = count($eventData['attendee']);
        $eventData['attendee'][$numAttendee] = array(
            'user_id' => $this->_personasContacts['pwulf']->getId(),
        );
        
        $updatedEventData = $this->_uit->saveEvent($eventData);
        $pwulf = $this->_findAttender($updatedEventData['attendee'], 'pwulf');
        
        // he he, we don't have his authkey, cause json class sorts it out due to rights restrictions.
        $attendeeBackend = new Calendar_Backend_Sql_Attendee();
        $pwulf['status_authkey'] = $attendeeBackend->get($pwulf['id'])->status_authkey;
        
        $updatedEventData['container_id'] = $updatedEventData['container_id']['id'];
        
        $pwulf['status'] = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_uit->setAttenderStatus($updatedEventData, $pwulf, $pwulf['status_authkey']);
        
        $loadedEventData = $this->_uit->getEvent($eventData['id']);
        $loadedPwulf = $this->_findAttender($loadedEventData['attendee'], 'pwulf');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $loadedPwulf['status']);
    }
    
    /**
     * testCreateRecurEvent
     */
    public function testCreateRecurEvent()
    {
        $eventData = $this->testCreateEvent();
        $eventData['rrule'] = array(
            'freq'     => 'WEEKLY',
            'interval' => 1,
            'byday'    => 'WE'
        );
        
        $updatedEventData = $this->_uit->saveEvent($eventData);
        $this->assertTrue(is_array($updatedEventData['rrule']));

        return $updatedEventData;
    }
    
    /**
    * testSearchRecuringIncludes
    */
    public function testSearchRecuringIncludes()
    {
        $recurEvent = $this->testCreateRecurEvent();
    
        $from = $recurEvent['dtstart'];
        $until = new Tinebase_DateTime($from);
        $until->addWeek(5)->addHour(10);
        $until = $until->get(Tinebase_Record_Abstract::ISO8601LONG);
    
        $filter = array(
        array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
    
        $searchResultData = $this->_uit->searchEvents($filter, array());
    
        $this->assertEquals(6, $searchResultData['totalcount']);
    
        return $searchResultData;
    }
    
    /**
     * testSearchRecuringIncludesAndSort
     */
    public function testSearchRecuringIncludesAndSort()
    {
        $recurEvent = $this->testCreateRecurEvent();
        
        $from = $recurEvent['dtstart'];
        $until = new Tinebase_DateTime($from);
        $until->addWeek(5)->addHour(10);
        $until = $until->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array('sort' => 'dtstart', 'dir' => 'DESC'));
        
        $this->assertEquals(6, $searchResultData['totalcount']);
        
        // check sorting
        $this->assertEquals('2009-04-29 06:00:00', $searchResultData['results'][0]['dtstart']);
        $this->assertEquals('2009-04-22 06:00:00', $searchResultData['results'][1]['dtstart']);
    }
    
    /**
     * testCreateRecurException
     */
    public function testCreateRecurException()
    {
        $recurSet = array_value('results', $this->testSearchRecuringIncludes());
        
        $persistentException = $recurSet[1];
        $persistentException['summary'] = 'go sleeping';
        
        // create persistent exception
        $this->_uit->createRecurException($persistentException, FALSE, FALSE);
        
        // create exception date
        $this->_uit->createRecurException($recurSet[2], TRUE, FALSE);
        
        // delete all following (including this)
        $this->_uit->createRecurException($recurSet[4], TRUE, TRUE);
        
        $from = $recurSet[0]['dtstart'];
        $until = new Tinebase_DateTime($from);
        $until->addWeek(5)->addHour(10);
        $until = $until->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        
        // we deleted one and cropped
        $this->assertEquals(3, count($searchResultData['results']));
        
        $summaryMap = array();
        foreach ($searchResultData['results'] as $event) {
            $summaryMap[$event['dtstart']] = $event['summary'];
        }
        $this->assertTrue(array_key_exists('2009-04-01 06:00:00', $summaryMap));
        $this->assertEquals($persistentException['summary'], $summaryMap['2009-04-01 06:00:00']);
    }
    
    /**
     * testUpdateRecurSeries
     */
    public function testUpdateRecurSeries()
    {
        $recurSet = array_value('results', $this->testSearchRecuringIncludes());
        
        $persistentException = $recurSet[1];
        $persistentException['summary'] = 'go sleeping';
        $persistentException['dtstart'] = '2009-04-01 20:00:00';
        $persistentException['dtend']   = '2009-04-01 20:30:00';
        
        // create persistent exception
        $this->_uit->createRecurException($persistentException, FALSE, FALSE);
        
        // update recurseries 
        $someRecurInstance = $recurSet[2];
        $someRecurInstance['summary'] = 'go fishing';
        $someRecurInstance['dtstart'] = '2009-04-08 10:00:00';
        $someRecurInstance['dtend']   = '2009-04-08 12:30:00';
        
        $this->_uit->updateRecurSeries($someRecurInstance, FALSE, FALSE);
        
        $from = $recurSet[0]['dtstart'];
        $until = new Tinebase_DateTime($from);
        $until->addWeek(5)->addHour(10);
        $until = $until->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        
        $this->assertEquals(6, count($searchResultData['results']));
        
        $summaryMap = array();
        foreach ($searchResultData['results'] as $event) {
            $summaryMap[$event['dtstart']] = $event['summary'];
        }
        
        $this->assertTrue(array_key_exists('2009-04-01 20:00:00', $summaryMap));
        $this->assertEquals('go sleeping', $summaryMap['2009-04-01 20:00:00']);
        
        $fishings = array_keys($summaryMap, 'go fishing');
        $this->assertEquals(5, count($fishings));
        foreach($fishings as $dtstart) {
            $this->assertEquals('10:00:00', substr($dtstart, -8));
        }
    }
    
    /**
     * testUpdateRecurExceptionsFromSeriesOverDstMove
     * 
     * @todo implement
     */
    public function testUpdateRecurExceptionsFromSeriesOverDstMove()
    {
        /*
         * 1. create recur event 1 day befor dst move
         * 2. create an exception and exdate
         * 3. move dtstart from 1 over dst boundary
         * 4. test recurid and exdate by calculating series
         */
    }
    
    /**
     * testDeleteRecurSeries
     */
    public function testDeleteRecurSeries()
    {
        $recurSet = array_value('results', $this->testSearchRecuringIncludes());
        
        $persistentException = $recurSet[1];
        $persistentException['summary'] = 'go sleeping';
        
        // create persistent exception
        $this->_uit->createRecurException($persistentException, FALSE, FALSE);
        
        // delete recurseries 
        $someRecurInstance = $persistentException = $recurSet[2];
        $this->_uit->deleteRecurSeries($someRecurInstance);
        
        $from = $recurSet[0]['dtstart'];
        $until = new Tinebase_DateTime($from);
        $until->addWeek(5)->addHour(10);
        $until = $until->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        
        $this->assertEquals(0, count($searchResultData['results']));
    }
    
    /**
     * testMeAsAttenderFilter
     */
    public function testMeAsAttenderFilter()
    {
        $eventData = $this->testCreateEvent();
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
            ))
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $resultEventData = $searchResultData['results'][0];
        
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to filter for me as attender');
    }
    
    /**
     * compare expected event data with test event
     *
     * @param array $expectedEventData
     * @param array $eventData
     * @param string $msg
     */
    protected function _assertJsonEvent($expectedEventData, $eventData, $msg) {
        $this->assertEquals($expectedEventData['summary'], $eventData['summary'], $msg . ': failed to create/load event');
        
        // assert effective grants are set
        $this->assertEquals((bool) $expectedEventData[Tinebase_Model_Grants::GRANT_EDIT], (bool) $eventData[Tinebase_Model_Grants::GRANT_EDIT], $msg . ': effective grants mismatch');
        // container, assert attendee, tags, relations
        $this->assertEquals($expectedEventData['dtstart'], $eventData['dtstart'], $msg . ': dtstart mismatch');
        $this->assertTrue(is_array($eventData['container_id']), $msg . ': failed to "resolve" container');
        $this->assertTrue(is_array($eventData['container_id']['account_grants']), $msg . ': failed to "resolve" container account_grants');
        $this->assertGreaterThan(0, count($eventData['attendee']));
        $this->assertEquals(count($eventData['attendee']), count($expectedEventData['attendee']), $msg . ': failed to append attendee');
        $this->assertTrue(is_array($eventData['attendee'][0]['user_id']), $msg . ': failed to resolve attendee user_id');
        // NOTE: due to sorting isshues $eventData['attendee'][0] may be a non resolvable container (due to rights restrictions)
        $this->assertTrue(is_array($eventData['attendee'][0]['displaycontainer_id']) || (isset($eventData['attendee'][1]) && is_array($eventData['attendee'][1]['displaycontainer_id'])), $msg . ': failed to resolve attendee displaycontainer_id');
        $this->assertEquals(count($expectedEventData['tags']), count($eventData['tags']), $msg . ': failed to append tag');
        $this->assertEquals(count($expectedEventData['notes']), count($eventData['notes']), $msg . ': failed to create note or wrong number of notes');
        
        if (array_key_exists('alarms', $expectedEventData)) {
            $this->assertTrue(array_key_exists('alarms', $eventData), ': failed to create alarms');
            $this->assertEquals(count($expectedEventData['alarms']), count($eventData['alarms']), $msg . ': failed to create correct number of alarms');
            if (count($expectedEventData['alarms']) > 0) {
                $this->assertTrue(array_key_exists('minutes_before', $eventData['alarms'][0]));
            }
        }
    }
    
    /**
     * find attender 
     *
     * @param array $attendeeData
     * @param string $name
     * @return array
     */
    protected function _findAttender($attendeeData, $name) {
        $attenderData = false;
        $searchedId = $this->_personasContacts[$name]->getId();
        
        foreach ($attendeeData as $key => $attender) {
            if ($attender['user_type'] == Calendar_Model_Attender::USERTYPE_USER) {
                if (is_array($attender['user_id']) && array_key_exists('id', $attender['user_id'])) {
                    if ($attender['user_id']['id'] == $searchedId) {
                        $attenderData = $attendeeData[$key];
                    }
                }
            }
        }
        
        return $attenderData;
    }
}
