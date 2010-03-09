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
    define('PHPUnit_MAIN_METHOD', 'Calendar_Controller_EventTests::main');
}

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class ActiveSync_Controller_ContactsTests extends PHPUnit_Framework_TestCase
{
    
    /**
     * @var ActiveSync_Controller_Contacts controller
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Controller Contacts Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    
    protected function setUp()
    {   	
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
        $containerWithSyncGrant = new Tinebase_Model_Container(array(
            'name'              => 'ContainerWithSycnGrant',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            //'account_grants'    => 'Tine 2.0',
        ));
        #Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'ContainerWithSycnGrant', Tinebase_Model_Container::TYPE_PERSONAL);
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $container = $personalContainer[0];
        $this->objects['container'] = $container;
        
        $contact = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'bday'                  => '1975-01-02 03:00:00', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
//            'jpegphoto'             => file_get_contents(dirname(__FILE__) . '/../../Tinebase/ImageHelper/phpunit-logo.gif'),
            'container_id'          => $container->id,
            'role'                  => 'Role',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
        )); 
        
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        #var_dump($contact->toArray());
        $this->objects['contact'] = $contact;
        
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
        
        //$this->_controller = new ActiveSync_Controller_Contacts($device, new Zend_Date(null, null, 'de_DE'));
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

        Addressbook_Controller_Contact::getInstance()->delete(array($this->objects['contact']->getId()));
    }
    
    /**
     * validate getFolders for all devices except IPhone
     */
    public function testGetFoldersPalm()
    {
    	$controller = new ActiveSync_Controller_Contacts($this->objects['devicePalm'], new Zend_Date(null, null, 'de_DE'));
    	
    	$folders = $controller->getFolders();
    	
    	$this->assertArrayHasKey("addressbook-root", $folders, "key addressbook-root not found");
    }
    
    /**
     * validate getFolders for IPhones
     */
    public function testGetFoldersIPhone()
    {
        $controller = new ActiveSync_Controller_Contacts($this->objects['deviceIPhone'], new Zend_Date(null, null, 'de_DE'));
        
        $folders = $controller->getFolders();
        #var_dump($folders);
        $this->assertArrayNotHasKey("addressbook-root", $folders, "key addressbook-root found");
    }
    
    /**
     * validate xml generation for all devices except IPhone
     */
    public function testAppendXmlPalm()
    {
    	$dtd                   = @DOMImplementation::createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDom               = @DOMImplementation::createDocument('uri:AirSync', 'Sync', $dtd);
        $testDom->formatOutput = false;
        $testDom->encoding     = 'utf-8';
        $testNode = $testDom->appendChild($testDom->createElementNS('uri:AirSync', 'TestAppendXml'));
        
        $controller = new ActiveSync_Controller_Contacts($this->objects['devicePalm'], new Zend_Date(null, null, 'de_DE'));   	
        
    	$controller->appendXML($testDom, $testNode, null, $this->objects['contact']->getId());
    	$this->assertEquals(Tinebase_Translation::getCountryNameByRegionCode('DE'), $testDom->getElementsByTagName('BusinessCountry')->item(0)->nodeValue);
    	$this->assertEquals('Germany', $testDom->getElementsByTagName('BusinessCountry')->item(0)->nodeValue);
    	$this->assertEquals('1975-01-02T03:00:00.000Z', $testDom->getElementsByTagName('Birthday')->item(0)->nodeValue);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testAppendXmlIPhone()
    {
        $dtd                   = @DOMImplementation::createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDom               = @DOMImplementation::createDocument('uri:AirSync', 'Sync', $dtd);
        $testDom->formatOutput = false;
        $testDom->encoding     = 'utf-8';
        $testNode = $testDom->appendChild($testDom->createElementNS('uri:AirSync', 'TestAppendXml'));
        
        $controller = new ActiveSync_Controller_Contacts($this->objects['deviceIPhone'], new Zend_Date(null, null, 'de_DE'));     
        
        $controller->appendXML($testDom, $testNode, null, $this->objects['contact']->getId());
        // offset birthday 12 hours
        $this->assertEquals('1975-01-02T15:00:00.000Z', $testDom->getElementsByTagName('Birthday')->item(0)->nodeValue);
    }
    
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Controller_Contacts::main') {
    ActiveSync_Controller_Contacts::main();
}
