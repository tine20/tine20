<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_ServerTests extends Syncroton_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    protected function setUp()
    {
        parent::setUp();
        $_GET = array();
    }
    
    protected function tearDown()
    {
        parent::tearDown();
        
        header_remove();
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testServer()
    {
        $_SERVER['REQUEST_METHOD']            = 'POST';
        $_SERVER['HTTP_MS_ASPROTOCOLVERSION'] = '2.5';
        $_SERVER['HTTP_USER_AGENT']           = 'Apple-iPhone/705.18';
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1356</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $request = new Zend_Controller_Request_Http(Zend_Uri::factory('http://localhost/Microsoft-Server-ActiveSync?User=abc1234&DeviceId=Appl7R743U8YWH8&DeviceType=iPhone&Cmd=Sync'));
        
        ob_start();
        
        $server = new Syncroton_Server('abc1234', $request, $doc);
        
        $server->handle();
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('AwFqAEVcT0sDMAABUgM0OHJ1NDdmaGY3Z2hmN2ZnaDQAAU4DMwABAQEB', base64_encode($result));
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testBase64EncodedParameters()
    {
        $_SERVER['REQUEST_METHOD']            = 'POST';
        $_SERVER['HTTP_MS_ASPROTOCOLVERSION'] = '14.1';
        $_SERVER['HTTP_USER_AGENT']           = 'Apple-iPhone/705.18';
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1356</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $request = new Zend_Controller_Request_Http(Zend_Uri::factory('http://localhost/Microsoft-Server-ActiveSync?jAAJBAp2MTQwRGV2aWNlAApTbWFydFBob25l'));
        #DeviceId=A81F31E18BC6F962B3674D98788A7C9A&DeviceType=WindowsPhone
        #$request = new Zend_Controller_Request_Http(Zend_Uri::factory('http://localhost/Microsoft-Server-ActiveSync?jAkHBBCoHzHhi8b5YrNnTZh4inyaBAAAAAACV1A='));
        #$request = new Zend_Controller_Request_Http(Zend_Uri::factory('http://localhost/Microsoft-Server-ActiveSync?jBMHBBCoHzHhi8b5YrNnTZh4inyaBAAAAAACV1AHAQI='));
        
        $server = new Syncroton_Server('abc1234', $request, $doc);
        
        ob_start();
        
        $server->handle();
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('AwFqAEVcT0sDMAABUgM0OHJ1NDdmaGY3Z2hmN2ZnaDQAAU4DMwABAQEB', base64_encode($result));
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testBase64EncodedParametersWithPlus()
    {
        $_SERVER['REQUEST_METHOD']            = 'POST';
        $_SERVER['HTTP_MS_ASPROTOCOLVERSION'] = '14.1';
        $_SERVER['HTTP_USER_AGENT']           = 'Apple-iPhone/705.18';
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1356</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $request = new Zend_Controller_Request_Http(Zend_Uri::factory('http://localhost/Microsoft-Server-ActiveSync?jAkHBBC32YBsEj5p+IUKLTDTcJoYBAAAAAALV2luZG93c01haWw='));
        
        $server = new Syncroton_Server('abc1234', $request, $doc);
        
        ob_start();
        
        $server->handle();
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('AwFqAAAHVkwDMQABUgMxAAFOVwM2AAFPSANjYWxlbmRhckZvbGRlcklkAAFJAzAA', substr(base64_encode($result), 0, 64));
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testFetchMultiPart()
    {
        $_SERVER['REQUEST_METHOD']            = 'POST';
        $_SERVER['HTTP_MS_ASPROTOCOLVERSION'] = '2.5';
        $_SERVER['HTTP_USER_AGENT']           = 'Apple-iPhone/705.18';
        $_SERVER['HTTP_MS_ASACCEPTMULTIPART'] = 'T';
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1356</SyncKey><CollectionId>48ru47fhf7ghf7fgh4</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $request = new Zend_Controller_Request_Http(Zend_Uri::factory('http://localhost/Microsoft-Server-ActiveSync?User=abc1234&DeviceId=Appl7R743U8YWH8&DeviceType=iPhone&Cmd=Sync'));
        
        ob_start();
        
        $server = new Syncroton_Server('abc1234', $request, $doc);
        
        $server->handle();
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('AQAAAAwAAAAqAAAAAwFqAEVcT0sDMAABUgM0OHJ1NDdmaGY3Z2hmN2ZnaDQAAU4DMwABAQEB', base64_encode($result));
    }
}
