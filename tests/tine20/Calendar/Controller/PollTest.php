<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Test class for Calendar_Controller_Poll
 *
 * @package     Calendar
 */
class Calendar_Controller_PollTest extends TestCase
{
    /**
     * @var Calendar_Controller_Poll
     */
    protected $_uit = null;

    /**
     * @var Calendar_Frontend_Json_PollTest
     */
    protected $jt = null;

    /**
     * @var \Zend\Http\Request
     */
    protected $_oldRequest = null;

    /**
     * @var Tinebase_Model_FullUser
     *
     * TODO replace with $_originalTestUser
     */
    protected $_origUser = null;

    /**
     * (non-PHPdoc)
     * @see Calendar/Calendar_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->_oldRequest = Tinebase_Core::get(Tinebase_Core::REQUEST);
        Tinebase_Core::set(Tinebase_Core::REQUEST, new Tinebase_Http_Request());

        $this->jt = new Calendar_Frontend_Json_PollTest();
        $this->jt->setUp();

        $this->_uit = Calendar_Controller_Poll::getInstance();
    }

    public function tearDown()
    {
        Tinebase_Core::set(Tinebase_Core::REQUEST, $this->_oldRequest);
        $this->jt->tearDown();

        if ($this->_origUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_origUser);
        }

        parent::tearDown();
    }

    protected function assertAnonymousTest()
    {
        $this->_origUser = Tinebase_Core::getUser();
        Tinebase_Core::set(Tinebase_Core::USER, '');
    }

    /**
     * anonymous - public url
     * @return mixed
     */
    public function testPublicApiGetPoll()
    {
        list($persistentEvent, $poll , $alternativeEvents) = $this->jt->testGetPollEvents();

        $this->assertAnonymousTest();

        Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $pollData = json_decode($this->_uit->publicApiGetPoll($poll['id'])->getBody(), true);

        $this->assertEquals('test poll', $pollData['name'], 'poll base data missing');
        $this->assertTrue(is_array($pollData['alternative_dates']), 'alternative dates missing');
        $this->assertTrue(is_array($pollData['attendee_status']), 'attendee_status missing');

        // @TODO assert no authkeys at all -> we are not a user!

        return $pollData;
    }

    /**
     * anonymous - user url
     */
    public function testPublicApiGetPollPersonalisedAnonymous()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();

        $this->assertAnonymousTest();

        $pollId = $pollData['id'];
        $userKey = $responseData[0]['user_type'] . '-' . $responseData[0]['user_id'];
        $authKey = $responseData[0]['status_authkey'];

        Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $pollData = json_decode($this->_uit->publicApiGetPoll($pollId, $userKey, $authKey)->getBody(), true);

        foreach($pollData['attendee_status'] as $userStatus) {
            $authKeys = array_unique(array_column($userStatus['status'], 'status_authkey'));
            if ($userStatus['key'] == $userKey) {
                $this->assertTrue(in_array($authKey, $authKeys));
            } else {
                $this->assertEquals(null, $authKeys[0]);
            }
        }
    }

    /**
     * anonymous - user url
     */
    public function testPublicApiGetPollPersonalisedAnonymousWrongAuthkey()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();

        $this->assertAnonymousTest();

        $pollId = $pollData['id'];
        $userKey = $responseData[0]['user_type'] . '-' . $responseData[0]['user_id'];
        $authKey = 'wrong';

        Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $response = $this->_uit->publicApiGetPoll($pollId, $userKey, $authKey);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertRegexp('/mismatch/', (string)$response->getBody());
    }

    /**
     * account - public url
     * @return mixed
     */
    public function testPublicApiGetPollWithAccount()
    {
        list($persistentEvent, $poll , $alternativeEvents) = $this->jt->testGetPollEvents();
        $pollId = $poll['id'];
        $userKey = 'user-' . Tinebase_Core::getUser()->contact_id;
        $authKey = 'not needed here';


        Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $pollData = json_decode($this->_uit->publicApiGetPoll($poll['id'])->getBody(), true);

        foreach($pollData['attendee_status'] as $userStatus) {
            $authKeys = array_unique(array_column($userStatus['status'], 'status_authkey'));
            if ($userStatus['key'] == $userKey) {
                $this->assertNotNull($authKeys[0]);
            } else {
                $this->assertEquals(null, $authKeys[0]);
            }
        }

        return $pollData;
    }

    /**
     * account - public url - no attendee yet
     * @return mixed
     */
    public function testPublicApiGetPollWithAccountNoAttendee()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();
        $pollId = $pollData['id'];
        $userKey = 'user-' . Tinebase_Core::getUser()->contact_id;
        $authKey = 'not needed here';

        $pwulf = Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'));
        $this->assertAnonymousTest();
        Tinebase_Core::set(Tinebase_Core::USER, $pwulf);

        Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $pollData = json_decode($this->_uit->publicApiGetPoll($pollId)->getBody(), true);

        // assert no authkeys at all
        foreach($pollData['attendee_status'] as $userStatus) {
            $authKeys = array_unique(array_column($userStatus['status'], 'status_authkey'));
            $this->assertEquals(null, $authKeys[0]);
        }

        return $pollData;
    }

    /**
     * user
     */
    public function testPublicApiUpdateAttenderStatusWidthAccount()
    {
        $pollData = $this->testPublicApiGetPollWithAccount();
        $contact_id = Tinebase_Core::getUser()->contact_id;
        $statusData = $pollData['attendee_status'][array_search('user-' . $contact_id, array_column($pollData['attendee_status'], 'key'))];

        $requestBody = <<<EOT
{
    "status":[{
        "cal_event_id":"{$statusData['status'][0]['cal_event_id']}",
        "status":"TENTATIVE",
        "user_type":"user",
        "user_id":"{$contact_id}",
        "status_authkey":"{$statusData['status'][0]['status_authkey']}"
    } , {
        "cal_event_id":"{$statusData['status'][1]['cal_event_id']}",
        "status":"DECLINED",
        "user_type":"user",
        "user_id":"{$contact_id}",
        "status_authkey":"{$statusData['status'][1]['status_authkey']}"
    }]
}
EOT;

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent($requestBody);

        $response = $this->_uit->publicApiUpdateAttendeeStatus($pollData['id']);
        $this->assertEquals(200, $response->getStatusCode());

        $event = Calendar_Controller_Event::getInstance()->get($statusData['status'][0]['cal_event_id']);
        $attendee = $event->attendee->filter('user_id', Tinebase_Core::getUser()->contact_id)->getFirstRecord();
        $this->assertEquals('TENTATIVE', $attendee->status);

        $event = Calendar_Controller_Event::getInstance()->get($statusData['status'][1]['cal_event_id']);
        $attendee = $event->attendee->filter('user_id', Tinebase_Core::getUser()->contact_id)->getFirstRecord();
        $this->assertEquals('DECLINED', $attendee->status);


    }

    /**
     * anonymous with (some) authkey
     */
    public function testPublicApiUpdateAttenderStatusAnonymous()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();
        $this->assertAnonymousTest();

        $responseData[0]['status'] = 'TENTATIVE';
        $responseData[1]['status'] = 'TENTATIVE';

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode(['status' => $responseData]));

        $response = $this->_uit->publicApiUpdateAttendeeStatus($pollData['id']);
        $this->assertEquals(200, $response->getStatusCode());

        $userKey = $responseData[0]['user_type'] . '-' . $responseData[0]['user_id'];
        $pollData = json_decode($this->_uit->publicApiGetPoll($pollData['id'], $userKey, $responseData[1]['status_authkey'])->getBody(), true);

        $johndoe = Tinebase_Helper::array_value(array_search($userKey, array_column($pollData['attendee_status'], 'key')), $pollData['attendee_status']);
        $this->assertEquals('TENTATIVE', $johndoe['status'][0]['status']);
        $this->assertEquals('TENTATIVE', $johndoe['status'][1]['status']);
    }

    public function testPublicApiUpdateAttenderStatusClosedPoll()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();
        $alternativeEvents = Calendar_Controller_Poll::getInstance()->getPollEvents($pollData['id']);
        Calendar_Controller_Poll::getInstance()->setDefiniteEvent($alternativeEvents[1]);

        $this->assertAnonymousTest();

        $responseData[0]['status'] = 'TENTATIVE';
        $responseData[1]['status'] = 'TENTATIVE';

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode(['status' => $responseData]));

        $response = $this->_uit->publicApiUpdateAttendeeStatus($pollData['id']);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertRegexp('/poll is closed/', (string)$response->getBody());
    }

    public function testPublicApiUpdateAttenderStatusNoAttendee()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();

        $this->assertAnonymousTest();

        $pwulf = Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'));
        $userKey = 'user-' . $pwulf->contact_id;
        $requestStatusData = [[
            'key'            => $userKey,
            'user_type'      => 'user',
            'user_id'        => $pwulf->contact_id,
            'cal_event_id'   => $responseData[0]['cal_event_id'],
            'status'         => 'ACCEPTED',
            'status_authkey' => 'none'
        ]];

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode(['status' => $requestStatusData]));

        $response = $this->_uit->publicApiUpdateAttendeeStatus($pollData['id']);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPublicApiAddAttender()
    {
        $pollData = $this->testPublicApiGetPoll();

        $statusMap = [Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::STATUS_DECLINED];
        $requestData = [
            'name' => 'John Doe',
            'email' => 'john@doe.net',
            'status' => []
        ];
        foreach($pollData['alternative_dates'] as $idx => $date) {
            $statusMap[$date['id']] = $statusMap[$idx];
            $requestData['status'][] = [
                'cal_event_id' => $date['id'],
                'status' => $statusMap[$idx],
            ];
        }

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

        $response = $this->_uit->publicApiAddAttendee($pollData['id']);

        $this->assertEquals(200, $response->getStatusCode());

        $contact = Calendar_Model_Attender::resolveEmailToContact($requestData);
        $this->assertEquals('John', $contact->n_given);
        $this->assertEquals('Doe', $contact->n_family);

        $currentAttendee = new Calendar_Model_Attender([
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id' => $contact->getId(),
        ]);

        Tinebase_Core::set(Tinebase_Core::USER, $this->_origUser);

        foreach($pollData['alternative_dates'] as $idx => $date) {
            $event = Calendar_Controller_Event::getInstance()->get($date['id']);
            $attendee = Calendar_Model_Attender::getAttendee($event->attendee, $currentAttendee);
            $this->assertNotNull($attendee);
            $this->assertEquals($statusMap[$date['id']], $attendee->status);
            $statusMap[$attendee['id']] = $attendee->status;
        }

        $responseData = json_decode($response->getBody(), true);
        foreach($responseData as $attendeeStatusData) {
            $this->assertEquals($statusMap[$attendeeStatusData['cal_event_id']], $attendeeStatusData['status']);
        }

        return [$responseData, $pollData];
    }

    public function testPublicApiAddAttenderLockedPoll()
    {
        $pollData = $this->testPublicApiGetPoll();

        Tinebase_Core::set(Tinebase_Core::USER, $this->_origUser);
        $poll = Calendar_Controller_Poll::getInstance()->get($pollData['id']);
        $poll->locked = true;
        $poll = Calendar_Controller_Poll::getInstance()->update($poll);
        $this->assertAnonymousTest();

        $requestData = [
            'name' => 'John Doe',
            'email' => 'john@doe.net',
            'status' => []
        ];

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

        $response = $this->_uit->publicApiAddAttendee($pollData['id']);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertRegexp('/poll is locked/', (string)$response->getBody());
    }

    public function testPublicApiAddAttenderClosedPoll()
    {
        list($persistentEvent, $poll , $alternativeEvents) = $this->jt->testGetPollEvents();

        $definiteEvent = new Calendar_Model_Event($alternativeEvents['results'][1]);
        Calendar_Controller_Poll::getInstance()->setDefiniteEvent($definiteEvent);

        $this->assertAnonymousTest();

        $requestData = [
            'name' => 'John Doe',
            'email' => 'john@doe.net',
            'status' => []
        ];

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

         $response = $this->_uit->publicApiAddAttendee($poll['id']);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertRegexp('/poll is closed/', (string)$response->getBody());
    }

    /**
     * user has account but is not logged in
     */
    public function testPublicApiAddAttenderAnonymousWithAccount()
    {
        $pollData = $this->testPublicApiGetPoll();
        $this->assertAnonymousTest();

        $sclever = Tinebase_Helper::array_value('sclever', Zend_Registry::get('personas'));

        $requestData = [
            'name' => $sclever->accountFullName,
            'email' => $sclever->accountEmailAddress,
            'status' => []
        ];

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

        $response = $this->_uit->publicApiAddAttendee($pollData['id']);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertRegexp('/please log in/', (string)$response->getBody());
    }

    /**
     * user has account but is logged but no attendee yet
     */
    public function testPublicApiAddAttenderWithAccountNoAttendee()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();
        $this->assertAnonymousTest();
        $statusMap = [Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::STATUS_DECLINED];

        $pwulf = Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'));
        Tinebase_Core::set(Tinebase_Core::USER, $pwulf);

        $requestData = [
            'name' => $pwulf->accountFullName,
            'email' => $pwulf->accountEmailAddress,
            'status' => []
        ];
        foreach($pollData['alternative_dates'] as $idx => $date) {
            $statusMap[$date['id']] = $statusMap[$idx];
            $requestData['status'][] = [
                'cal_event_id' => $date['id'],
                'status' => $statusMap[$idx],
            ];
        }

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

        $response = $this->_uit->publicApiAddAttendee($pollData['id']);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);
        foreach($responseData as $attendeeData) {
            $this->assertEquals($statusMap[$attendeeData['cal_event_id']], $attendeeData['status']);
        }
    }

    public function testPublicApiAddAttenderExistingAttendee()
    {
        list ($responseData, $pollData) = $this->testPublicApiAddAttender();
        $this->assertAnonymousTest();
        $statusMap = [Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::STATUS_DECLINED];

        $requestData = [
            'name' => 'John Doe',
            'email' => 'john@doe.net',
            'status' => []
        ];
        foreach($pollData['alternative_dates'] as $idx => $date) {
            $statusMap[$date['id']] = $statusMap[$idx];
            $requestData['status'][] = [
                'cal_event_id' => $date['id'],
                'status' => $statusMap[$idx],
            ];
        }

        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

        $response = $this->_uit->publicApiAddAttendee($pollData['id']);

//        // other strategy
//        $this->assertEquals(401, $response->getStatusCode());
//        $this->assertRegexp('/use personal link/', (string)$response->getBody());

        // its allowed for the moment
        $this->assertEquals(200, $response->getStatusCode());
//        $responseData = json_decode($response->getBody(), true);

        $contact = Calendar_Model_Attender::resolveEmailToContact($requestData);
        $currentAttendee = new Calendar_Model_Attender([
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id' => $contact->getId(),
        ]);

        Tinebase_Core::set(Tinebase_Core::USER, $this->_origUser);

        foreach($pollData['alternative_dates'] as $idx => $date) {
            $event = Calendar_Controller_Event::getInstance()->get($date['id']);
            $attendee = Calendar_Model_Attender::getAttendee($event->attendee, $currentAttendee);
            $this->assertNotNull($attendee);
            $this->assertEquals($statusMap[$date['id']], $attendee->status);
            $statusMap[$attendee['id']] = $attendee->status;
        }

        $responseData = json_decode($response->getBody(), true);
        foreach($responseData as $attendeeData) {
            $this->assertEquals($statusMap[$attendeeData['id']], $attendeeData['status']);
        }

    }

    public function testPublicApiAddAttenderNotification()
    {
        $this->_setMailDomainIfEmpty();
        $oldTransport = Tinebase_Smtp::getDefaultTransport();
        $oldTestTransport = Felamimail_Transport::setTestTransport(null);
        static::resetMailer();

        try {
            Tinebase_Notification::destroyInstance();
            Tinebase_Smtp::setDefaultTransport(new Felamimail_Transport_Array());
            Felamimail_Transport::setTestTransport(Tinebase_Smtp::getDefaultTransport());
            static::flushMailer();

            list ($responseData, $pollData) = $this->testPublicApiAddAttender();

            $messages = static::getMessages();
            $expectedMessages = Calendar_Config::getInstance()->get(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS) ? 1 : 2;
            static::assertEquals($expectedMessages, count($messages), 'expected ' . $expectedMessages . ' mails send');

            /** @var Tinebase_Mail $confirmationMessage */
            $confirmationMessage = $messages[0];
            $this->assertEquals('john@doe.net', $confirmationMessage->getRecipients()[0]);
            $text = $confirmationMessage->getBodyText()->getContent();
            $this->assertContains('Thank you for attendening', $text);
            $this->assertNotContains('Array', $text, 'notification did not cope with resolved stuff');

        } finally {
            Tinebase_Smtp::setDefaultTransport($oldTransport);
            Felamimail_Transport::setTestTransport($oldTestTransport);
            static::resetMailer();
        }
    }

    /**
     * testDefiniteEventNotification
     *
     * TODO add test case(s) for list/group/resource attendee
     */
    public function testDefiniteEventNotification()
    {
        $this->_setMailDomainIfEmpty();

        $oldTransport = Tinebase_Smtp::getDefaultTransport();
        $oldTestTransport = Felamimail_Transport::setTestTransport(null);
        static::resetMailer();

        try {
            Tinebase_Notification::destroyInstance();
            Tinebase_Smtp::setDefaultTransport(new Felamimail_Transport_Array());
            Felamimail_Transport::setTestTransport(Tinebase_Smtp::getDefaultTransport());
            static::flushMailer();

            list ($updatedEvent, $alternativeEvents) = $this->jt->testSetDefiniteEvent();

            $messages = static::getMessages();
//            $message = current(array_filter($messages, function($message) {
//                return $message->getRecipients()[0] == Zend_Registry::get('personas')['sclever']->accountEmailAddress;
//            }));
//            foreach($messages as $message) {
//                echo $message->getSubject() . "\n";
//                echo $message->getBodyText()->getContent();
//                echo "\n -- \n\n";
//            }

            static::assertTrue(isset($messages[1]), 'excepted message is not available');
            $message = $messages[1];
            $text = $message->getBodyText()->getContent();
//            $html = $message->getBodyHtml()->getContent();

            $this->assertContains('has been closed', $text);

        } finally {
            Tinebase_Smtp::setDefaultTransport($oldTransport);
            Felamimail_Transport::setTestTransport($oldTestTransport);
            static::resetMailer();
        }
    }

    public function testSupressDeleteNotifications()
    {
        $this->_setMailDomainIfEmpty();

        $oldTransport = Tinebase_Smtp::getDefaultTransport();
        $oldTestTransport = Felamimail_Transport::setTestTransport(null);
        static::resetMailer();

        try {
            Tinebase_Notification::destroyInstance();
            Tinebase_Smtp::setDefaultTransport(new Felamimail_Transport_Array());
            Felamimail_Transport::setTestTransport(Tinebase_Smtp::getDefaultTransport());
            static::flushMailer();

            Calendar_Controller_Event::getInstance()->sendNotifications(true);
            Calendar_Config::getInstance()->set(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS, true);

            list($persistentEvent, $poll, $alternativeEvents) = $this->jt->testGetPollEvents();
            $alternativeEvent = Tinebase_Helper::array_value(0, array_values(
                array_filter($alternativeEvents['results'],
                    function ($event) use ($persistentEvent) {
                        return $event['id'] != $persistentEvent['id'];
                    })));


            Calendar_Controller_Event::getInstance()->delete($alternativeEvent['id']);

            $messages = static::getMessages();
            $this->assertEmpty($messages);

        } finally {
            Tinebase_Smtp::setDefaultTransport($oldTransport);
            Felamimail_Transport::setTestTransport($oldTestTransport);
            static::resetMailer();
        }
    }
}
