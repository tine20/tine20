<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test helper
 */
require_once __DIR__ . '/../../../../../tine20/vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Calendar_Frontend_CalDAV_SpeedUpPropfindPluginTest extends Calendar_TestCase
{
    /**
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * @var Calendar_Frontend_WebDAV_EventTest
     */
    protected $calDAVTests;

    /**
     * @var Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin
     */
    protected $plugin;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    public function setUp()
    {
        $this->calDAVTests = new Calendar_Frontend_WebDAV_EventTest();
        $this->calDAVTests->setup();

        parent::setUp();

        $this->server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());

        $this->plugin = new Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin();

        $this->server->addPlugin($this->plugin);

        $this->response = new Sabre\HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();

        $this->assertEquals('speedUpPropfindPlugin', $pluginName);
    }

    /**
     * test testGetProperties method
     */
    public function testGetProperties()
    {
        $event = $this->calDAVTests->testCreateRepeatingEventAndPutExdate();

        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <propfind xmlns="DAV:">
                    <prop>
                        <getcontenttype/>
                        <getetag/>
                    </prop>
                 </propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $event->getRecord()->container_id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);

        /*$responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //echo $this->response->body;
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vevent-datetime');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vevent-date');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vtodo-datetime');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vtodo-date');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());*/
    }

    public function testInvitationToExceptionOnly()
    {
        $cctrl = Calendar_Controller_Event::getInstance();
        $event = $this->_getEvent(true);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;COUNT=5';
        /*$createdEvent = */$cctrl->create($event);

        $allEvents = $cctrl->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()],
        ]));
        Calendar_Model_Rrule::mergeRecurrenceSet($allEvents, Tinebase_DateTime::now()->setTime(0,0,0),
            Tinebase_DateTime::now()->addWeek(1));
        $allEvents[2]->dtstart->subMinute(1);
        $allEvents[2]->dtend->subMinute(1);
        $allEvents[2]->attendee->addRecord($this->_createAttender($this->_personas['jmcblack']->contact_id));
        $recurException = $cctrl->createRecurException($allEvents[2]);

        Tinebase_Core::setUser($this->_personas['jmcblack']);
        $allEvents = $cctrl->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_personasDefaultCals['jmcblack']->getId()],
        ]));

        static::assertSame(1, $allEvents->count());
        static::assertSame($recurException->getId(), $allEvents->getFirstRecord()->getId());


        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <propfind xmlns="DAV:">
                    <prop>
                        <getcontenttype/>
                        <getetag/>
                    </prop>
                 </propfind>';

        $uri = '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->_personasDefaultCals['jmcblack']->getId();
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => $uri,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        static::assertSame('HTTP/1.1 207 Multi-Status', $this->response->status);
        static::assertContains($uri . '/' . $recurException->getId(), $this->response->body);
    }
}
