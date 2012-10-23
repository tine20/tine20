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
class Syncroton_Command_MeetingResponseTests extends Syncroton_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync FolderCreate command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test processing of meeting reponse
     */
    public function testMeetingResponse()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <MeetingResponse xmlns="uri:MeetingResponse" xmlns:Search="uri:Search">
                <Request>
                    <UserResponse>2</UserResponse>
                    <CollectionId>17</CollectionId>
                    <RequestId>f0c79775b6b44be446f91187e24566aa1c5d06ab</RequestId>
                </Request>
                <Request>
                    <UserResponse>2</UserResponse>
                    <Search:LongId>1129::64c542b3b4bf624630a1fbbc30d2375a3729b734</Search:LongId>
                </Request>
            </MeetingResponse>
        ');
        
        $meetingResponse = new Syncroton_Command_MeetingResponse($doc, $this->_device, null);
        
        $meetingResponse->handle();
        
        $responseDoc = $meetingResponse->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('MeetingResponse', 'uri:MeetingResponse');
        
        $nodes = $xpath->query('//MeetingResponse:MeetingResponse/MeetingResponse:Result');
        $this->assertEquals(2, $nodes->length, $responseDoc->saveXML());
        
        #$nodes = $xpath->query('//Search:Search/Search:Response/Search:Store/Search:Total');
        #$this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEquals(5, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        #$nodes = $xpath->query('//Search:Search/Search:Response/Search:Store/Search:Result');
        #$this->assertEquals(4, $nodes->length, $responseDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Search::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }    
}
