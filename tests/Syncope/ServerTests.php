<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for FolderSync_Controller_Event
 * 
 * @package     Tests
 */
class Syncope_ServerTests extends Syncope_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
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
        
        $server = new Syncope_Server('abc1234', $request, $doc);
        
        $server->handle();
    }
}
