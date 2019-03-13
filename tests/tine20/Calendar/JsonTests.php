<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

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
     * Calendar Controller Event Object
     *
     * @var Calendar_Controller_Event
     */
    protected $_eventController = null;

    protected $_oldFreebusyInfoAllowed = null;
    
    /**
     * (non-PHPdoc)
     * @see Calendar/Calendar_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
        
        Calendar_Controller_Event::getInstance()->doContainerACLChecks(true);
        
        $this->_uit = new Calendar_Frontend_Json();
        $this->_eventController = Calendar_Controller_Event::getInstance();

        $this->_oldFreebusyInfoAllowed = Calendar_Config::getInstance()->{Calendar_Config::FREEBUSY_INFO_ALLOWED};
    }

    public function tearDown()
    {
        Calendar_Model_Event::resetFreeBusyCleanupCache();
        Calendar_Config::getInstance()->set(Calendar_Config::FREEBUSY_INFO_ALLOWED, $this->_oldFreebusyInfoAllowed);

        parent::tearDown();
    }

    /**
     * testGetRegistryData
     */
    public function testGetRegistryData()
    {
        // enforce fresh instance of calendar preferences
        Tinebase_Core::set(Tinebase_Core::PREFERENCES, array());
        
        $registryData = $this->_uit->getRegistryData();
        
        $this->assertTrue(is_array($registryData['defaultContainer']['account_grants']));
        $this->assertTrue(is_array($registryData['defaultContainer']['ownerContact']));
    }

    /**
     * test shared calendar as default
     * @see 0011986: Default Calender in Preferences restet to personal one after logout/login
     */
    public function testGetRegistryDataWithSharedDefault()
    {
        $fe = new Tinebase_Frontend_Json_Container();
        $container = $fe->addContainer('Calendar', 'testdeletecontacts', Tinebase_Model_Container::TYPE_SHARED, '');

        Tinebase_Core::set(Tinebase_Core::PREFERENCES, array());
        Tinebase_Core::getPreference('Calendar')
            ->setValue(Calendar_Preference::DEFAULTCALENDAR, $container['id']);

        $registryData = $this->_uit->getRegistryData();

        $this->assertTrue(is_array($registryData['defaultContainer']['account_grants']));
        $this->assertFalse(isset($registryData['defaultContainer']['ownerContact']));

        Tinebase_Core::getPreference('Calendar')
            ->deleteUserPref(Calendar_Preference::DEFAULTCALENDAR);
    }

    /**
     * testCreateEvent
     * 
     * @param $now should the current date be used
     */
    public function testCreateEvent($now = FALSE)
    {
        $scleverDisplayContainerId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $this->_getPersona('sclever')->getId());
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
        $eventData['etag'] = Tinebase_Record_Abstract::generateUID();
        
        $persistentEventData = $this->_uit->saveEvent($eventData);
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        
        $this->_assertJsonEvent($eventData, $loadedEventData, 'failed to create/load event');
        $this->assertEquals($eventData['etag'], $loadedEventData['etag']);
        
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
        if (PHP_VERSION_ID >= 70200) {
            static::markTestSkipped('FIXME fix for php 7.2+');
        }

        $eventData = $this->_getEventWithAlarm(TRUE)->toArray();
        $persistentEventData = $this->_uit->saveEvent($eventData);
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        
        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($loadedEventData['alarms']));
        $this->assertEquals('Calendar_Model_Event', $loadedEventData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedEventData['alarms'][0]['sent_status']);
        $this->assertTrue((isset($loadedEventData['alarms'][0]['minutes_before']) || array_key_exists('minutes_before', $loadedEventData['alarms'][0])), 'minutes_before is missing');
        
        $scheduler = Tinebase_Core::getScheduler();
        /** @var Tinebase_Model_SchedulerTask $task */
        $task = $scheduler->getBackend()->getByProperty('Tinebase_Alarm', 'name');
        $task->config->run();
        
        // check alarm status
        $loadedEventData = $this->_uit->getEvent($persistentEventData['id']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_SUCCESS, $loadedEventData['alarms'][0]['sent_status']);
    }
    
    /**
     * testUpdateEvent
     *
     * @return array
     */
    public function testUpdateEvent()
    {
        $event = new Calendar_Model_Event($this->testCreateEvent(), true);
        $event->dtstart->addHour(5);
        $event->dtend->addHour(5);
        $event->description = 'are you kidding?';
        
        $eventData = $event->toArray();
        foreach ($eventData['attendee'] as $key => $attenderData) {
            if ($eventData['attendee'][$key]['user_id'] != $this->_getTestUserContact()->getId()) {
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
    public function testDeleteEvent()
    {
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
        
        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to find event');
    }

    /**
     * testSearchEventsWithoutFixedCalendars
     *
     * TODO add fixedCalendar (with event) and assertion
     */
    public function testSearchEventsWithoutFixedCalendars()
    {
        $eventData = $this->testCreateEvent(TRUE);

        $filter = $this->_getEventFilterArray();
        $searchResultData = $this->_uit->searchEvents($filter, array(), false);

        $this->assertTrue(! empty($searchResultData['results']));
        $resultEventData = $searchResultData['results'][0];

        $this->_assertJsonEvent($eventData, $resultEventData, 'failed to find event');
    }

    /**
     * testSearchEvents
     */
    public function testSearchEventsWithResourceAttender()
    {
        $eventData = $this->testCreateEvent(TRUE);

        $resource = Calendar_Controller_Resource::getInstance()->create($this->_getResource());
        $attendee = $eventData['attendee'][0];
        $attendee['user_type'] = Calendar_Model_Attender::USERTYPE_RESOURCE;
        $attendee['user_id'] = $resource->getId();
        unset($attendee['id']);
        $eventData['attendee'][] = $attendee;
        $updatedEvent = $this->_uit->saveEvent($eventData);

        $found = false;
        foreach($updatedEvent['attendee'] as $attendee) {
            if ($resource->getId() === $attendee['user_id']['id']) {
                $found = true;
                break;
            }
        }
        static::assertTrue($found, 'resource attender not created');

        Calendar_Model_Attender::clearCache();
        $filter = $this->_getEventFilterArray();
        $searchResultData = $this->_uit->searchEvents($filter, array());

        $this->assertTrue(! empty($searchResultData['results']));
        $resultEventData = $searchResultData['results'][0];
        $found = false;
        foreach($resultEventData['attendee'] as $attendee) {
            if ($resource->getId() === $attendee['user_id']['id']) {
                $found = true;
                static::assertTrue(isset($attendee['user_id']['container_id']['account_grants']) &&
                    is_array($attendee['user_id']['container_id']['account_grants']) &&
                    !empty($attendee['user_id']['container_id']['account_grants']),
                    'resource attender account grants missing');
                static::assertTrue($attendee['user_id']['container_id']['account_grants']
                    [Calendar_Model_ResourceGrants::RESOURCE_SYNC], 'resource_sync grant not set');
                break;
            }
        }
        static::assertTrue($found, 'resource attender not in search result');
    }
    
    /**
     * get filter array with container and period filter
     * 
     * @param string|int $containerId
     * @return array
     */
    protected function _getEventFilterArray($containerId = NULL)
    {
        $containerId = ($containerId) ? $containerId : $this->_getTestCalendar()->getId();
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
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        
        $this->assertTrue(isset($searchResultData['results'][0]), 'event not found in result: ' . print_r($searchResultData['results'], true));
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
        $filter = array(array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()));
        
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $returnedFilter = $searchResultData['filter'];
        $this->assertEquals(2, count($returnedFilter), 'Two filters shoud have been returned!');
        $this->assertTrue($returnedFilter[1]['field'] == 'period' || $returnedFilter[0]['field'] == 'period', 'One returned filter shoud be a period filter');
    }
    
    /**
     * add period filter if none is given / configure from+until
     * 
     * @see 0009688: allow to configure default period filter in json frontend
     */
    public function testSearchEventsWithOutPeriodFilterConfiguredFromAndUntil()
    {
        Calendar_Config::getInstance()->set(Calendar_Config::MAX_JSON_DEFAULT_FILTER_PERIOD_FROM, 12);
        
        $filter = array(array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()));
        $searchResultData = $this->_uit->searchEvents($filter, array());
        
        $now = Tinebase_DateTime::now()->setTime(0,0,0);
        foreach ($searchResultData['filter'] as $filter) {
            if ($filter['field'] === 'period') {
                $this->assertEquals($now->getClone()->subYear(1)->toString(), $filter['value']['from']);
                $this->assertEquals($now->getClone()->addMonth(1)->toString(), $filter['value']['until']);
            }
        }
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
     * testSearchEventsWithSharedContainerFilter
     *
     * @see 0011968: shared calendars filter leads to sql error with pgsql
     */
    public function testSearchEventsWithSharedContainerFilter()
    {
        $filter = $this->_getEventFilterArray();
        $pathFilterValue = array("path" => "/shared");
        $filter[0]['value'] = $pathFilterValue;
        $searchResultData = $this->_uit->searchEvents($filter, array());

        $this->assertEquals($pathFilterValue, $searchResultData['filter'][0]['value'], print_r($searchResultData['filter'], true));
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
            'user_id' => $this->_getPersonasContacts('pwulf')->getId(),
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
     * testCreateRecurEventYearly
     * 
     * @see 0010610: yearly event is not shown in week view
     */
    public function testCreateRecurEventYearly()
    {
        $eventData = $this->_getEvent()->toArray();
        $eventData['is_all_day_event'] = true;
        $eventData['dtstart'] = '2015-01-04 00:00:00';
        $eventData['dtend'] = '2015-01-04 23:59:59';
        $eventData['rrule'] = array(
            'freq'       => 'YEARLY',
            'interval'   => 1,
            'bymonthday' => 4,
            'bymonth'    => 1,
        );
        
        $updatedEventData = $this->_uit->saveEvent($eventData);
        $this->assertTrue(is_array($updatedEventData['rrule']));
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $eventData['container_id']),
            array('field' => 'period', 'operator' => 'within', 'value' =>
                array("from" => '2014-12-29 00:00:00', "until" => '2015-01-05 00:00:00')
            )
        );
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertEquals(1, $searchResultData['totalcount'], 'event not found');
    }
    
    /**
     * testCreateRecurEventWithRruleUntil
     * 
     * @see 0008906: rrule_until is saved in usertime
     */
    public function testCreateRecurEventWithRruleUntil()
    {
        $eventData = $this->testCreateRecurEvent();
        $localMidnight = Tinebase_DateTime::now()->setTime(23,59,59)->toString();
        $eventData['rrule']['until'] = $localMidnight;
        //$eventData['rrule']['freq']  = 'WEEKLY';
        
        $updatedEventData = $this->_uit->saveEvent($eventData);
        $this->assertGreaterThanOrEqual($localMidnight, $updatedEventData['rrule']['until']);
        
        // check db record
        $calbackend = new Calendar_Backend_Sql();
        $db = $calbackend->getAdapter();
        $select = $db->select();
        $select->from(array($calbackend->getTableName() => $calbackend->getTablePrefix() . $calbackend->getTableName()), array('rrule_until', 'rrule'))->limit(1);
        $select->where($db->quoteIdentifier($calbackend->getTableName() . '.id') . ' = ?', $updatedEventData['id']);
        
        $stmt = $db->query($select);
        $queryResult = $stmt->fetch();
        
//         echo Tinebase_Core::getUserTimezone();
//         echo date_default_timezone_get();
        
        $midnightInUTC = new Tinebase_DateTime($queryResult['rrule_until']);
        $this->assertEquals(Tinebase_DateTime::now()->setTime(23,59,59)->toString(), $midnightInUTC->setTimezone(Tinebase_Core::getUserTimezone(), TRUE)->toString());
    }

    /**
     * testCreateRecurEventWithConstrains
     */
    public function testCreateRecurEventWithConstrains()
    {
        /* $conflictEventData = */$this->testCreateEvent();

        $eventData = $this->testCreateEvent();
        $eventData['rrule'] = array(
            'freq'       => 'WEEKLY',
            'interval'   => 1,
            'byday'      => 'WE',
        );

        $nonExistingPath = '/shared/bf69ccb52613742ee2b84ed2769d8568a1e57d74';
        $virtualParts = '/shared/foo/' . $eventData['container_id']['id'];

        $eventData['rrule_constraints'] = array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => array(
                $eventData['container_id'],
                $nonExistingPath,
                $virtualParts
            )),
        );

        $updatedEventData = $this->_uit->saveEvent($eventData);

        $this->assertTrue(is_array($updatedEventData['rrule_constraints']));
        $this->assertEquals('personal',$updatedEventData['rrule_constraints'][0]['value'][0]['type'], 'filter is not resolved');
        $this->assertEquals($nonExistingPath, $updatedEventData['rrule_constraints'][0]['value'][1]['path'], 'no exception was thrown *yeah*');
        $this->assertEquals('personal',$updatedEventData['rrule_constraints'][0]['value'][2]['type'], 'cannot cope with virtual segments');
        $this->assertEquals(1, count($updatedEventData['exdate']));
        $this->assertEquals('2009-03-25 06:00:00', $updatedEventData['exdate'][0]);

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
        array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
    
        $searchResultData = $this->_uit->searchEvents($filter, array());
    
        $this->assertEquals(6, $searchResultData['totalcount']);
        
        // test appending tags to recurring instances
        $this->assertTrue(isset($searchResultData['results'][4]['tags'][0]), 'tags not set: ' . print_r($searchResultData['results'][4], true));
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
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
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
        $recurSet = Tinebase_Helper::array_value('results', $this->testSearchRecuringIncludes());
        
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
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
        
        $searchResultData = $this->_uit->searchEvents($filter, array('sort' => 'dtstart'));
        
        // we deleted one and cropped
        $this->assertEquals(3, count($searchResultData['results']));
        
        $summaryMap = array();
        foreach ($searchResultData['results'] as $event) {
            $summaryMap[$event['dtstart']] = $event['summary'];
        }
        $this->assertTrue((isset($summaryMap['2009-04-01 06:00:00']) || array_key_exists('2009-04-01 06:00:00', $summaryMap)));
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
        $recurSet = Tinebase_Helper::array_value('results', $this->testSearchRecuringIncludes());
        
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
        $testCalendar = $this->_getTestCalendar();
        Tinebase_Container::getInstance()->setGrants($testCalendar, new Tinebase_Record_RecordSet(
            $testCalendar->getGrantClass(), [[
            'account_id'    => $this->_getTestUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ], [
            'account_id'    => $this->_getPersona('sclever')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ]]), true);
        
        $unittestUser = Tinebase_Core::getUser();
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));
        
        // create persistent exception
        $createdException = $this->_uit->createRecurException($persistentException, FALSE, FALSE);
        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        
        $sclever = $this->_findAttender($createdException['attendee'], 'sclever');
        $defaultCal = $this->_getPersonasDefaultCals('sclever');
        $this->assertEquals('Susan Clever', $sclever['user_id']['n_fn']);
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $sclever['status'], 'status mismatch: ' . print_r($sclever, TRUE));
        $this->assertTrue(is_array($sclever['displaycontainer_id']));
        $this->assertEquals($defaultCal['id'], $sclever['displaycontainer_id']['id']);
    }
    
    /**
     * testUpdateRecurSeries
     */
    public function testUpdateRecurSeries()
    {
        $recurSet = Tinebase_Helper::array_value('results', $this->testSearchRecuringIncludes());
        
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
        
        $someRecurInstance['seq'] = 3;
        $this->_uit->updateRecurSeries($someRecurInstance, FALSE, FALSE);
        
        $searchResultData = $this->_searchRecurSeries($recurSet[0]);
        $this->assertEquals(6, count($searchResultData['results']));
        
        $summaryMap = array();
        foreach ($searchResultData['results'] as $event) {
            $summaryMap[$event['dtstart']] = $event['summary'];
        }
        
        $this->assertTrue((isset($summaryMap['2009-04-01 20:00:00']) || array_key_exists('2009-04-01 20:00:00', $summaryMap)));
        $this->assertEquals('go sleeping', $summaryMap['2009-04-01 20:00:00']);
        
        $fishings = array_keys($summaryMap, 'go fishing');
        $this->assertEquals(5, count($fishings));
        foreach ($fishings as $dtstart) {
            $this->assertEquals('10:00:00', substr($dtstart, -8), 'all fishing events should start at 10:00');
        }
    }
    
    /**
     * testUpdateRecurSeriesRruleWeekly
     * 
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleWeekly()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=WEEKLY;INTERVAL=1;BYDAY=WE';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => TH
        $updatedEvent['dtstart'] = '2009-04-02 12:00:00';
        $updatedEvent['dtend'] = '2009-04-02 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('TH', $newBaseEvent['rrule']['byday'], 'Rrule should have changed');
    }

    /**
     * testUpdateRecurSeriesRruleMonthly
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYDAY=4WE';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-04-02 12:00:00';
        $updatedEvent['dtend']   = '2009-04-02 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('1TH', $newBaseEvent['rrule']['byday'], 'Rrule should have changed');

        $this->_eventController->delete(array($createdEvent->getId()));
    }

    /**
     * testUpdateRecurSeriesRruleMonthly1
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly1()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYDAY=4WE';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-03-26 12:00:00';
        $updatedEvent['dtend']   = '2009-03-26 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('4TH', $newBaseEvent['rrule']['byday'], 'Rrule should have changed');
    }

    /**
     * testUpdateRecurSeriesRruleMonthly2
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly2()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYDAY=-1WE';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-03-26 12:00:00';
        $updatedEvent['dtend']   = '2009-03-26 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('-1TH', $newBaseEvent['rrule']['byday'], 'Rrule should have changed');
    }

    /**
     * testUpdateRecurSeriesRruleMonthly3
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly3()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYDAY=-1WE';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-04-02 12:00:00';
        $updatedEvent['dtend']   = '2009-04-02 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('1TH', $newBaseEvent['rrule']['byday'], 'Rrule should have changed');
    }

    /**
     * testUpdateRecurSeriesRruleMonthly4
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly4()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYDAY=-1WE';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-03-24 12:00:00';
        $updatedEvent['dtend']   = '2009-03-24 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('4TU', $newBaseEvent['rrule']['byday'], 'Rrule should have changed');
    }

    /**
     * testUpdateRecurSeriesRruleMonthly5
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly5()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYDAY=-1TU'; // <- this is a mismatch! so nothing should happen
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-03-24 12:00:00';
        $updatedEvent['dtend']   = '2009-03-24 13:00:00';

        try {
            // this will trigger a rollback -> don't expect the data to be there afterwards
            $this->_uit->updateRecurSeries($updatedEvent, false);
            static::fail('Tinebase_Exception_SystemGeneric exception expected');
        } catch (Tinebase_Exception_SystemGeneric $tesg) {}
    }

    /**
     * testUpdateRecurSeriesRruleMonthly6
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly6()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=25';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-04-02 12:00:00';
        $updatedEvent['dtend']   = '2009-04-02 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('2', $newBaseEvent['rrule']['bymonthday'], 'Rrule should have changed');
    }

    /**
     * testUpdateRecurSeriesRruleMonthly7
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleMonthly7()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=26'; // this is a mismatch, so nothing should happen
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-04-02 12:00:00';
        $updatedEvent['dtend']   = '2009-04-02 13:00:00';

        try {
            // this will trigger a rollback -> don't expect the data to be there afterwards
            $this->_uit->updateRecurSeries($updatedEvent, FALSE);
            static::fail('Tinebase_Exception_SystemGeneric exception expected');
        } catch (Tinebase_Exception_SystemGeneric $tesg) {}
    }

    /**
     * testUpdateRecurSeriesRruleYearly
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleYearly()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=YEARLY;INTERVAL=1;BYMONTH=3;BYMONTHDAY=26'; // this is a mismatch, so nothing should happen
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-04-02 12:00:00';
        $updatedEvent['dtend']   = '2009-04-02 13:00:00';

        try {
            // this will trigger a rollback -> don't expect the data to be there afterwards
            $this->_uit->updateRecurSeries($updatedEvent, FALSE);
            static::fail('Tinebase_Exception_SystemGeneric exception expected');
        } catch (Tinebase_Exception_SystemGeneric $tesg) {}
    }

    /**
     * testUpdateRecurSeriesRruleYearly1
     *
     * Changing the weekday for a whole series should change the rrule as well
     */
    public function testUpdateRecurSeriesRruleYearly1()
    {
        // dtstart = 2009-03-25 => WE
        $eventToCreate = $this->_getEvent();
        $eventToCreate->rrule = 'FREQ=YEARLY;INTERVAL=1;BYMONTH=3;BYMONTHDAY=25';
        $createdEvent = $this->_eventController->create($eventToCreate);

        $updatedEvent = $this->_uit->getEvent($createdEvent->getId());
        // change day => 1TH
        $updatedEvent['dtstart'] = '2009-04-02 12:00:00';
        $updatedEvent['dtend']   = '2009-04-02 13:00:00';

        $oldBaseEvent = $this->_uit->getEvent($createdEvent->getId());

        $newBaseEvent = $this->_uit->updateRecurSeries($updatedEvent, FALSE);

        $this->assertNotEquals($oldBaseEvent['dtstart'], $newBaseEvent['dtstart'], 'dtstart of baseEvent should have changed');
        $this->assertEquals('2', $newBaseEvent['rrule']['bymonthday'], 'Rrule should have changed');
        $this->assertEquals('4', $newBaseEvent['rrule']['bymonth'], 'Rrule should have changed');
    }
    
    
    /**
     * search updated recur set
     * 
     * @param array $firstInstance
     * @return array
     */
    protected function _searchRecurSeries($firstInstance)
    {
        $from = $firstInstance['dtstart'];
        $until = new Tinebase_DateTime($from);
        $until->addWeek(5)->addHour(10);
        $until = $until->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period',       'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
        );
        
        return $this->_uit->searchEvents($filter, array());
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
        $recurSet = Tinebase_Helper::array_value('results', $this->testSearchRecuringIncludes());
        
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
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
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
     * 
     * @return array event data
     */
    public function testFreeBusyCleanup($doAllTesting = true)
    {
        $resource = $this->_getResource();
        $resource->grants = [[
            'account_id'      => '0',
            'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            Calendar_Model_ResourceGrants::RESOURCE_READ => true,
            Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
        ]];
        $resource = Calendar_Controller_Resource::getInstance()->create($resource);
        // give fb grants from sclever
        $scleverCal = Tinebase_Container::getInstance()->getContainerById($this->_getPersonasDefaultCals('sclever'));
        Tinebase_Container::getInstance()->setGrants($scleverCal->getId(), new Tinebase_Record_RecordSet($scleverCal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('sclever')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ), array(
            'account_id'    => $this->_getTestUser()->getId(),
            'account_type'  => 'user',
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ))), TRUE);
        
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));
        $eventData = $this->_getEvent()->toArray();
        unset($eventData['organizer']);
        $eventData['container_id'] = $scleverCal->getId();
        $eventData['attendee'] = [[
            'user_id' => $this->_getPersonasContacts('sclever')->getId()
        ],[
            'user_id' => $resource->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE
        ]];
        $eventData['organizer'] = $this->_getPersonasContacts('sclever')->getId();
        $eventData = $this->_uit->saveEvent($eventData);
        $filter = $this->_getEventFilterArray($this->_getPersonasDefaultCals('sclever')->getId());
        $filter[] = array('field' => 'summary', 'operator' => 'equals', 'value' => 'Wakeup');
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected event in search result (search by sclever): ' 
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));

        Calendar_Model_Attender::clearCache();
        Calendar_Model_Event::resetFreeBusyCleanupCache();
        Calendar_Config::getInstance()->set(Calendar_Config::FREEBUSY_INFO_ALLOWED, 10);
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getTestUser());
        $this->_removeRoleRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES, true);
        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected (freebusy cleanup) event in search result: ' 
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));
        $eventData = $searchResultData['results'][0];
        if (!$doAllTesting) {
            return $eventData;
        }
        $this->_assertFreebusyData($eventData, 10);

        Calendar_Model_Attender::clearCache();
        Calendar_Model_Event::resetFreeBusyCleanupCache();
        Calendar_Config::getInstance()->set(Calendar_Config::FREEBUSY_INFO_ALLOWED, 20);

        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected (freebusy cleanup) event in search result: '
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));
        $eventData = $searchResultData['results'][0];
        $this->_assertFreebusyData($eventData, 20);

        Calendar_Model_Attender::clearCache();
        Calendar_Model_Event::resetFreeBusyCleanupCache();
        Calendar_Config::getInstance()->set(Calendar_Config::FREEBUSY_INFO_ALLOWED, 30);

        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected (freebusy cleanup) event in search result: '
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));
        $eventData = $searchResultData['results'][0];
        $this->_assertFreebusyData($eventData, 30);

        Calendar_Model_Attender::clearCache();
        Calendar_Model_Event::resetFreeBusyCleanupCache();
        Calendar_Config::getInstance()->set(Calendar_Config::FREEBUSY_INFO_ALLOWED, 40);

        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected (freebusy cleanup) event in search result: '
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));
        $eventData = $searchResultData['results'][0];
        $this->_assertFreebusyData($eventData, 40);

        Calendar_Model_Attender::clearCache();
        Calendar_Model_Event::resetFreeBusyCleanupCache();
        Calendar_Config::getInstance()->set(Calendar_Config::FREEBUSY_INFO_ALLOWED, 50);

        $searchResultData = $this->_uit->searchEvents($filter, array());
        $this->assertTrue(! empty($searchResultData['results']), 'expected (freebusy cleanup) event in search result: '
            . print_r($eventData, TRUE) . 'search filter: ' . print_r($filter, TRUE));
        $eventData = $searchResultData['results'][0];
        $this->_assertFreebusyData($eventData, 50);

        return null;
    }

    protected function _assertFreebusyData($eventData, $accessLevel)
    {
        static::assertFalse(isset($eventData['summary']), 'summary not empty: ' . print_r($eventData, TRUE));
        static::assertFalse(isset($eventData['description']), 'description not empty');
        static::assertTrue(empty($eventData['tags']), 'tags not empty');
        static::assertTrue(empty($eventData['notes']), 'notes not empty');
        static::assertTrue(empty($eventData['alarms']), 'alarms not empty');
        if ($accessLevel < 20) {
            static::assertFalse(isset($eventData['container_id']), 'container_id not empty');
        } else {
            static::assertTrue(isset($eventData['container_id']) && !empty($eventData['container_id']),
                'container_id empty');
        }
        if ($accessLevel < 30) {
            static::assertFalse(isset($eventData['organizer']), 'organizer not empty');
        } else {
            static::assertTrue(isset($eventData['organizer']) && !empty($eventData['organizer']), 'organizer empty');
        }
        if ($accessLevel < 40) {
            static::assertTrue(empty($eventData['attendee']), 'attendee not empty');
        } else {
            static::assertFalse(empty($eventData['attendee']), 'attendee empty');
        }

        if ($accessLevel === 40) {
            static::assertEquals(1, count($eventData['attendee']), 'number of attendees wrong');
        }
        if ($accessLevel === 50) {
            static::assertEquals(2, count($eventData['attendee']), 'number of attendees wrong');
        }
    }

    /**
     * testFreeBusyCleanupOfNotes
     * 
     * @see 0009918: shared (only free/busy) calendar is showing event details within the history tab.
     */
    public function testFreeBusyCleanupOfNotes()
    {
        $eventData = $this->testFreeBusyCleanup(false);
        
        $tinebaseJson = new Tinebase_Frontend_Json();
        $filter = array(array(
            'field' => "record_model",
            'operator' => "equals",
            'value' => "Calendar_Model_Event"
        ), array(
            'field' => 'record_id',
            'operator' => 'equals',
            'value' => $eventData['id']
        ));
        $notes = $tinebaseJson->searchNotes($filter, array());
        
        $this->assertEquals(0, $notes['totalcount'], 'should not find any notes of record');
        $this->assertEquals(0, count($notes['results']), 'should not find any notes of record');
    }
    
    /**
     * test deleting container and the containing events
     * #6704: events do not disappear when shared calendar got deleted
     * https://forge.tine20.org/mantisbt/view.php?id=6704
     */
    public function testDeleteContainerAndEvents()
    {
        $fe = new Tinebase_Frontend_Json_Container();
        $container = $fe->addContainer('Calendar', 'testdeletecontacts', Tinebase_Model_Container::TYPE_SHARED, 'Event');
        // test if model is set automatically
        $this->assertEquals($container['model'], 'Calendar_Model_Event');
        
        $date = new Tinebase_DateTime();
        $event = new Calendar_Model_Event(array(
            'dtstart' => $date,
            'dtend'    => $date->subHour(1),
            'summary' => 'bla bla',
            'class'    => 'PUBLIC',
            'transp'    => 'OPAQUE',
            'container_id' => $container['id'],
            'organizer' => Tinebase_Core::getUser()->contact_id
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
        // container, assert attendee, tags
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
        
        if ((isset($expectedEventData['alarms']) || array_key_exists('alarms', $expectedEventData))) {
            $this->assertTrue((isset($eventData['alarms']) || array_key_exists('alarms', $eventData)), ': failed to create alarms');
            $this->assertEquals(count($expectedEventData['alarms']), count($eventData['alarms']), $msg . ': failed to create correct number of alarms');
            if (count($expectedEventData['alarms']) > 0) {
                $this->assertTrue((isset($eventData['alarms'][0]['minutes_before']) || array_key_exists('minutes_before', $eventData['alarms'][0])));
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
        $searchedId = $this->_getPersonasContacts($name)->getId();
        
        foreach ($attendeeData as $key => $attender) {
            if ($attender['user_type'] == Calendar_Model_Attender::USERTYPE_USER) {
                if (is_array($attender['user_id']) && (isset($attender['user_id']['id']) || array_key_exists('id', $attender['user_id']))) {
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
        $this->_groupIdsToDelete[] = $hiddenGroup->getId();
        
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
    public function testSaveResource($grants = [Calendar_Model_ResourceGrants::RESOURCE_READ => true,
         Calendar_Model_ResourceGrants::EVENTS_EDIT => true, Calendar_Model_ResourceGrants::RESOURCE_INVITE => true])
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
        if (count($filteredGrantsInput = array_filter($grants)) > 0 ) {
            foreach ($filteredGrantsInput as $key => $value) {
                static::assertTrue(isset($resoureData['grants'][0][$key]) && $resoureData['grants'][0][$key],
                    $key . ' grant missing');
            }
        }
        
        return $resoureData;
    }

    /**
     * test creating a resource that had no grants
     */
    public function testSaveResourcesWithoutRights()
    {
        static::setExpectedException(Tinebase_Exception_AccessDenied::class, 'No Permission.');
        $this->testSaveResource(array());
    }


    /**
     * assert only resources with read grant are returned
     */
    public function testSearchResources()
    {
        $nonReadableResoureData = $this->testSaveResource();
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        $readableResoureData = $this->testSaveResource();
        
        $filter = array(
            array('field' => 'name', 'operator' => 'in', 'value' => array(
                $readableResoureData['name'],
                $nonReadableResoureData['name'],
            ))
        );

        $searchResult = $this->_uit->searchResources($filter, array());
        $this->assertEquals(1, count($searchResult['results']), 'only one record should be found');
    }

    /**
     * assert add attendee does not work for non readable resources
     */
    public function testResourceAttendeeAddFail()
    {
        $nonreadableResourceData = $this->testSaveResource();

        $event = $this->_getEvent(TRUE);
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type'  => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'    => $nonreadableResourceData['id'],
                'status'     => Calendar_Model_Attender::STATUS_ACCEPTED
            )
        ));

        $persistentEventData = $this->_uit->saveEvent($event->toArray());
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $persistentEventData['attendee']);
        static::assertEquals(1, count($attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)), 'one accepted');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        $event = $this->_getEvent(TRUE);
        $event->organizer = $this->_personas['pwulf']->contact_id;
        $event->container_id = $this->_getPersonalContainer(Calendar_Model_Event::class, $this->_personas['pwulf'])->getId();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type'  => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'    => $nonreadableResourceData['id'],
                'status'     => Calendar_Model_Attender::STATUS_ACCEPTED
            )
        ));

        try {
            $this->_uit->saveEvent($event->toArray());
            static::fail('it should not be possible to create an attender for a resource without read rights');
        } catch (Tinebase_Exception_AccessDenied $tead) {}
    }

    protected function _setResourceRights($resource, $grants, $user = null)
    {
        if (null === $user) {
            $user = $this->_personas['pwulf'];
        }
        if (!empty($grants) && !isset($grants['account_id'])) {
            $grants['account_id'] = Tinebase_Core::getUser()->getId();
            $grants['account_type'] = 'user';
        }

        $newGrants = [
            'account_id' => $user->getId(),
            'account_type' => 'user',
            Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
        ];
        $resource['grants'][] = $newGrants;

        Tinebase_Container::getInstance()->setGrants($resource['container_id'],
            new Tinebase_Record_RecordSet(Calendar_Model_ResourceGrants::class, $resource['grants']), true, false);
        $oldUser = Tinebase_Core::getUser();
        Tinebase_Core::set(Tinebase_Core::USER, $user);
        Tinebase_Container::getInstance()->setGrants($resource['container_id'],
            new Tinebase_Record_RecordSet(Calendar_Model_ResourceGrants::class, array_merge([$newGrants],
                empty($grants) ? [] : [$grants])), true, false);
        Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
    }

    /**
     * test that no status auth key is returned for non editable resources
     * test non readable resources are displayed as attenders
     * test status updates do not work for non readable attenders
     * test attendee updates do not remove non readable attenders
     * test removing non readable attenders works
     */
    public function testResourceAttendeeNonReadAble()
    {
        $editableResoureData = $this->testSaveResource();
        $nonreadableResourceData = $this->testSaveResource();


        $event = $this->_getEvent(TRUE);
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type'  => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'    => $editableResoureData['id'],
                'status'     => Calendar_Model_Attender::STATUS_ACCEPTED
            ),
            array(
                'user_type'  => Calendar_Model_Attender::USERTYPE_RESOURCE,
                'user_id'    => $nonreadableResourceData['id'],
                'status'     => Calendar_Model_Attender::STATUS_ACCEPTED
            )
        ));

        $persistentEventData = $this->_uit->saveEvent($event->toArray());

        $this->_setResourceRights($nonreadableResourceData, []);

        $persistentEventData = $this->_uit->getEvent($persistentEventData['id']);
        $this->assertEquals(2, count($persistentEventData['attendee']), 'resource without read grant must not be missing in attendee: '
            . print_r($persistentEventData['attendee'], true));

        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $persistentEventData['attendee']);
        $this->assertEquals(2, count($attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)), 'two accepted');
        $this->assertEquals(1, count($attendee->filter('status_authkey', '/[a-z0-9]+/', TRUE)), 'one has authkey');

        // saving event should not remove the non readable resource
        // status update should only work for editable resource
        $attendee->status = Calendar_Model_Attender::STATUS_TENTATIVE;
        $persistentEventData['attendee'] = $attendee->toArray();
        $updatedEventData = $this->_uit->saveEvent($persistentEventData);

        $this->assertEquals(2, count($updatedEventData['attendee']), 'resource without read grant must not be missing in attendee: '
            . print_r($updatedEventData['attendee'], true));
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $updatedEventData['attendee']);
        $this->assertEquals(1, count($attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)), 'one accepted');
        $this->assertEquals(1, count($attendee->filter('status', Calendar_Model_Attender::STATUS_TENTATIVE)), 'one tentative');

        // removing non readable resource should work
        $attendee->removeRecord($attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)->getFirstRecord());
        $updatedEventData['attendee'] = $attendee->toArray();
        $updatedEventData = $this->_uit->saveEvent($updatedEventData);

        $this->assertEquals(1, count($updatedEventData['attendee']), 'resource without read grant must not be present in attendee: '
            . print_r($updatedEventData['attendee'], true));
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $updatedEventData['attendee']);
        $this->assertEquals(1, count($attendee->filter('status', Calendar_Model_Attender::STATUS_TENTATIVE)), 'one tentative');
    }

    /**
     * assert status authkey with editGrant
     * assert status can be set with editGrant
     * assert status can't be set without editGrant
     */
    public function testResourceAttendeeGrants()
    {
        $editableResoureData = $this->testSaveResource();
        $nonEditableResoureData = $this->testSaveResource();

        $this->_setResourceRights($nonEditableResoureData, [Calendar_Model_ResourceGrants::RESOURCE_INVITE => true]);
        
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
     * testConcurrentAttendeeChangeAdd
     * 
     * @see 0008078: concurrent attendee change should be merged
     */
    public function testConcurrentAttendeeChangeAdd()
    {
        $eventData = $this->testCreateEvent();
        $numAttendee = count($eventData['attendee']);
        $eventData['attendee'][$numAttendee] = array(
            'user_id' => $this->_getPersonasContacts('pwulf')->getId(),
        );
        $this->_uit->saveEvent($eventData);
        
        $eventData['attendee'][$numAttendee] = array(
            'user_id' => $this->_getPersonasContacts('jsmith')->getId(),
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
            'user_id' => $this->_getPersonasContacts('pwulf')->getId(),
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
        
        $loggedMods = Tinebase_Timemachine_ModificationLog::getInstance()->getModificationsBySeq(
            Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            new Calendar_Model_Attender($eventData['attendee'][$adminIndex]), 2);
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

    /**
     * testFreeBusyCheckForExdates
     * 
     * @see 0008464: freebusy check does not work when creating recur exception
     */
    public function testFreeBusyCheckForExdates()
    {
        $events = $this->testCreateRecurException();
        $exception = $this->_getException($events, 1);
        
        $anotherEvent = $this->_getEvent(TRUE);
        $anotherEvent = $this->_uit->saveEvent($anotherEvent->toArray());
        
        $exception['dtstart'] = $anotherEvent['dtstart'];
        $exception['dtend'] = $anotherEvent['dtend'];
        
        try {
            $event = $this->_uit->saveEvent($exception, TRUE);
            $this->fail('Calendar_Exception_AttendeeBusy expected when saving exception: ' . print_r($exception, TRUE));
        } catch (Calendar_Exception_AttendeeBusy $ceab) {
            $this->assertEquals('Calendar_Exception_AttendeeBusy', get_class($ceab));
        }
    }
    
    /**
     * testAddAttachmentToRecurSeries
     * 
     * @see 0005024: allow to attach external files to records
     */
    public function testAddAttachmentToRecurSeries()
    {
        $tempFile = $this->_getTempFile();
        $recurSet = Tinebase_Helper::array_value('results', $this->testSearchRecuringIncludes());
        // update recurseries 
        $someRecurInstance = $recurSet[2];
        $someRecurInstance['attachments'] = array(array('tempFile' => array('id' => $tempFile->getId())));
        $someRecurInstance['seq'] = 2;
        $this->_uit->updateRecurSeries($someRecurInstance, FALSE, FALSE);
        
        $searchResultData = $this->_searchRecurSeries($recurSet[0]);
        foreach ($searchResultData['results'] as $recurInstance) {
            $this->assertTrue(isset($recurInstance['attachments']), 'no attachments found in event: ' . print_r($recurInstance, TRUE));
            $this->assertEquals(1, count($recurInstance['attachments']));
            $attachment = $recurInstance['attachments'][0];
            $this->assertEquals('text/plain', $attachment['contenttype'], print_r($attachment, TRUE));
        }
    }
    
    /**
     * checks if manipulated dtend and dtstart gets set to the correct values on creating or updating an event
     * 
     * @see 0009696: time is not grayed out for all-day events
     */
    public function testWholedayEventTimes()
    {
        $event = $this->_getEvent(TRUE);
        $event->is_all_day_event = TRUE;
        
        $event = Calendar_Controller_Event::getInstance()->create($event);
        $event->setTimezone(Tinebase_Core::getUserTimezone());
        
        $this->assertEquals('00:00:00', $event->dtstart->format('H:i:s'));
        $this->assertEquals('23:59:59', $event->dtend->format('H:i:s'));
        
        $event->dtstart = Tinebase_DateTime::now();
        $event->dtend   = Tinebase_DateTime::now()->addHour(1);
        
        $event = Calendar_Controller_Event::getInstance()->update($event);
        $event->setTimezone(Tinebase_Core::getUserTimezone());
        
        $this->assertEquals('00:00:00', $event->dtstart->format('H:i:s'));
        $this->assertEquals('23:59:59', $event->dtend->format('H:i:s'));
    }
    
     /**
     * testAttendeeChangeQuantityToInvalid
     * 
     * @see 9630: sanitize attender quantity
     */
    public function testAttendeeChangeQuantityToInvalid()
    {
        $eventData = $this->testCreateEvent();
        $currentAttendee = $eventData['attendee'];
        $eventData['attendee'][1]['quantity'] = '';
        $event = $this->_uit->saveEvent($eventData);
        $this->assertEquals(1, $event['attendee'][1]['quantity'], 'The invalid quantity should be saved as 1');
    }

    /**
     * trigger caldav import by json frontend
     * 
     * @todo use mock as fallback (if server can not be reached by curl)
     * @todo get servername from unittest config / skip or mock if no servername found
     */
    public function testCalDAVImport()
    {
        // Skip if tine20.com.local could not be resolved
        if (gethostbyname('tine20.com.local') == 'tine20.com.local') {
            $this->markTestSkipped('Can\'t perform test, because instance is not reachable.');
        }

        $this->_testNeedsTransaction();
        
        $event = $this->testCreateEvent(/* $now = */ true);
        
        $fe = new Calendar_Frontend_Json();
        $testUserCredentials = TestServer::getInstance()->getTestCredentials();
        $fe->importRemoteEvents(
            'http://tine20.com.local/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $event['container_id']['id'],
            Tinebase_Model_Import::INTERVAL_DAILY,
            array(
                'container_id'          => 'remote_caldav_calendar',
                'sourceType'            => 'remote_caldav',
                'importFileByScheduler' => false,
                'allowDuplicateEvents'  => true,
                'username'              => $testUserCredentials['username'],
                'password'              => $testUserCredentials['password'],
            ));

        $importScheduler = Tinebase_Controller_ScheduledImport::getInstance();
        $record = $importScheduler->runNextScheduledImport();

        $container = Tinebase_Container::getInstance()->getContainerByName(Calendar_Model_Event::class, 'remote_caldav_calendar', Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser()->getId());
        $this->_testCalendars[] = $container;
        $this->assertTrue($container instanceof Tinebase_Model_Container, 'Container was not created');

        $this->assertNotEquals($record, null, 'The import could not start!');
        
        $filter = $this->_getEventFilterArray($container->getId());
        $result = $this->_uit->searchEvents($filter, array());
        $this->assertEquals(1, $result['totalcount']);
    }
    
    /**
     * testGetRelations
     * 
     * @see 0009542: load event relations on demand
     */
    public function testGetRelations()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'Contact for relations test'
        )));
        $eventData = $this->_getEvent()->toArray();
        $eventData['relations'] = array(
            array(
                'own_model' => 'Calendar_Model_Event',
                'own_backend' => 'Sql',
                'own_id' => 0,
                'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type' => '',
                'related_backend' => 'Sql',
                'related_id' => $contact->getId(),
                'related_model' => 'Addressbook_Model_Contact',
                'remark' => NULL,
            ));
        $event = $this->_uit->saveEvent($eventData);

        $tfj = new Tinebase_Frontend_Json();
        $relations = $tfj->getRelations('Calendar_Model_Event', $event['id']);

        $this->assertEquals(1, $relations['totalcount']);
        $this->assertEquals($contact->n_fn, $relations['results'][0]['related_record']['n_family'], print_r($relations['results'], true));
    }

    public function testGetFreeBusyInfo()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        /** @var Calendar_Model_Event $persistentEvent */
        $persistentEvent = $this->_eventController->create($event);
        $persistentEvent->setTimezone(Tinebase_Core::getUserTimezone());

        unset($persistentEvent->rrule);

        $eventData = [$persistentEvent->toArray()];
        $eventData[0]['id'] = 0;
        $persistentEvent->dtstart->addMinute(10);
        $persistentEvent->dtend->addMinute(10);
        $eventData[] = $persistentEvent->toArray();
        $eventData[1]['id'] = Tinebase_Record_Abstract::generateUID();
        $persistentEvent->dtstart->addMinute(5);
        $persistentEvent->dtend->addMinute(5);
        $eventData[] = $persistentEvent->toArray();
        $eventData[2]['id'] = Tinebase_Record_Abstract::generateUID();
        // to test sorting, we add a late event as first element of the array
        $persistentEvent->dtstart->addDay(1)->subMinute(15);
        $persistentEvent->dtend->addDay(1)->subMinute(15);
        array_unshift($eventData, $persistentEvent->toArray());

        $fbinfo = $this->_uit->getFreeBusyInfo($persistentEvent->attendee->toArray(), $eventData);
        // 4 events
        $this->assertEquals(4, count($fbinfo));
        // the +1 day
        $this->assertEquals(2, count($fbinfo[$eventData[0]['id']]));
        // the unchanged date
        $this->assertEquals(2, count($fbinfo[$eventData[1]['id']]));
        // the +10 minutes
        $this->assertEquals(2, count($fbinfo[$eventData[2]['id']]));
        // the +15 minutes
        $this->assertEquals(0, count($fbinfo[$eventData[3]['id']]));

        $fbinfo = $this->_uit->getFreeBusyInfo($persistentEvent->attendee->toArray(), $eventData, array($persistentEvent->uid));
        // 4 events
        $this->assertEquals(4, count($fbinfo));
        // no conflicts for all of them
        foreach ($fbinfo as $fb) {
            static::assertEquals(0, count($fb));
        }
    }

    public function testSearchAttenders()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        $persistentEvent = $this->_eventController->create($event);
        $persistentEvent->setTimezone(Tinebase_Core::getUserTimezone());

        $filter = array(array('field' => 'query', 'operator' => 'contains', 'value' => 'l'));
        $paging = array('sort' => 'name', 'dir' => 'ASC', 'start' => 0, 'limit' => 50);

        $result = $this->_uit->searchAttenders($filter, $paging, [$persistentEvent->toArray()], array());
        $this->assertTrue(
            isset($result[Calendar_Model_Attender::USERTYPE_USER]) &&
            count($result[Calendar_Model_Attender::USERTYPE_USER]) === 3 &&
            count($result[Calendar_Model_Attender::USERTYPE_USER]['results']) > 4 &&
            isset($result[Calendar_Model_Attender::USERTYPE_GROUP]) &&
            isset($result[Calendar_Model_Attender::USERTYPE_RESOURCE]) &&
            isset($result['freeBusyInfo']) &&
            count(array_pop($result['freeBusyInfo'])) === 2, print_r($result, true));

        $filter[] = array('field' => 'type', 'value' => array(Calendar_Model_Attender::USERTYPE_RESOURCE));
        $result = $this->_uit->searchAttenders($filter, $paging, [$persistentEvent->toArray()], array());
        $this->assertTrue(
            !isset($result[Calendar_Model_Attender::USERTYPE_USER]) &&
            !isset($result[Calendar_Model_Attender::USERTYPE_GROUP]) &&
            isset($result[Calendar_Model_Attender::USERTYPE_RESOURCE]) &&
            count($result[Calendar_Model_Attender::USERTYPE_RESOURCE]) === 3 &&
            count($result[Calendar_Model_Attender::USERTYPE_RESOURCE]['results']) === 0 &&
            isset($result['freeBusyInfo']) &&
            array_pop($result['freeBusyInfo']) === null,
            print_r($result, true)
        );

        $filter = [
            ['field' => 'query', 'operator' => 'contains', 'value' => ''],
            ['field' => 'type',  'operator' => 'oneof',    'value' => [Calendar_Model_Attender::USERTYPE_GROUP]]
        ];

        $result = $this->_uit->searchAttenders($filter, $paging, [$persistentEvent->toArray()], array());
        $this->assertTrue(
            !isset($result[Calendar_Model_Attender::USERTYPE_USER]) &&
            isset($result[Calendar_Model_Attender::USERTYPE_GROUP]) &&
            !isset($result[Calendar_Model_Attender::USERTYPE_RESOURCE])
        );
    }

    public function testSearchAttendeersConfigUserFilter()
    {
        $filter = json_decode('[{
                "field":"type",
                "value":["user"]
            }, {
                "field":"query",
                "operator":"contains",
                "value":"McBlack"
            }]', true);
        //$filter[1]['value']['value'] = $allIds;

        $result = $this->_uit->searchAttenders($filter, [], [], []);
        $count = count($result['user']['results']);
        $this->assertGreaterThanOrEqual(1, $count);

        /** @var Addressbook_Model_Contact $aContact */
        $aContact = Addressbook_Controller_Contact::getInstance()->get($result['user']['results'][0]['id']);
        $aContact->tags = [new Tinebase_Model_Tag(['name' => 'myTag'])];
        $aContact = Addressbook_Controller_Contact::getInstance()->update($aContact);

        
        $oldConfig = clone Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER};
        Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER}
            ->{Calendar_Config::SEARCH_ATTENDERS_FILTER_USER} = ['condition' => 'AND', 'filters' =>
                [['field' => 'tag', 'operator' => 'notin', 'value' => [$aContact->tags->getFirstRecord()->getId()]]]];

        try {
            $result = $this->_uit->searchAttenders($filter, [], [], []);
            $this->assertEquals($count - 1, count($result['user']['results']));
        } finally {
            Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER} = $oldConfig;
        }
    }

    public function testSearchAttendeersConfigGroupFilter()
    {
        $allIds = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter(), null, false,
            true);
        static::assertGreaterThanOrEqual(2, count($allIds), 'test needs at least 2 ids');

        $oldConfig = clone Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER};
        Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER}
            ->{Calendar_Config::SEARCH_ATTENDERS_FILTER_GROUP} = ['condition' => 'AND', 'filters' =>
            [['field' => 'id', 'operator' => 'equals', 'value' => $allIds[0]]]];

        try {
            $filter = json_decode('[{
                "field":"type",
                "value":["group"]
            }, {
                "field":"id",
                "operator":"in",
                "value":null
            }]', true);
            $filter[1]['value'] = $allIds;

            $result = $this->_uit->searchAttenders($filter, [], [], []);
            $this->assertEquals(1, count($result['group']['results']));
        } finally {
            Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER} = $oldConfig;
        }
    }

    public function testSearchAttendeersConfigResourceFilter()
    {
        $resController = Calendar_Controller_Resource::getInstance();
        $resController->create($this->_getResource());
        $resource = $this->_getResource();
        $resource->name = 'blablub';
        $resController->create($resource);
        $allIds = $resController->search(new Calendar_Model_ResourceFilter(), null, false, true);
        static::assertGreaterThanOrEqual(2, count($allIds), 'test needs at least 2 ids');

        $oldConfig = clone Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER};
        Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER}
            ->{Calendar_Config::SEARCH_ATTENDERS_FILTER_RESOURCE} = ['condition' => 'AND', 'filters' =>
            [['field' => 'id', 'operator' => 'equals', 'value' => $allIds[0]]]];

        try {
            $filter = json_decode('[{
                "field":"type",
                "value":["resource"]
            }, {
                "field":"id",
                "operator":"in",
                "value":null
            }]', true);
            $filter[1]['value'] = $allIds;

            $result = $this->_uit->searchAttenders($filter, [], [], []);
            $this->assertEquals(1, count($result['resource']['results']));
        } finally {
            Calendar_Config::getInstance()->{Calendar_Config::SEARCH_ATTENDERS_FILTER} = $oldConfig;
        }
    }

    public function testSearchAttendeersByTypeAndId()
    {
        $allIds = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(), null,
            false, true);

        $filter = json_decode('[{
            "field":"type",
            "value":["user"]
        }, {
            "field":"userFilter",
            "value":{
                "field":"id",
                "operator":"in",
                "value":[]
            }
        }]', true);
        $filter[1]['value']['value'] = $allIds;

        $result = $this->_uit->searchAttenders($filter, [], [], []);
        $this->assertEquals(count($allIds), count($result['user']['results']));
    }

    public function testSearchFreeTime()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER)
        ));
        $event->rrule = 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TU,FR';
        $event->originator_tz = $event->dtstart->getTimezone()->getName();

        $options = array(
            'from'        => $event->dtstart->getClone()->addDay(2)->setHour(12),
            'constraints' => array(array(
                'dtstart'   => $event->dtstart->getClone()->setHour(10),
                'dtend'     => $event->dtstart->getClone()->setHour(22),
                'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU,WE,TH,FR'
            )),
        );

        $expectedDtStart = new Tinebase_DateTime('2009-03-27 12:00:00', $event->originator_tz);
        $expectedDtStart->setTimezone(Tinebase_Core::getUserTimezone());

        $result = $this->_uit->searchFreeTime($event->toArray(), $options);
        static::assertTrue(is_array($result) && count($result) === 4 && count($result['results']) === 1);
        static::assertEquals($expectedDtStart->toString(), $result['results'][0]['dtstart']);
    }

    public function testSearchFreeTime1()
    {
        // 2009-03-25 => Mittwoch
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER)
        ));
        $event->originator_tz = $event->dtstart->getTimezone()->getName();

        $options = array(
            'constraints' => array(array(
                'dtstart'   => $event->dtstart->getClone()->subDay(1)->setHour(10),
                'dtend'     => $event->dtstart->getClone()->subDay(1)->setHour(22),
                'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU'
            ), array(
                'dtstart'   => $event->dtstart->getClone()->subDay(1)->setHour(13),
                'dtend'     => $event->dtstart->getClone()->subDay(1)->setHour(16),
                'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH,FR'
            )),
        );

        $expectedDtStart = new Tinebase_DateTime('2009-03-26 13:00:00', $event->originator_tz);
        $expectedDtStart->setTimezone(Tinebase_Core::getUserTimezone());

        $result = $this->_uit->searchFreeTime($event->toArray(), $options);
        static::assertTrue(is_array($result) && count($result) === 4 && count($result['results']) === 1);
        static::assertEquals($expectedDtStart->toString(), $result['results'][0]['dtstart']);
    }

    public function testSearchFreeTime2()
    {
        // 2009-03-25 => Mittwoch
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER)
        ));
        $event->originator_tz = $event->dtstart->getTimezone()->getName();

        $options = array(
            'constraints' => array(array(
                'dtstart'   => $event->dtstart->getClone()->subDay(1)->setHour(10),
                'dtend'     => $event->dtstart->getClone()->subDay(1)->setHour(22),
                'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU'
            ), array(
                'dtstart'   => $event->dtstart->getClone()->subDay(1)->setHour(13),
                'dtend'     => $event->dtstart->getClone()->subDay(1)->setHour(16),
                'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH,FR'
            )),
        );

        $expectedDtStart = new Tinebase_DateTime('2009-03-27 13:00:00', $event->originator_tz);
        $expectedDtStart->setTimezone(Tinebase_Core::getUserTimezone());

        $event->dtstart->addDay(1)->setTime(16, 0 ,0);
        $event->dtend->addDay(1)->setTime(17, 0 ,0);

        $createEvent = new Calendar_Model_Event(array(), true);
        $eventData = $event->toArray();
        $createEvent->setFromJsonInUsersTimezone($eventData);
        Calendar_Controller_Event::getInstance()->create($createEvent);

        $result = $this->_uit->searchFreeTime($event->toArray(), $options);
        static::assertTrue(is_array($result) && count($result) === 4 && count($result['results']) === 1);
        static::assertEquals($expectedDtStart->toString(), $result['results'][0]['dtstart']);
    }

    public function testSearchByBoolCustomField()
    {
        $cfc = Tinebase_CustomFieldTest::getCustomField([
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'          => Calendar_Model_Event::class,
            'definition'     => [
                'type'          => 'bool'
            ]
        ]);
        $cfc = Tinebase_CustomField::getInstance()->addCustomField($cfc);

        $event = $this->_getEvent();
        $event->customfields = [$cfc->name => 1];
        $createdEvent = Calendar_Controller_Event::getInstance()->create($event);

        $filter = $this->_getEventFilterArray();
        $filter[] = [
            'field' => 'customfield', 'operator' => 'equals', 'value' => [
                'cfId'  => $cfc->getId(),
                'value' => 1,
            ]
        ];
        $searchResultData = $this->_uit->searchEvents($filter, array());

        $this->assertTrue(! empty($searchResultData['results']) && 1 === count($searchResultData['results']));
        $resultEventData = $searchResultData['results'][0];
        $this->assertEquals($createdEvent->getId(), $resultEventData['id']);
    }

    public function testSearchByBooleanCustomField()
    {
        $cfc = Tinebase_CustomFieldTest::getCustomField([
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'          => Calendar_Model_Event::class,
            'definition'     => [
                'type'          => 'boolean'
            ]
        ]);
        $cfc = Tinebase_CustomField::getInstance()->addCustomField($cfc);

        $event = $this->_getEvent();
        $event->customfields = [$cfc->name => 1];
        $createdEvent = Calendar_Controller_Event::getInstance()->create($event);

        $filter = $this->_getEventFilterArray();
        $filter[] = [
            'field' => 'customfield', 'operator' => 'equals', 'value' => [
                'cfId'  => $cfc->getId(),
                'value' => 1,
            ]
        ];
        $searchResultData = $this->_uit->searchEvents($filter, array());

        $this->assertTrue(! empty($searchResultData['results']) && 1 === count($searchResultData['results']));
        $resultEventData = $searchResultData['results'][0];
        $this->assertEquals($createdEvent->getId(), $resultEventData['id']);
    }

    public function testSearchAttenderTypeAny()
    {
        $event = $this->_getEvent();
        Calendar_Controller_Event::getInstance()->create($event);

        $filter = $this->_getEventFilterArray();
        $filter[] = [
            'field' => 'attender', 'operator' => 'in', 'value' => [
                [
                    'user_type' => 'any',
                    'role' => 'REQ',
                    'quantity' => 1,
                    'status' => 'CONFIRMED',
                    'user_id' => $this->_getTestUserContact()->getId(),
                    'id' => NULL,
                ]
            ]
        ];
        $searchResultData = $this->_uit->searchEvents($filter, array());
        self::assertEquals(1, $searchResultData['totalcount']);
    }

    /**
     * query filter should find description content
     */
    public function testSearchFulltextDescriptionInQuery()
    {
        $event = $this->_getEvent();
        $createdEvent = Calendar_Controller_Event::getInstance()->create($event);
        $oldValue = Tinebase_Config::getInstance()->{Tinebase_Config::FULLTEXT}
            ->{Tinebase_Config::FULLTEXT_QUERY_FILTER};

        try {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = Tinebase_TransactionManager::getInstance()
                ->startTransaction(Tinebase_Core::getDb());
            // activate fulltext query filter
            Tinebase_Config::getInstance()->{Tinebase_Config::FULLTEXT}
                ->{Tinebase_Config::FULLTEXT_QUERY_FILTER} = true;
            $filter = $this->_getEventFilterArray();
            $filter[] =
                ['field' => 'query', 'operator' => 'contains', 'value' => 'healthy'];
            $searchResultData = $this->_uit->searchEvents($filter, array());

            $this->assertEquals(1, $searchResultData['totalcount'], 'event not found. filter: '
                . print_r($searchResultData['filter'], true));
            $resultEventData = $searchResultData['results'][0];
            $this->assertEquals($createdEvent->getId(), $resultEventData['id']);
        } finally {
            Calendar_Controller_Event::getInstance()->delete([$createdEvent->getId()]);
            Tinebase_Config::getInstance()->{Tinebase_Config::FULLTEXT}
                ->{Tinebase_Config::FULLTEXT_QUERY_FILTER} = $oldValue;
        }
    }
}
