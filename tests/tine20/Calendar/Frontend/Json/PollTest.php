<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 20017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Test class for Calendar Json Frontend Poll related functions
 *
 * @package     Calendar
 */
class Calendar_Frontend_Json_PollTest extends Calendar_TestCase
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

        Calendar_Controller_Event::getInstance()->doContainerACLChecks(true);

        $this->_uit = new Calendar_Frontend_Json();
    }

    public function testCreatePoll()
    {
        $event = $this->_getEvent()->toArray();
        $event['attendee'] = $this->_getAttendee()->toArray();

        $event['poll_id'] = [
            'id' => Tinebase_Record_Abstract::generateUID(),
            'name' => 'test poll',
            'locked' => false,
            'password' => 'testpwd',
            'alternative_dates' => [
                [
                    'dtstart' => $event['dtstart'],
                    'dtend' => $event['dtend'],
                ], [
                    'dtstart' => Tinebase_DateTime::now(),
                    'dtend' => Tinebase_DateTime::now()->addHour(2),
                ]
            ],
        ];

        $persistentEvent = $this->_uit->saveEvent($event);

        $this->assertEquals($event['poll_id']['id'], $persistentEvent['poll_id']['id'], 'client id not preserved');
        $this->assertEquals(Calendar_Model_Event::STATUS_TENTATIVE, $persistentEvent['status'], 'poll events must be tentative');

        return $persistentEvent;
    }

    public function testCreatePollDuplicateContainer()
    {
        $containersBefore = Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser()->getId(),
            'Calendar_Model_Event',
            Tinebase_Core::getUser()->getId()
        );

        $this->testCreatePoll();

        $containersAfter = Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser()->getId(),
            'Calendar_Model_Event',
            Tinebase_Core::getUser()->getId()
        );
        self::assertEquals(count($containersBefore), count($containersAfter));
    }

    public function testCreatePollDuringUpdate()
    {
        $event = $this->_getEvent()->toArray();
        $persistentEvent = $this->_uit->saveEvent($event);
        $persistentEvent['poll_id'] = [
            'id' => Tinebase_Record_Abstract::generateUID(),
            'name' => 'test poll',
            'locked' => false,
            'password' => 'testpwd',
            'alternative_dates' => [
                [
                    'dtstart' => $event['dtstart'],
                    'dtend' => $event['dtend'],
                ], [
                    'dtstart' => Tinebase_DateTime::now(),
                    'dtend' => Tinebase_DateTime::now()->addHour(2),
                ]
            ],
        ];
        $updatedEvent = $this->_uit->saveEvent($persistentEvent);

        $this->assertEquals($persistentEvent['poll_id']['id'], $updatedEvent['poll_id']['id'], 'client id not preserved');
        $this->assertTrue($updatedEvent['status'] == Calendar_Model_Event::STATUS_TENTATIVE, 'poll events must be tentative');

    }

    public function testCreatePollByCopy()
    {
        $persistentEvent = $this->testCreatePoll();

        $copy = $persistentEvent;
        unset($copy['id']);
        unset($copy['uid']);
        $copy['dtstart'] = Tinebase_DateTime::now()->addDay(1);
        $copy['dtend'] = Tinebase_DateTime::now()->addDay(1)->addHour(2);
        $copy['summary'] = 'change on copy';

        // @TODO: copy via dlg with resolved alternatives - ui change?

        $persistentCopy = $this->_uit->saveEvent($copy);
        $alternativeEvents = $this->_uit->getPollEvents($persistentEvent['poll_id']['id']);

        $this->assertEquals($persistentCopy['poll_id']['id'], $persistentEvent['poll_id']['id'], 'copy not part of poll');
        $this->assertCount(3, $alternativeEvents['results'], 'copy not added');
        foreach($alternativeEvents['results'] as $alternativeEvent) {
            $this->assertEquals($copy['summary'], $alternativeEvent['summary']);
        }
    }

    public function testCreatePollForRecurringEvent()
    {
        $persistentEvent = $this->testCreatePoll();
        $persistentEvent['rrule'] = 'FREQ=DAILY;INTERVAL=1';

        $this->setExpectedException(Tinebase_Exception_SystemGeneric::class);
        $this->_uit->saveEvent($persistentEvent);
    }

    public function testGetPoll()
    {
        $persistentEvent = $this->testCreatePoll();
        $poll = $this->_uit->getPoll($persistentEvent['poll_id']['id']);

        $this->assertTrue(is_array($poll), 'poll not saved/resolved');
        $this->assertTrue(!isset($poll['alternative_dates']), 'dates are resolved on demand');

        return [$persistentEvent, $poll];
    }

    public function testResolvedPoll()
    {
        $persistentEvent = $this->testCreatePoll();
        $searchResult = $this->_uit->searchEvents([
            ['field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent['id']],
            ['field' => 'period', 'operator' => 'within', 'value' => [
                'from' => $persistentEvent['dtstart'],
                'until' => $persistentEvent['dtend'],
            ]]
        ], []);

        $this->assertTrue(is_array($searchResult['results'][0]['poll_id']));
    }

    public function testResolvePollOtherUser()
    {
        $persistentEvent = $this->testCreatePoll();

        $sclever = Tinebase_Helper::array_value('sclever', Zend_Registry::get('personas'));

        $origUser = Tinebase_Core::getUser();
        Tinebase_Core::set(Tinebase_Core::USER, $sclever);

        $searchResult = $this->_uit->searchEvents([
            ['field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent['id']],
            ['field' => 'period', 'operator' => 'within', 'value' => [
                'from' => $persistentEvent['dtstart'],
                'until' => $persistentEvent['dtend'],
            ]]
        ], []);
        Tinebase_Core::set(Tinebase_Core::USER, $origUser);

        $this->assertTrue(is_array($searchResult['results'][0]['poll_id']));
    }

    public function testGetPollAcl()
    {
        list($persistentEvent, $poll) = $this->testGetPoll();
        $container = Tinebase_Container::getInstance()->setGrants($poll['container_id']['id'],
            new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class), true, false);

        $this->setExpectedException(Tinebase_Exception_AccessDenied::class);
        $poll = $this->_uit->getPoll($persistentEvent['poll_id']['id']);
    }

    public function testGetPollEvents()
    {
        list($persistentEvent, $poll) = $this->testGetPoll();
        $alternativeEvents = $this->_uit->getPollEvents($persistentEvent['poll_id']['id']);

        $this->assertTrue(isset($alternativeEvents['results']));
        $this->assertEquals(2, count($alternativeEvents['results']), 'alternative/own event not present');
        $alternativeEvent = Tinebase_Helper::array_value(0, array_values(
            array_filter($alternativeEvents['results'], function ($event) use ($persistentEvent) {
                return $event['id'] != $persistentEvent['id'];
            })));
        $dtstart = Tinebase_DateTime::createFromFormat(Tinebase_Record_Abstract::ISO8601LONG,
            $alternativeEvent['dtstart']);
        Tinebase_DateTime::createFromFormat(Tinebase_Record_Abstract::ISO8601LONG, $alternativeEvent['dtend']);

        $this->assertTrue(Tinebase_DateTime::now()->getTimestamp() - $dtstart->getTimestamp() < 10,
            'alternative date wrong');
        $this->assertEquals($persistentEvent['summary'], $alternativeEvent['summary'],
            'summary got not set in alternative event');

        return [$persistentEvent, $poll, $alternativeEvents];
    }

    public function testUpdatePollRelatedData()
    {
        list($persistentEvent, , $alternativeEvents) = $this->testGetPollEvents();
        $alternativeEvent = Tinebase_Helper::array_value(0, array_values(
            array_filter($alternativeEvents['results'],
                function ($event) use ($persistentEvent) {
                    return $event['id'] != $persistentEvent['id'];
                })));
        $user = Tinebase_Core::getUser();
        $cField1 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => Calendar_Model_Event::class,
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        )));
        $cField2 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => Calendar_Model_Event::class,
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        )));

        $updateEvent = $persistentEvent;
        $updateEvent['notes'] = [[
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',
        ]];
        $updateEvent['relations'] = [[
            'related_id'        => $user->contact_id,
            'related_model'     => Addressbook_Model_Contact::class,
            'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'foo'
        ]];
        $updateEvent['tags'] = [['name' => 'testtag1']];
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'testAttachementData');
        $updateEvent['attachments'] = [[
                'name'      => 'testAttachementData.txt',
                'tempFile'  => ['id' => Tinebase_TempFile::getInstance()->createTempFile($path)->getId()]
        ]];
        $updateEvent['customfields'] = [
            $cField1->name => 'test field1'
        ];
        $updateEvent['alarms'] = [
            ['minutes_before' => 10],
        ];

        $updatedEvent = $this->_uit->saveEvent($updateEvent);
        static::assertEquals(1, count($updatedEvent['notes']));
        static::assertEquals(1, count($updatedEvent['relations']));
        static::assertEquals(1, count($updatedEvent['tags']));
        static::assertEquals(1, count($updatedEvent['attachments']));
        static::assertEquals(1, count($updatedEvent['customfields']));
        static::assertEquals(1, count($updatedEvent['alarms']));

        $changedAlternative = $this->_uit->getEvent($alternativeEvent['id']);
        static::assertEquals(1, count($changedAlternative['notes']));
        static::assertEquals(1, count($changedAlternative['relations']));
        static::assertEquals(1, count($changedAlternative['tags']));
        static::assertEquals(1, count($changedAlternative['attachments']));
        static::assertEquals(1, count($changedAlternative['customfields']));
        static::assertEquals(1, count($changedAlternative['alarms']));

        $changedAlternative['notes'][] = [
            'note_type_id'      => 1,
            'note'              => 'phpunit test note 2',
        ];
        $changedAlternative['relations'][] = [
            'related_id'        => $this->_personas['sclever']->contact_id,
            'related_model'     => Addressbook_Model_Contact::class,
            'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'bar'
        ];
        $changedAlternative['tags'][] = ['name' => 'testtag2'];
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'testAttachementData1');
        $changedAlternative['attachments'][] = [
            'name'      => 'testAttachementData1.txt',
            'tempFile'  => ['id' => Tinebase_TempFile::getInstance()->createTempFile($path)->getId()]
        ];
        $changedAlternative['customfields'][$cField2->name] = 'test field1';
        $changedAlternative['alarms'][] = ['minutes_before' => 15];

        $changedAlternative = $this->_uit->saveEvent($changedAlternative);
        static::assertEquals(2, count($changedAlternative['notes']));
        static::assertEquals(2, count($changedAlternative['relations']));
        static::assertEquals(2, count($changedAlternative['tags']));
        static::assertEquals(2, count($changedAlternative['attachments']));
        static::assertEquals(2, count($changedAlternative['customfields']));
        static::assertEquals(2, count($changedAlternative['alarms']));

        $changedPersistentEvent = $this->_uit->getEvent($updateEvent['id']);
        static::assertEquals(2, count($changedPersistentEvent['notes']));
        static::assertEquals(2, count($changedPersistentEvent['relations']));
        static::assertEquals(2, count($changedPersistentEvent['tags']));
        static::assertEquals(2, count($changedPersistentEvent['attachments']));
        static::assertEquals(2, count($changedPersistentEvent['customfields']));
        static::assertEquals(2, count($changedPersistentEvent['alarms']));

        array_pop($changedPersistentEvent['notes']);
        array_pop($changedPersistentEvent['relations']);
        array_pop($changedPersistentEvent['tags']);
        array_pop($changedPersistentEvent['attachments']);
        $changedPersistentEvent['customfields'][key($changedPersistentEvent['customfields'])] = null;
        array_pop($changedPersistentEvent['alarms']);

        $changedPersistentEvent = $this->_uit->saveEvent($changedPersistentEvent);
        static::assertEquals(1, count($changedPersistentEvent['notes']));
        static::assertEquals(1, count($changedPersistentEvent['relations']));
        static::assertEquals(1, count($changedPersistentEvent['tags']));
        static::assertEquals(1, count($changedPersistentEvent['attachments']));
        static::assertEquals(1, count($changedPersistentEvent['customfields']));
        static::assertEquals(1, count($changedPersistentEvent['alarms']));

        $changedAlternative = $this->_uit->getEvent($changedAlternative['id']);
        static::assertEquals(1, count($changedAlternative['notes']));
        static::assertEquals(1, count($changedAlternative['relations']));
        static::assertEquals(1, count($changedAlternative['tags']));
        static::assertEquals(1, count($changedAlternative['attachments']));
        static::assertEquals(1, count($changedAlternative['customfields']));
        static::assertEquals(1, count($changedAlternative['alarms']));

        $changedPersistentEvent['notes'] = [];
        $changedPersistentEvent['relations'] = [];
        $changedPersistentEvent['tags'] = [];
        $changedPersistentEvent['attachments'] = [];
        $changedPersistentEvent['customfields'][key($changedPersistentEvent['customfields'])] = null;
        $changedPersistentEvent['alarms'] = [];

        $changedPersistentEvent = $this->_uit->saveEvent($changedPersistentEvent);
        static::assertEquals(0, count($changedPersistentEvent['notes']));
        static::assertEquals(0, count($changedPersistentEvent['relations']));
        static::assertEquals(0, count($changedPersistentEvent['tags']));
        static::assertEquals(0, count($changedPersistentEvent['attachments']));
        static::assertFalse(isset($changedPersistentEvent['customfields']));
        static::assertEquals(0, count($changedPersistentEvent['alarms']));

        $changedAlternative = $this->_uit->getEvent($changedAlternative['id']);
        static::assertEquals(0, count($changedAlternative['notes']));
        static::assertEquals(0, count($changedAlternative['relations']));
        static::assertEquals(0, count($changedAlternative['tags']));
        static::assertEquals(0, count($changedAlternative['attachments']));
        static::assertFalse(isset($changedAlternative['customfields']));
        static::assertEquals(0, count($changedAlternative['alarms']));


        $changedPersistentEvent['summary'] = 'update';
        $changedPersistentEvent['poll_id']['alternative_dates'] = [
            $changedAlternative,
            [
                'dtstart' => Tinebase_DateTime::now()->addDay(2),
                'dtend' => Tinebase_DateTime::now()->addDay(2)->addHour(2),
            ]
        ];

        $updatedEvent = $this->_uit->saveEvent($changedPersistentEvent);
        $updatedPoll = $this->_uit->getPoll($updatedEvent['poll_id']['id']);
        $updatedAlternativeEvents = $this->_uit->getPollEvents($persistentEvent['poll_id']['id']);

        $this->assertTrue(!!$updatedEvent['is_deleted'], 'event did not got deleted');
        $this->assertEquals(2, count($updatedAlternativeEvents['results']), 'alternative events not present');
        foreach ($updatedAlternativeEvents['results'] as $updatedAlternativeEvent) {
            $this->assertEquals($changedPersistentEvent['summary'], $updatedAlternativeEvent['summary'],
                'summary did not get updated');
        }

        // NOTE: we need to distinguish between events which got deleted during an active poll
        //       and events which got deleted when the poll where closed. Therefore we have
        //       the deleted_events property in the poll where the (real) deleted events get referenced
        $this->assertTrue(in_array($updatedEvent['id'], $updatedPoll['deleted_events']),
            'explicit deleted event not referenced');
    }

    public function testUpdatePollUnresolvedAlternatives()
    {
        list($persistentEvent, $poll) = $this->testGetPoll();
        $persistentEvent['summary'] = 'update without alternatives';

        $updatedEvent = $this->_uit->saveEvent($persistentEvent);
        $updatedAlternativeEvents = $this->_uit->getPollEvents($persistentEvent['poll_id']['id']);

        $this->assertEquals(2, count($updatedAlternativeEvents['results']), 'alternative events not present');
        foreach ($updatedAlternativeEvents['results'] as $updatedAlternativeEvent) {
            $this->assertEquals($persistentEvent['summary'], $updatedAlternativeEvent['summary'],
                'summary did not get updated');
        }
    }

    public function testUpdatePollClosed()
    {
        list ($eventWithClosedPoll, $alternativeEvents) = $this->testSetDefiniteEvent();
        $eventWithClosedPoll['poll_id']['alternative_dates'] = $alternativeEvents;

        $eventWithClosedPoll['summary'] = 'update after definite';

        $updatedEvent = $this->_uit->save($eventWithClosedPoll);
        $updatedAlternativeEvents = $this->_uit->getPollEvents($updatedEvent['poll_id']['id']);

        foreach ($updatedAlternativeEvents['results'] as $updatedAlternativeEvent) {
            $this->assertNotEquals($eventWithClosedPoll['summary'], $updatedAlternativeEvent['summary'],
                'summary must not be updated');
        }
    }

    public function testDeleteAlternativeDirectly()
    {
        list($persistentEvent, $poll, $alternativeEvents) = $this->testGetPollEvents();
        $alternativeEvent = Tinebase_Helper::array_value(0, array_values(
            array_filter($alternativeEvents['results'],
                function ($event) use ($persistentEvent) {
                    return $event['id'] != $persistentEvent['id'];
                })));

        $this->_uit->deleteEvents([$alternativeEvent['id']]);
        $updatedEvent = $this->_uit->getEvent($persistentEvent['id']);
        $updatedPoll = $this->_uit->getPoll($updatedEvent['poll_id']['id']);

        // NOTE: we need to distinguish between events which got deleted during an active poll
        //       and events which got deleted when the poll where closed. Therefore we have
        //       the deleted_events property in the poll where the (real) deleted events get referenced
        $this->assertTrue(in_array($alternativeEvent['id'], $updatedPoll['deleted_events']),
            'explicit deleted event not referenced');
    }

    public function testSetDefiniteEvent()
    {
        list($persistentEvent, $poll, $alternativeEvents) = $this->testGetPollEvents();
        $alternativeEvent = Tinebase_Helper::array_value(0, array_values(
            array_filter($alternativeEvents['results'],
                function ($event) use ($persistentEvent) {
                    return $event['id'] != $persistentEvent['id'];
                })));

        $this->_uit->setDefinitePollEvent($alternativeEvent);

        $updatedEvent = $this->_uit->getEvent($alternativeEvent['id']);
        $updatedPoll = $this->_uit->getPoll($persistentEvent['poll_id']['id']);

        $this->assertTrue(!!$updatedEvent['poll_id']['closed'], 'poll should be closed directly');
        $this->assertEquals(Calendar_Model_Event::STATUS_CONFIRMED, $updatedEvent['status'], 'poll events must be tentative');

        $this->assertTrue(!!$updatedPoll['closed'], 'poll not closed');

        $alternativeEvents = $this->_uit->getPollEvents($updatedPoll['id']);
        $this->assertEquals(2, count($alternativeEvents['results']), 'not all alternative events are present');
        $this->assertEmpty($updatedPoll['deleted_events']);

        $rejectedAlternative = Tinebase_Helper::array_value(0, array_values(
            array_filter($alternativeEvents['results'], function ($event) use ($persistentEvent) {
                return $event['id'] == $persistentEvent['id'];
            })));
        $this->assertTrue(!!$rejectedAlternative['is_deleted'], 'alternative event not deleted');

        return [$updatedEvent, $alternativeEvents];
    }

    public function testNotifications()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
            $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }

        Calendar_Config::getInstance()->set(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS, false);
        Calendar_Controller_Event::getInstance()->sendNotifications(true);
        self::flushMailer();
        $this->testCreatePoll();
        $this->assertCount(1, self::getMessages());

        self::flushMailer();
        Calendar_Config::getInstance()->set(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS, true);
        $this->testCreatePoll();
        $this->assertCount(0, self::getMessages());

        self::flushMailer();
        $this->testUpdatePollRelatedData();
        $this->assertCount(0, self::getMessages());
    }

    /**
     * the public poll api uses the event / poll Controller without a user set.
     *
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testAnonymousUsage()
    {
        $data = $this->testCreatePoll();

        $pollController = Calendar_Controller_Poll::getInstance();
        $oldUser = Tinebase_Core::getUser();
        $oldContainerACLChecks = $pollController->doContainerACLChecks();
        Calendar_Controller_Event::unsetInstance();
        $calendarController = Calendar_Controller_Event::getInstance();
        $oldCalContainerACLChecks = $calendarController->doContainerACLChecks();

        try {
            Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()
                ->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS));
            $pollController->doContainerACLChecks(false);
            $calendarController->doContainerACLChecks(false);

            $poll = $pollController->get($data['poll_id']['id']);
            static::assertEquals($poll->getId(), $data['poll_id']['id']);

            $events = $pollController->getPollEvents($data['poll_id']['id']);
            static::assertGreaterThan(0, $events->count());
            /** @var Calendar_Model_Event $event */
            foreach ($events as $event) {
                static::assertEquals($data['poll_id']['id'], $event->poll_id);
            }

            $event = Calendar_Controller_Event::getInstance()->get($events->getFirstRecord()->getId());
            static::assertEquals($events->getFirstRecord()->getId(), $event->getId());
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            $pollController->doContainerACLChecks($oldContainerACLChecks);
            $calendarController->doContainerACLChecks($oldCalContainerACLChecks);
            Calendar_Controller_Event::unsetInstance();
            Calendar_Controller_MSEventFacade::unsetInstance();
        }
    }
}