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
            'dtstart'       => '2009-04-25 18:00:00',
            'dtend'         => '2009-04-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'container_id'  => $this->objects['containerWithSyncGrant']->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $event = Calendar_Controller_Event::getInstance()->create($event);
        #var_dump($event->toArray());
        $this->objects['event'] = $event;
        
        $eventDaily = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => '2009-05-25 18:00:00',
            'dtend'         => '2009-05-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-05-31 17:30:00',
            'exdate'        => '2009-05-27 18:00:00,2009-05-29 17:00:00',
            'container_id'  => $this->objects['containerWithSyncGrant']->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $eventDaily = Calendar_Controller_Event::getInstance()->create($eventDaily);
        #var_dump($eventDaily->toArray());
        $this->objects['eventDaily'] = $eventDaily;
        
        ########### Test Controller / uit ###############
        $palm = new ActiveSync_Model_Device(array(
            'deviceid'  => 'test_device_id',
            'devicetype' => 'palm',
            'owner_id' => $user->getId(),
            'policy_id'=> 'test_:policy_id'
           )
        );
        $this->objects['devicePalm'] = $palm;
        
        $iphone = new ActiveSync_Model_Device(array(
            'deviceid'  => 'test_device_id',
            'devicetype' => 'iphone',
            'owner_id' => $user->getId(),
            'policy_id'=> 'test_:policy_id'
           )
        );
        $this->objects['deviceIPhone'] = $iphone;
        
        //$this->_controller = new ActiveSync_Controller_Calendar($device, new Zend_Date(null, null, 'de_DE'));
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
    }
    
    /**
     * validate getFolders for all devices except IPhone
     */
    public function testGetFoldersPalm()
    {
    	$controller = new ActiveSync_Controller_Calendar($this->objects['devicePalm'], new Zend_Date(null, null, 'de_DE'));
    	
    	$folders = $controller->getSupportedFolders();
    	
    	$this->assertArrayHasKey("calendar-root", $folders, print_r($folders, true));
    }
    
    /**
     * validate getFolders for IPhones
     */
    public function testGetFoldersIPhone()
    {
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Zend_Date(null, null, 'de_DE'));
        
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
        
        
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Zend_Date(null, null, 'de_DE'));     
        
        $controller->appendXML($appData, null, $this->objects['event']->getId());
        
        // namespace === uri:Calendar
        $this->assertEquals('20090425T183000Z', @$testDom->getElementsByTagNameNS('uri:Calendar', 'EndTime')->item(0)->nodeValue, $testDom->saveXML());
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
        
        
        $controller = new ActiveSync_Controller_Calendar($this->objects['deviceIPhone'], new Zend_Date(null, null, 'de_DE'));     
        
        $controller->appendXML($appData, null, $this->objects['eventDaily']->getId());
        
        // namespace === uri:Calendar
        $this->assertEquals(ActiveSync_Controller_Calendar::RECUR_TYPE_DAILY, @$testDom->getElementsByTagNameNS('uri:Calendar', 'Type')->item(0)->nodeValue, $testDom->saveXML());
        $this->assertEquals('20090525T183000Z', @$testDom->getElementsByTagNameNS('uri:Calendar', 'EndTime')->item(0)->nodeValue, $testDom->saveXML());
        $this->assertEquals('20090531T173000Z', @$testDom->getElementsByTagNameNS('uri:Calendar', 'Until')->item(0)->nodeValue, $testDom->saveXML());
        
    }
    
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Controller_Calendar::main') {
    ActiveSync_Controller_Calendar::main();
}
