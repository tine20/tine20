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
class Calendar_Frontend_CalDAV_SpeedUpPropfindPluginTest extends TestCase
{
    /**
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
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

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //echo $this->response->body;
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        /*$xpath = new DomXPath($responseDoc);
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
}
