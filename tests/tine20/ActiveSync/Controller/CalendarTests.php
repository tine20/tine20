<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Controller_Calendar::main');
}

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class ActiveSync_Controller_CalendarTests extends PHPUnit_Framework_TestCase
{
    
    /**
     * @var ActiveSync_Controller_Calendar controller
     */
    protected $_controller;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * test domdocument with an calendar event
     * 
     * @var DOMDocument
     */
    protected $testDOM;
    
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
    
    
    protected function setUp()
    {   	
    	$appName = 'Calendar';
    	
    	############# TEST DOMDOCUMENT #############
    	$this->testDOM = new DOMDocument();
        $this->testDOM->formatOutput = false;
        $this->testDOM->encoding     = 'utf-8';
        $this->testDOM->loadXML('<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:Calendar="uri:Calendar"><Collections><Collection><Class>Calendar</Class><SyncKey>9</SyncKey><CollectionId>41</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>5</FilterType></Options><Commands><Change><ServerId>6de7cb687964dc6eea109cd81750177979362217</ServerId><ApplicationData><Calendar:Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Calendar:Timezone><Calendar:AllDayEvent>0</Calendar:AllDayEvent><Calendar:BusyStatus>2</Calendar:BusyStatus><Calendar:DtStamp>20101125T150537Z</Calendar:DtStamp><Calendar:EndTime>20101123T160000Z</Calendar:EndTime><Calendar:Sensitivity>0</Calendar:Sensitivity><Calendar:Subject>Repeat</Calendar:Subject><Calendar:StartTime>20101123T130000Z</Calendar:StartTime><Calendar:UID>6de7cb687964dc6eea109cd81750177979362217</Calendar:UID><Calendar:MeetingStatus>1</Calendar:MeetingStatus><Calendar:Attendees><Calendar:Attendee><Calendar:Name>Lars Kneschke</Calendar:Name><Calendar:Email>lars@kneschke.de</Calendar:Email></Calendar:Attendee></Calendar:Attendees><Calendar:Recurrence><Calendar:Type>0</Calendar:Type><Calendar:Interval>1</Calendar:Interval><Calendar:Until>20101128T225959Z</Calendar:Until></Calendar:Recurrence><Calendar:Exceptions><Calendar:Exception><Calendar:Deleted>0</Calendar:Deleted><Calendar:ExceptionStartTime>20101125T130000Z</Calendar:ExceptionStartTime><Calendar:StartTime>20101125T140000Z</Calendar:StartTime><Calendar:EndTime>20101125T170000Z</Calendar:EndTime><Calendar:Subject>Repeat mal anders</Calendar:Subject><Calendar:BusyStatus>2</Calendar:BusyStatus><Calendar:AllDayEvent>0</Calendar:AllDayEvent></Calendar:Exception><Calendar:Exception><Calendar:Deleted>1</Calendar:Deleted><Calendar:ExceptionStartTime>20101124T130000Z</Calendar:ExceptionStartTime></Calendar:Exception></Calendar:Exceptions></ApplicationData></Change></Commands></Collection></Collections></Sync>');
        #$this->testDOM->formatOutput = true; echo $this->testDOM->saveXML(); $this->testDOM->formatOutput = false;
    	
        
    	############# TEST USER ##########
    	$user = new Tinebase_Model_FullUser(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        ));
        
        try {
            $user = Tinebase_User::getInstance()->getUserById($user->accountId) ;
        } catch (Tinebase_Exception_NotFound $e) {
            Tinebase_User::getInstance()->addUser($user);
        }
        $this->objects['user'] = $user;
        
        
        ############# TEST CONTACT ##########
        try {
            $containerWithSyncGrant = Tinebase_Container::getInstance()->getContainerByName($appName, 'ContainerWithSyncGrant', Tinebase_Model_Container::TYPE_PERSONAL);
        } catch (Tinebase_Exception_NotFound $e) {
	        $containerWithSyncGrant = new Tinebase_Model_Container(array(
	            'name'              => 'ContainerWithSyncGrant',
	            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
	            'backend'           => 'Sql',
	            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($appName)->getId()
	        ));
	        $containerWithSyncGrant = Tinebase_Container::getInstance()->addContainer($containerWithSyncGrant);
        }
        $this->objects['containerWithSyncGrant'] = $containerWithSyncGrant;
        
        try {
            $containerWithoutSyncGrant = Tinebase_Container::getInstance()->getContainerByName($appName, 'ContainerWithoutSyncGrant', Tinebase_Model_Container::TYPE_PERSONAL);
        } catch (Tinebase_Exception_NotFound $e) {
            $creatorGrants = array(
                'account_id'     => Tinebase_Core::getUser()->getId(),
                'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => true,
                //Tinebase_Model_Grants::GRANT_EXPORT    => true,
                //Tinebase_Model_Grants::GRANT_SYNC      => true,
                Tinebase_Model_Grants::GRANT_ADMIN     => true,
            );        	
        	$grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array($creatorGrants));
        	
            $containerWithoutSyncGrant = new Tinebase_Model_Container(array(
                'name'              => 'ContainerWithoutSyncGrant',
                'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($appName)->getId()
            ));
            $containerWithSyncGrant = Tinebase_Container::getInstance()->addContainer($containerWithoutSyncGrant, $grants);
        }
        $this->objects['containerWithoutSyncGrant'] = $containerWithoutSyncGrant;

        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->addMonth(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->addMonth(1)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'container_id'  => $this->objects['containerWithSyncGrant']->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        $this->objects['event'] = $event;
        
        $eventDaily = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->addMonth(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-05-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->addMonth(1)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-05-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=' . Tinebase_DateTime::now()->addMonth(1)->addHour(1)->addDay(6)->toString(Tinebase_Record_Abstract::ISO8601LONG), //2009-05-31 17:30:00',
            'container_id'  => $this->objects['containerWithSyncGrant']->getId(),
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
        
        Tinebase_Core::getPreference('ActiveSync')->setValue(ActiveSync_Preference::DEFAULTCALENDAR, $containerWithSyncGrant->getId());
        
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
                    'value'     => $this->objects['containerWithSyncGrant']->getId()
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
        $palm->owner_id     = $user->getId();
        $palm->calendarfilter_id = $this->objects['filter']->getId();
        $this->objects['devicePalm']   = ActiveSync_Controller_Device::getInstance()->create($palm);
        
        $iphone = ActiveSync_Backend_DeviceTests::getTestDevice();
        $iphone->devicetype = 'iphone';
        $iphone->owner_id   = $user->getId();
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
        // remove accounts for group member tests
        try {
            Tinebase_User::getInstance()->deleteUser($this->objects['user']->accountId);
        } catch (Exception $e) {
            // do nothing
        }

        Calendar_Controller_Event::getInstance()->delete(array($this->objects['event']->getId()));
        Calendar_Controller_Event::getInstance()->delete(array($this->objects['eventDaily']->getId()));
        #Calendar_Controller_Event::getInstance()->delete(array($this->objects['eventWeekly']->getId()));
        
        Tinebase_Container::getInstance()->deleteContainer($this->objects['containerWithSyncGrant']);
        Tinebase_Container::getInstance()->deleteContainer($this->objects['containerWithoutSyncGrant']);
        
        ActiveSync_Controller_Device::getInstance()->delete($this->objects['devicePalm']);
        ActiveSync_Controller_Device::getInstance()->delete($this->objects['deviceIPhone']);
        
        $filterBackend = new Tinebase_PersistentFilter_Backend_Sql();
        $filterBackend->delete($this->objects['filter']->getId());
    }
    
    /**
     * validate getFolders for all devices except IPhone
     */
    public function testGetFoldersPalm()
    {
    	$controller = new ActiveSync_Controller_Calendar($this->objects['devicePalm'], new Tinebase_DateTime(null, null, 'de_DE'));
    	
    	$folders = $controller->getSupportedFolders();
    	
    	$this->assertArrayHasKey("calendar-root", $folders, print_r($folders, true));
    }
    
    /**
     * validate getFolders for IPhones
     */
    public function testGetFoldersIPhone()
    {
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
        
        $folders = $controller->getSupportedFolders();
        foreach($folders as $folder) {
        	$this->assertTrue(Tinebase_Core::getUser()->hasGrant($folder['folderId'], Tinebase_Model_Grants::GRANT_SYNC), print_r($folder, true));
        }
        $this->assertArrayNotHasKey("calendar-root", $folders, print_r($folders, true));
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testAppendXml()
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
        
        
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));     
        
        $controller->appendXML($appData, null, $this->objects['event']->getId(), array());
        
        // namespace === uri:Calendar
        $endTime = $this->objects['event']->dtend->format("Ymd\THis") . 'Z';
        $this->assertEquals($endTime, @$testDom->getElementsByTagNameNS('uri:Calendar', 'EndTime')->item(0)->nodeValue, $testDom->saveXML());
        $this->assertEquals($this->objects['event']->getId(), @$testDom->getElementsByTagNameNS('uri:Calendar', 'UID')->item(0)->nodeValue, $testDom->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDom);
        
        #rewind($outputStream);
        #fpassthru($outputStream);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testAppendXmlDailyEvent()
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
        
        #echo $testDom->saveXML();
        
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
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testGetServerEntries()
    {
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime(null, null, 'de_DE'));
        
        $entries = $controller->getServerEntries('calendar-root', ActiveSync_Controller_Calendar::FILTER_2_WEEKS_BACK);
        
        $this->assertContains($this->objects['event']->getId(), $entries);
        #$this->assertNotContains($this->objects['unSyncableContact']->getId(), $entries);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testConvertToTine20Model()
    {
        $xml = simplexml_import_dom($this->testDOM);
        
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime());     
        
        $event = $controller->toTineModel($xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        #var_dump($event->toArray());
        
        $this->assertEquals('Repeat', $event->summary);
        $this->assertEquals(2,        count($event->exdate));
        #$this->assertEquals('Europe/Berlin', $event->originator_tz);
    }
    
    public function testAddEntryToBackend()
    {
        $xml = simplexml_import_dom($this->testDOM);
        
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Tinebase_DateTime());
        
        $event = $controller->add($this->objects['containerWithSyncGrant']->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        Calendar_Controller_Event::getInstance()->delete($event->getId());
    }
    
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Controller_Calendar::main') {
    ActiveSync_Controller_Calendar::main();
}
