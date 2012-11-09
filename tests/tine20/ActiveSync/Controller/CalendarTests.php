<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    
    protected $_class = Syncroton_Data_Factory::CLASS_CALENDAR;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_testXMLInput = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:Calendar="uri:Calendar">
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
                            <Calendar:DtStamp>20121125T150537Z</Calendar:DtStamp>
                            <Calendar:EndTime>20121123T160000Z</Calendar:EndTime>
                            <Calendar:Sensitivity>0</Calendar:Sensitivity>
                            <Calendar:Subject>Repeat</Calendar:Subject>
                            <Calendar:StartTime>20121123T130000Z</Calendar:StartTime>
                            <Calendar:UID>6de7cb687964dc6eea109cd81750177979362217</Calendar:UID>
                            <Calendar:MeetingStatus>1</Calendar:MeetingStatus>
                            <Calendar:Attendees>
                                <Calendar:Attendee>
                                    <Calendar:Name>Lars Kneschke</Calendar:Name>
                                    <Calendar:Email>lars@kneschke.de</Calendar:Email>
                                </Calendar:Attendee>
                            </Calendar:Attendees>
                            <Calendar:Recurrence>
                                <Calendar:Type>0</Calendar:Type><Calendar:Interval>1</Calendar:Interval><Calendar:Until>20121128T225959Z</Calendar:Until>
                            </Calendar:Recurrence>
                            <Calendar:Exceptions>
                                <Calendar:Exception>
                                    <Calendar:Deleted>0</Calendar:Deleted><Calendar:ExceptionStartTime>20121125T130000Z</Calendar:ExceptionStartTime><Calendar:StartTime>20121125T140000Z</Calendar:StartTime><Calendar:EndTime>20121125T170000Z</Calendar:EndTime><Calendar:Subject>Repeat mal anders</Calendar:Subject><Calendar:BusyStatus>2</Calendar:BusyStatus><Calendar:AllDayEvent>0</Calendar:AllDayEvent>
                                </Calendar:Exception>
                                <Calendar:Exception>
                                    <Calendar:Deleted>1</Calendar:Deleted><Calendar:ExceptionStartTime>20121124T130000Z</Calendar:ExceptionStartTime></Calendar:Exception>
                                </Calendar:Exceptions>
                            <Calendar:Reminder>15</Calendar:Reminder>
                            <Body xmlns="uri:AirSyncBase"><Type>1</Type><Data>Hello</Data></Body>
                        </ApplicationData>
                    </Change>
                </Commands>
            </Collection>
        </Collections>
    </Sync>';
    
    protected $_testXMLInput_palmPreV12 = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
        <Collections>
            <Collection>
                <Class>Calendar</Class>
                <SyncKey>345</SyncKey>
                <CollectionId>calendar-root</CollectionId>
                <DeletesAsMoves/>
                <GetChanges/>
                <WindowSize>50</WindowSize>
                <Options>
                    <FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type></AirSyncBase:BodyPreference>
                </Options>
                <Commands>
                    <Change>
                        <ServerId>3452c1dd3f21d1c12589e517f0c6a928137113a4</ServerId>
                        <ApplicationData>
                            <AirSyncBase:Body>
                                <AirSyncBase:Type>1</AirSyncBase:Type>
                                <AirSyncBase:Data>test beschreibung zeile 1&#13;
Zeile 2&#13;
Zeile 3</AirSyncBase:Data>
                            </AirSyncBase:Body>
                            <Calendar:Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Calendar:Timezone>
                            <Calendar:UID>3452c1dd3f21d1c12589e517f0c6a928137113a4</Calendar:UID>
                            <Calendar:DtStamp>20101104T070652Z</Calendar:DtStamp>
                            <Calendar:Subject>GM straussberg2</Calendar:Subject>
                            <Calendar:MeetingStatus>1</Calendar:MeetingStatus>
                            <Calendar:OrganizerName>Nadine Blau</Calendar:OrganizerName>
                            <Calendar:OrganizerEmail>meine@mail.com</Calendar:OrganizerEmail>
                            <Calendar:Attendees><Calendar:Attendee><Calendar:Name>Nadine Blau</Calendar:Name><Calendar:Email>meine@mail.com</Calendar:Email></Calendar:Attendee></Calendar:Attendees>
                            <Calendar:BusyStatus>0</Calendar:BusyStatus>
                            <Calendar:AllDayEvent>1</Calendar:AllDayEvent>
                            <Calendar:StartTime>20101103T230000Z</Calendar:StartTime>
                            <Calendar:EndTime>20101106T230000Z</Calendar:EndTime>
                            <Calendar:Sensitivity>0</Calendar:Sensitivity>
                        </ApplicationData>
                    </Change>
                </Commands>
            </Collection>
        </Collections>
    </Sync>';
    
    protected $_testXMLInput_DailyRepeatingEvent = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
        <Collections>
            <Collection>
                <Class>Calendar</Class>
                <SyncKey>8</SyncKey>
                <CollectionId>calendar-root</CollectionId>
                <DeletesAsMoves/><GetChanges/>
                <WindowSize>100</WindowSize>
                <Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><AirSyncBase:BodyPreference><AirSyncBase:Type>3</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize><AirSyncBase:AllOrNone>1</AirSyncBase:AllOrNone></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                <Commands>
                    <Add>
                        <ClientId>1073741902</ClientId>
                        <ApplicationData><Calendar:Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Calendar:Timezone>
                            <Calendar:AllDayEvent>0</Calendar:AllDayEvent>
                            <Calendar:BusyStatus>2</Calendar:BusyStatus>
                            <Calendar:DtStamp>20101224T082738Z</Calendar:DtStamp>
                            <Calendar:EndTime>20101220T100000Z</Calendar:EndTime>
                            <Calendar:MeetingStatus>0</Calendar:MeetingStatus>
                            <Calendar:Reminder>15</Calendar:Reminder>
                            <Calendar:Sensitivity>0</Calendar:Sensitivity>
                            <Calendar:Subject>Tdfffdd</Calendar:Subject>
                            <Calendar:StartTime>20101220T090000Z</Calendar:StartTime>
                            <Calendar:UID>040000008200E00074C5B7101A82E00800000000DCA959CF1C69F280D15448CF43450B301000000019B5FB15984956377D4EBEFE125A8EF6</Calendar:UID>
                            <Calendar:Recurrence>
                                <Calendar:Until>20101222T230000Z</Calendar:Until>
                                <Calendar:Interval>1</Calendar:Interval>
                                <Calendar:Type>0</Calendar:Type>
                            </Calendar:Recurrence>
                        </ApplicationData>
                    </Add>
                </Commands>
            </Collection>
        </Collections>
    </Sync>';

    protected $_testXMLInput_SamsungGalaxyStatus = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
        <Collections>
            <Collection>
                <Class>Calendar</Class>
                <SyncKey>20</SyncKey>
                <CollectionId>calendar-root</CollectionId>
                <DeletesAsMoves/>
                <GetChanges/>
                <WindowSize>5</WindowSize>
                <Options><FilterType>4</FilterType><BodyPreference xmlns="uri:AirSyncBase"><Type>1</Type><TruncationSize>400000</TruncationSize></BodyPreference></Options>
                <Commands>
                    <Change>
                        <ServerId>b2b0593bc21c89e5d07edb7b5caa56ff7e243e92</ServerId>
                        <ApplicationData>
                            <Timezone xmlns="uri:Calendar">xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAEAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Timezone>
                            <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
                            <StartTime xmlns="uri:Calendar">20120229T120000Z</StartTime>
                            <EndTime xmlns="uri:Calendar">20120229T140000Z</EndTime>
                            <DtStamp xmlns="uri:Calendar">20120228T093153Z</DtStamp>
                            <Subject xmlns="uri:Calendar">testtermin</Subject>
                            <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
                            <OrganizerEmail xmlns="uri:Calendar">user2@example.com</OrganizerEmail>
                            <Reminder xmlns="uri:Calendar">30</Reminder>
                            <UID xmlns="uri:Calendar">2b4a047d3f71b4d47d2d3907f4a27d70f650cd30</UID>
                            <Attendees xmlns="uri:Calendar">
                                <Attendee><Name>user1@example.com</Name><Email>user1@example.com</Email><AttendeeType>1</AttendeeType></Attendee>
                                <Attendee><Name>user2@example.com</Name><Email>user2@example.com</Email><AttendeeType>1</AttendeeType></Attendee>
                                <Attendee><Name>user3@example.com</Name><Email>user3@example.com</Email><AttendeeType>1</AttendeeType></Attendee>
                            </Attendees>
                            <BusyStatus xmlns="uri:Calendar">0</BusyStatus>
                            <MeetingStatus xmlns="uri:Calendar">3</MeetingStatus>
                        </ApplicationData>
                    </Change>
                </Commands>
            </Collection>
        </Collections>
    </Sync>';
    
    protected $_testXMLInputOutlook13 = '<?xml version="1.0" encoding="utf-8"?>
    <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
      <Collections>
        <Collection>
          <SyncKey>5</SyncKey>
          <CollectionId>calendar-root</CollectionId>
          <DeletesAsMoves>0</DeletesAsMoves>
          <GetChanges>0</GetChanges>
          <WindowSize>512</WindowSize>
          <Options>
            <FilterType>0</FilterType>
            <BodyPreference xmlns="uri:AirSyncBase">
              <Type>2</Type>
              <AllOrNone>1</AllOrNone>
            </BodyPreference>
          </Options>
          <Commands>
            <Change>
              <ServerId>d8a9cecb073736aa78c95a249f383123cc03365b</ServerId>
              <ApplicationData>
                <Timezone xmlns="uri:Calendar">xP///1cALgAgAEUAdQByAG8AcABlACAAUwB0AGEAbgBkAGEAcgBkACAAVABpAG0AZQAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAFcALgAgAEUAdQByAG8AcABlACAARABhAHkAbABpAGcAaAB0ACAAVABpAG0AZQAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Timezone>
                <DtStamp xmlns="uri:Calendar">20120914T125503Z</DtStamp>
                <StartTime xmlns="uri:Calendar">20120914T140000Z</StartTime>
                <Subject xmlns="uri:Calendar">TEST9</Subject>
                <UID xmlns="uri:Calendar">fd4b68a5871aa0082284e9bc5664a35da207b75b</UID>
                <OrganizerName xmlns="uri:Calendar">unittest@tine20.org</OrganizerName>
                <OrganizerEmail xmlns="uri:Calendar">unittest@tine20.org</OrganizerEmail>
                <Attendees xmlns="uri:Calendar">
                  <Attendee>
                    <Email>unittest@tine20.org</Email>
                    <Name>Admin Account, Tine 2.0</Name>
                    <AttendeeStatus>3</AttendeeStatus>
                    <AttendeeType>1</AttendeeType>
                  </Attendee>
                  <Attendee>
                    <Email>pwulf@tine20.org</Email>
                    <Name>Wulf, Paul</Name>
                    <AttendeeStatus>0</AttendeeStatus>
                    <AttendeeType>1</AttendeeType>
                  </Attendee>
                </Attendees>
                <EndTime xmlns="uri:Calendar">20120914T143000Z</EndTime>
                <Recurrence xmlns="uri:Calendar">
                  <Type>0</Type>
                  <Interval>1</Interval>
                  <Occurrences>3</Occurrences>
                </Recurrence>
                <Exceptions xmlns="uri:Calendar">
                  <Exception>
                    <ExceptionStartTime>20120916T140000Z</ExceptionStartTime>
                    <StartTime>20120916T140000Z</StartTime>
                    <DtStamp>20120914T125503Z</DtStamp>
                    <EndTime>20120916T143000Z</EndTime>
                    <Sensitivity>0</Sensitivity>
                    <BusyStatus>2</BusyStatus>
                    <Attendees>
                      <Attendee>
                        <Email>unittest@tine20.org</Email>
                        <Name>Admin Account, Tine 2.0</Name>
                        <AttendeeStatus>0</AttendeeStatus>
                        <AttendeeType>1</AttendeeType>
                      </Attendee>
                      <Attendee>
                        <Email>unittest@tine20.org</Email>
                        <Name>Admin Account, Tine 2.0</Name>
                        <AttendeeStatus>3</AttendeeStatus>
                        <AttendeeType>1</AttendeeType>
                      </Attendee>
                      <Attendee>
                        <Email>pwulf@tine20.org</Email>
                        <Name>Wulf, Paul</Name>
                        <AttendeeStatus>4</AttendeeStatus>
                        <AttendeeType>1</AttendeeType>
                      </Attendee>
                    </Attendees>
                  </Exception>
                </Exceptions>
                <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
                <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
                <MeetingStatus xmlns="uri:Calendar">1</MeetingStatus>
                <ResponseRequested xmlns="uri:Calendar">1</ResponseRequested>
              </ApplicationData>
            </Change>
          </Commands>
        </Collection>
      </Collections>
    </Sync>';
    
    protected $_testXMLMeetingResponse = '<?xml version="1.0" encoding="utf-8"?>
    <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <MeetingResponse xmlns="uri:MeetingResponse" xmlns:Search="uri:Search">
        <Request>
            <UserResponse>2</UserResponse>
            <CollectionId>17</CollectionId>
            <RequestId>f0c79775b6b44be446f91187e24566aa1c5d06ab</RequestId>
            <InstanceId>20121125T130000Z</InstanceId>
        </Request>
    </MeetingResponse>';
    
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
    }
    
    public function testCreateEntry($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS), Tinebase_DateTime::now());
    
        $xml = new SimpleXMLElement($this->_testXMLInput);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
    
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
    
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
    
        #echo '----------------' . PHP_EOL; foreach ($syncrotonEvent as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals(0,         $syncrotonEvent->allDayEvent, 'alldayEvent');
        $this->assertEquals(2,         $syncrotonEvent->busyStatus);
        $this->assertEquals('Repeat',  $syncrotonEvent->subject);
        $this->assertEquals(15,        $syncrotonEvent->reminder);
        $this->assertTrue($syncrotonEvent->endTime instanceof DateTime);
        $this->assertTrue($syncrotonEvent->startTime instanceof DateTime);
        $this->assertEquals('20121123T160000Z', $syncrotonEvent->endTime->format('Ymd\THis\Z'));
        $this->assertEquals('20121123T130000Z', $syncrotonEvent->startTime->format('Ymd\THis\Z'));
        $this->assertEquals(1, count($syncrotonEvent->attendees), 'event: ' . var_export($syncrotonEvent->attendees, TRUE));
        $this->assertEquals(Tinebase_Core::getUser()->accountEmailAddress, $syncrotonEvent->attendees[0]->email, 'event: ' . var_export($syncrotonEvent, TRUE));
        
        //Body
        $this->assertTrue($syncrotonEvent->body instanceof Syncroton_Model_EmailBody);
        $this->assertEquals('Hello', $syncrotonEvent->body->data);
        
        // Recurrence
        $this->assertTrue($syncrotonEvent->recurrence instanceof Syncroton_Model_EventRecurrence);
        $this->assertEquals(Syncroton_Model_EventRecurrence::TYPE_DAILY, $syncrotonEvent->recurrence->type);
        $this->assertEquals(1, $syncrotonEvent->recurrence->interval);
        $this->assertTrue($syncrotonEvent->recurrence->until instanceof DateTime);
        $this->assertEquals('20121128T225959Z', $syncrotonEvent->recurrence->until->format('Ymd\THis\Z'));
        
        // Exceptions
        $this->assertEquals(2, count($syncrotonEvent->exceptions));
        $this->assertTrue($syncrotonEvent->exceptions[0] instanceof Syncroton_Model_EventException);
        $this->assertEquals(0, $syncrotonEvent->exceptions[0]->deleted, 'exception deleted');
        $this->assertEquals('Repeat mal anders', $syncrotonEvent->exceptions[0]->subject);
        $this->assertEquals('20121125T130000Z', $syncrotonEvent->exceptions[0]->exceptionStartTime->format('Ymd\THis\Z'));
        $this->assertEquals('20121125T170000Z', $syncrotonEvent->exceptions[0]->endTime->format('Ymd\THis\Z'));
        $this->assertEquals('20121125T140000Z', $syncrotonEvent->exceptions[0]->startTime->format('Ymd\THis\Z'));
        
        $this->assertEquals(1, $syncrotonEvent->exceptions[1]->deleted);
        $this->assertEquals('20121124T130000Z', $syncrotonEvent->exceptions[1]->exceptionStartTime->format('Ymd\THis\Z'));
        
        return array($serverId, $syncrotonEvent);
    }
    
    public function testCreateEntryPalmPreV12($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
    
        $xml = new SimpleXMLElement($this->_testXMLInput_palmPreV12);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
    
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
    
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
    
        #echo '----------------' . PHP_EOL; foreach ($syncrotonEvent as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals(1,         $syncrotonEvent->allDayEvent);
        $this->assertTrue($syncrotonEvent->endTime instanceof DateTime);
        $this->assertTrue($syncrotonEvent->startTime instanceof DateTime);
        $this->assertEquals('20101106T230000Z', $syncrotonEvent->endTime->format('Ymd\THis\Z'));
        $this->assertEquals('20101103T230000Z', $syncrotonEvent->startTime->format('Ymd\THis\Z'));
    
        return array($serverId, $syncrotonEvent);
    }
    
    public function testCreateEntryDailyRepeating($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
    
        $xml = new SimpleXMLElement($this->_testXMLInput_DailyRepeatingEvent);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Add[0]->ApplicationData);
    
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
    
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
    
        #echo '----------------' . PHP_EOL; foreach ($syncrotonEvent as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals(0, $syncrotonEvent->allDayEvent);
        $this->assertTrue($syncrotonEvent->endTime instanceof DateTime);
        $this->assertTrue($syncrotonEvent->startTime instanceof DateTime);
        $this->assertEquals('20101220T100000Z', $syncrotonEvent->endTime->format('Ymd\THis\Z'));
        $this->assertEquals('20101220T090000Z', $syncrotonEvent->startTime->format('Ymd\THis\Z'));
    
        // Recurrence
        $this->assertTrue($syncrotonEvent->recurrence instanceof Syncroton_Model_EventRecurrence);
        $this->assertEquals(Syncroton_Model_EventRecurrence::TYPE_DAILY, $syncrotonEvent->recurrence->type);
        $this->assertEquals(1, $syncrotonEvent->recurrence->interval);
        $this->assertTrue($syncrotonEvent->recurrence->until instanceof DateTime);
        $this->assertEquals('20101223T225959Z', $syncrotonEvent->recurrence->until->format('Ymd\THis\Z'));
        
        return array($serverId, $syncrotonEvent);
    }
    
    public function testCreateEntrySamsungGalaxyStatus($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_SMASUNGGALAXYS2), Tinebase_DateTime::now());
    
        $xml = new SimpleXMLElement($this->_testXMLInput_SamsungGalaxyStatus);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
    
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
    
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
    
        #echo '----------------' . PHP_EOL; foreach ($syncrotonEvent as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals(Syncroton_Model_Event::BUSY_STATUS_BUSY, $syncrotonEvent->busyStatus);
        
        return array($serverId, $syncrotonEvent);
    }
    
    public function testCreateEntryOutlook13($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS), Tinebase_DateTime::now());
    
        $xml = new SimpleXMLElement($this->_testXMLInputOutlook13);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
    
        $tine20Event = $controller->toTineModel($syncrotonEvent);
//         $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
    
        $this->assertFalse(!!$tine20Event->exdate[0]->is_deleted);
    }
    
    public function testUpdateEntry($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
    
        list($serverId, $syncrotonEvent) = $this->testCreateEntry($syncrotonFolder);
    
        unset($syncrotonEvent->recurrence);
        unset($syncrotonEvent->exceptions);
        
        // need to creaate new controller to set new sync timestamp for concurrency handling
        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->last_modified_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $syncTimestamp);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEvent);
        
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
    
        $this->assertFalse($syncrotonEvent->recurrence instanceof Syncroton_Model_EventRecurrence);
    
        return array($serverId, $syncrotonEvent);
    }
    
    public function testRecurEventExceptionFilters($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        
        list($serverId, $syncrotonEvent) = $this->testCreateEntry($syncrotonFolder);
        
        // remove testuser as attendee
        $eventBackend = new Calendar_Backend_Sql();
        $exception = $eventBackend->getByProperty($syncrotonEvent->uID . '-' . $syncrotonEvent->exceptions[0]->exceptionStartTime->format(Tinebase_Record_Abstract::ISO8601LONG), 'recurid');
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($exception->attendee);
        $eventBackend->deleteAttendee(array($ownAttendee->getId()));
        
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        // assert fallout by filter
        $this->assertTrue((bool) $syncrotonEvent->exceptions[0]->deleted);
        $this->assertTrue((bool) $syncrotonEvent->exceptions[1]->deleted);
    }
    
    public function testStatusUpdate($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        
        list($serverId, $syncrotonEvent) = $this->testCreateEntry($syncrotonFolder);
        
        // transfer event to other user
        $rwright = array_value('rwright', $this->_personas = Zend_Registry::get('personas'));
        $eventBackend = new Calendar_Backend_Sql();
        $eventBackend->updateMultiple($eventBackend->getMultipleByProperty($syncrotonEvent->uID, 'uid')->id, array(
            'container_id'  => Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $rwright->getId()),
            'organizer'     => $rwright->contact_id
        ));
        
        $syncrotonEvent->exceptions[0]->busyStatus = 1;
        $syncrotonEvent->exceptions[0]->subject = 'do not update';
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEvent);
        
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $event = Calendar_Controller_Event::getInstance()->get($serverId);
        
        // how to validate container / it's not present in syncroton event?
        $this->assertEquals(1, $syncrotonEvent->exceptions[0]->busyStatus);
        $this->assertNotEquals('do not update', $syncrotonEvent->exceptions[0]->subject);
    }
    
    public function testMeetingResponse()
    {
        $syncrotonFolder = $this->testCreateFolder();
        
        list($serverId, $event) = $this->testCreateEntry($syncrotonFolder);
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        
        $XMLMeetingResponse = $this->_testXMLMeetingResponse;
        
        $XMLMeetingResponse = str_replace('<CollectionId>17</CollectionId>', '<CollectionId>' . $syncrotonFolder->serverId . '</CollectionId>', $XMLMeetingResponse);
        $XMLMeetingResponse = str_replace('<RequestId>f0c79775b6b44be446f91187e24566aa1c5d06ab</RequestId>', '<RequestId>' . $serverId . '</RequestId>', $XMLMeetingResponse);
        $XMLMeetingResponse = str_replace('<InstanceId>20121125T130000Z</InstanceId>', '', $XMLMeetingResponse);
        
        $xml = new SimpleXMLElement($XMLMeetingResponse);
        
        $meetingResponse = new Syncroton_Model_MeetingResponse($xml->Request[0]);
        
        $eventId = $controller->setAttendeeStatus($meetingResponse);
        
        $event = Calendar_Controller_Event::getInstance()->get($serverId);
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee);
        
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $ownAttendee->status);
    }
    
    public function testMeetingResponseWithExistingInstanceId()
    {
        $syncrotonFolder = $this->testCreateFolder();
        
        list($serverId, $event) = $this->testCreateEntry($syncrotonFolder);
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $XMLMeetingResponse = $this->_testXMLMeetingResponse;
        
        $XMLMeetingResponse = str_replace('<CollectionId>17</CollectionId>', '<CollectionId>' . $syncrotonFolder->serverId . '</CollectionId>', $XMLMeetingResponse);
        $XMLMeetingResponse = str_replace('<RequestId>f0c79775b6b44be446f91187e24566aa1c5d06ab</RequestId>', '<RequestId>' . $serverId . '</RequestId>', $XMLMeetingResponse);
        
        $xml = new SimpleXMLElement($XMLMeetingResponse);
        
        $meetingResponse = new Syncroton_Model_MeetingResponse($xml->Request[0]);
        
        $eventId = $controller->setAttendeeStatus($meetingResponse);
        
        $event = Calendar_Controller_MSEventFacade::getInstance()->get($serverId);
        $instance = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($instance->attendee);
        
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $ownAttendee->status);
    }
    
    public function testMeetingResponseWithNewInstanceId()
    {
        $syncrotonFolder = $this->testCreateFolder();
        
        list($serverId, $event) = $this->testCreateEntry($syncrotonFolder);
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $XMLMeetingResponse = $this->_testXMLMeetingResponse;
        
        $XMLMeetingResponse = str_replace('<CollectionId>17</CollectionId>', '<CollectionId>' . $syncrotonFolder->serverId . '</CollectionId>', $XMLMeetingResponse);
        $XMLMeetingResponse = str_replace('<RequestId>f0c79775b6b44be446f91187e24566aa1c5d06ab</RequestId>', '<RequestId>' . $serverId . '</RequestId>', $XMLMeetingResponse);
        $XMLMeetingResponse = str_replace('<InstanceId>20121125T130000Z</InstanceId>', '<InstanceId>20121126T130000Z</InstanceId>', $XMLMeetingResponse);
        
        $xml = new SimpleXMLElement($XMLMeetingResponse);
        
        $meetingResponse = new Syncroton_Model_MeetingResponse($xml->Request[0]);
        
        $eventId = $controller->setAttendeeStatus($meetingResponse);
        
        $event = Calendar_Controller_MSEventFacade::getInstance()->get($serverId);
        $event->exdate->sort('dtstart', 'DESC');
        $instance = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($instance->attendee);
        
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $ownAttendee->status);
    }
    
    /**
     * test search events (unsyncable)
     * 
     * TODO finish this -> assertion fails atm because the event is found even if it is in an unsyncable folder and has no attendees (but 1 exdate)
     */
    public function _testUnsyncableSearch()
    {
        $this->markTestSkipped();
        
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));

        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        
        $record = $controller->createEntry($this->_getContainerWithoutSyncGrant()->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        $record->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $record->attendee = NULL;

        $this->objects['events'][] = Calendar_Controller_MSEventFacade::getInstance()->update($record);
        
        $events = $controller->search($this->_specialFolderName, $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $this->assertEquals(0, count($events));
    }
    
    /**
     * testEventWithTags
     * 
     * @see 0007346: events with tags are not synced
     */
    public function testEventWithTags()
    {
        $event = ActiveSync_TestCase::getTestEvent();
        $event->tags = array(array(
            'name' => 'test tag',
            'type' => Tinebase_Model_Tag::TYPE_PERSONAL
        ));
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        $syncrotonEvent = $controller->toSyncrotonModel($event);
        
        $this->assertTrue(is_array($syncrotonEvent->categories));
        $this->assertTrue(in_array('test tag', $syncrotonEvent->categories), 'tag not found in categories: ' . print_r($syncrotonEvent->categories, TRUE));
    }
}
