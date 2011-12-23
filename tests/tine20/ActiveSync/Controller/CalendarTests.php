<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     ActiveSync
 */
class ActiveSync_Controller_CalendarTests extends ActiveSync_TestCase
{
    /**
     * name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Calendar';
    
    protected $_controllerName = 'ActiveSync_Controller_Calendar';
    
    protected $_specialFolderName = 'calendar-root';
    
    protected $_class = ActiveSync_Controller::CLASS_CALENDAR;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_testXMLInput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:Calendar="uri:Calendar"><Collections><Collection><Class>Calendar</Class><SyncKey>9</SyncKey><CollectionId>41</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>5</FilterType></Options><Commands><Change><ServerId>6de7cb687964dc6eea109cd81750177979362217</ServerId><ApplicationData><Calendar:Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Calendar:Timezone><Calendar:AllDayEvent>0</Calendar:AllDayEvent><Calendar:BusyStatus>2</Calendar:BusyStatus><Calendar:DtStamp>20101125T150537Z</Calendar:DtStamp><Calendar:EndTime>20101123T160000Z</Calendar:EndTime><Calendar:Sensitivity>0</Calendar:Sensitivity><Calendar:Subject>Repeat</Calendar:Subject><Calendar:StartTime>20101123T130000Z</Calendar:StartTime><Calendar:UID>6de7cb687964dc6eea109cd81750177979362217</Calendar:UID><Calendar:MeetingStatus>1</Calendar:MeetingStatus><Calendar:Attendees><Calendar:Attendee><Calendar:Name>Lars Kneschke</Calendar:Name><Calendar:Email>lars@kneschke.de</Calendar:Email></Calendar:Attendee></Calendar:Attendees><Calendar:Recurrence><Calendar:Type>0</Calendar:Type><Calendar:Interval>1</Calendar:Interval><Calendar:Until>20101128T225959Z</Calendar:Until></Calendar:Recurrence><Calendar:Exceptions><Calendar:Exception><Calendar:Deleted>0</Calendar:Deleted><Calendar:ExceptionStartTime>20101125T130000Z</Calendar:ExceptionStartTime><Calendar:StartTime>20101125T140000Z</Calendar:StartTime><Calendar:EndTime>20101125T170000Z</Calendar:EndTime><Calendar:Subject>Repeat mal anders</Calendar:Subject><Calendar:BusyStatus>2</Calendar:BusyStatus><Calendar:AllDayEvent>0</Calendar:AllDayEvent></Calendar:Exception><Calendar:Exception><Calendar:Deleted>1</Calendar:Deleted><Calendar:ExceptionStartTime>20101124T130000Z</Calendar:ExceptionStartTime></Calendar:Exception></Calendar:Exceptions><Calendar:Reminder>15</Calendar:Reminder></ApplicationData></Change></Commands></Collection></Collections></Sync>';
    
    protected $_testXMLInput_palmPreV12 = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar"><Collections><Collection><Class>Calendar</Class><SyncKey>345</SyncKey><CollectionId>calendar-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type></AirSyncBase:BodyPreference></Options><Commands><Change><ServerId>3452c1dd3f21d1c12589e517f0c6a928137113a4</ServerId><ApplicationData><AirSyncBase:Body><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:Data>test beschreibung zeile 1&#13;
Zeile 2&#13;
Zeile 3</AirSyncBase:Data></AirSyncBase:Body><Calendar:Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Calendar:Timezone><Calendar:UID>3452c1dd3f21d1c12589e517f0c6a928137113a4</Calendar:UID><Calendar:DtStamp>20101104T070652Z</Calendar:DtStamp><Calendar:Subject>GM straussberg2</Calendar:Subject><Calendar:MeetingStatus>1</Calendar:MeetingStatus><Calendar:OrganizerName>Nadine </Calendar:OrganizerName><Calendar:OrganizerEmail>meine@mail.com</Calendar:OrganizerEmail><Calendar:Attendees><Calendar:Attendee><Calendar:Name>Nadine </Calendar:Name><Calendar:Email>meine@mail.com</Calendar:Email></Calendar:Attendee></Calendar:Attendees><Calendar:BusyStatus>0</Calendar:BusyStatus><Calendar:AllDayEvent>1</Calendar:AllDayEvent><Calendar:StartTime>20101103T230000Z</Calendar:StartTime><Calendar:EndTime>20101106T230000Z</Calendar:EndTime><Calendar:Sensitivity>0</Calendar:Sensitivity></ApplicationData></Change></Commands></Collection></Collections></Sync>';
    
    protected $_testXMLInput_DailyRepeatingEvent = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar"><Collections><Collection><Class>Calendar</Class><SyncKey>8</SyncKey><CollectionId>calendar-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><AirSyncBase:BodyPreference><AirSyncBase:Type>3</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize><AirSyncBase:AllOrNone>1</AirSyncBase:AllOrNone></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options><Commands><Add><ClientId>1073741902</ClientId><ApplicationData><Calendar:Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Calendar:Timezone><Calendar:AllDayEvent>0</Calendar:AllDayEvent><Calendar:BusyStatus>2</Calendar:BusyStatus><Calendar:DtStamp>20101224T082738Z</Calendar:DtStamp><Calendar:EndTime>20101220T100000Z</Calendar:EndTime><Calendar:MeetingStatus>0</Calendar:MeetingStatus><Calendar:Reminder>15</Calendar:Reminder><Calendar:Sensitivity>0</Calendar:Sensitivity><Calendar:Subject>Tdfffdd</Calendar:Subject><Calendar:StartTime>20101220T090000Z</Calendar:StartTime><Calendar:UID>040000008200E00074C5B7101A82E00800000000DCA959CF1C69F280D15448CF43450B301000000019B5FB15984956377D4EBEFE125A8EF6</Calendar:UID><Calendar:Recurrence><Calendar:Until>20101222T230000Z</Calendar:Until><Calendar:Interval>1</Calendar:Interval><Calendar:Type>0</Calendar:Type></Calendar:Recurrence></ApplicationData></Add></Commands></Collection></Collections></Sync>';    
    
    protected $_testXMLOutput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar"><Collections><Collection><Class>Calendar</Class><SyncKey>17</SyncKey><CollectionId>calendar-root</CollectionId><Commands><Change><ClientId>1</ClientId><ApplicationData/></Change></Commands></Collection></Collections></Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Controller Calendar Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {   	
        parent::setUp();	

        // replace email to make current user organizer and attendee
        $this->_testXMLInput = str_replace('lars@kneschke.de', Tinebase_Core::getUser()->accountEmailAddress, $this->_testXMLInput);
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->addMonth(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->addMonth(1)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'container_id'  => $this->_getContainerWithSyncGrant()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            'attendee'      => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id' => Tinebase_Core::getUser()->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'status' => Calendar_Model_Attender::STATUS_ACCEPTED
                )
            ))
        ));
        
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        $this->objects['event'] = $event;
        
        $event2MonthsBack = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->subMonth(2)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->subMonth(2)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'container_id'  => $this->_getContainerWithSyncGrant()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $event = Calendar_Controller_Event::getInstance()->create($event2MonthsBack);
        
        $this->objects['event2MonthsBack'] = $event;
        
        $eventDaily = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->addMonth(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-05-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->addMonth(1)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-05-25 19:00:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=' . Tinebase_DateTime::now()->addMonth(1)->addDay(6)->setHour(22)->setMinute(59)->setSecond(59)->toString(Tinebase_Record_Abstract::ISO8601LONG), //2009-05-31 22:59:59',
            'container_id'  => $this->_getContainerWithSyncGrant()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
                
        $eventDaily = Calendar_Controller_Event::getInstance()->create($eventDaily);
        
        // existing exceptions
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        // first deleted instance
        $deletedInstance1 = Calendar_Model_Rrule::computeNextOccurrence($eventDaily, $exceptions, $eventDaily->dtend);
        Calendar_Controller_Event::getInstance()->createRecurException($deletedInstance1, true);
        
        // second deleted instance
        $deletedInstance2 = Calendar_Model_Rrule::computeNextOccurrence($eventDaily, $exceptions, $deletedInstance1->dtend);
        Calendar_Controller_Event::getInstance()->createRecurException($deletedInstance2, true);
        
        // first exception instance
        $exceptionInstance1 = Calendar_Model_Rrule::computeNextOccurrence($eventDaily, $exceptions, $deletedInstance2->dtend);
        $exceptionInstance1->dtstart->addHour(2);
        $exceptionInstance1->dtend->addHour(2);
        $exceptionInstance1->summary = 'Test Exception 1';
        $exceptionInstance1 = Calendar_Controller_Event::getInstance()->createRecurException($exceptionInstance1);
        
        // first exception instance
        $exceptionInstance2 = Calendar_Model_Rrule::computeNextOccurrence($eventDaily, $exceptions, $exceptionInstance1->dtend);
        $exceptionInstance2->dtstart->addHour(3);
        $exceptionInstance2->dtend->addHour(3);
        $exceptionInstance2->summary = 'Test Exception 2';
        $exceptionInstance2 = Calendar_Controller_Event::getInstance()->createRecurException($exceptionInstance2);
        
        // reread event from database again
        $eventDaily = Calendar_Controller_Event::getInstance()->get($eventDaily);        
        #var_dump($eventDaily->toArray());
        
        $this->objects['eventDaily'] = $eventDaily;
        
        Tinebase_Core::getPreference('ActiveSync')->setValue(ActiveSync_Preference::DEFAULTCALENDAR, $this->_getContainerWithSyncGrant()->getId());
        
        ########### define test filter
        $filterBackend = new Tinebase_PersistentFilter_Backend_Sql();
        
        try {
            $filter = $filterBackend->getByProperty('Calendar Sync Test', 'name');
        } catch (Tinebase_Exception_NotFound $e) {
            $filter = new Tinebase_Model_PersistentFilter(array(
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'account_id'        => Tinebase_Core::getUser()->getId(),
                'model'             => 'Calendar_Model_EventFilter',
                'filters'           => array(array(
                    'field'     => 'container_id', 
                    'operator'  => 'equals', 
                    'value'     => $this->_getContainerWithSyncGrant()->getId()
                )),
                'name'              => 'Calendar Sync Test',
                'description'       => 'Created by unit test'
            ));
            
            $filter = $filterBackend->create($filter);
        }
        $this->objects['filter'] = $filter;
        
        
        ########### define test devices
        $palm = ActiveSync_Backend_DeviceTests::getTestDevice();
        $palm->devicetype   = 'palm';
        $palm->owner_id     = $this->_testUser->getId();
        $palm->calendarfilter_id = $this->objects['filter']->getId();
        $this->objects['devicePalm']   = ActiveSync_Controller_Device::getInstance()->create($palm);
        
        $iphone = ActiveSync_Backend_DeviceTests::getTestDevice();
        $iphone->devicetype = 'iphone';
        $iphone->owner_id   = $this->_testUser->getId();
        $iphone->calendarfilter_id = $this->objects['filter']->getId();
        $this->objects['deviceIPhone'] = ActiveSync_Controller_Device::getInstance()->create($iphone);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        Calendar_Controller_Event::getInstance()->delete(array($this->objects['event']->getId()));
        Calendar_Controller_Event::getInstance()->delete(array($this->objects['event2MonthsBack']->getId()));
        Calendar_Controller_Event::getInstance()->delete(array($this->objects['eventDaily']->getId()));
        
        ActiveSync_Controller_Device::getInstance()->delete($this->objects['devicePalm']);
        ActiveSync_Controller_Device::getInstance()->delete($this->objects['deviceIPhone']);
        
        $filterBackend = new Tinebase_PersistentFilter_Backend_Sql();
        $filterBackend->delete($this->objects['filter']->getId());
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testAppendXml()
    {
        $imp                   = new DOMImplementation();
        
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDom               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDom->formatOutput = true;
        $testDom->encoding     = 'utf-8';
        
        $collections    = $testDom->documentElement->appendChild($testDom->createElementNS('uri:AirSync', 'Collections'));
        $collection     = $collections->appendChild($testDom->createElementNS('uri:AirSync', 'Collection'));
        $commands       = $collection->appendChild($testDom->createElementNS('uri:AirSync', 'Commands'));
        $add            = $commands->appendChild($testDom->createElementNS('uri:AirSync', 'Add'));
        $appData        = $add->appendChild($testDom->createElementNS('uri:AirSync', 'ApplicationData'));
        
        
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
        
        $controller->appendXML($appData, null, $this->objects['event']->getId(), array());
        
        // namespace === uri:Calendar
        $endTime = $this->objects['event']->dtend->format("Ymd\THis") . 'Z';
        $this->assertEquals($endTime, @$testDom->getElementsByTagNameNS('uri:Calendar', 'EndTime')->item(0)->nodeValue, $testDom->saveXML());
        $this->assertEquals($this->objects['event']->uid, @$testDom->getElementsByTagNameNS('uri:Calendar', 'UID')->item(0)->nodeValue, $testDom->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDom);
    }
    
    /**
     * testAppendXml_allDayEvent
     */
    public function testAppendXml_allDayEvent()
    {
        $startDate = Tinebase_DateTime::now()->setTime(0,0,0)->addMonth(1);
        $endDate   = Tinebase_DateTime::now()->setTime(23,59,59)->addMonth(1)->addDay(3);
        
        $allDayEvent = new Calendar_Model_Event(array(
            'summary'       => 'Allday SyncTest',
            'dtstart'       => $startDate->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:00:00'
            'dtend'         => $endDate->toString(Tinebase_Record_Abstract::ISO8601LONG),   //'2009-04-25 23:59:59'
            'is_all_day_event' => true,
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=' . Tinebase_DateTime::now()->addMonth(1)->addDay(6)->setHour(22)->setMinute(59)->setSecond(59)->toString(Tinebase_Record_Abstract::ISO8601LONG),
            'container_id'  => $this->_getContainerWithSyncGrant()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $allDayEvent = Calendar_Controller_Event::getInstance()->create($allDayEvent);
        $this->objects['events']['allDayEvent'] = $allDayEvent;
        
        $dom     = $this->_getOutputDOMDocument();
        $appData = $dom->getElementsByTagNameNS('uri:AirSync', 'ApplicationData')->item(0);

        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM)); 
        
        $controller->appendXML($appData, null, $allDayEvent->getId(), array());
        
        #$dom->formatOutput = true; echo $dom->saveXML(); $dom->formatOutput = false;
        
        # ;'20110106T000000Z'
        $this->assertEquals($endDate->addSecond(1)->format('Ymd\THis\Z'), @$dom->getElementsByTagNameNS('uri:Calendar', 'EndTime')->item(0)->nodeValue, $dom->saveXML());
        // check that no Exceptions tag is set
        $this->assertEquals(0,                                            $dom->getElementsByTagNameNS('uri:Calendar', 'Exceptions')->length);
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testAppendXml_dailyEvent()
    {
        $imp                   = new DOMImplementation();
        
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDom               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDom->formatOutput = true;
        $testDom->encoding     = 'utf-8';
        $testDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:Calendar', 'uri:Calendar');
        
        $collections    = $testDom->documentElement->appendChild($testDom->createElementNS('uri:AirSync', 'Collections'));
        $collection     = $collections->appendChild($testDom->createElementNS('uri:AirSync', 'Collection'));
        $commands       = $collection->appendChild($testDom->createElementNS('uri:AirSync', 'Commands'));
        $add            = $commands->appendChild($testDom->createElementNS('uri:AirSync', 'Add'));
        $appData        = $add->appendChild($testDom->createElementNS('uri:AirSync', 'ApplicationData'));
        
        
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime());     
        
        $controller->appendXML($appData, null, $this->objects['eventDaily']->getId(), array());
        
        // namespace === uri:Calendar
        $this->assertEquals(ActiveSync_Controller_Calendar::RECUR_TYPE_DAILY, @$testDom->getElementsByTagNameNS('uri:Calendar', 'Type')->item(0)->nodeValue, $testDom->saveXML());
        $this->assertEquals(4, @$testDom->getElementsByTagNameNS('uri:Calendar', 'Exception')->length, $testDom->saveXML());
        $this->assertEquals(4, @$testDom->getElementsByTagNameNS('uri:Calendar', 'ExceptionStartTime')->length, $testDom->saveXML());
        $this->assertEquals(3, @$testDom->getElementsByTagNameNS('uri:Calendar', 'Subject')->length, $testDom->saveXML());
        
        $endTime = $this->objects['eventDaily']->dtend->format("Ymd\THis") . 'Z';
        $this->assertEquals($endTime, @$testDom->getElementsByTagNameNS('uri:Calendar', 'EndTime')->item(0)->nodeValue, $testDom->saveXML());
        
        $untilTime = Calendar_Model_Rrule::getRruleFromString($this->objects['eventDaily']->rrule)->until->format("Ymd\THis") . 'Z';
        $this->assertEquals($untilTime, @$testDom->getElementsByTagNameNS('uri:Calendar', 'Until')->item(0)->nodeValue, $testDom->saveXML());
        
    }
    
    /**
     * test update of record
     * 
     * @return Tinebase_Record_Abstract
     */
    public function testChangeEntryInBackend()
    {
        $record = $this->testAddEntryToBackend();
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
    
        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        $record = $controller->change($this->_getContainerWithSyncGrant()->getId(), $record->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
    
        $this->_validateAddEntryToBackend($record);
    
        return $record;
    }
        
    /**
     * test get list all record ids not older than 2 weeks back
     */
    public function testGetServerEntries2WeeksBack()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
    
        $records = $controller->getServerEntries($this->_specialFolderName, ActiveSync_Command_Sync::FILTER_2_WEEKS_BACK);

        $this->assertNotContains($this->objects['event2MonthsBack']->getId(), $records, 'found event 2 months back');
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::_validateGetServerEntries()
     */
    protected function _validateGetServerEntries(Tinebase_Record_Abstract $_record)
    {
        $this->objects['events'][] = $_record;
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        $records = $controller->getServerEntries($this->_specialFolderName, ActiveSync_Command_Sync::FILTER_NOTHING);
        
        $this->assertContains($_record->getId(), $records, 'event not found');
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testConvertToTine20Model()
    {
        if (empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $this->markTestSkipped('current user has no email address');
        }
        
        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        
        $event = $controller->toTineModel($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $this->assertEquals('Repeat'             , $event->summary);
        $this->assertEquals(2                    , count($event->exdate));
        $this->assertEquals('2010-11-23 12:45:00', $event->alarms[0]->alarm_time->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * testConvertToTine20Model_fromPalm
     */
    public function testConvertToTine20Model_fromPalm()
    {
        $xml = simplexml_import_dom($this->_getInputDOMDocument($this->_testXMLInput_palmPreV12));
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        
        $event = $controller->toTineModel($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        //var_dump($event->toArray());
        
        $this->assertEquals('2010-11-03 23:00:00', $event->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2010-11-06 22:59:59', $event->dtend->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertTrue(!!$event->is_all_day_event);
        $this->assertEquals("test beschreibung zeile 1\r\nZeile 2\r\nZeile 3", $event->description);
    }
    
    /**
     * testConvertToTine20Model_dailyRepeatingEvent
     */
    public function testConvertToTine20Model_dailyRepeatingEvent()
    {
        $xml = simplexml_import_dom($this->_getInputDOMDocument($this->_testXMLInput_DailyRepeatingEvent));
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        
        $event = $controller->toTineModel($xml->Collections->Collection->Commands->Add[0]->ApplicationData);
        
        $this->assertEquals('2010-12-20 09:00:00', $event->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2010-12-20 10:00:00', $event->dtend->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2010-12-23 22:59:59', $event->rrule->until->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::_validateAddEntryToBackend()
     */
    protected function _validateAddEntryToBackend(Tinebase_Record_Abstract $_record)
    {
        $this->objects['events'][] = $_record;
        
        #var_dump($_record->toArray());
        
        $this->assertEquals('Repeat', $_record->summary);
        $this->assertEquals('FREQ=DAILY;INTERVAL=1;UNTIL=2010-11-28 22:59:59', $_record->rrule);
        $this->assertEquals(2,        count($_record->exdate));
    }
    
    /**
     * test search events
     */
    public function testSearch()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));

        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        
        $record = $controller->add($this->_getContainerWithSyncGrant()->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        $this->objects['events'][] = $record;
        
        $event = $controller->search($this->_specialFolderName, $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $this->assertEquals(1       , count($event));
        $this->assertEquals('Repeat', $event[0]->summary);
    }
    
    /**
     * test get multiple
     * 
     * @todo check alarm
     */
    public function testGetMultiple()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        
        $record = $controller->add($this->_getContainerWithSyncGrant()->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        $this->objects['events'][] = $record;
        $events = $controller->getMultiple(array($record->getId()));
        
        $this->assertEquals(1       , count($events));
        $this->assertEquals('Repeat', $events[0]->summary);
    }
    
    /**
     * test search events (unsyncable)
     * 
     * TODO finish this -> assertion fails atm because the event is found even if it is in an unsyncable folder and has no attendees (but 1 exdate)
     */
    public function testUnsyncableSearch()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));

        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        
        $record = $controller->add($this->_getContainerWithoutSyncGrant()->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        $record->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $record->attendee = NULL;

        $this->objects['events'][] = Calendar_Controller_MSEventFacade::getInstance()->update($record);
        
        $events = $controller->search($this->_specialFolderName, $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $this->assertEquals(0, count($events));
    }
    
    /**
     * test supported folders
     */
    public function testGetSupportedFolders()
    {
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
        
        $syncable = $this->_getContainerWithSyncGrant();
        $supportedFolders = $controller->getSupportedFolders();
        
        //$this->assertEquals(1, count($supportedFolders));
        $this->assertTrue(isset($supportedFolders[$syncable->getId()]));
    }
}
