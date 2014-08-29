<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once 'vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Tinebase_WebDav_Plugin_OwnCloudTest extends TestCase
{
    /**
     * 
     * @var Sabre\DAV\Server
     */
    protected $server;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Plugin OwnCloud Tests');
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
        
        $this->server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        
        $this->plugin = new Tinebase_WebDav_Plugin_OwnCloud();
        
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
        
        $this->assertEquals('Tinebase_WebDav_Plugin_OwnCloud', $pluginName);
    }
    
    /**
     * test testGetProperties method
     */
    public function testGetProperties()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <propfind xmlns="DAV:">
                    <prop>
                        <getlastmodified xmlns="DAV:"/>
                        <getcontentlength xmlns="DAV:"/>
                        <resourcetype xmlns="DAV:"/>
                        <getetag xmlns="DAV:"/>
                        <id xmlns="http://owncloud.org/ns"/>
                    </prop>
                 </propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/remote.php/webdav/' . Tinebase_Core::getUser()->accountDisplayName,
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
        $xpath->registerNamespace('owncloud', 'http://owncloud.org/ns');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/owncloud:id');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
