<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Calendar_Frontend_ActiveSync
 * 
 * @package     Calendar
 */
class Calendar_Frontend_ActiveSyncTest extends ActiveSync_Controller_ControllerTest
{
    /**
     * name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Calendar';
    
    protected $_controllerName = 'Calendar_Frontend_ActiveSync';
    
    protected $_class = Syncroton_Data_Factory::CLASS_CALENDAR;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_testXMLInput;
    
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

    protected $_testConcurrentUpdate = '<?xml version="1.0" encoding="utf-8"?>
    <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
      <Collections>
        <Collection>
          <SyncKey>26</SyncKey>
          <CollectionId>calendar-root</CollectionId>
          <GetChanges/>
          <WindowSize>25</WindowSize>
          <Options>
            <FilterType>4</FilterType>
            <BodyPreference xmlns="uri:AirSyncBase">
              <Type>1</Type>
              <TruncationSize>32768</TruncationSize>
            </BodyPreference>
          </Options>
          <Commands>
            <Change>
              <ServerId>b2cb312482f641185b205591f40f78a47ea9f667</ServerId>
              <ApplicationData>
                <Timezone xmlns="uri:Calendar">xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAMAAAAAAAAAxP///w==</Timezone>
                <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
                <BusyStatus xmlns="uri:Calendar">2</BusyStatus>
                <DtStamp xmlns="uri:Calendar">20131106T072501Z</DtStamp>
                <EndTime xmlns="uri:Calendar">20140113T140000Z</EndTime>
                <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
                <Subject xmlns="uri:Calendar">Mittagspause</Subject>
                <StartTime xmlns="uri:Calendar">20140113T120000Z</StartTime>
                <UID xmlns="uri:Calendar">1a5010a3d46316a2709cda40683911f95a856665</UID>
                <MeetingStatus xmlns="uri:Calendar">1</MeetingStatus>
                <Attendees xmlns="uri:Calendar">
                  <Attendee>
                    <Name>Unittest, Tine</Name>
                    <Email>unittest@tine20.org</Email>
                    <AttendeeType>1</AttendeeType>
                  </Attendee>
                </Attendees>
                <Recurrence xmlns="uri:Calendar">
                  <Type>1</Type>
                  <Interval>1</Interval>
                  <DayOfWeek>62</DayOfWeek>
                  <FirstDayOfWeek>1</FirstDayOfWeek>
                </Recurrence>
                <Exceptions xmlns="uri:Calendar">
                  <Exception>
                    <Body xmlns="uri:AirSyncBase">
                      <Type>1</Type>
                      <Data/>
                    </Body>
                    <Deleted>0</Deleted>
                    <ExceptionStartTime>20141203T120000Z</ExceptionStartTime>
                    <StartTime>20141203T140000Z</StartTime>
                    <EndTime>20141203T153000Z</EndTime>
                    <Subject>Mittagspause</Subject>
                    <BusyStatus>2</BusyStatus>
                    <AllDayEvent>0</AllDayEvent>
                    <Attendees>
                      <Attendee>
                        <Name>Unittest, Tine</Name>
                        <Email>unittest@tine20.org</Email>
                        <AttendeeType>1</AttendeeType>
                      </Attendee>
                    </Attendees>
                  </Exception>
                  <Exception>
                    <Body xmlns="uri:AirSyncBase">
                      <Type>1</Type>
                      <Data/>
                    </Body>
                    <Deleted>0</Deleted>
                    <ExceptionStartTime>20140625T110000Z</ExceptionStartTime>
                    <StartTime>20140625T130000Z</StartTime>
                    <EndTime>20140625T150000Z</EndTime>
                    <Subject>Mittagspause</Subject>
                    <BusyStatus>2</BusyStatus>
                    <AllDayEvent>0</AllDayEvent>
                    <Attendees>
                      <Attendee>
                        <Name>Unittest, Tine</Name>
                        <Email>unittest@tine20.org</Email>
                        <AttendeeType>1</AttendeeType>
                      </Attendee>
                    </Attendees>
                  </Exception>
                </Exceptions>
              </ApplicationData>
            </Change>
          </Commands>
        </Collection>
      </Collections>
    </Sync>';

    protected $_testConcurrentUpdateUpdate = '<?xml version="1.0" encoding="utf-8"?>
    <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
    <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
      <Collections>
        <Collection>
          <SyncKey>26</SyncKey>
          <CollectionId>calendar-root</CollectionId>
          <GetChanges/>
          <WindowSize>25</WindowSize>
          <Options>
            <FilterType>4</FilterType>
            <BodyPreference xmlns="uri:AirSyncBase">
              <Type>1</Type>
              <TruncationSize>32768</TruncationSize>
            </BodyPreference>
          </Options>
          <Commands>
            <Change>
              <ServerId>b2cb312482f641185b205591f40f78a47ea9f667</ServerId>
              <ApplicationData>
                <Timezone xmlns="uri:Calendar">xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAMAAAAAAAAAxP///w==</Timezone>
                <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
                <BusyStatus xmlns="uri:Calendar">2</BusyStatus>
                <DtStamp xmlns="uri:Calendar">20131106T072501Z</DtStamp>
                <EndTime xmlns="uri:Calendar">20140113T140000Z</EndTime>
                <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
                <Subject xmlns="uri:Calendar">Mittagspause</Subject>
                <StartTime xmlns="uri:Calendar">20140113T120000Z</StartTime>
                <UID xmlns="uri:Calendar">1a5010a3d46316a2709cda40683911f95a856665</UID>
                <MeetingStatus xmlns="uri:Calendar">1</MeetingStatus>
                <Attendees xmlns="uri:Calendar">
                  <Attendee>
                    <Name>Unittest, Tine</Name>
                    <Email>unittest@tine20.org</Email>
                    <AttendeeType>1</AttendeeType>
                  </Attendee>
                </Attendees>
                <Recurrence xmlns="uri:Calendar">
                  <Type>1</Type>
                  <Interval>1</Interval>
                  <DayOfWeek>62</DayOfWeek>
                  <FirstDayOfWeek>1</FirstDayOfWeek>
                </Recurrence>
                <Exceptions xmlns="uri:Calendar">
                  <Exception>
                    <Body xmlns="uri:AirSyncBase">
                      <Type>1</Type>
                      <Data/>
                    </Body>
                    <Deleted>0</Deleted>
                    <ExceptionStartTime>20141203T120000Z</ExceptionStartTime>
                    <StartTime>20141203T140000Z</StartTime>
                    <EndTime>20141203T153000Z</EndTime>
                    <Subject>Mittagspause</Subject>
                    <BusyStatus>2</BusyStatus>
                    <AllDayEvent>0</AllDayEvent>
                    <Attendees>
                      <Attendee>
                        <Name>Unittest, Tine</Name>
                        <Email>unittest@tine20.org</Email>
                        <AttendeeType>1</AttendeeType>
                      </Attendee>
                    </Attendees>
                  </Exception>
                  <Exception>
                    <Body xmlns="uri:AirSyncBase">
                      <Type>1</Type>
                      <Data/>
                    </Body>
                    <Deleted>0</Deleted>
                    <ExceptionStartTime>20140625T110000Z</ExceptionStartTime>
                    <StartTime>20140625T130000Z</StartTime>
                    <EndTime>20140625T150000Z</EndTime>
                    <Subject>Abendessen</Subject>
                    <BusyStatus>2</BusyStatus>
                    <AllDayEvent>0</AllDayEvent>
                    <Attendees>
                      <Attendee>
                        <Name>Unittest, Tine</Name>
                        <Email>unittest@tine20.org</Email>
                        <AttendeeType>1</AttendeeType>
                      </Attendee>
                    </Attendees>
                  </Exception>
                </Exceptions>
              </ApplicationData>
            </Change>
          </Commands>
        </Collection>
      </Collections>
    </Sync>';

    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        
        Calendar_Controller_Event::getInstance()->doContainerACLChecks(true);

        $this->_testXMLInput = $this->_loadFile('event_with_attendee.xml');
    }

    protected function _loadFile($filename)
    {
        $xml = file_get_contents(__DIR__ . '/files/' . $filename);

        // replace email to make current user organizer and attendee
        $email = $this->_getEmailAddress();

        $xml = str_replace(array(
            'lars@kneschke.de',
            'unittest@tine20.org',
        ), $email, $xml);

        return $xml;
    }

    /**
     * (non-PHPdoc)
     * @see ActiveSync_TestCase::testCreateEntry()
     */
    public function testCreateEntry($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS), Tinebase_DateTime::now());
        
        $now = Tinebase_DateTime::now();
        $thisYear = $now->format('Y');
        $xmlString = preg_replace('/2012/', $thisYear, $this->_testXMLInput);
        $xml = new SimpleXMLElement($xmlString);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);

        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $this->assertEquals(0,         $syncrotonEvent->allDayEvent, 'alldayEvent');
        $this->assertEquals(2,         $syncrotonEvent->busyStatus);
        $this->assertEquals('Repeat',  $syncrotonEvent->subject);
        $this->assertEquals(15,        $syncrotonEvent->reminder);
        $this->assertEquals(2,         $syncrotonEvent->sensitivity);
        $this->assertTrue($syncrotonEvent->endTime instanceof DateTime);
        $this->assertTrue($syncrotonEvent->startTime instanceof DateTime);
        $this->assertEquals($thisYear . '1123T160000Z', $syncrotonEvent->endTime->format('Ymd\THis\Z'));
        $this->assertEquals($thisYear . '1123T130000Z', $syncrotonEvent->startTime->format('Ymd\THis\Z'));
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
        $this->assertEquals($thisYear . '1128T225959Z', $syncrotonEvent->recurrence->until->format('Ymd\THis\Z'));
        
        // Exceptions
        $this->assertEquals(2, count($syncrotonEvent->exceptions));
        $this->assertTrue($syncrotonEvent->exceptions[0] instanceof Syncroton_Model_EventException);
        $this->assertFalse(isset($syncrotonEvent->exceptions[0]->deleted), 'exception deleted with value of 0 should not be set');
        $this->assertEquals('Repeat mal anders', $syncrotonEvent->exceptions[0]->subject);
        $this->assertEquals($thisYear . '1125T130000Z', $syncrotonEvent->exceptions[0]->exceptionStartTime->format('Ymd\THis\Z'));
        $this->assertEquals($thisYear . '1125T170000Z', $syncrotonEvent->exceptions[0]->endTime->format('Ymd\THis\Z'));
        $this->assertEquals($thisYear . '1125T140000Z', $syncrotonEvent->exceptions[0]->startTime->format('Ymd\THis\Z'));
        
        $this->assertEquals(1, $syncrotonEvent->exceptions[1]->deleted);
        $this->assertEquals($thisYear . '1124T130000Z', $syncrotonEvent->exceptions[1]->exceptionStartTime->format('Ymd\THis\Z'));
        
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
        $this->assertEquals(0, $syncrotonEvent->sensitivity);
        $this->assertTrue($syncrotonEvent->endTime instanceof DateTime);
        $this->assertTrue($syncrotonEvent->startTime instanceof DateTime);
        $this->assertEquals('20101220T100000Z', $syncrotonEvent->endTime->format('Ymd\THis\Z'));
        $this->assertEquals('20101220T090000Z', $syncrotonEvent->startTime->format('Ymd\THis\Z'));
    
        // Recurrence
        $this->assertTrue($syncrotonEvent->recurrence instanceof Syncroton_Model_EventRecurrence);
        $this->assertEquals(Syncroton_Model_EventRecurrence::TYPE_DAILY, $syncrotonEvent->recurrence->type);
        $this->assertEquals(1, $syncrotonEvent->recurrence->interval);
        $this->assertTrue($syncrotonEvent->recurrence->until instanceof DateTime);
        $this->assertEquals('20101222T230000Z', $syncrotonEvent->recurrence->until->format('Ymd\THis\Z'));
        
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
    
    public function testConcurrentUpdate($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        
        $xml = new SimpleXMLElement($this->_testConcurrentUpdate);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
        
        $event = Calendar_Controller_Event::getInstance()->get($serverId);
        
        // change exception #2
        $exceptions = Calendar_Controller_Event::getInstance()->getRecurExceptions($event);
        foreach ($exceptions as $exception) {
            if ($exception->dtstart->toString() == '2014-06-25 13:00:00') {
                $exception->summary = 'Fruehstueck';
                Calendar_Controller_Event::getInstance()->update($exceptions[1]);
            }
        }
        
        $xmlUpdate = new SimpleXMLElement($this->_testConcurrentUpdateUpdate);
        $syncrotonEvent = new Syncroton_Model_Event($xmlUpdate->Collections->Collection->Commands->Change[0]->ApplicationData);

        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->creation_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $syncTimestamp);
        
        // update exception - should not throw concurrency exception
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEvent);
        
        $event = Calendar_Controller_Event::getInstance()->get($serverId);
        $exceptions = Calendar_Controller_Event::getInstance()->getRecurExceptions($event);
        
        foreach ($exceptions as $exception) {
            if ($exception->dtstart->toString() == '2014-06-25 13:00:00') {
                $this->assertEquals('Abendessen', $exception->summary, print_r($exceptions[1]->toArray(), true));
            } else {
                $this->assertEquals('Mittagspause', $exception->summary, print_r($exceptions[1]->toArray(), true));
            }
        }
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
        
        // need to create new controller to set new sync timestamp for concurrency handling
        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->last_modified_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $syncTimestamp);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEvent);
        
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
    
        $this->assertFalse($syncrotonEvent->recurrence instanceof Syncroton_Model_EventRecurrence);
    
        return array($serverId, $syncrotonEvent);
    }

    /**
     * testUpdateExdate
     * 
     * @see 0010417: Exdate update does not update seq of base event
     */
    public function testUpdateExdate()
    {
        list($serverId, $syncrotonEvent) = $this->testCreateEntry();
        
        $event = Calendar_Controller_Event::getInstance()->get($serverId);
        $container = Tinebase_Container::getInstance()->get($event->container_id);
        
        $exception = Calendar_Controller_Event::getInstance()->getRecurExceptions($event)->getFirstRecord();
        $exception->dtstart->subHour(1);
        Calendar_Controller_Event::getInstance()->update($exception);
        
        $baseEventAfterExdateUpdate = Calendar_Controller_Event::getInstance()->get($serverId);
        $containerAfterExdateUpdate = Tinebase_Container::getInstance()->get($event->container_id);
        $this->assertEquals($event->seq + 1, $baseEventAfterExdateUpdate->seq, 'base sequence should be updated');
        $this->assertEquals($container->content_seq + 2, $containerAfterExdateUpdate->content_seq, 'container sequence should be updated');
    }

    /**
     * testDeleteExdate
     *
     * @see : Exdate delete does not update seq of base event
     */
    public function testDeleteExdate()
    {
        list($serverId, $syncrotonEvent) = $this->testCreateEntry();

        $event = Calendar_Controller_Event::getInstance()->get($serverId);
        $container = Tinebase_Container::getInstance()->get($event->container_id);

        $exception = Calendar_Controller_Event::getInstance()->getRecurExceptions($event)->getFirstRecord();
        Calendar_Controller_Event::getInstance()->delete($exception);

        $baseEventAfterExdateDelete = Calendar_Controller_Event::getInstance()->get($serverId);
        $containerAfterExdateDelete = Tinebase_Container::getInstance()->get($event->container_id);
        $this->assertGreaterThan($event->seq, $baseEventAfterExdateDelete->seq, 'base sequence should be updated');
        $this->assertGreaterThan($container->content_seq, $containerAfterExdateDelete->content_seq, 'container sequence should be updated');
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

    public function testRecurEventFirstInstanceException($syncrotonFolder = null)
    {
        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }

        $xmlString = $this->_loadFile('repeating_with_first_instance_exception.xml');
        $xml = new SimpleXMLElement($xmlString);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Add[0]->ApplicationData);

        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());

        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);

        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);

        $this->assertEquals('First Instance Exception', $syncrotonEvent->exceptions[0]->subject);
        $this->assertCount(1, $syncrotonEvent->exceptions);

        return array($serverId, $syncrotonEvent);
    }

    public function testStatusUpdate($syncrotonFolder = null)
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('only working in non-AD setups');
        }

        if ($syncrotonFolder === null) {
            $syncrotonFolder = $this->testCreateFolder();
        }
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        
        list($serverId, $syncrotonEvent) = $this->testCreateEntry($syncrotonFolder);
        
        // transfer event to other user
        $rwright = Tinebase_Helper::array_value('rwright', $this->_personas = Zend_Registry::get('personas'));
        $eventBackend = new Calendar_Backend_Sql();
        $eventBackend->updateMultiple($eventBackend->getMultipleByProperty($syncrotonEvent->uID, 'uid')->id, array(
            'container_id'  => Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $rwright->getId()),
            'organizer'     => $rwright->contact_id
        ));
        
        $syncrotonEvent->exceptions[0]->busyStatus = 1;
        $syncrotonEvent->exceptions[0]->subject = 'do not update';
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEvent);
        
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $event = Calendar_Controller_MSEventFacade::getInstance()->get($serverId);
        $attendee = $event->exdate->getFirstRecord()->attendee->getFirstRecord();

        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $attendee->status);
        $this->assertNotEquals('do not update', $event->summary);
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
        
        $thisYear = Tinebase_DateTime::now()->format('Y');
        $XMLMeetingResponse = str_replace('2012', $thisYear, $XMLMeetingResponse);
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
        
        $thisYear = Tinebase_DateTime::now()->format('Y');
        $XMLMeetingResponse = str_replace('2012', $thisYear, $XMLMeetingResponse);
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

    /**
     * testEventTimezone
     *
     * @see 0007346: events with tags are not synced
     */
    public function testEventTimezone()
    {
        $event = ActiveSync_TestCase::getTestEvent();
        $event->originator_tz =  'Europe/Moscow';
        $event = Calendar_Controller_Event::getInstance()->create($event);

        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        $syncrotonEvent = $controller->toSyncrotonModel($event);

        $this->assertEquals($syncrotonEvent->timezone, 'TP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==');

    }
    
    /**
     * testEventWithAlarmToSyncrotonModel
     */
    public function testEventWithAlarmToSyncrotonModel()
    {
        $event = ActiveSync_TestCase::getTestEvent();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(array(
            'minutes_before' => 15,
            'alarm_time'     => $event->dtstart->getClone()->subMinute(15),
            'model'          => 'Calendar_Model_Event'
        )));
        
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        $syncrotonEvent = $controller->toSyncrotonModel($event);
        
        $this->assertEquals(15, $syncrotonEvent->reminder);
    }
    
    /**
     * @see 0007346 - organizer perspective for alarms
     */
    public function testEventWithAlarmToTine20Model()
    {
        $syncrotonFolder = $this->testCreateFolder();
        list($serverId, $event) = $this->testCreateEntry($syncrotonFolder);
        
        $alarm = Calendar_Controller_Event::getInstance()->get($serverId)->alarms->getFirstRecord();
        $this->assertFalse(!!$alarm->getOption('attendee'));
    }
    
    /**
     * calendar has different grant handling
     * @see 0007450: shared calendars of other users (iOS)
     */
    public function testGetAllFoldersWithContainerSyncFilter()
    {
        $device = $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE);
        $controller = Syncroton_Data_Factory::factory($this->_class, $device, new Tinebase_DateTime(null, null, 'de_DE'));
    
        $folderA = $this->testCreateFolder(); // personal of test user
        
        $sclever = Tinebase_Helper::array_value('sclever', Zend_Registry::get('personas'));
        /** @var Tinebase_Model_Container $folderB */
        $folderB = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $sclever->getId());
        $folderBContainer = Tinebase_Container::getInstance()->getContainerById($folderB);

        // have syncGerant for sclever
        Tinebase_Container::getInstance()->setGrants($folderBContainer, new Tinebase_Record_RecordSet($folderBContainer->getGrantClass(), array(
            array(
                'account_id'    => $sclever->getId(),
                'account_type'  => 'user',
                Tinebase_Model_Grants::GRANT_ADMIN    => true,
            ),
            array(
                'account_id'    => Tinebase_Core::getUser()->getId(),
                'account_type'  => 'user',
                Tinebase_Model_Grants::GRANT_READ     => true,
                Tinebase_Model_Grants::GRANT_SYNC     => true,
        ))), TRUE);
        
        $syncFilter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => array(
                $folderA->serverId,
                $folderB,
            ))
        ));
        
        $syncFavorite = Tinebase_PersistentFilter::getInstance()->create(new Tinebase_Model_PersistentFilter(array(
            'application_id'        => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'account_id'            => Tinebase_Core::getUser()->getId(),
            'model'                 => 'Calendar_Model_EventFilter',
            'filters'               => $syncFilter,
            'name'                  => 'testSyncFilter',
            'description'           => 'test two folders'
        )));
        
        $device->calendarfilterId = $syncFavorite->getId();

        $allSyncrotonFolders = $controller->getAllFolders();
        
        $defaultFolderId = Tinebase_Core::getPreference('Calendar')->{Calendar_Preference::DEFAULTCALENDAR};
        
        foreach ($allSyncrotonFolders as $syncrotonFolder) {
            $this->assertTrue($syncrotonFolder->serverId == $defaultFolderId 
                ? $syncrotonFolder->type === Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR
                : $syncrotonFolder->type === Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR_USER_CREATED
            );
        }
        
        $this->assertEquals(2, count($allSyncrotonFolders));
        $this->assertArrayHasKey($folderA->serverId, $allSyncrotonFolders);
        $this->assertArrayHasKey($folderB, $allSyncrotonFolders);
    }
    
    /**
     * validate that current is not removed from the event after creating and updating event from phone 
     * 
     * @link https://forge.tine20.org/mantisbt/view.php?id=7606
     */
    public function testUpdateAfterCreate()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_SMASUNGGALAXYS2), Tinebase_DateTime::now());
    
        $xml = new SimpleXMLElement('
            <ApplicationData xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
              <Timezone xmlns="uri:Calendar">xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAEAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Timezone>
              <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
              <StartTime xmlns="uri:Calendar">20130124T070000Z</StartTime>
              <EndTime xmlns="uri:Calendar">20130124T080000Z</EndTime>
              <DtStamp xmlns="uri:Calendar">20121228T001432Z</DtStamp>
              <Subject xmlns="uri:Calendar">Neues Ereignis</Subject>
              <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
              <Body xmlns="uri:AirSyncBase">
                <Type>1</Type>
                <Data></Data>
              </Body>
              <OrganizerEmail xmlns="uri:Calendar">rep@tine.de</OrganizerEmail>
              <UID xmlns="uri:Calendar">24f59962-7243-4938-8b8c-a81fda46977e</UID>
              <BusyStatus xmlns="uri:Calendar">2</BusyStatus>
              <MeetingStatus xmlns="uri:Calendar">0</MeetingStatus>
            </ApplicationData>
        ');
        $syncrotonEvent = new Syncroton_Model_Event($xml);
    
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
    
        #var_dump(Calendar_Controller_Event::getInstance()->get($serverId)->toArray());
        $this->assertEquals(1, count(Calendar_Controller_Event::getInstance()->get($serverId)->attendee), 'count after create');
        
        $xml = new SimpleXMLElement('
            <ApplicationData xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Calendar="uri:Calendar">
                <Timezone xmlns="uri:Calendar">xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAEAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Timezone>
                <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
                <StartTime xmlns="uri:Calendar">20130124T070000Z</StartTime>
                <EndTime xmlns="uri:Calendar">20130124T080000Z</EndTime>
                <DtStamp xmlns="uri:Calendar">20121228T001526Z</DtStamp>
                <Subject xmlns="uri:Calendar">Neues Ereignis geändert</Subject>
                <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
                <Body xmlns="uri:AirSyncBase">
                  <Type>1</Type>
                  <Data></Data>
                </Body>
                <OrganizerEmail xmlns="uri:Calendar">rep@tine.de</OrganizerEmail>
                <UID xmlns="uri:Calendar">24f59962-7243-4938-8b8c-a81fda46977e</UID>
                <BusyStatus xmlns="uri:Calendar">2</BusyStatus>
                <MeetingStatus xmlns="uri:Calendar">0</MeetingStatus>
            </ApplicationData>
        ');
        $syncrotonEvent = new Syncroton_Model_Event($xml);
        
        // need to creaate new controller to set new sync timestamp for concurrency handling
        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->creation_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_SMASUNGGALAXYS2), $syncTimestamp);
        
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEvent);
        
        #var_dump(Calendar_Controller_Event::getInstance()->get($serverId)->toArray());
        $this->assertEquals(1, count(Calendar_Controller_Event::getInstance()->get($serverId)->attendee), 'count after update');
    }

    /**
     * testAddAlarm
     * 
     * @see 0008230: added alarm to event on iOS 6.1 -> description removed
     */
    public function testAddAlarm()
    {
        $syncrotonFolder = $this->testCreateFolder();
        $event = $this->_createEvent();

        $xml = new SimpleXMLElement('
          <ApplicationData>
            <Timezone xmlns="uri:Calendar">xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAMAAAAAAAAAxP///w==</Timezone>
            <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
            <BusyStatus xmlns="uri:Calendar">2</BusyStatus>
            <DtStamp xmlns="uri:Calendar">20131021T142015Z</DtStamp>
            <EndTime xmlns="uri:Calendar">20131022T170000Z</EndTime>
            <Reminder xmlns="uri:Calendar">60</Reminder>
            <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
            <Subject xmlns="uri:Calendar">testtermin</Subject>
            <StartTime xmlns="uri:Calendar">20131022T160000Z</StartTime>
            <UID xmlns="uri:Calendar">' . $event->uid . '</UID>
            <MeetingStatus xmlns="uri:Calendar">1</MeetingStatus>
            <Attendees xmlns="uri:Calendar">
              <Attendee>
                <Name>' . Tinebase_Core::getUser()->accountDisplayName . '</Name>
                <Email>' . Tinebase_Core::getUser()->accountEmailAddress . '</Email>
                <AttendeeType>1</AttendeeType>
              </Attendee>
            </Attendees>
          </ApplicationData>
        ');
        $syncrotonEvent = new Syncroton_Model_Event($xml);
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $event->creation_time);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $event->getId(), $syncrotonEvent);
        
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($serverId);
        
        $this->assertEquals(1, count($updatedEvent->alarms));
        $this->assertEquals($event->description, $updatedEvent->description, 'description mismatch: ' . print_r($updatedEvent->toArray(), true));
    }

    /**
     * @param string $attendeeStatus
     * @param boolean $addScleverAttendee
     * @return Tinebase_Record_Interface
     */
    protected function _createEvent($attendeeStatus = Calendar_Model_Attender::STATUS_ACCEPTED, $addScleverAttendee = false)
    {
        $event = ActiveSync_TestCase::getTestEvent(null, $attendeeStatus, $addScleverAttendee);
        $event->summary = 'testtermin';
        $event->description = 'some text';
        $event->dtstart = new Tinebase_DateTime('2013-10-22 16:00:00');
        $event->dtend = new Tinebase_DateTime('2013-10-22 17:00:00');
        return Calendar_Controller_Event::getInstance()->create($event);
    }

    public function testGetEntriesIPhone()
    {
        $syncrotonFolder = $this->testCreateFolder();
        
        $syncrotonFolder2 = $this->testCreateFolder();
        
        //make $syncrotonFolder2 the default
        Tinebase_Core::getPreference('Calendar')->setValue(Calendar_Preference::DEFAULTCALENDAR, $syncrotonFolder2->serverId);
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        
        $now = Tinebase_DateTime::now();
        $thisYear = $now->format('Y');
        $xmlString = preg_replace('/2012/', $thisYear, $this->_testXMLInput);
        $xml = new SimpleXMLElement($xmlString);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);
        
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        
        $this->assertEmpty($syncrotonEvent->attendees, 'Events attendees found in folder which is not the default calendar');
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        
        $now = Tinebase_DateTime::now();
        $thisYear = $now->format('Y');
        $xmlString = preg_replace('/2012/', $thisYear, $this->_testXMLInput);
        $xml = new SimpleXMLElement($xmlString);
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $serverId = $controller->createEntry($syncrotonFolder2->serverId, $syncrotonEvent);
        
        $syncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder2->serverId)), $serverId);
        
        $this->assertNotEmpty($syncrotonEvent->attendees, 'Events attendees not found in default calendar');
    }

    public function testUpdateEntriesIPhone($syncrotonFolder = null)
    {
        // ensure folder is default
        $syncrotonFolder = $syncrotonFolder ? $syncrotonFolder : $this->testCreateFolder();
        Tinebase_Core::getPreference('Calendar')->setValue(Calendar_Preference::DEFAULTCALENDAR, $syncrotonFolder->serverId);

        // create event with external attendee in default calendar
        $xml = new SimpleXMLElement(file_get_contents(__DIR__ . '/files/event_with_attendee.xml'));
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);

        // assert external attendee is present
        $syncrotonEventtoUpdate = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        $this->assertEquals(2, count($syncrotonEventtoUpdate->attendees), 'event: ' . var_export($syncrotonEventtoUpdate->attendees, TRUE));

        // update event in non default folder
        $syncrotonEventtoUpdate->exceptions[0]->subject = 'update';
        $syncrotonEventtoUpdate->exceptions[0]->startTime->addHour(1);
        $syncrotonEventtoUpdate->exceptions[0]->endTime->addHour(1);
        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->last_modified_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $syncTimestamp);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEventtoUpdate);

        // assert update in default folder
        $updatedSyncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        $this->assertEquals('update', $updatedSyncrotonEvent->exceptions[0]->subject);
        $this->assertEquals('2012-11-25 15:00:00', $updatedSyncrotonEvent->exceptions[0]->startTime->toString());
        $this->assertEquals(2, count($updatedSyncrotonEvent->attendees), 'event: ' . var_export($updatedSyncrotonEvent->attendees, TRUE));

        return array($serverId, $updatedSyncrotonEvent);
    }

    /**
     * testUpdateEntriesIPhoneNonDefaultFolder
     *
     * for iOS devices we need to suppress attendee during sync for non default folder (see foldertype in foldersync)
     * iOS can only have one default folder
     */
    public function testUpdateEntriesIPhoneNonDefaultFolder()
    {
        // create event in default folder
        $syncrotonFolder = $this->testCreateFolder();
        list($serverId, $syncrotonEvent) = $this->testUpdateEntriesIPhone($syncrotonFolder);

        // create new default folder2
        $syncrotonFolder2 = $this->testCreateFolder();
        Tinebase_Core::getPreference('Calendar')->setValue(Calendar_Preference::DEFAULTCALENDAR, $syncrotonFolder2->serverId);

        // load event in non default folder
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        $syncrotonEventtoUpdate = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        $this->assertEmpty($syncrotonEventtoUpdate->attendees, 'attendee in non default folders must be removed for ios');

        // update event in non default folder
        $syncrotonEventtoUpdate->exceptions[0]->subject = 'update2';
        $syncrotonEventtoUpdate->exceptions[0]->startTime->addHour(1);
        $syncrotonEventtoUpdate->exceptions[0]->endTime->addHour(1);

        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->last_modified_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $syncTimestamp);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEventtoUpdate);

        // assert update from default folder
        $updatedSyncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder2->serverId)), $serverId);
        $this->assertEquals('update2', $updatedSyncrotonEvent->exceptions[0]->subject);
        $this->assertNotEquals($syncrotonEvent->exceptions[0]->startTime->toString(), $updatedSyncrotonEvent->exceptions[0]->startTime->toString());
        $this->assertEquals($syncrotonEventtoUpdate->exceptions[0]->startTime->toString(), $updatedSyncrotonEvent->exceptions[0]->startTime->toString());
        $this->assertEquals(2, count($updatedSyncrotonEvent->attendees), 'event: ' . var_export($updatedSyncrotonEvent->attendees, TRUE));
    }

    public function testCreateUpdateGroupEvents()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('only working in non-AD setups');
        }

        $syncrotonFolder = $this->testCreateFolder();
        Tinebase_Core::getPreference('Calendar')->setValue(Calendar_Preference::DEFAULTCALENDAR, $syncrotonFolder->serverId);

        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $defaultUserGroupMembers = Tinebase_Group::getInstance()->getGroupMembers($defaultUserGroup->getId());

        // create event with default user group (without explicit groupmembers)
        $xml = new SimpleXMLElement(str_replace(array(
            'usersgroupid',
        ), $defaultUserGroup->list_id, file_get_contents(__DIR__ . '/files/event_with_group_attendee.xml')));
        $syncrotonEvent = new Syncroton_Model_Event($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        $serverId = $controller->createEntry($syncrotonFolder->serverId, $syncrotonEvent);

        // assert created group & groupmembers
        $syncrotonEventtoUpdate = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        $this->assertCount(count($defaultUserGroupMembers) + 1, $syncrotonEventtoUpdate->attendees, 'groupmembers not resolved: ' . print_r($syncrotonEventtoUpdate->attendees, true));
        $this->assertCount(count($defaultUserGroupMembers) + 1, $syncrotonEventtoUpdate->exceptions[0]->attendees, 'groupmembers not resolved');

        // update event
        $syncrotonEventtoUpdate->exceptions[0]->subject = 'update';
        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->last_modified_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $syncTimestamp);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEventtoUpdate);

        // assert updated group & groupmembers
        $updatedSyncrotonEvent = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        $this->assertCount(count($defaultUserGroupMembers) + 1, $updatedSyncrotonEvent->attendees, 'groupmembers not resolved');
        $this->assertCount(count($defaultUserGroupMembers) + 1, $updatedSyncrotonEvent->exceptions[0]->attendees, 'groupmembers not resolved');
    }

    public function testUpdateEventWithoutDtstartAndEnd()
    {
        $syncrotonFolder = $this->testCreateFolder();
        $event = $this->_createEvent();

        $xml = new SimpleXMLElement('
          <ApplicationData>
            <Timezone xmlns="uri:Calendar">xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAMAAAAAAAAAxP///w==</Timezone>
            <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
            <BusyStatus xmlns="uri:Calendar">2</BusyStatus>
            <DtStamp xmlns="uri:Calendar">20131021T142015Z</DtStamp>
            <Sensitivity xmlns="uri:Calendar">0</Sensitivity>
            <Subject xmlns="uri:Calendar">testtermin updated</Subject>
            <UID xmlns="uri:Calendar">' . $event->uid . '</UID>
            <MeetingStatus xmlns="uri:Calendar">1</MeetingStatus>
            <Attendees xmlns="uri:Calendar">
              <Attendee>
                <Name>' . Tinebase_Core::getUser()->accountDisplayName . '</Name>
                <Email>' . Tinebase_Core::getUser()->accountEmailAddress . '</Email>
                <AttendeeType>1</AttendeeType>
              </Attendee>
            </Attendees>
          </ApplicationData>
        ');
        $syncrotonEvent = new Syncroton_Model_Event($xml);

        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $event->creation_time);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $event->getId(), $syncrotonEvent);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($serverId);
        self::assertEquals('testtermin updated', $updatedEvent->summary);
        self::assertEquals('2013-10-22 16:00:00', $updatedEvent->dtstart->toString());
    }

    public function testUpdateEventAddRecureException()
    {
        $syncrotonFolder = $this->testCreateFolder();
        $event = $this->_createEvent();
        $event->attendee->addRecord(new Calendar_Model_Attender([
            'user_id' => $this->_personas['sclever']->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
        ]));
        $event->rrule = new Calendar_Model_Rrule('FREQ=WEEKLY;INTERVAL=1;WKST=MO;BYDAY=TU,FR');
        $event = Calendar_Controller_Event::getInstance()->update($event);
        $event->attendee->find('user_id', $this->_personas['sclever']->contact_id)->status =
            Calendar_Model_Attender::STATUS_ACCEPTED;
        $event = Calendar_Controller_Event::getInstance()->update($event);

        $controller = Syncroton_Data_Factory::factory($this->_class,
            $this->_getDevice(ActiveSync_TestCase::TYPE_IOS_11), $event->creation_time);

        $syncrotonEventtoUpdate = $controller->getEntry(new Syncroton_Model_SyncCollection(
            ['collectionId' => $syncrotonFolder->serverId]), $event->getId());
        self::assertNotNull($syncrotonEventtoUpdate->attendees, 'attendee should not be null');

        // 20180703T160000Z is a Tuesday
        $xml = new SimpleXMLElement('<Exception>
                <Body xmlns="uri:AirSyncBase">
                  <Type>1</Type>
                  <Data/>
                </Body>
                <Deleted xmlns="uri:Calendar">0</Deleted>
                <ExceptionStartTime xmlns="uri:Calendar">20180703T160000Z</ExceptionStartTime>
                <StartTime xmlns="uri:Calendar">20180703T160000Z</StartTime>
                <EndTime xmlns="uri:Calendar">20180703T170000Z</EndTime>
                <Subject xmlns="uri:Calendar">TEST-EXCEPTION-FROM-IOS yeah</Subject>
                <BusyStatus xmlns="uri:Calendar">2</BusyStatus>
                <AllDayEvent xmlns="uri:Calendar">0</AllDayEvent>
              </Exception>');
        $exception = new Syncroton_Model_EventException($xml);
        $syncrotonEventtoUpdate->exceptions = [$exception];
        $attendees = [];
        /** @var Syncroton_Model_EventAttendee $attendee */
        foreach ($syncrotonEventtoUpdate->attendees as $attendee) {
            //unset($attendee->attendeeStatus);
            $attendees[] = clone $attendee;
        }
        $exception->attendees = $attendees;

        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $event->getId(), $syncrotonEventtoUpdate);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($serverId);
        static::assertEquals(1, count($updatedEvent->exdate), 'exdates count not right');
        static::assertEquals('2018-07-03 16:00:00', $updatedEvent->exdate[0]);

        $exceptions = Calendar_Controller_Event::getInstance()->getRecurExceptions($updatedEvent);
        static::assertEquals(1, $exceptions->count(), 'exdates count not right');
        /** @var Calendar_Model_Event $exception */
        $exception = $exceptions->getFirstRecord();
        static::assertEquals(2, $exception->attendee->count(), 'attendee count is wrong');
        static::assertEquals(2, $exception->attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)
            ->count(), 'expected both attendees have accepted');
    }

    public function testPreserveDataOnCreateRecurException()
    {
        $cfCfg = $this->_createCustomField(Tinebase_Record_Abstract::generateUID(), Calendar_Model_Event::class);

        // create event in default folder
        $syncrotonFolder = $this->testCreateFolder();

        $event = $this->_createEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=2';
        $event->xprops('customfields')[$cfCfg->name] = __METHOD__;
        $event = Calendar_Controller_Event::getInstance()->update($event);
        static::assertTrue(isset($event->customfields[$cfCfg->name]), 'saving customfield didnt work');
        static::assertEquals(__METHOD__, $event->customfields[$cfCfg->name], 'saving customfield didnt work');

        $exception = clone $event;
        $exception->dtstart->addDay(1)->addHour(1);
        $exception->dtend->addDay(1)->addHour(1);
        $exception->summary = 'exception';
        $exception->setRecurId($event->getId());
        $exception->setId('fakeid_lalalala');
        $exception = Calendar_Controller_Event::getInstance()->createRecurException($exception);
        static::assertTrue(isset($exception->customfields[$cfCfg->name]), 'copying customfield didnt work');
        static::assertEquals(__METHOD__, $exception->customfields[$cfCfg->name], 'copying customfield didnt work');

        $event = Calendar_Controller_Event::getInstance()->get($event->getId());
        static::assertEquals(1, count($event->exdate), 'no exception created');

        $serverId = $event->getId();

        // load event
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), Tinebase_DateTime::now());
        $syncrotonEventtoUpdate = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);

        // update event in non default folder
        $data = [];
        foreach ($syncrotonEventtoUpdate->exceptions[0] as $key => $val) {
            if (is_object($val)) {
                $val = clone $val;
            }
            $data[$key] = $val;
        }
        $syncrotonEventtoUpdate->exceptions[1] = new Syncroton_Model_EventException($data);
        $syncrotonEventtoUpdate->exceptions[1]->subject = 'exception1';
        $syncrotonEventtoUpdate->exceptions[1]->endTime->addDay(1)->addHour(1);
        $syncrotonEventtoUpdate->exceptions[1]->exceptionStartTime->addDay(1)->addHour(1);
        $syncrotonEventtoUpdate->exceptions[1]->startTime->addDay(1)->addHour(1);

        $syncTimestamp = Calendar_Controller_Event::getInstance()->get($serverId)->last_modified_time;
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), $syncTimestamp);
        $serverId = $controller->updateEntry($syncrotonFolder->serverId, $serverId, $syncrotonEventtoUpdate);

        $event = Calendar_Controller_Event::getInstance()->get($event->getId());
        static::assertEquals(2, count($event->exdate), 'no exception created');
        $exceptions = Calendar_Controller_Event::getInstance()->getRecurExceptions($event);
        static::assertEquals(2, count($exceptions), 'no exception created');

        $exception = $exceptions->find('summary', 'exception1');
        static::assertNotNull($exception, 'no exception created');
        $exception = Calendar_Controller_Event::getInstance()->get($exception->getId());
        static::assertNotNull($exception, 'no exception created');
        static::assertTrue(isset($exception->customfields[$cfCfg->name]), 'copying customfield didnt work');
        static::assertEquals(__METHOD__, $exception->customfields[$cfCfg->name], 'copying customfield didnt work');

        $exception = $exceptions->find('summary', 'exception');
        static::assertNotNull($exception, 'no exception created');
        $exception = Calendar_Controller_Event::getInstance()->get($exception->getId());
        static::assertNotNull($exception, 'we lost an exception');
        static::assertTrue(isset($exception->customfields[$cfCfg->name]), 'we lost a customfield');
        static::assertEquals(__METHOD__, $exception->customfields[$cfCfg->name], 'we lost a customfield');
    }

    public function testAndroidNonOrganizerAttenderMeetingStatus()
    {
        $syncrotonFolder = $this->testCreateFolder();
        $event = $this->_createEvent(Calendar_Model_Attender::STATUS_ACCEPTED, true);
        $serverId = $event->getId();

        $scleverAttendee = $event->attendee
            ->filter('status', Calendar_Model_Attender::STATUS_NEEDSACTION)
            ->getFirstRecord();
        $scleverAttendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $scleverAttendee, $scleverAttendee->status_authkey);

        // non-organizer view
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID), $event->creation_time);

        $syncrotonEventtoCreate = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
        self::assertEquals(3, $syncrotonEventtoCreate->meetingStatus, 'meetingStatus should be 3 - '
            . 'This event is a meeting, and the user is not the meeting organizer; the meeting was received from someone else.');
        self::assertEquals(3, $syncrotonEventtoCreate->responseType, 'responseType should be 3 - '
            . 'Accepted. The user has accepted the meeting request.');
        self::assertEquals(0, $syncrotonEventtoCreate->responseRequested);
    }
}
