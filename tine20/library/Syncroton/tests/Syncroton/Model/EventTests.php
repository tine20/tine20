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
class Syncroton_Model_EventTests extends Syncroton_Model_ATestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_testXMLInput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
        <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
            <Collections>
                <Collection>
                    <Class>Calendar</Class>
                    <SyncKey>9</SyncKey>
                    <CollectionId>41</CollectionId>
                    <DeletesAsMoves/>
                    <GetChanges/>
                    <WindowSize>50</WindowSize>
                    <Options><FilterType>5</FilterType></Options>
                    <Commands>
                        <Change>
                            <ServerId>6de7cb687964dc6eea109cd81750177979362217</ServerId>
                            <ApplicationData>
                                <Calendar:Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Calendar:Timezone>
                                <Calendar:AllDayEvent>0</Calendar:AllDayEvent>
                                <Calendar:BusyStatus>2</Calendar:BusyStatus>
                                <Calendar:DtStamp>20101125T150537Z</Calendar:DtStamp>
                                <Calendar:EndTime>20101123T160000Z</Calendar:EndTime>
                                <Calendar:Sensitivity>0</Calendar:Sensitivity>
                                <Calendar:Subject>Repeat</Calendar:Subject>
                                <Calendar:StartTime>20101123T130000Z</Calendar:StartTime>
                                <Calendar:UID>6de7cb687964dc6eea109cd81750177979362217</Calendar:UID>
                                <Calendar:MeetingStatus>1</Calendar:MeetingStatus>
                                <Calendar:Attendees>
                                    <Calendar:Attendee><Calendar:Name>Lars Kneschke</Calendar:Name><Calendar:Email>lars@kneschke.de</Calendar:Email></Calendar:Attendee>
                                </Calendar:Attendees>
                                <Calendar:Recurrence>
                                    <Calendar:Type>0</Calendar:Type>
                                    <Calendar:Interval>1</Calendar:Interval>
                                    <Calendar:Until>20101128T225959Z</Calendar:Until>
                                </Calendar:Recurrence>
                                <Calendar:Exceptions>
                                    <Calendar:Exception>
                                        <Calendar:Deleted>0</Calendar:Deleted>
                                        <Calendar:ExceptionStartTime>20101125T130000Z</Calendar:ExceptionStartTime>
                                        <Calendar:StartTime>20101125T140000Z</Calendar:StartTime>
                                        <Calendar:EndTime>20101125T170000Z</Calendar:EndTime>
                                        <Calendar:Subject>Repeat mal anders</Calendar:Subject>
                                        <Calendar:BusyStatus>2</Calendar:BusyStatus>
                                        <Calendar:AllDayEvent>0</Calendar:AllDayEvent>
                                    </Calendar:Exception>
                                    <Calendar:Exception>
                                        <Calendar:Deleted>1</Calendar:Deleted>
                                        <Calendar:ExceptionStartTime>20101124T130000Z</Calendar:ExceptionStartTime>
                                    </Calendar:Exception>
                                </Calendar:Exceptions>
                                <Calendar:Reminder>15</Calendar:Reminder>
                                <AirSyncBase:Body>
                                    <AirSyncBase:Data>lars</AirSyncBase:Data>
                                    <AirSyncBase:Type>1</AirSyncBase:Type>
                                </AirSyncBase:Body>
                            </ApplicationData>
                        </Change>
                    </Commands>
                </Collection>
            </Collections>
        </Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton calendar event tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test add contact
     */
    public function testParseSimpleXMLElement()
    {
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $event = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change->ApplicationData);
        
        #foreach ($event as $key => $value) {echo "$key: "; var_dump($value);} 
        
        $this->assertEquals(15, count($event));
        $this->assertEquals(0,  $event->allDayEvent); 
        $this->assertEquals(2,  $event->busyStatus);
        $this->assertEquals(0,  $event->sensitivity);
        $this->assertEquals(1,  $event->meetingStatus);
        $this->assertEquals(1,  count($event->attendees));
        $this->assertTrue(is_array($event->exceptions));
        $this->assertEquals(2,  count($event->exceptions), 'exceptions mismatch');
        $this->assertEquals(0,  $event->exceptions[0]->sensitivity, 'sensitivity of exception mismatch');
        $this->assertEquals(15, $event->reminder);
        $this->assertEquals('Repeat', $event->subject);
        $this->assertEquals('20101125T150537Z', $event->dtStamp->format("Ymd\THis\Z"));
        $this->assertEquals('20101123T160000Z', $event->endTime->format("Ymd\THis\Z"));
        $this->assertEquals('20101123T130000Z', $event->startTime->format("Ymd\THis\Z"));
        $this->assertEquals('20101128T225959Z', $event->recurrence->until->format("Ymd\THis\Z"));
        $this->assertEquals('6de7cb687964dc6eea109cd81750177979362217', $event->uID);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testAppendXmlData()
    {
        $imp                   = new DOMImplementation();
        
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDoc               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        
        $appData    = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $event = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change->ApplicationData);
        $event->body = new Syncroton_Model_EmailBody(array('data' => 'lars', 'type' => Syncroton_Model_EmailBody::TYPE_PLAINTEXT));
        
        $event->appendXML($appData, $this->_testDevice);
        
        #echo $testDoc->saveXML();
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('AirSyncBase', 'uri:AirSyncBase');
        $xpath->registerNamespace('Calendar', 'uri:Calendar');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Calendar:Subject');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('Repeat', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Calendar:Recurrence/Calendar:Until');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('20101128T225959Z', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Calendar:Exceptions/Calendar:Exception/Calendar:ExceptionStartTime');
        $this->assertEquals(2, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('20101125T130000Z', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Body');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
    
}
