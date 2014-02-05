<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once 'vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Calendar_Frontend_CalDAV_PluginDefaultAlarmsTest extends TestCase
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
        parent::setUp();
        
        $this->server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        
        $this->plugin = new Calendar_Frontend_CalDAV_PluginDefaultAlarms();
        
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
        
        $this->assertEquals('calendarDefaultAlarms', $pluginName);
    }
    
    /**
     * test testGetProperties method
     */
    public function testGetProperties()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <propfind xmlns="DAV:">
                    <prop>
                        <default-alarm-vevent-date xmlns="urn:ietf:params:xml:ns:caldav"/>
                        <default-alarm-vevent-datetime xmlns="urn:ietf:params:xml:ns:caldav"/>
                        <default-alarm-vtodo-date xmlns="urn:ietf:params:xml:ns:caldav"/>
                        <default-alarm-vtodo-datetime xmlns="urn:ietf:params:xml:ns:caldav"/>
                    </prop>
                 </propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id,
            'HTTP_DEPTH'     => '0',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
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
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
