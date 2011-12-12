<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * abstract test class for activesync controller tests
 * 
 * @package     ActiveSync
 */
abstract class ActiveSync_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * name of the application
     * 
     * @var string
     */
    protected $_applicationName;
    
    /**
     * name of the controller
     * 
     * @var string
     */
    protected $_controllerName;
    
    /**
     * @var ActiveSync_Controller_Abstract controller
     */
    protected $_controller;
    
    protected $_specialFolderName;
    
    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_testUser;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_testXMLInput;
    
    protected $_testXMLOutput;
    
    protected $_testEmptyXML;
    
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
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {   	
        $this->_testUser          = Tinebase_Core::getUser();        
        $this->_specialFolderName = strtolower($this->_applicationName) . '-root';
        
        $this->objects['container'] = array();
        $this->objects['devices']   = array();
        
        $this->objects['tasks']   = array();
        $this->objects['events']   = array();
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        foreach($this->objects['container'] as $container) {
            Tinebase_Container::getInstance()->deleteContainer($container, TRUE);
        }
        
        foreach($this->objects['devices'] as $device) {
            ActiveSync_Controller_Device::getInstance()->delete($device);
        }
        
        foreach($this->objects['events'] as $event) {
            Calendar_Controller_Event::getInstance()->delete(array($event->getId()));
        }
        
        foreach($this->objects['tasks'] as $task) {
            Tasks_Controller_Task::getInstance()->delete(array($task->getId()));
        }
    }
    
    
    /**
     * validate getFolders for all devices except IPhone
     */
    public function testGetFoldersPalm()
    {
    	$controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        
    	$folders = $controller->getSupportedFolders();
    	
    	$this->assertArrayHasKey($this->_specialFolderName, $folders, "key {$this->_specialFolderName} not found in " . print_r($folders, true));
    }
    
    /**
     * test search tasks
     */
    public function testGetFolder()
    {
        // create at least one folder with sync grants
        $syncAbleFolder    = $this->_getContainerWithSyncGrant();
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_IPHONE));
        
        $folder = $controller->getFolder($syncAbleFolder);
        
        //var_dump($folder);
        
        $this->assertArrayHasKey($syncAbleFolder->getId(), $folder);
    }
    
    /**
     * test search tasks
     */
    public function testGetSpecialFolder()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_IPHONE));
        
        $folder = $controller->getFolder($this->_specialFolderName);
        
        //var_dump($folder);
        
        $this->assertArrayHasKey($this->_specialFolderName, $folder);
    }
    
    /**
     * validate getFolders for IPhones
     */
    public function testGetFoldersIPhone()
    {
        // create at least one folder with sync grants
        $syncAbleFolder    = $this->_getContainerWithSyncGrant();
        
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_IPHONE));
        
        $folders = $controller->getSupportedFolders();
        
        foreach($folders as $folder) {
        	$this->assertTrue($this->_testUser->hasGrant($folder['folderId'], Tinebase_Model_Grants::GRANT_SYNC));
        }
        
        $this->assertArrayNotHasKey($this->_specialFolderName, $folders, "key {$this->_specialFolderName} found in " . print_r($folders, true));
        $this->assertGreaterThanOrEqual(1, count($folders));
    }

    /**
     * test convert from XML to Tine 2.0 model
     * 
     */
    abstract public function testConvertToTine20Model();
    
    /**
     * test xml generation for sync to client
     */
    abstract public function testAppendXml();
        
    /**
     * @return Tinebase_Record_Abstract
     */
    public function testAddEntryToBackend()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        
        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        $record = $controller->add($this->_getContainerWithSyncGrant()->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);

        $this->_validateAddEntryToBackend($record);
        
        return $record;
    }
    
    abstract protected function _validateAddEntryToBackend(Tinebase_Record_Abstract $_record);
    
    /**
     * test get list all record ids
     */
    public function testGetServerEntries()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM));
        
        $xml = simplexml_import_dom($this->_getInputDOMDocument());
        $record = $controller->add($this->_getContainerWithSyncGrant()->getId(), $xml->Collections->Collection->Commands->Change[0]->ApplicationData);
        
        $this->_validateGetServerEntries($record);        
    }
    
    abstract protected function _validateGetServerEntries(Tinebase_Record_Abstract $_record);
    
    /**
     * test search records
     */
    abstract public function testSearch();
    
    /**
     * create container with sync grant
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getContainerWithSyncGrant()
    {
        if (isset($this->objects['container']['withSyncGrant'])) {
            return $this->objects['container']['withSyncGrant'];
        }
        
        try {
            $containerWithSyncGrant = Tinebase_Container::getInstance()->getContainerByName(
                $this->_applicationName, 
                'ContainerWithSyncGrant-' . $this->_applicationName, 
                Tinebase_Model_Container::TYPE_PERSONAL,
                Tinebase_Core::getUser()
            );
        } catch (Tinebase_Exception_NotFound $e) {
	        $containerWithSyncGrant = new Tinebase_Model_Container(array(
	            'name'              => 'ContainerWithSyncGrant-' . $this->_applicationName,
	            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
	        	'owner_id'          => Tinebase_Core::getUser(),
	            'backend'           => 'Sql',
	            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
	        ));
	        $containerWithSyncGrant = Tinebase_Container::getInstance()->addContainer($containerWithSyncGrant);
        }
        
        $this->objects['container']['withSyncGrant'] = $containerWithSyncGrant;
        
        return $this->objects['container']['withSyncGrant'];
    }
    
    /**
     * create container without sync grant
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getContainerWithoutSyncGrant()
    {
        if (isset($this->objects['container']['withoutSyncGrant'])) {
            return $this->objects['container']['withoutSyncGrant'];
        }
        
        try {
            $containerWithoutSyncGrant = Tinebase_Container::getInstance()->getContainerByName(
                $this->_applicationName, 
                'ContainerWithoutSyncGrant-' . $this->_applicationName, 
                Tinebase_Model_Container::TYPE_PERSONAL,
                Tinebase_Core::getUser()
            );
        } catch (Tinebase_Exception_NotFound $e) {
            $creatorGrants = array(
                'account_id'     => $this->_testUser->getId(),
                'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => true,
                //Tinebase_Model_Grants::GRANT_EXPORT    => true,
                //Tinebase_Model_Grants::GRANT_SYNC      => true,
                // NOTE: Admin Grant implies all other grants
                //Tinebase_Model_Grants::GRANT_ADMIN     => true,
            );        	
        	$grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array($creatorGrants));
        	
            $containerWithoutSyncGrant = new Tinebase_Model_Container(array(
                'name'              => 'ContainerWithoutSyncGrant-' . $this->_applicationName,
                'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            	'owner_id'          => Tinebase_Core::getUser(),
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
            ));
            
            $containerWithSyncGrant = Tinebase_Container::getInstance()->addContainer($containerWithoutSyncGrant);
            Tinebase_Container::getInstance()->setGrants($containerWithSyncGrant, $grants, TRUE, FALSE);
        }
        
        $this->objects['container']['withoutSyncGrant'] = $containerWithoutSyncGrant;
        
        return $this->objects['container']['withoutSyncGrant'];
    }
    
    /**
     * return active device
     * 
     * @param string $_deviceType
     * @return ActiveSync_Model_Device
     */
    protected function _getDevice($_deviceType)
    {
        if (isset($this->objects['devices'][$_deviceType])) {
            return $this->objects['devices'][$_deviceType];
        }
        
        switch ($_deviceType) {
            case ActiveSync_Backend_Device::TYPE_IPHONE:
                $device = ActiveSync_Backend_DeviceTests::getTestDevice();
                $device->devicetype   = $_deviceType;
                $device->owner_id     = $this->_testUser->getId();
                #$palm->contactsfilter_id = $this->objects['filter']->getId();
                
                break;
                
            case ActiveSync_Backend_Device::TYPE_PALM:
                $device = ActiveSync_Backend_DeviceTests::getTestDevice();
                $device->devicetype   = $_deviceType;
                $device->owner_id     = $this->_testUser->getId();
                $device->acsversion   = '12.0';
                #$palm->contactsfilter_id = $this->objects['filter']->getId();
                
                break;
                
            default:
                throw new Exception('unsupported device: ' , $_deviceType);
        }
        
        $this->objects['devices'][$_deviceType] = ActiveSync_Controller_Device::getInstance()->create($device);

        return $this->objects['devices'][$_deviceType];
    }
    
    /**
     * get application activesync controller
     * 
     * @param ActiveSync_Model_Device $_device
     */
    protected function _getController(ActiveSync_Model_Device $_device)
    {
        if ($this->_controller === null) {
            $this->_controller = ActiveSync_Controller::dataFactory($this->_class, $_device, new Tinebase_DateTime(null, null, 'de_DE'));
        } 
        
        return $this->_controller;
    }
    
    /**
     * 
     * @return DOMDocument
     */
    protected function _getInputDOMDocument($xml = NULL)
    {
    	$dom = new DOMDocument();
        $dom->formatOutput = false;
        $dom->encoding     = 'utf-8';
        $dom->loadXML($xml ? $xml : $this->_testXMLInput);
        #$dom->formatOutput = true; echo $dom->saveXML(); $dom->formatOutput = false;
        
        return $dom;
    }
    
    /**
     * 
     * @return DOMDocument
     */
    protected function _getOutputDOMDocument()
    {
    	$dom = new DOMDocument();
        $dom->formatOutput = false;
        $dom->encoding     = 'utf-8';
        $dom->loadXML($this->_testXMLOutput);
        #$dom->formatOutput = true; echo $dom->saveXML(); $dom->formatOutput = false;
        
        return $dom;
    }
}
