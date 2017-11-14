<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
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

        $this->_oldRequest = Tinebase_Core::get(Tinebase_Core::REQUEST);
        Tinebase_Core::set(Tinebase_Core::REQUEST, new \Zend\Http\PhpEnvironment\Request());

        $this->_uit = Calendar_Controller_Poll::getInstance();
    }

    public function tearDown()
    {
        Tinebase_Core::set(Tinebase_Core::REQUEST, $this->_oldRequest);
        parent::tearDown();
    }

    public function testPublicApiGetPoll()
    {
        $jt = new Calendar_Frontend_Json_PollTest();
        $jt->setUp();

        list($persistentEvent, $poll , $alternativeEvents) = $jt->testGetPollEvents();

        Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $pollData = json_decode($this->_uit->publicApiGetPoll($poll['id'])->getBody(), true);

        $this->assertEquals('test poll', $pollData['name'], 'poll base data missing');
        $this->assertTrue(is_array($pollData['dates']), 'dates missing');
        $this->assertTrue(is_array($pollData['attendee_status']), 'attendee_status missing');
    }
}