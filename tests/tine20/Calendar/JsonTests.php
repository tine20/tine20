<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * 
     * @param $now should the current date be used
     */
    public function testCreateEvent($now = FALSE)
    {
        $scleverDisplayContainerId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $this->_personas['sclever']->getId());
        $contentSeqBefore = Tinebase_Container::getInstance()->getContentSequence($scleverDisplayContainerId);
        
        $eventData = $this->_getEvent($now)->toArray();
        
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
        $this->assertEquals($contentSeqBefore + 1, $contentSeqAfter,
            'content sequence of display container should be increased by 1:' . $contentSeqAfter);
        $this->assertEquals($contentSeqAfter, Tinebase_Container::getInstance()->get($scleverDisplayContainerId)->content_seq);
        
        return $loadedEventData;
    }
    
    public function testStripWindowsLinebreaks()
    {
        $e = $this->_getEvent(TRUE);
        $e->description = 'Hello my friend,' . chr(13) . chr(10) .'bla bla bla.'  . chr(13) . chr(10) .'good bye.';
        $persistentEventData = $this->_uit->saveEvent($e->toArray());
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        $this->assertEquals($loadedEventData['description'], 'Hello my friend,' . chr(10) . 'bla bla bla.' . chr(10) . 'good bye.');
    }

    /**
    * testCreateEventWithNonExistantAttender
    */
    public function testCreateEventWithNonExistantAttender()
    {
        $testEmail = 'unittestnotexists@example.org';
        $eventData = $this->_getEvent(TRUE)->toArray();
        $eventData['attendee'][] = $this->_getUserTypeAttender($testEmail);
        
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
     * get single attendee array
     * 
     * @param string $email
     * @return array
     */
    protected function _getUserTypeAttender($email = 'unittestnotexists@example.org')
    {
        return array(
            'user_id'        => $email,
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
            'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
        );
    }
    
    /**
     * test create event with alarm
     *
     * @todo add testUpdateEventWithAlarm
     */
    public function testCreateEventWithAlarm()
    {
        $eventData = $this->_getEventWithAlarm(TRUE)->toArray();
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
        $eventData = $this->testCreateEvent(TRUE); 
        
        $filter = $this->_getEventFilterArray();
        $searchResultData = $this->_uit->searchEvents($filter, array());
        
        $this->assertTrue(! empty($searchResultData['results']));
        $resultEventData = $searchResultData['results'][0];
        
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to search event');
        return $searchResultData;
    }
    
    /**
     * get filter array with container and period filter
     * 
     * @param string|int $containerId
     * @return multitype:multitype:string Ambigous <number, multitype:>  multitype:string multitype:string
     */
    protected function _getEventFilterArray($containerId = NULL)
    {
        $containerId = ($containerId) ? $containerId : $this->_testCalendar->getId();
        return array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $containerId),
            array('field' => 'period', 'operator' => 'within', 'value' =>
                array("from" => '2009-03-20 06:15:00', "until" => Tinebase_DateTime::now()->addDay(1)->toString())
            )
        );
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
     * #7688: Internal Server Error on calendar search
     * 
     * add period filter if none is given
     * 
     * https://forge.tine20.org/mantisbt/view.php?id=7688
     */
    public function testSearchEventsWithOutPeriodFilter()
    {
        $eventData = $this->testCreateRecurEvent();
        $filter = array(array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()));
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $returnedFilter = $searchResultData['filter'];
        $this->assertEquals(2, count($returnedFilter), 'Two filters shoud have been returned!');
        $this->assertTrue($returnedFilter[1]['field'] == 'period' || $returnedFilter[0]['field'] == 'period', 'One returned filter shoud be a period filter');
    }

    /**
     * testSearchEvents with organizer = me filter
     * 
     * @see #6716: default favorite "me" is not resolved properly
     */
    public function testSearchEventsWithOrganizerMeFilter()
    {
        $eventData = $this->testCreateEvent(TRUE);
        
        $filter = $this->_getEventFilterArray();
        $filter[] = array('field' => 'organizer', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT);
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']));
        $resultEventData = $searchResultData['results'][0];
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to search event');
        
        // check organizer filter resolving
        $organizerfilter = $searchResultData['filter'][2];
        $this->assertTrue(is_array($organizerfilter['value']), 'organizer should be resolved: ' . print_r($organizerfilter, TRUE));
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $organizerfilter['value']['id']);
    }
    
    /**
     * search event with alarm
     */
    public function testSearchEventsWithAlarm()
    {
        $eventData = $this->_getEventWithAlarm(TRUE)->toArray();
        $persistentEventData = $this->_uit->saveEvent($eventData);
        
        $searchResultData = $this->_uit->searchEvents($this->_getEventFilterArray(), array());
        $this->assertTrue(! empty($searchResultData['results']));
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
        
        // test appending tags to recurring instances
        $this->assertEquals('phpunit-', substr($searchResultData['results'][4]['tags'][0]['name'], 0, 8));
    
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
        $updatedBaseEvent = Calendar_Controller_Event::getInstance()->getRecurBaseEvent(new Calendar_Model_Event($recurSet[2]));
        $recurSet[2]['last_modified_time'] = $updatedBaseEvent->last_modified_time;
        $this->_uit->createRecurException($recurSet[2], TRUE, FALSE);
        
        // delete all following (including this)
        $updatedBaseEvent = Calendar_Controller_Event::getInstance()->getRecurBaseEvent(new Calendar_Model_Event($recurSet[4]));
        $recurSet[4]['last_modified_time'] = $updatedBaseEvent->last_modified_time;
        $this->_uit->createRecurException($recurSet[4], TRUE, TRUE);
        
        $from = $recurSet[0]['dtstart'];
        $until = new Tinebase_DateTime($from);
        $until->addWeek(5)->addHour(10);
        $until = $until->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array('sort' => 'dtstart'));
        
        // we deleted one and cropped
        $this->assertEquals(3, count($searchResultData['results']));
        
        $summaryMap = array();
        foreach ($searchResultData['results'] as $event) {
            $summaryMap[$event['dtstart']] = $event['summary'];
        }
        $this->assertTrue(array_key_exists('2009-04-01 06:00:00', $summaryMap));
        $this->assertEquals($persistentException['summary'], $summaryMap['2009-04-01 06:00:00']);
        
        return $searchResultData;
    }
    
    /**
     * testCreateRecurExceptionWithOtherUser
     * 
     * @see 0008172: displaycontainer_id not set when recur exception is created
     */
    public function testCreateRecurExceptionWithOtherUser()
    {
        $recurSet = array_value('results', $this->testSearchRecuringIncludes());
        
        // create persistent exception (just status update)
        $persistentException = $recurSet[1];
        $scleverAttender = $this->_findAttender($persistentException['attendee'], 'sclever');
        $attendeeBackend = new Calendar_Backend_Sql_Attendee();
        $status_authkey = $attendeeBackend->get($scleverAttender['id'])->status_authkey;
        $scleverAttender['status'] = Calendar_Model_Attender::STATUS_ACCEPTED;
        $scleverAttender['status_authkey'] = $status_authkey;
        foreach ($persistentException['attendee'] as $key => $attender) {
            if ($attender['id'] === $scleverAttender['id']) {
                $persistentException['attendee'][$key] = $scleverAttender;
                break;
            }
        }
        
        // sclever has only READ grant
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_testUser->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Tinebase_Model_Grants::GRANT_FREEBUSY => true,
        ), array(
            'account_id'    => $this->_personas['sclever']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_FREEBUSY => true,
        ))), TRUE);
        
        $unittestUser = Tinebase_Core::getUser();
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        
        // create persistent exception
        $createdException = $this->_uit->createRecurException($persistentException, FALSE, FALSE);
        Tinebase_Core::set(Tinebase_Core::USER, $unittestUser);
        
        $sclever = $this->_findAttender($createdException['attendee'], 'sclever');
        $this->assertEquals('Susan Clever', $sclever['user_id']['n_fn']);
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $sclever['status'], 'status mismatch: ' . print_r($sclever, TRUE));
        $this->assertTrue(is_array($sclever['displaycontainer_id']));
        $this->assertEquals($this->_personasDefaultCals['sclever']['id'], $sclever['displaycontainer_id']['id']);
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
        $recurResult = $this->_uit->createRecurException($persistentException, FALSE, FALSE);
        
        // update recurseries 
        $someRecurInstance = $recurSet[2];
        $someRecurInstance['summary'] = 'go fishing';
        $someRecurInstance['dtstart'] = '2009-04-08 10:00:00';
        $someRecurInstance['dtend']   = '2009-04-08 12:30:00';
        
        $someRecurInstance['seq'] = 2;
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
        foreach ($fishings as $dtstart) {
            $this->assertEquals('10:00:00', substr($dtstart, -8), 'all fishing events should start at 10:00');
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
        $eventData = $this->testCreateEvent(TRUE);
        
        $filter = $this->_getEventFilterArray();
        $filter[] = array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => Addressbook_Model_Contact::CURRENTCONTACT,
        ));
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']));
        $resultEventData = $searchResultData['results'][0];
        
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to filter for me as attender');
    }
    
    /**
     * testFreeBusyCleanup
     */
    public function testFreeBusyCleanup()
    {
        // give fb grants from sclever
        $scleverCal = Tinebase_Container::getInstance()->getContainerById($this->_personasDefaultCals['sclever']);
        Tinebase_Container::getInstance()->setGrants($scleverCal->getId(), new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['sclever']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Tinebase_Model_Grants::GRANT_FREEBUSY => true,
        ), array(
            'account_id'    => $this->_testUser->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_FREEBUSY => true,
        ))), TRUE);
        
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $eventData = $this->_getEvent()->toArray();
        unset($eventData['organizer']);
        $eventData['container_id'] = $scleverCal->getId();
        $eventData['attendee'] = array(array(
            'user_id' => $this->_personasContacts['sclever']->getId()
        ));
        $eventData['organizer'] = $this->_personasContacts['sclever']->getId();
        $eventData = $this->_uit->saveEvent($eventData);
        $filter = $this->_getEventFilterArray($this->_personasDefaultCals['sclever']->getId());
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected event in search result (search by sclever): ' 
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));
        
        Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected (freebusy cleanup) event in search result: ' 
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));
        $eventData = $searchResultData['results'][0];
        
        $this->assertFalse(array_key_exists('summary', $eventData), 'summary not empty: ' . print_r($eventData, TRUE));
        $this->assertFalse(array_key_exists('description', $eventData), 'description not empty');
        $this->assertFalse(array_key_exists('tags', $eventData), 'tags not empty');
        $this->assertFalse(array_key_exists('notes', $eventData), 'notes not empty');
        $this->assertFalse(array_key_exists('attendee', $eventData), 'attendee not empty');
        $this->assertFalse(array_key_exists('organizer', $eventData), 'organizer not empty');
        $this->assertFalse(array_key_exists('alarms', $eventData), 'alarms not empty');
    }
    
    /**
     * test deleting container and the containing events
     * #6704: events do not disappear when shared calendar got deleted
     * https://forge.tine20.org/mantisbt/view.php?id=6704
     */
    public function testDeleteContainerAndEvents()
    {
        $fe = new Tinebase_Frontend_Json_Container();
        $container = $fe->addContainer('Calendar', 'testdeletecontacts', Tinebase_Model_Container::TYPE_SHARED, '');
        // test if model is set automatically
        $this->assertEquals($container['model'], 'Calendar_Model_Event');
        
        $date = new Tinebase_DateTime();
        $event = new Calendar_Model_Event(array(
            'dtstart' => $date,
            'dtend'    => $date->subHour(1),
            'summary' => 'bla bla',
            'class'    => 'PUBLIC',
            'transp'    => 'OPAQUE',
            'container_id' => $container['id']
            ));
        $event = Calendar_Controller_Event::getInstance()->create($event);
        $this->assertEquals($container['id'], $event->container_id);
        
        $fe->deleteContainer($container['id']);
        
        $e = new Exception('dummy');
        
        $cb = new Calendar_Backend_Sql();
        $deletedEvent = $cb->get($event->getId(), true);
        // record should be deleted
        $this->assertEquals($deletedEvent->is_deleted, 1);
        
        try {
            Calendar_Controller_Event::getInstance()->get($event->getId(), $container['id']);
            $this->fail('The expected exception was not thrown');
        } catch (Tinebase_Exception_NotFound $e) {
            // ok;
        }
        // record should not be found
        $this->assertEquals($e->getMessage(), 'Calendar_Model_Event record with id '.$event->getId().' not found!');
    }
    
    /**
     * compare expected event data with test event
     *
     * @param array $expectedEventData
     * @param array $eventData
     * @param string $msg
     */
    protected function _assertJsonEvent($expectedEventData, $eventData, $msg)
    {
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
    
    /**
     * test filter with hidden group -> should return empty result
     * 
     * @see 0006934: setting a group that is hidden from adb as attendee filter throws exception
     */
    public function testHiddenGroupFilter()
    {
        $hiddenGroup = new Tinebase_Model_Group(array(
            'name'          => 'hiddengroup',
            'description'   => 'hidden group',
            'visibility'     => Tinebase_Model_Group::VISIBILITY_HIDDEN
        ));
        $hiddenGroup = Admin_Controller_Group::getInstance()->create($hiddenGroup);
        
        $filter = array(array(
            'field'    => 'attender',
            'operator' => 'equals',
            'value'    => array(
                'user_id'   => $hiddenGroup->list_id,
                'user_type' => 'group',
            ),
        ));
        $result = $this->_uit->searchEvents($filter, array());
        $this->assertEquals(0, $result['totalcount']);
    }
    
    /**
     * testExdateDeleteAll
     * 
     * @see 0007382: allow to edit / delete the whole series / thisandfuture when editing/deleting recur exceptions
     */
    public function testExdateDeleteAll()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events);
        $this->_uit->deleteEvents(array($exception['id']), Calendar_Model_Event::RANGE_ALL);
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        $this->assertEquals(0, $search['totalcount'], 'all events should be deleted: ' . print_r($search,TRUE));
    }
    
    /**
     * get exception from event resultset
     * 
     * @param array $events
     * @param integer $index (1 = picks first, 2 = picks second, ...)
     * @return array|NULL
     */
    protected function _getException($events, $index = 1)
    {
        $event = NULL;
        $found = 0;
        foreach ($events['results'] as $event) {
            if (! empty($event['recurid'])) {
                $found++;
                if ($index === $found) {
                    return $event;
                }
            }
        }
        
        return $event;
    }
    
    /**
     * testExdateDeleteThis
     * 
     * @see 0007382: allow to edit / delete the whole series / thisandfuture when editing/deleting recur exceptions
     */
    public function testExdateDeleteThis()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events);
        $this->_uit->deleteEvents(array($exception['id']));
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        $this->assertEquals(2, $search['totalcount'], '2 events should remain: ' . print_r($search,TRUE));
    }
    
    /**
     * testExdateDeleteThisAndFuture
     * 
     * @see 0007382: allow to edit / delete the whole series / thisandfuture when editing/deleting recur exceptions
     */
    public function testExdateDeleteThisAndFuture()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        $this->_uit->deleteEvents(array($exception['id']), Calendar_Model_Event::RANGE_THISANDFUTURE);
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        $this->assertEquals(1, $search['totalcount'], '1 event should remain: ' . print_r($search,TRUE));
    }
    
    /**
     * assert grant handling
     */
    public function testSaveResource($grants = array('readGrant' => true,'editGrant' => true))
    {
        $resoureData = array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'email' => Tinebase_Record_Abstract::generateUID() . '@unittest.com',
            'grants' => array(array_merge($grants, array(
                'account_id' => Tinebase_Core::getUser()->getId(),
                'account_type' => 'user'
            )))
        );
        
        $resoureData = $this->_uit->saveResource($resoureData);
        $this->assertTrue(is_array($resoureData['grants']), 'grants are not resolved');
        
        return $resoureData;
    }
    
    /**
     * assert only resources with read grant are returned if the user has no manage right
     */
    public function testSearchResources()
    {
        $readableResoureData = $this->testSaveResource();
        $nonReadableResoureData = $this->testSaveResource(array());
        
        $filer = array(
            array('field' => 'name', 'operator' => 'in', 'value' => array(
                $readableResoureData['name'],
                $nonReadableResoureData['name'],
            ))
        );
        
        $searchResultManager = $this->_uit->searchResources($filer, array());
        $this->assertEquals(2, count($searchResultManager['results']), 'with manage grants all records should be found');
        
        // steal manage right and reactivate container checks
        Tinebase_Acl_Roles::getInstance()->deleteAllRoles();
        Calendar_Controller_Resource::getInstance()->doContainerACLChecks(TRUE);
        
        $searchResult = $this->_uit->searchResources($filer, array());
        $this->assertEquals(1, count($searchResult['results']), 'without manage grants only one record should be found');
    }
    
    /**
     * assert status authkey with editGrant
     * assert stauts can be set with editGrant
     * assert stauts can't be set without editGrant
     */
    public function testResourceAttendeeGrants()
    {
        $editableResoureData = $this->testSaveResource();
        $nonEditableResoureData = $this->testSaveResource(array('readGrant'));
        
        $event = $this->_getEvent(TRUE);
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type'  => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'    => $editableResoureData['id'],
                'status'     => Calendar_Model_Attender::STATUS_ACCEPTED
            ),
            array(
                'user_type'  => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'    => $nonEditableResoureData['id'],
                'status'     => Calendar_Model_Attender::STATUS_ACCEPTED
            )
        ));
        
        $persistentEventData = $this->_uit->saveEvent($event->toArray());
        
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $persistentEventData['attendee']);
        $this->assertEquals(1, count($attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)), 'one accepted');
        $this->assertEquals(1, count($attendee->filter('status', Calendar_Model_Attender::STATUS_NEEDSACTION)), 'one needs action');
        
        $this->assertEquals(1, count($attendee->filter('status_authkey', '/[a-z0-9]+/', TRUE)), 'one has authkey');
        
        $attendee->status = Calendar_Model_Attender::STATUS_TENTATIVE;
        $persistentEventData['attendee'] = $attendee->toArray();
        
        $updatedEventData = $this->_uit->saveEvent($persistentEventData);
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $updatedEventData['attendee']);
        $this->assertEquals(1, count($attendee->filter('status', Calendar_Model_Attender::STATUS_TENTATIVE)), 'one tentative');
    }

    /**
     * testExdateUpdateAllSummary
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     */
    public function testExdateUpdateAllSummary()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        $exception['summary'] = 'new summary';
        
        $event = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_ALL);
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            $this->assertEquals('new summary', $event['summary']);
        }
    }

    /**
     * testExdateUpdateAllDtStart
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     * 
     * @todo finish
     */
    public function testExdateUpdateAllDtStart()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        $exception['dtstart'] = '2009-04-01 08:00:00';
        $exception['dtend'] = '2009-04-01 08:15:00';
        
        $event = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_ALL);
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            $this->assertContains('08:00:00', $event['dtstart'], 'wrong dtstart: ' . print_r($event, TRUE));
            $this->assertContains('08:15:00', $event['dtend']);
        }
    }
    
    /**
     * testExdateUpdateThis
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     */
    public function testExdateUpdateThis()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        $exception['summary'] = 'exception';
        
        $event = $this->_uit->saveEvent($exception);
        $this->assertEquals('exception', $event['summary']);
        
        // check for summary (only changed in one event)
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            if (! empty($event['recurid']) && ! preg_match('/^fakeid/', $event['id'])) {
                $this->assertEquals('exception', $event['summary'], 'summary not changed in exception: ' . print_r($event, TRUE));
            } else {
                $this->assertEquals('Wakeup', $event['summary']);
            }
        }
    }

    /**
     * testExdateUpdateThisAndFuture
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     */
    public function testExdateUpdateThisAndFuture()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        $exception['summary'] = 'new summary';
        
        $updatedEvent = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_THISANDFUTURE);
        $this->assertEquals('new summary', $updatedEvent['summary'], 'summary not changed in exception: ' . print_r($updatedEvent, TRUE));
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            if ($event['dtstart'] >= $updatedEvent['dtstart']) {
                $this->assertEquals('new summary', $event['summary'], 'summary not changed in event: ' . print_r($event, TRUE));
            } else {
                $this->assertEquals('Wakeup', $event['summary']);
            }
        }
    }

    /**
     * testExdateUpdateThisAndFutureWithRruleUntil
     * 
     * @see 0008244: "rrule until must not be before dtstart" when updating recur exception (THISANDFUTURE)
     */
    public function testExdateUpdateThisAndFutureWithRruleUntil()
    {
        $events = $this->testCreateRecurException();
        
        $exception = $this->_getException($events, 1);
        $exception['dtstart'] = Tinebase_DateTime::now()->toString();
        $exception['dtend'] = Tinebase_DateTime::now()->addHour(1)->toString();
        
        // move exception
        $updatedEvent = $this->_uit->saveEvent($exception);
        // try to update the whole series
        $updatedEvent['summary'] = 'new summary';
        $updatedEvent = $this->_uit->saveEvent($updatedEvent, FALSE, Calendar_Model_Event::RANGE_THISANDFUTURE);
        
        $this->assertEquals('new summary', $updatedEvent['summary'], 'summary not changed in event: ' . print_r($updatedEvent, TRUE));
    }
    
    /**
     * testExdateUpdateThisAndFutureRemoveAttendee
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     */
    public function testExdateUpdateThisAndFutureRemoveAttendee()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        // remove susan from attendee
        unset($exception['attendee'][0]);
        
        $updatedEvent = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_THISANDFUTURE);
        $this->assertEquals(1, count($updatedEvent['attendee']), 'attender not removed from exception: ' . print_r($updatedEvent, TRUE));
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            if ($event['dtstart'] >= $updatedEvent['dtstart']) {
                $this->assertEquals(1, count($event['attendee']), 'attendee count mismatch: ' . print_r($event, TRUE));
            } else {
                $this->assertEquals(2, count($event['attendee']), 'attendee count mismatch: ' . print_r($event, TRUE));
            }
        }
    }

    /**
     * testExdateUpdateAllAddAttendee
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     */
    public function testExdateUpdateAllAddAttendee()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        // add new attender
        $exception['attendee'][] = $this->_getUserTypeAttender();
        
        $updatedEvent = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_ALL);
        $this->assertEquals(3, count($updatedEvent['attendee']), 'attender not added to exception: ' . print_r($updatedEvent, TRUE));
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            $this->assertEquals(3, count($event['attendee']), 'attendee count mismatch: ' . print_r($event, TRUE));
        }
    }
    
    /**
     * testExdateUpdateThisAndFutureChangeDtstart
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     */
    public function testExdateUpdateThisAndFutureChangeDtstart()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        $exception['dtstart'] = '2009-04-01 08:00:00';
        $exception['dtend'] = '2009-04-01 08:15:00';
        
        $updatedEvent = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_THISANDFUTURE);
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            if ($event['dtstart'] >= $updatedEvent['dtstart']) {
                $this->assertContains('08:00:00', $event['dtstart'], 'wrong dtstart: ' . print_r($event, TRUE));
                $this->assertContains('08:15:00', $event['dtend']);
            } else {
                $this->assertContains('06:00:00', $event['dtstart'], 'wrong dtstart: ' . print_r($event, TRUE));
                $this->assertContains('06:15:00', $event['dtend']);
            }
        }
    }
    
    /**
     * testExdateUpdateAllWithModlog
     * - change base event, then update all
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     */
    public function testExdateUpdateAllWithModlog()
    {
        $events = $this->testCreateRecurException();
        $baseEvent = $events['results'][0];
        $exception = $this->_getException($events, 1);
        
        $baseEvent['summary'] = 'Get up, lazyboy!';
        $baseEvent = $this->_uit->saveEvent($baseEvent);
        sleep(1);
        
        $exception['summary'] = 'new summary';
        $updatedEvent = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_ALL);
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            if ($event['dtstart'] == $updatedEvent['dtstart']) {
                $this->assertEquals('new summary', $event['summary'], 'Recur exception should have the new summary');
            } else {
                $this->assertEquals('Get up, lazyboy!', $event['summary'], 'Wrong summary in base/recur event: ' . print_r($event, TRUE));
            }
        }
    }

    /**
     * testExdateUpdateAllWithModlogAddAttender
     * - change base event, then update all
     * 
     * @see 0007690: allow to update the whole series / thisandfuture when updating recur exceptions
     * @see 0007826: add attendee changes to modlog
     */
    public function testExdateUpdateAllWithModlogAddAttender()
    {
        $events = $this->testCreateRecurException();
        $baseEvent = $events['results'][0];
        $exception = $this->_getException($events, 1);
        
        // add new attender
        $baseEvent['attendee'][] = $this->_getUserTypeAttender();
        $baseEvent = $this->_uit->saveEvent($baseEvent);
        $this->assertEquals(3, count($baseEvent['attendee']), 'Attendee count mismatch in baseEvent: ' . print_r($baseEvent, TRUE));
        sleep(1);
        
        // check recent changes (needs to contain attendee change)
        $exdate = Calendar_Controller_Event::getInstance()->get($exception['id']);
        $recentChanges = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications('Calendar', $baseEvent['id'], NULL, 'Sql', $exdate->creation_time);
        $this->assertGreaterThan(2, count($recentChanges), 'Did not get all recent changes: ' . print_r($recentChanges->toArray(), TRUE));
        $this->assertTrue(in_array('attendee', $recentChanges->modified_attribute), 'Attendee change missing: ' . print_r($recentChanges->toArray(), TRUE));
        
        $exception['attendee'][] = $this->_getUserTypeAttender('unittestnotexists@example.com');
        $updatedEvent = $this->_uit->saveEvent($exception, FALSE, Calendar_Model_Event::RANGE_ALL);
        
        $search = $this->_uit->searchEvents($events['filter'], NULL);
        foreach ($search['results'] as $event) {
            if ($event['dtstart'] == $updatedEvent['dtstart']) {
                $this->assertEquals(3, count($event['attendee']), 'Attendee count mismatch in exdate: ' . print_r($event, TRUE));
            } else {
                $this->assertEquals(4, count($event['attendee']), 'Attendee count mismatch: ' . print_r($event, TRUE));
            }
        }
    }

    /**
     * testConcurrentAttendeeChangeAdd
     * 
     * @see 0008078: concurrent attendee change should be merged
     */
    public function testConcurrentAttendeeChangeAdd()
    {
        $eventData = $this->testCreateEvent();
        $numAttendee = count($eventData['attendee']);
        $eventData['attendee'][$numAttendee] = array(
            'user_id' => $this->_personasContacts['pwulf']->getId(),
        );
        $this->_uit->saveEvent($eventData);
        
        $eventData['attendee'][$numAttendee] = array(
            'user_id' => $this->_personasContacts['jsmith']->getId(),
        );
        $event = $this->_uit->saveEvent($eventData);
        
        $this->assertEquals(4, count($event['attendee']), 'both new attendee (pwulf + jsmith) should be added: ' . print_r($event['attendee'], TRUE));
    }

    /**
     * testConcurrentAttendeeChangeRemove
     * 
     * @see 0008078: concurrent attendee change should be merged
     */
    public function testConcurrentAttendeeChangeRemove()
    {
        $eventData = $this->testCreateEvent();
        $currentAttendee = $eventData['attendee'];
        unset($eventData['attendee'][1]);
        $event = $this->_uit->saveEvent($eventData);
        
        $eventData['attendee'] = $currentAttendee;
        $numAttendee = count($eventData['attendee']);
        $eventData['attendee'][$numAttendee] = array(
            'user_id' => $this->_personasContacts['pwulf']->getId(),
        );
        $event = $this->_uit->saveEvent($eventData);
        
        $this->assertEquals(2, count($event['attendee']), 'one attendee should added and one removed: ' . print_r($event['attendee'], TRUE));
    }

    /**
     * testConcurrentAttendeeChangeUpdate
     * 
     * @see 0008078: concurrent attendee change should be merged
     */
    public function testConcurrentAttendeeChangeUpdate()
    {
        $eventData = $this->testCreateEvent();
        $currentAttendee = $eventData['attendee'];
        $adminIndex = ($eventData['attendee'][0]['user_id']['n_fn'] === 'Susan Clever') ? 1 : 0;
        $eventData['attendee'][$adminIndex]['status'] = Calendar_Model_Attender::STATUS_TENTATIVE;
        $event = $this->_uit->saveEvent($eventData);
        
        $loggedMods = Tinebase_Timemachine_ModificationLog::getInstance()->getModificationsBySeq(new Calendar_Model_Attender($eventData['attendee'][$adminIndex]), 1);
        $this->assertEquals(1, count($loggedMods), 'attender modification has not been logged');
        
        $eventData['attendee'] = $currentAttendee;
        $scleverIndex = ($adminIndex === 1) ? 0 : 1;
        $attendeeBackend = new Calendar_Backend_Sql_Attendee();
        $eventData['attendee'][$scleverIndex]['status_authkey'] = $attendeeBackend->get($eventData['attendee'][$scleverIndex]['id'])->status_authkey;
        $eventData['attendee'][$scleverIndex]['status'] = Calendar_Model_Attender::STATUS_TENTATIVE;
        $event = $this->_uit->saveEvent($eventData);

        foreach ($event['attendee'] as $attender) {
            $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $attender['status'], 'both attendee status should be TENTATIVE: ' . print_r($attender, TRUE));
        }
    }
}
