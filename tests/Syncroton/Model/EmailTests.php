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
class Syncroton_Model_EmailTests extends PHPUnit_Framework_TestCase
{
    protected $_testXMLInput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
        <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Email="uri:Email" xmlns:Tasks="uri:Tasks">
            <Collections>
                <Collection>
                    <SyncKey>17</SyncKey>
                    <CollectionId>a130b7462fde72c7d6215ce32226e1794d631fa8</CollectionId>
                    <DeletesAsMoves>1</DeletesAsMoves>
                    <GetChanges/>
                    <WindowSize>5</WindowSize>
                    <Options><FilterType>5</FilterType><BodyPreference xmlns="uri:AirSyncBase"><Type>2</Type><TruncationSize>200000</TruncationSize></BodyPreference></Options>
                    <Commands>
                        <Change>
                            <ServerId>193556ef7ce9ad9a6c3997b51b1c9e646c6cf373</ServerId>
                            <ApplicationData>
                                <Read xmlns="uri:Email">1</Read>
                                <Flag xmlns="uri:Email">
                                    <Status xmlns="uri:Email">2</Status>
                                    <FlagType xmlns="uri:Email">for Follow Up</FlagType>
                                    <StartDate xmlns="uri:Tasks">2009-02-24T08:00:00.000Z</StartDate>
                                    <UtcStartDate xmlns="uri:Tasks">2009-02-24T08:00:00.000Z</UtcStartDate>
                                    <DueDate xmlns="uri:Tasks">2009-02-25T12:00:00.000Z</DueDate>
                                    <UtcDueDate xmlns="uri:Tasks">2009-02-25T12:00:00.000Z</UtcDueDate>
                                    <ReminderSet xmlns="uri:Tasks">0</ReminderSet>
                                </Flag>
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
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton email model tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test add contact
     */
    public function testParseSimpleXMLElement()
    {
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $email = new Syncroton_Model_Email($xml->Collections->Collection->Commands->Change->ApplicationData);
    
        #foreach ($event as $key => $value) {echo "$key: "; var_dump($value);}
    
        $this->assertEquals(2, count($email));
        $this->assertEquals(1, $email->Read);
        $this->assertTrue($email->Flag instanceof Syncroton_Model_EmailFlag);
        
        // validate flags        
        $this->assertEquals('0', $email->Flag->ReminderSet);
        $this->assertEquals('20090224T080000Z', $email->Flag->StartDate->format("Ymd\THis\Z"), 'StartDate');
        $this->assertEquals('20090224T080000Z', $email->Flag->UtcStartDate->format("Ymd\THis\Z"), 'UtcStartDate');
        $this->assertEquals('20090225T120000Z', $email->Flag->DueDate->format("Ymd\THis\Z"), 'DueDate');
        $this->assertEquals('20090225T120000Z', $email->Flag->UtcDueDate->format("Ymd\THis\Z"), 'UtcDueDate');
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
        
        $email = new Syncroton_Model_Email(array(
            'AccountId'    => 'FooBar',
            'Attachments'  => array(
                new Syncroton_Model_EmailAttachment(array(
                    'FileReference' => '12345abcd',
                    'UmAttOrder'    => 1
                ))
            ),
            'Categories'   => array('123', '456'),
            'Cc'           => 'l.kneschke@metaways.de',
            'DateReceived' => new DateTime('2012-03-21 14:00:00', new DateTimeZone('UTC')), 
            'Flag'         => new Syncroton_Model_EmailFlag(array(
                'Status'       => Syncroton_Model_EmailFlag::STATUS_COMPLETE,
                'ReminderTime' => new DateTime('2012-04-21 14:00:00', new DateTimeZone('UTC'))
            )),
            'From'         => 'k.kneschke@metaways.de',
            'Subject'      => 'Test Subject',
            'To'           => 'j.kneschke@metaways.de',
            'Read'         => 1,
            'Body'         => new Syncroton_Model_EmailBody(array(
                'Type'         => Syncroton_Model_EmailBody::TYPE_HTML,
                'Data'         => 'Hallo <br>',
                'EstimatedDataSize' => 1234,
                'Truncated'    => 1
            ))
        ));
        
        $email->appendXML($appData);
        
        #echo $testDoc->saveXML(); 
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('AirSyncBase', 'uri:AirSyncBase');
        $xpath->registerNamespace('Email', 'uri:Email');
        $xpath->registerNamespace('Email2', 'uri:Email2');
        $xpath->registerNamespace('Tasks', 'uri:Tasks');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Email2:AccountId');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('FooBar', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Email:DateReceived');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('2012-03-21T14:00:00.000Z', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Attachments/AirSyncBase:Attachment/AirSyncBase:FileReference');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('12345abcd', $nodes->item(0)->nodeValue, $testDoc->saveXML());

        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Attachments/AirSyncBase:Attachment/Email2:UmAttOrder');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('1', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Body/AirSyncBase:Type');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals(Syncroton_Model_EmailBody::TYPE_HTML, $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Email:Flag/Tasks:ReminderTime');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('2012-04-21T14:00:00.000Z', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
}
