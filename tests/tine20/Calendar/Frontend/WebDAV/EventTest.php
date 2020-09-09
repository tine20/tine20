<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Calendar_Frontend_WebDAV_Event
 */
class Calendar_Frontend_WebDAV_EventTest extends Calendar_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // account email addresses are empty with AD backend
            $this->markTestSkipped('skipped for ad backend');
        }

        parent::setUp();
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        )));
        $this->objects['sharedContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        )));
        
        $prefs = Tinebase_Core::getPreference('Calendar');
        $prefs->setValue(Calendar_Preference::DEFAULTCALENDAR, $this->objects['initialContainer']->getId());

        // rw cal agent
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        $_SERVER['REQUEST_URI'] = 'lars';
    }

    public function tearDown()
    {
        parent::tearDown();
        Tinebase_Core::getPreference('Calendar')->resetAppPrefsCache();
        Tinebase_Core::set(Tinebase_Core::PREFERENCES, null);
    }

    /**
     * test create event with internal organizer
     * 
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventWithInternalOrganizer()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $record = $event->getRecord();

        $this->assertEquals('New Event', $record->summary);
        
        return $event;
    }

    /**
     * test create event for different users from same file (same id) in their personal folder (no grants for the other user)
     *
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventForDifferentUsers()
    {
      
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
       

        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/gotomeeting.ics');
        
        //Import event for sclever
        $sclever = $this->_personas['sclever'];
        $personalContainer = $this->_getPersonalContainer(Calendar_Model_Event::class, $sclever->accountId);
        Tinebase_Core::set(Tinebase_Core::USER, $sclever);
        $event = Calendar_Frontend_WebDAV_Event::create($personalContainer, "gotomeeting.ics", $vcalendar);

        $record = $event->getRecord();

        $this->assertEquals('Meeting', $record->summary);

        //Import same file for pwulf
        $pwulf = $this->_personas['pwulf'];
        $personalContainer = $this->_getPersonalContainer(Calendar_Model_Event::class, $pwulf->accountId);
        Tinebase_Core::set(Tinebase_Core::USER, $pwulf);
        $event = Calendar_Frontend_WebDAV_Event::create($personalContainer, "gotomeeting.ics", $vcalendar);

        $record = $event->getRecord();

        $this->assertEquals('Meeting', $record->summary);
    }
    
    /**
     * test create event with internal organizer
     * 
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventWithRRule()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_yearly.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $record = $event->getRecord();
        
        $this->assertEquals('New Event', $record->summary);
        $this->assertEquals($record->rrule['bymonthday'], 25);
        
        return $event;
    }
    
    /**
     * test create event with external organizer
     * 
     * @param Tinebase_Model_Container  $targetContainer
     * @param string $id
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventWithExternalOrganizer($targetContainer = null, $id = null, $extendedChecks = true)
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = file_get_contents(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        if ($id === null) {
            $id = Tinebase_Record_Abstract::generateUID();
        }
        if ($targetContainer === null) {
            $targetContainer = $this->objects['initialContainer'];
        }
        $event = Calendar_Frontend_WebDAV_Event::create($targetContainer, "$id.ics", $vcalendar);
        
        $record = $event->getRecord();
        $container = Tinebase_Container::getInstance()->getContainerById($record->container_id);
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($record->attendee);
        
        $this->assertEquals('New Event', $record->summary);
        $this->assertEquals('l.kneschke@metaways.de', $container->name, 'event no in invitation calendar');
        if (!$extendedChecks) {
            return $event;
        }
        $this->assertTrue(!!$ownAttendee, 'own attendee missing');
        $this->assertEquals(1, $record->seq, 'tine20 seq starts with 1');
        $this->assertEquals(0, $record->external_seq, 'external seq not set -> 0');
        
        return $event;
    }
    
    /**
     * create an event which already exists on the server
     * - this happen when the client moves an event to another calendar -> see testMove*
     * - or when an client processes an iMIP which is not already loaded by CalDAV
     */
    public function testCreateEventWhichExistsAlready()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $oldUserAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

        $existingEvent = $this->testCreateEventWithInternalOrganizer();
        $existingRecord = $existingEvent->getRecord();
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], $existingEvent->getRecord()->getId() . '.ics', $vcalendar);
        
        if (isset($oldUserAgent)) {
            $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent;
        }
        
        $record = $event->getRecord();
        
        $this->assertEquals($existingRecord->getId(), $record->getId(), 'event got duplicated');
    }

    public function testUpdateOldEvent()
    {
        // TODO should not depend on SMTP config ...
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
        if (! $smtpConfig || ! isset($smtpConfig->from)
        ) {
            $this->markTestSkipped('SMTP notification backend not configured');
        }

        Calendar_Controller_Event::getInstance()->sendNotifications(true);

        self::flushMailer();
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $oldUserAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

        $vcalendar = self::getVCalendar(__DIR__ . '/../files/invitation_request_external_2internals.ics');
        $vcalendar = preg_replace('/20181020/', Tinebase_DateTime::now()->addDay(1)->format('Ymd'), $vcalendar);
        $id = 'e679217e8c3f89e8ca55779f70f9940e6689ed98';
        $targetContainer = $this->objects['initialContainer'];
        $event = Calendar_Frontend_WebDAV_Event::create($targetContainer, "$id.ics", $vcalendar);

        $record = $event->getRecord();
        $container = Tinebase_Container::getInstance()->getContainerById($record->container_id);
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($record->attendee);
        $resolvedAttendees = Calendar_Model_Attender::getResolvedAttendees($record->attendee, true);

        static::assertSame('Hi12', $record->summary);
        //static::assertSame($targetContainer->name, $container->name, 'event not in invitation calendar');
        static::assertTrue(!! $ownAttendee, 'own attendee missing');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status);
        static::assertEquals('1', $record->seq, 'tine20 seq starts with 1');
        static::assertEquals('1', $record->external_seq, 'external seq: 1');
        static::assertSame('1500', $record->dtstart->setTimezone($record->originator_tz)->format('Hi'));
        static::assertCount(3, $resolvedAttendees, '3 attendees expected');
        foreach ($resolvedAttendees as $attendee) {
            static::assertTrue(in_array($attendee->user_id->account_id, [Tinebase_Core::getUser()->getId(),
                    $this->_personas['sclever']->getId(), null]), 'unexpected attendee');
        }
        static::assertTrue($record->hasExternalOrganizer(), 'should have external organizer');
        static::assertEquals(1, count(self::getMessages()));
        static::assertContains('accepted event "Hi12"', self::getMessages()[0]->getSubject());


        self::flushMailer();
        $vcalendar = self::getVCalendar(__DIR__ . '/../files/invitation_accepted_by_internal1.ics');
        $vcalendar = preg_replace('/20181020/', Tinebase_DateTime::now()->addDay(1)->format('Ymd'), $vcalendar);
        $event = Calendar_Frontend_WebDAV_Event::create($targetContainer, "$id.ics", $vcalendar);

        $updated = $event->getRecord();
        $container = Tinebase_Container::getInstance()->getContainerById($updated->container_id);
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($updated->attendee);
        $resolvedAttendees = Calendar_Model_Attender::getResolvedAttendees($record->attendee, true);

        static::assertSame($updated->getId(), $record->getId());
        //static::assertSame($targetContainer->name, $container->name, 'event not in invitation calendar');
        static::assertTrue(!! $ownAttendee, 'own attendee missing');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status);
        static::assertEquals('2', $updated->seq, 'tine20 seq should be 2');
        static::assertEquals('3', $updated->external_seq, 'external seq: 3');
        static::assertSame('1500', $record->dtstart->setTimezone($record->originator_tz)->format('Hi'));
        static::assertCount(3, $resolvedAttendees, '3 attendees expected');
        foreach ($resolvedAttendees as $attendee) {
            static::assertTrue(in_array($attendee->user_id->account_id, [Tinebase_Core::getUser()->getId(),
                $this->_personas['sclever']->getId(), null]), 'unexpected attendee');
        }
        static::assertEquals(1, count(self::getMessages()));
        static::assertContains('accepted event "Hi12"', self::getMessages()[0]->getSubject());


        self::flushMailer();
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $vcalendar = self::getVCalendar(__DIR__ . '/../files/old_invitation_accepted_by_internal2.ics');
        try {
            $cId = Calendar_Controller_Event::getDefaultDisplayContainerId($this->_personas['sclever']->getId());
            Calendar_Frontend_WebDAV_Event::create(Tinebase_Container::getInstance()->get($cId), "$id.ics", $vcalendar);
            static::fail('external seq out of order, we should not reach this point');
        } catch(Sabre\DAV\Exception\PreconditionFailed $sdepf) {
            static::assertSame('updating existing event with outdated external seq', $sdepf->getMessage());
        }
        static::assertEquals(0, count(self::getMessages()));

        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        if (isset($oldUserAgent)) {
            $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent;
        }
    }

    /**
     * create an event which already exists on the server, but user can no longer access it
     *
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventWhichExistsAlreadyInAnotherContainer()
    {
        $existingEvent = $this->testCreateEventWithExternalOrganizer()->getRecord();
        
        // save event as sclever
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);

        $existingEventForSclever = $this->testCreateEventWithExternalOrganizer($this->_getTestContainer('Calendar',
            Calendar_Model_Event::class), $existingEvent->getId(), false)->getRecord();

        $this->_testCalendars[] = Tinebase_Container::getInstance()->getContainerById($existingEventForSclever->container_id);
        
        $this->assertEquals($existingEvent->uid, $existingEventForSclever->uid);
    }
    
    /**
     * folderChanges are implemented as DELETE/PUT actions in most CalDAV
     * clients. Unfortunately clients send both requests in parallel. This
     * creates raise conditions when DELETE is faster (e.g. due to transport
     * issues) than the PUT.
     */
    public function testCreateEventWhichExistsAlreadyDeleted()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $oldUserAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

        $existingEvent = $this->testCreateEventWithInternalOrganizer();

        $existingRecord = $existingEvent->getRecord();
        Calendar_Controller_Event::getInstance()->delete($existingRecord->getId());

        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');

        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], $existingRecord->getId() . '.ics', $vcalendar);

        if (isset($oldUserAgent)) {
            $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent;
        }

        $record = $event->getRecord();

        $this->assertEquals($existingRecord->getId(), $record->getId(), 'event got duplicated');
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
    
        $vcalendarStream = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_daily.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendarStream);
        $this->_checkExdate($event, ['2011-10-05 08:00:00', '2011-10-06 08:00:00', '2011-10-07 08:00:00',
            '2011-10-08 08:00:00']);
        
        // check rrule_until normalisation
        $record = $event->getRecord();
        $this->assertEquals('2011-10-30 06:00:00', $record->rrule_until->toString(), 'rrule_until not normalised');
        $this->assertEquals('2011-10-30 06:00:00', $record->rrule->until->toString(), 'rrule->until not normalised');
        
        return $event;
    }
    
    /**
     * check event exdate
     * 
     * @param Calendar_Frontend_WebDAV_Event $event
     */
    protected function _checkExdate(Calendar_Frontend_WebDAV_Event $event, $dates = null)
    {
        $record = $event->getRecord();
        $exdate = $record->exdate[0];
        $this->assertEquals('New Event', $record->summary);

        $organizer = (is_object($exdate->organizer)) ? $exdate->organizer->getId() : $exdate->organizer;
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $organizer,
            'organizer mismatch, expected :'
                . print_r(Addressbook_Controller_Contact::getInstance()->get(
                    Tinebase_Core::getUser()->contact_id
                )->toArray(), TRUE) .
            'got: '
                . print_r(Addressbook_Controller_Contact::getInstance()->get($exdate->organizer)->toArray(), TRUE));
        $this->assertTrue(in_array(Tinebase_Core::getUser()->contact_id, $exdate->attendee->user_id),
            'user contact id not found in exdate attendee: ' . print_r($exdate->attendee->toArray(), TRUE));

        foreach ($exdate->attendee as $attender) {
            $this->assertTrue(! empty($attender->displaycontainer_id),
                'displaycontainer_id not set for attender: ' . print_r($attender->toArray(), TRUE));
        }
        if (is_array($dates)) {
            foreach ($dates as $date) {
                static::assertNotNull($record->exdate->find(function($evt) use ($date) {
                    return $date === substr($evt->recurid, -19);
                }, null), 'did not find exdate: ' . $date);
            }
            static::assertCount(count($dates), $record->exdate, 'number of exdates does not match');
        }
    }
    
    /**
     * testCreateRepeatingEventAndPutExdate
     * 
     * @see 0008172: displaycontainer_id not set when recur exception is created
     */
    public function testCreateRepeatingEventAndPutExdate()
    {
        $cfCfg = $this->_createCustomField(Tinebase_Record_Abstract::generateUID(), Calendar_Model_Event::class);

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $oldUserAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendarStream = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_weekly.ics');
        $id = '353de608-4b50-41e6-9f6c-35889584fe8d';
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendarStream);
        $existingEvent = $event->getRecord();
        $existingEvent->xprops('customfields')[$cfCfg->name] = __METHOD__;
        $existingEvent = Calendar_Controller_Event::getInstance()->update($existingEvent);
        static::assertTrue(isset($existingEvent->customfields[$cfCfg->name]), 'saving customfield did not work');
        static::assertEquals(__METHOD__, $existingEvent->customfields[$cfCfg->name]);
        
        // put exception
        $vcalendarStreamException = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_weekly_exception.ics');
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $existingEvent);
        $event->put($vcalendarStreamException);
        
        $this->_checkExdate($event);

        // need to refetch it to resolve customfields, relations etc.
        $recExEvent = Calendar_Controller_Event::getInstance()->get($event->getRecord()->exdate[0]->getId());
        static::assertTrue(isset($recExEvent->customfields[$cfCfg->name]),
            'recur exception should have customfield');
        static::assertEquals(__METHOD__, $recExEvent->customfields[$cfCfg->name],
            'recur exception should have customfield');

        return $event;
    }
    
    /**
     * #7388: Wrong container id's in calendar (maybe CalDAV related)
     */
    public function testCreateEventInviteInternalAttendee()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/event_with_persona_attendee.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $event = Calendar_Controller_Event::getInstance()->get($id);
        $pwulf = $event->attendee->filter('user_id', $this->_getPersonasContacts('pwulf')->getId())->getFirstRecord();
        
        $this->assertTrue($pwulf !== null, 'could not find pwulf in attendee: ' . print_r($event->attendee->toArray(), true));
        $this->assertEquals($this->_getPersonasDefaultCals('pwulf')->getId(), $pwulf->displaycontainer_id, 'event not in pwulfs personal calendar');
    }

    /**
     * _testEventMissingAttendee helper
     * 
     * @param Tinebase_Model_Container $container
     * @param Calendar_Model_Attender $assertionAttendee
     * @param boolean $assertMissing
     */
    public function _testEventMissingAttendee($container, $assertionAttendee, $assertMissing = false)
    {
        $not = $assertMissing ? '' : 'not ';
        $assertFn = $assertMissing ? 'assertFalse' : 'assertTrue';
        
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/apple_caldendar_repeating.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();

        $event = Calendar_Frontend_WebDAV_Event::create($container, "$id.ics", $vcalendar);

        $baseAttendee = Calendar_Model_Attender::getAttendee($event->getRecord()->attendee, $assertionAttendee);
        $exceptionAttendee = Calendar_Model_Attender::getAttendee($event->getRecord()->exdate->getFirstRecord()->attendee, $assertionAttendee);
        $this->$assertFn(!! $baseAttendee, "attendee has {$not}been added: " . print_r($event->getRecord()->attendee->toArray(), true));
        $this->$assertFn(!! $exceptionAttendee, "attendee has {$not}been added to exdate");
        if (! $assertMissing) {
            $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED,
                $baseAttendee->status, 'attendee status wrong');
            $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED,
                $exceptionAttendee->status, 'attendee status wrong for exdate ');
        }
        
        // Simulate OSX which updates w.o. fetching first
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/apple_caldendar_repeating.ics', 'r');
        
        $event = new Calendar_Frontend_WebDAV_Event($container, $event->getRecord()->getId());
        $event->put($vcalendarStream);

        $baseAttendee = Calendar_Model_Attender::getAttendee($event->getRecord()->attendee, $assertionAttendee);
        $exceptionAttendee = Calendar_Model_Attender::getAttendee($event->getRecord()->exdate->getFirstRecord()->attendee, $assertionAttendee);
        $this->$assertFn(!! $baseAttendee, "attendee has {$not}been preserved: " . print_r($event->getRecord()->attendee->toArray(), true));
        $this->$assertFn(!! $exceptionAttendee, "attendee has {$not}been preserved in exdate");
        if (! $assertMissing) {
            $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED,
                $baseAttendee->status, 'attendee status not preserved');
            $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED,
                $exceptionAttendee->status, 'attendee not preserved for exdate');
        }

        // create new exception from client w.o. fetching first
        Calendar_Controller_Event::getInstance()->purgeRecords(TRUE);
        Calendar_Controller_Event::getInstance()->delete($event->getRecord()->exdate->getFirstRecord());
        Calendar_Controller_Event::getInstance()->purgeRecords(FALSE);
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/apple_caldendar_repeating.ics', 'r');
        
        $event = new Calendar_Frontend_WebDAV_Event($container, $event->getRecord()->getId());
        $event->put($vcalendarStream);
        
        $this->$assertFn(!! Calendar_Model_Attender::getAttendee($event->getRecord()->attendee, $assertionAttendee),
                "attendee has {$not}been created in exdate");
        $this->$assertFn(!! Calendar_Model_Attender::getAttendee($event->getRecord()->exdate->getFirstRecord()->attendee, $assertionAttendee),
                "attendee has {$not}been created in exdate");
    }
    
    /**
     * testEventMissingAttendeeOwnCalendar
     *
     * validate test user is added for event in own container
     */
    public function testEventMissingAttendeeOwnCalendar()
    {
        $this->_testEventMissingAttendee($this->objects['initialContainer'], new Calendar_Model_Attender(array(
            'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'      => Tinebase_Core::getUser()->contact_id,
        )));
    }
    /**
     * testEventMissingAttendeeOtherCalendar
     *
     * validate calendar owner is added for event in other user container
     */
    public function testEventMissingAttendeeOtherCalendar()
    {
        $egt = new Calendar_Controller_EventGrantsTests();
        $egt->setup();
        
        $scleverCalendar = $this->_getPersonasDefaultCals('sclever');
        $sclever = new Calendar_Model_Attender(array(
            'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'      => $this->_getPersonasContacts('sclever')->getId(),
        ));
        
        $this->_testEventMissingAttendee($scleverCalendar, $sclever);
    }
    
    /**
     * testEventMissingAttendeeSharedCalendar
     * 
     * validate no attendee is implicitly added for shared calendars
     */
    public function testEventMissingAttendeeSharedCalendar()
    {
        $this->markTestSkipped('not yet active');
        $this->_testEventMissingAttendee($this->objects['sharedContainer'], new Calendar_Model_Attender(array(
                'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'      => Tinebase_Core::getUser()->contact_id,
        )), true);
    }
    
    public function testFilterRepeatingException()
    {
        // create event in shared calendar test user is attendee
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        $vcalendarStream = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_daily.ics');
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendarStream);
        
        // decline exception -> no implicit fallout as exception is still in initialContainer via displaycal
        $exception = $event->getRecord()->exdate->filter('is_deleted', 0)->getFirstRecord();
        self::assertGreaterThan(0, count($exception->attendee));
        $exception->attendee[0]->status = Calendar_Model_Attender::STATUS_DECLINED;
        $updatedException = Calendar_Controller_Event::getInstance()->update($exception);
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $event->getRecord()->getId());
        $vcalendar = stream_get_contents($event->get());
        $this->assertContains('DTSTART;TZID=Europe/Berlin:20111008T130000', $vcalendar, 'exception must not be implicitly deleted');
        
        // delete attendee from exception -> implicit fallout exception is not longer in displaycal
        $exception = $event->getRecord()->exdate->filter('is_deleted', 0)->getFirstRecord();
        $exception->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        $updatedException = Calendar_Controller_Event::getInstance()->update($exception);
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $event->getRecord()->getId());
        $vcalendar = stream_get_contents($event->get());
        $this->assertContains('EXDATE:20111008T080000Z', $vcalendar, 'exception must be implicitly deleted from event ' . print_r($event->getRecord()->toArray(), TRUE));
        
        // save back event -> implicit delete must not be deleted on server
        $event->put($vcalendar);
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], $event->getRecord()->getId());
        $vcalendar = stream_get_contents($event->get());
        $this->assertContains('DTSTART;TZID=Europe/Berlin:20111008T130000', $vcalendar, 'exception must not be implicitly deleted after update');
    }
    
    /**
     * test get vcard
     * @depends testCreateEventWithInternalOrganizer
     */
    public function testGetVCalendar()
    {
        $event = $this->testCreateEventWithInternalOrganizer();
        
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
        $this->assertContains('EXDATE:20111005T080000Z', $vcalendar);
        $this->assertContains('EXDATE:20111006T080000Z', $vcalendar);
        $this->assertContains('EXDATE:20111007T080000Z', $vcalendar);
        $this->assertContains('RECURRENCE-ID;TZID=Europe/Berlin:20111008T100000', $vcalendar);
        $this->assertContains('TRIGGER;VALUE=DURATION:-PT1H30M', $vcalendar); // base alarm
        $this->assertContains('TRIGGER;VALUE=DURATION:-PT1H15M', $vcalendar); // exception alarm
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventFromThunderbird()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('New Event', $record->summary);
        $this->assertTrue(! empty($record->attendee[0]["status_authkey"]));
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventWithExternalOrganizer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $event = $this->testCreateEventWithExternalOrganizer();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('New Event', $record->summary);
        $this->assertEquals(2, $record->seq, 'tine20 seq should have increased');
        $this->assertEquals(0, $record->external_seq, 'external seq must not have increased');
    }

    /**
     * test updating existing event
     */
    public function testPutEventFromMacOsX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        $event = $this->testCreateEventWithInternalOrganizer();

        // assert get contains X-CALENDARSERVER-ACCESS
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->getRecord()->class);
        $this->assertContains('X-CALENDARSERVER-ACCESS:CONFIDENTIAL', stream_get_contents($event->get()));

        // put PUBLIC
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        $vcalendar = str_replace("CLASS:PRIVATE", "X-CALENDARSERVER-ACCESS:PUBLIC", $vcalendar);

        $event->put($vcalendar);

        $record = $event->getRecord();

        // assert put evaluates X-CALENDARSERVER-ACCESS
        $this->assertEquals(Calendar_Model_Event::CLASS_PUBLIC, $record->class);
        $this->assertContains('X-CALENDARSERVER-ACCESS:PUBLIC', stream_get_contents($event->get()));

        $this->assertEquals('New Event', $record->summary);
    }

    public function testPutEventFromMacOsXSierraThisAndFuture()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        // create series with exceptions
        $id = '025F3A29-25F7-4BAD-8F8D-BD3EBD2BDB21';
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/apple_calendar_10.12_THISANDFUTURE.ics');
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);


        // NOTE: THISANDFUTURE update (comes with two requests)

        // first request is the future part of the series after the split (keeps old uid)
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/apple_calendar_10.12_THISANDFUTURE-update-THISANDFUTURE.ics', 'r');
        $event->put($vcalendarStream);
        $futurerecord = $event->getRecord();
        $futurerecord->exdate->sort('dtstart');

        // second request is the past part of the series with a new uid
        $id = 'FB8150C1-E7C6-4758-9E92-BB8BCA48F808';
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/apple_calendar_10.12_THISANDFUTURE-past.ics');
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        $pastrecord = $event->getRecord();

        $this->assertCount(2, $futurerecord->exdate);
        $this->assertEquals('025F3A29-25F7-4BAD-8F8D-BD3EBD2BDB21-2017-10-21 00:15:00', $futurerecord->exdate[0]->recurid);
        $this->assertEquals('025F3A29-25F7-4BAD-8F8D-BD3EBD2BDB21-2017-11-17 01:15:00', $futurerecord->exdate[1]->recurid);

        $this->assertCount(1, $pastrecord->exdate);
        $this->assertEquals('FB8150C1-E7C6-4758-9E92-BB8BCA48F808-2017-10-16 23:15:00', $pastrecord->exdate[0]->recurid);
    }

    public function testPutEventFromMacOsXSierraThisAndFutureOverDST()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        // create series with exceptions
        $id = 'F8224438-A3ED-42DB-90BB-ACF20C6449F5';
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/apple_calendar_10.12_TEST-THISANDFUTURE-OVER-DST.ics');
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);


        // first request is the future part of the series after the split (keeps old uid)
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/apple_calendar_10.12_TEST-THISANDFUTURE-OVER-DST-update-THISANDFUTURE-after-DST-bound.ics', 'r');
        $event->put($vcalendarStream);
        $futurerecord = $event->getRecord();
        $futurerecord->exdate->sort('dtstart');

        $this->assertCount(1, $futurerecord->exdate);
        $this->assertEquals('F8224438-A3ED-42DB-90BB-ACF20C6449F5-2017-11-04 19:00:00', $futurerecord->exdate[0]->recurid);

    }


    /**
     * test updating existing event when attendee or organizer email changed in the meantime
     */
    public function testPutEventWhenEmailChanged()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        $event = $this->testCreateEventWithExternalOrganizer();

        // change email address of organizer / attendee
        $contact = $event->getRecord()->organizer;
        $contact->email = 'changed@mail.domain';
        sleep(1);
        Addressbook_Controller_Contact::getInstance()->update($contact);
        Calendar_Model_Attender::clearCache();

        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        $vcalendar = str_replace("lars@kneschke.de", "l.kneschke@metaways.de", $vcalendar);
        $event->put($vcalendar);

        /** @var Calendar_Model_Event $record */
        $record = $event->getRecord();

        $this->assertEquals($contact->getId(), $record->organizer->getId(), 'organizer must not change');
        $this->assertCount(1, $record->attendee->filter('user_id', $contact->getId()), 'attendee must not change');
    }

    public function testPutEventWithRecurExceptions()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_exdate_mozlastack.ics');
        $id = Tinebase_Record_Abstract::generateUID();
        $targetContainer = $this->objects['initialContainer'];

        $event = Calendar_Frontend_WebDAV_Event::create($targetContainer, "$id.ics", $vcalendar);
        $record = $event->getRecord();

        $eventToUpdate = new Calendar_Frontend_WebDAV_Event($targetContainer, $id);
        $vcalendarToUpdate = stream_get_contents($eventToUpdate->get());
        $vcalendarToUpdate = str_replace("abendessen später am heiligabend", "vesper später am heiligabend", $vcalendarToUpdate);
//        echo $vcalendarToUpdate;
        $eventToUpdate->put($vcalendarToUpdate);

        $updatedRecord = Calendar_Controller_MSEventFacade::getInstance()->get($id);
        $xmas = $updatedRecord->exdate->filter('summary', '/^vesper.*/', true)->getFirstRecord();
        $this->assertNotNull($xmas, print_r($updatedRecord->toArray(), true));
    }

    public function testPutEventWithRecurFirstInstanceException()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

        $vcalendar = file_get_contents(dirname(__FILE__) . '/../../Import/files/lightning_repeating_group_first_instance_exception.ics');
        $vcalendar = str_replace("d981a72be8f21808b588daa0e8644046c634250f", Tinebase_Group::getInstance()->getDefaultGroup()->list_id, $vcalendar);

        $id = Tinebase_Record_Abstract::generateUID();
        $targetContainer = $this->objects['initialContainer'];

        $event = Calendar_Frontend_WebDAV_Event::create($targetContainer, "$id.ics", $vcalendar);

        // sometimes base_event_id is set for base_events;
        $record = $event->getRecord();
        $record->base_event_id = $record->getId();
        Calendar_Controller_Event::getInstance()->update($record);

        $eventToUpdate = new Calendar_Frontend_WebDAV_Event($targetContainer, $id);
        $vcalendarToUpdate = stream_get_contents($eventToUpdate->get());
        $eventToUpdate->put($vcalendarToUpdate);
    }

    public function testPutEventWithRecurExceptionsExternalOrganizer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_exdate_mozlastack.ics');
        $vcalendar = str_replace("sclever", "external", $vcalendar);
//        echo $vcalendar;

        $id = Tinebase_Record_Abstract::generateUID();
        $targetContainer = $this->objects['initialContainer'];

        $event = Calendar_Frontend_WebDAV_Event::create($targetContainer, "$id.ics", $vcalendar);
        $record = $event->getRecord();

        $loadedEvent = new Calendar_Frontend_WebDAV_Event($targetContainer, $id);
        $loadedRecord = $loadedEvent->getRecord();
//        echo stream_get_contents($loadedEvent->get());

        $xmas = $loadedRecord->exdate->filter('summary', '/.*heiligabend$/', true)->getFirstRecord();
        $this->assertNotNull($xmas, print_r($loadedRecord->toArray(), true));
    }

    /**
     * test deleting attachment from existing event
     */
    public function testDeleteAttachment()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $event = $this->createEventWithAttachment(2);
        
        // remove agenda.html
        $clone = clone $event;
        $attachments = $clone->getRecord()->attachments;
        $firstAttachment = $attachments->filter('name', 'agenda.html')->getFirstRecord();
        self::assertFalse($firstAttachment === null, 'could not find attachment');
        $attachments->removeRecord($firstAttachment);
        $event->put($clone->get());
        
        // assert agenda2.html exists
        $record = $event->getRecord();
        $this->assertEquals(1, $record->attachments->count());
        $this->assertEquals('agenda2.html', $record->attachments->getFirstRecord()->name);
    }

    /**
     * test create event from unknown client
     */
    public function testCreateEventFromGenericClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';

        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');

        $event = $this->testCreateEventWithInternalOrganizer();
    }

    /**
     * test update event from unknown client
     */
    public function testPutEventFromGenericClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        $event = $this->testCreateEventWithInternalOrganizer();

        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');

        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "{$event->getRecord()->getId()}.ics");

        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        $loadedEvent->put($vcalendarStream);
    }

    /**
     * test delete event from unknown client
     */
    public function testDeleteEventFromGenericClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        $event = $this->testCreateEventWithInternalOrganizer();

        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');

        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "{$event->getRecord()->getId()}.ics");

        $loadedEvent->delete();
    }
    
    public function testPutEventMultipleAlarms()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/event_with_multiple_alarm.ics', 'r');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('3', count($record->alarms));
    }
    
    /**
     * test get name of vcard
     */
    public function testGetNameOfEvent()
    {
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $record = $event->getRecord();
        
        $this->assertEquals($event->getName(), $record->getId() . '.ics');
    }
    
    /**
     * move event orig container shared -> personal
     */
    public function testMoveOriginEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        // move event (origin container)
        Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", stream_get_contents($event->get()));
        $oldEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $oldEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $this->assertEquals($this->objects['initialContainer']->getId(), $loadedEvent->getRecord()->container_id, 'origin container not updated');
        
    }
    
    /**
     * (organizer) move originpersonal -> originshared
     * 
     * NOTE: this is the hard case, as a attendee reference stays in personal which must not be deleted
     * 
     * solution: prohibit deleting from displaycal when origcal != displaycal
     *  Exception: implicit decline if curruser owner of displaycal && ! organizer 
     */
    public function testMoveOriginPersonalToShared()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        // move event origin to shared (origin and display were the same)
        Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", stream_get_contents($event->get()));
        $oldEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $oldEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $this->assertEquals($this->objects['sharedContainer']->getId(), $loadedEvent->getRecord()->container_id, 'origin container not updated');
        
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($loadedEvent->getRecord()->attendee);
        $this->assertEquals($this->objects['initialContainer']->getId(), $ownAttendee->displaycontainer_id, 'display container must not change');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status, 'event must not be declined: ' . print_r($ownAttendee->toArray(), TRUE));
    }
    
    /**
     * move event displaycal onedisplaycal -> otherdisplaycal
     */
    public function testMoveDisplayEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($event->getRecord()->attendee);
        $displayCalendar = Tinebase_Container::getInstance()->getContainerById($ownAttendee->displaycontainer_id);
        
        $personalCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        )));
        
        // move event (displaycontainer)
        $displayCalendarEvent = new Calendar_Frontend_WebDAV_Event($displayCalendar, "$id.ics");
        Calendar_Frontend_WebDAV_Event::create($personalCalendar, "$id.ics", stream_get_contents($displayCalendarEvent->get()));
        $oldEvent = new Calendar_Frontend_WebDAV_Event($displayCalendar, "$id.ics");
        $oldEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($displayCalendar, "$id.ics");
        $this->assertEquals($this->objects['sharedContainer']->getId(), $loadedEvent->getRecord()->container_id, 'origin container must not be updated');
        
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($loadedEvent->getRecord()->attendee);
        $this->assertEquals($personalCalendar->getId(), $ownAttendee->displaycontainer_id, 'display container not changed');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status, 'event must not be declined');
    }
    
    public function testGetAlarm()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        $personalEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $sharedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        
        $personalVCalendar = stream_get_contents($personalEvent->get());
        $sharedVCalendar = stream_get_contents($sharedEvent->get());
        #var_dump($personalVCalendar);
        #var_dump($sharedVCalendar);
        $this->assertNotContains('X-MOZ-LASTACK;VALUE=DATE-TIME:21', $personalVCalendar, $personalVCalendar);
        $this->assertContains('X-MOZ-LASTACK;VALUE=DATE-TIME:21', $sharedVCalendar, $sharedVCalendar);
    }
    
    public function testGetNoAlarmAsNonAttendee()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = file_get_contents(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $egt = new Calendar_Controller_EventGrantsTests();
        $egt->setup();
        
        $pwulfPersonalCal = $this->_getPersonasDefaultCals('sclever');
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($pwulfPersonalCal, "$id.ics", $vcalendar);
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $ics = stream_get_contents($loadedEvent->get());
        
        $this->assertNotContains('BEGIN:VALARM', $ics, $ics);
    }
    
    public function testDeleteOriginEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/event_with_custom_alarm.ics');
        $vcalendar = preg_replace('#DTSTART;TZID=Europe/Berlin:20120214T100000#', 'DTSTART;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->format('Ymd\THis'), $vcalendar);
        $vcalendar = preg_replace('#DTEND;TZID=Europe/Berlin:20120214T140000#', 'DTEND;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(1)->format('Ymd\THis'), $vcalendar);
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $loadedEvent->delete();
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $loadedEvent->getRecord();
    }
    
    public function testDeleteImplicitDecline()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/event_with_custom_alarm.ics');
        $vcalendar = preg_replace('#DTSTART;TZID=Europe/Berlin:20120214T100000#', 'DTSTART;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->format('Ymd\THis'), $vcalendar);
        $vcalendar = preg_replace('#DTEND;TZID=Europe/Berlin:20120214T140000#', 'DTEND;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(1)->format('Ymd\THis'), $vcalendar);
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        $pwulf = new Calendar_Model_Attender(array(
            'user_type'  => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'    => Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'))->contact_id
        ));
        
        $pwulfAttendee = Calendar_Model_Attender::getAttendee($event->getRecord()->attendee, $pwulf);
        $this->assertTrue($pwulfAttendee !== null, 'could not find pwulf in attendee');
        $pwulfPersonalCal = Tinebase_Container::getInstance()->getContainerById($pwulfAttendee->displaycontainer_id);
        $pwulfPersonalEvent = new Calendar_Frontend_WebDAV_Event($pwulfPersonalCal, "$id.ics");
        $pwulfPersonalEvent->delete();
        
        $pwulfPersonalEvent = new Calendar_Frontend_WebDAV_Event($pwulfPersonalCal, "$id.ics");
        $pwulfAttendee = Calendar_Model_Attender::getAttendee($pwulfPersonalEvent->getRecord()->attendee, $pwulf);
        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $pwulfAttendee->status, 'event must be declined for pwulf');
        $this->assertEquals(0, $pwulfPersonalEvent->getRecord()->is_deleted, 'event must not be deleted');
    }
    
    /**
     * NOTE: As noted in {@see testMoveOriginPersonalToShared} we can't delete/decline for organizer in his
     *       personal cal because a move personal->shared would delete/decline the event.
     *       
     *       To support intensional delete/declines we allow the delte/decline only if ther is some
     *       time between this and the last update
     */
    public function testDeleteImplicitDeclineOrganizer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        $vcalendar = preg_replace('#DTSTART;TZID=Europe/Berlin:20111004T100000#', 'DTSTART;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->format('Ymd\THis'), $vcalendar);
        $vcalendar = preg_replace('#DTEND;TZID=Europe/Berlin:20111004T120000#', 'DTEND;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(1)->format('Ymd\THis'), $vcalendar);
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        // move event origin to shared (origin and display where the same)
        Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", stream_get_contents($event->get()));
//         $oldEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
//         $oldEvent->delete();
        
        // wait some time
        $cbs = new Calendar_Backend_Sql();
        $cbs->updateMultiple(array($id), array(
            'creation_time'      => Tinebase_DateTime::now()->subMinute(5),
            'last_modified_time' => Tinebase_DateTime::now()->subMinute(3),
        ));
        
        $personalEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $personalEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($loadedEvent->getRecord()->attendee);
        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $ownAttendee->status, 'event must be declined');
    }
    
    public function testDeletePastEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $loadedEvent->delete();
        
        $notDeletedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $this->assertTrue(!! $notDeletedEvent, 'past event must not be deleted');
    }
    
    /**
     * validate that users can set alarms for events with external organizers
     */
    public function testSetAlarmForEventWithExternalOrganizer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $event = $this->testCreateEventWithExternalOrganizer();
        
        $vcalendar = file_get_contents(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        $vcalendar = preg_replace('/PT1H15M/', 'PT1H30M', $vcalendar);
        
        $event->put($vcalendar);
        
        $record = $event->getRecord();
        
        $this->assertEquals('2011-10-04 06:30:00', (string) $record->alarms->getFirstRecord()->alarm_time);
    }
    
    /**
     * return vcalendar as string and replace organizers email address with emailaddess of current user
     * 
     * @param string $_filename  file to open
     * @return string
     */
    public static function getVCalendar($_filename)
    {
        $vcalendar = file_get_contents($_filename);
        
        $unittestUserEmail = Tinebase_Core::getUser()->accountEmailAddress;
        $vcalendar = preg_replace(
            array(
                '/l.kneschke@metaway\n s.de/',
                '/un[\r\n ]{0,3}ittest@[\r\n ]{0,3}ti[\r\n ]{0,3}ne20.org/',
                '/pwulf(\n )?@tine20.org/',
                '/sclever@tine20.org/',
            ), 
            array(
                $unittestUserEmail,
                $unittestUserEmail,
                Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'))->accountEmailAddress,
                Tinebase_Helper::array_value('sclever', Zend_Registry::get('personas'))->accountEmailAddress,
            ), 
            $vcalendar
        );
        
        return $vcalendar;
    }

    /**
     * testAcceptInvitationForRecurringEventException
     * 
     * @see 0009022: can not accept invitation to recurring event exception
     * @see 0009510: is it allowed to have no main vevent in ics?
     */
    public function testAcceptInvitationForRecurringEventException()
    {
        Tinebase_Container::getInstance()->setGrants($this->objects['initialContainer'], new Tinebase_Record_RecordSet($this->objects['initialContainer']->getGrantClass(), array(
            $this->_getAllCalendarGrants(),
            array(
                'account_id'    => 0,
                'account_type'  => 'anyone',
                Tinebase_Model_Grants::GRANT_READ     => true,
                Tinebase_Model_Grants::GRANT_ADD      => false,
                Tinebase_Model_Grants::GRANT_EDIT     => false,
                Tinebase_Model_Grants::GRANT_DELETE   => false,
                Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
                Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        $persistentEvent = Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event(array(
            'container_id' => $this->objects['initialContainer']->getId(),
            'rrule'   => 'FREQ=WEEKLY;BYDAY=WE',
            'dtstart' => new Tinebase_DateTime('20131016T120000'),
            'dtend'   => new Tinebase_DateTime('20131016T130000'),
            'summary' => 'Meeting',
            'attendee' => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id'   => Tinebase_Core::getUser()->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    'status_authkey' => 'e4546f26cb37f69baf59135e7bd379bf94bba429', // TODO is this needed??
                )
            )),
            'uid' => '3ef8b44333aea7c01aa5a9308e2cb014807c60b3'
        )));

        // add pwulf as attender to create exception
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exception = Calendar_Model_Rrule::computeNextOccurrence($persistentEvent, $exceptions, new Tinebase_DateTime('20131017T140000'));
        $exception->attendee->addRecord(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_getPersonasContacts('pwulf')->getId(),
        )));
        $persistentException = Calendar_Controller_Event::getInstance()->createRecurException($exception);
        $persistentEvent = Calendar_Controller_Event::getInstance()->get($persistentEvent->getId());
        
        // pwulf tries to accept invitation
        Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()->getFullUserByLoginName('pwulf'));
        
        $_SERVER['HTTP_USER_AGENT'] = 'Mac OS X/10.8.5 (12F45) CalendarAgent/57';
        // this ics only has an exdate vevent
        $vcalendarStream = self::getVCalendar(dirname(__FILE__) . '/../../Import/files/accept_exdate_invite.ics');
        
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $persistentEvent);
        $event->put($vcalendarStream);
        
        $exdateWebDAVEvent = $event->getRecord()->exdate[0];
        $this->_assertCurrentUserAttender($exdateWebDAVEvent);
        
        $exdateCalEvent = Calendar_Controller_Event::getInstance()->get($persistentException->getId());
        $this->_assertCurrentUserAttender($exdateCalEvent);
    }
    
    /**
     * assert current user as attender
     * 
     * @param Calendar_Model_Event $exdate
     */
    protected function _assertCurrentUserAttender($event)
    {
        $this->assertTrue($event->attendee instanceof Tinebase_Record_RecordSet, 'attendee is no recordset: ' . print_r($event->toArray(), true));
        $this->assertEquals(2, $event->attendee->count(), 'exdate should have 2 attendee: ' . print_r($event->toArray(), true));
        $currentUser = Calendar_Model_Attender::getAttendee($event->attendee, new Calendar_Model_Attender(array(
            'user_id'   => Tinebase_Core::getUser()->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
        )));
        $this->assertNotNull($currentUser, 'currentUser not found in attendee');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $currentUser->status, print_r($currentUser->toArray(), true));
    }
    
    /**
     * create event with attachment
     *
     * @return multitype:Ambigous <Calendar_Frontend_WebDAV_Event, Calendar_Frontend_WebDAV_Event> Ambigous <Tinebase_Model_Tree_Node, Tinebase_Record_Interface, Tinebase_Record_Abstract, NULL, unknown>
     */
    public function createEventWithAttachment($count=1)
    {
        $event = $this->testCreateRepeatingEvent();
        
        for ($i=1; $i<=$count; $i++) {
            $suffix = $i>1 ? $i : '';
            
            $agenda = fopen("php://temp", 'r+');
            fputs($agenda, "HELLO WORLD$suffix");
            rewind($agenda);
        
            $attachmentController = Tinebase_FileSystem_RecordAttachments::getInstance();
            $attachmentNode = $attachmentController->addRecordAttachment($event->getRecord(), "agenda{$suffix}.html", $agenda);
        }
    
        $event = new Calendar_Frontend_WebDAV_Event($event->getContainer(), $event->getRecord()->getId());
    
        return $event;
    }
}
