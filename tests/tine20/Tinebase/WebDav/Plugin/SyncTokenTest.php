<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_WebDav_Plugin_SyncTokenTest extends Tinebase_WebDav_Plugin_AbstractBaseTest
{
    /**
     * @var Tinebase_WebDav_Plugin_SyncToken
     */
    protected $plugin;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase WebDav Plugin SyncToken Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        parent::setupCalendarContainer();

        $this->setupCalendarContent();

        $this->plugin = new Tinebase_WebDav_Plugin_SyncToken();
        $this->server->addPlugin($this->plugin);
    }

    /**
     * tear down tests
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    protected function setupCalendarContent()
    {
        $eventController = Calendar_Controller_Event::getInstance();
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'container_id'  => $this->objects['initialContainer']->id,
            'summary'       => 'change socks',
            'dtstart'       => '1979-06-05 07:55:00',
            'dtend'         => '1979-06-05 08:00:00',
            'rrule'         => 'FREQ=DAILY;INTERVAL=2;UNTIL=2009-04-01 08:00:00',
            'exdate'        => '2009-03-31 07:00:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule_until'   => '2009-04-01 08:00:00',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $eventController->create($event);

        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'container_id'  => $this->objects['initialContainer']->id,
            'summary'       => 'change t-shirt',
            'dtstart'       => '1979-06-05 08:00:00',
            'dtend'         => '1979-06-05 08:05:00',
            'rrule'         => 'FREQ=DAILY;INTERVAL=2;UNTIL=2009-04-01 08:00:00',
            'exdate'        => '2009-03-31 07:00:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule_until'   => '2009-04-01 08:00:00',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));

        $persistentEvent = $eventController->create($event);

        $exception = clone $persistentEvent;
        $exception->summary = 'use blue t-shirt today';

        $eventController->createRecurException($exception);

    }

    /**
     * test getSupportedReportSet method
     */
    public function testGetSupportedReportSetForCalendarNode()
    {
        $set = $this->plugin->getSupportedReportSet('/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id);

        $this->assertContains('{DAV:}sync-collection', $set);
    }

    /**
     * test propfind sync-token request
     */
    public function testPropfindSyncToken()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <A:propfind xmlns:A="DAV:">
                     <A:prop>
                        <A:sync-token/>
                     </A:prop>
                 </A:propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '0',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<d:sync-token>http://tine20.net/ns/sync/5</d:sync-token></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response></d:multistatus>', $this->response->body);
    }

    public function testSyncCollectionEmptyContainer()
    {
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['jmcblack']);

        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <sync-collection xmlns="DAV:">
                <sync-token/>
                <sync-level>1</sync-level>
                <prop>
                    <getcontenttype/>
                    <getetag/>
                </prop>
            </sync-collection>';

        $uri = '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $this->_personas['jmcblack']->getId());
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => $uri,
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        static::assertSame('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<d:sync-token>http://tine20.net/ns/sync/-1</d:sync-token></d:multistatus>', $this->response->body);
    }

    /**
     * test sync-collection request
     */
    public function testSyncCollection()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
                <A:sync-collection xmlns:A="DAV:">
                    <A:sync-token>http://tine20.net/ns/sync/-1</A:sync-token>
                    <A:sync-level>1</A:sync-level>
                    <A:prop>
                        <A:getetag/>
                        <A:getcontenttype/>
                    </A:prop>
                </A:sync-collection>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<d:sync-token>http://tine20.net/ns/sync/5</d:sync-token></d:multistatus>', $this->response->body);

        //check that we only got 2 responses, so no reoccuring events!
        $this->assertEquals(2, preg_match_all('/<d:response>/', $this->response->body, $m));
    }
}
