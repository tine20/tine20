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
class Syncroton_Model_TaskTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_testXMLInput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
        <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Tasks="uri:Tasks">
            <Collections>
                <Collection>
                    <Class>Tasks</Class>
                    <SyncKey>17</SyncKey>
                    <CollectionId>tasks-root</CollectionId>
                    <DeletesAsMoves/>
                    <GetChanges/>
                    <WindowSize>50</WindowSize>
                    <Options><FilterType>8</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>2048</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>0</Conflict></Options>
                    <Commands>
                        <Change>
                            <ClientId>1</ClientId>
                            <ApplicationData>
                                <AirSyncBase:Body><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:Data>test beschreibung zeile 1&#13;
Zeile 2&#13;
Zeile 3</AirSyncBase:Data></AirSyncBase:Body>
                                <Tasks:Subject>Testaufgabe auf mfe</Tasks:Subject>
                                <Tasks:Importance>1</Tasks:Importance>
                                <Tasks:UtcDueDate>2010-11-28T22:59:00.000Z</Tasks:UtcDueDate>
                                <Tasks:DueDate>2010-11-28T23:59:00.000Z</Tasks:DueDate>
                                <Tasks:Complete>0</Tasks:Complete>
                                <Tasks:Sensitivity>0</Tasks:Sensitivity>
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
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton task model tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test add contact
     */
    public function testParseSimpleXMLElement()
    {
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $event = new Syncroton_Model_Task($xml->Collections->Collection->Commands->Change->ApplicationData);
        
        #foreach ($event as $key => $value) {echo "$key: "; var_dump($value);} 
        
        $this->assertEquals(6, count($event));
        $this->assertEquals(1,  $event->Importance); 
        $this->assertEquals(0,  $event->Complete);
        $this->assertEquals(0,  $event->Sensitivity);
        $this->assertEquals('Testaufgabe auf mfe', $event->Subject);
        $this->assertEquals('20101128T225900Z', $event->UtcDueDate->format("Ymd\THis\Z"));
        $this->assertEquals('20101128T235900Z', $event->DueDate->format("Ymd\THis\Z"));
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
        
        $appData = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        
        $xml   = new SimpleXMLElement($this->_testXMLInput);
        $event = new Syncroton_Model_Task($xml->Collections->Collection->Commands->Change->ApplicationData);
        
        $event->appendXML($appData);
        
        #echo $testDoc->saveXML(); 
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('Tasks', 'uri:Tasks');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Tasks:Subject');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('Testaufgabe auf mfe', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Tasks:Complete');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('0', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Tasks:DueDate');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('20101128T235900Z', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
    
}
