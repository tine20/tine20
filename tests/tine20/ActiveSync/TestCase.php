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
    
    /**
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
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
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_testUser          = Tinebase_Core::getUser();
        $this->_specialFolderName = strtolower($this->_applicationName) . '-root';
        
        $this->objects['container'] = array();
        $this->objects['devices']   = array();
        
        $this->objects['tasks']   = array();
        $this->objects['events']   = array();

        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set('loggerBackend',                         Tinebase_Core::getLogger());
        
        Syncroton_Registry::setContactsDataClass('ActiveSync_Controller_Contacts');
        Syncroton_Registry::setCalendarDataClass('ActiveSync_Controller_Calendar');
        Syncroton_Registry::setEmailDataClass('ActiveSync_Controller_Email');
        Syncroton_Registry::setTasksDataClass('ActiveSync_Controller_Tasks');
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     *
     * @return Syncroton_Model_IFolder
     */
    public function testCreateFolder()
    {
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $syncrotonFolder = $controller->createFolder(new Syncroton_Model_Folder(array(
            'parentId' => 0, 
            'displayName' => 'TestFolder'
        )));
    
        $this->assertTrue(!empty($syncrotonFolder->serverId));
        
        return $syncrotonFolder;
    }
    
    public function testUpdateFolder()
    {
        $this->markTestIncomplete();
    
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $controller->updateFolder($syncrotonFolder);
    }
    
    public function testDeleteFolder()
    {
        $this->markTestIncomplete();
    
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $controller->deleteFolder($syncrotonFolder);
    }
    
    public function testGetAllFolders()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $allSyncrotonFolders = $controller->getAllFolders();
        
        $this->assertArrayHasKey($syncrotonFolder->serverId, $allSyncrotonFolders);
        $this->assertArrayNotHasKey($this->_specialFolderName, $allSyncrotonFolders);
        $this->assertTrue($allSyncrotonFolders[$syncrotonFolder->serverId] instanceof Syncroton_Model_Folder);
        $this->assertEquals($syncrotonFolder->serverId, $allSyncrotonFolders[$syncrotonFolder->serverId]->serverId, 'serverId mismatch');
        $this->assertEquals($syncrotonFolder->parentId, $allSyncrotonFolders[$syncrotonFolder->serverId]->parentId, 'parentId mismatch');
        $this->assertEquals($syncrotonFolder->displayName, $allSyncrotonFolders[$syncrotonFolder->serverId]->displayName);
        $this->assertTrue(!empty($allSyncrotonFolders[$syncrotonFolder->serverId]->type));
    }
    
    public function testGetAllFoldersPalm()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $allSyncrotonFolders = $controller->getAllFolders();
        
        $this->assertArrayHasKey($this->_specialFolderName, $allSyncrotonFolders, "key {$this->_specialFolderName} not found in " . print_r($allSyncrotonFolders, true));
    }
    
    /**
     * test search tasks
     */
    #public function testGetFolder()
    #{
    #    // create at least one folder with sync grants
    #    $syncAbleFolder    = $this->_getContainerWithSyncGrant();
    #    
    #    $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
    #    
    #    $folder = $controller->getFolder($syncAbleFolder);
    #    
    #    //var_dump($folder);
    #    
    #    $this->assertArrayHasKey($syncAbleFolder->getId(), $folder);
    #}
    
    public function testDeleteEntry()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        list($serverId, $syncrotonContact) = $this->testCreateEntry($syncrotonFolder);
    
        $controller->deleteEntry($syncrotonFolder->serverId, $serverId, null);
    
        $this->setExpectedException('Syncroton_Exception_NotFound');
    
        $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
    }
    
    public function testGetInvalidEntry()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $this->setExpectedException('Syncroton_Exception_NotFound');
    
        $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), 'jdszfegd63gfrk');
    }
    
    public function testGetChangedEntries()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        list($serverId, $syncrotonContact) = $this->testUpdateEntry($syncrotonFolder);
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $changedEntries = $controller->getChangedEntries($syncrotonFolder->serverId, new DateTime('2000-01-01'));
        
        $this->assertContains($serverId, $changedEntries);
    }
    
    public function testGetChangedEntriesAndroid()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        list($serverId, $syncrotonContact) = $this->testUpdateEntry($syncrotonFolder);
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $changedEntries = $controller->getChangedEntries($this->_specialFolderName, new DateTime('2000-01-01'));
    
        $this->assertContains($serverId, $changedEntries);
    }
    
    /**
     * test convert from XML to Tine 2.0 model
     * 
     */
    abstract public function testCreateEntry($syncrotonFolder = null);
    
    /**
     * test xml generation for sync to client
     */
    abstract public function testUpdateEntry($syncrotonFolder = null);
        
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
        
        switch ($this->_applicationName) {
            case 'Calendar':    $recordClass = 'Calendar_Model_Event'; break;
            case 'Addressbook': $recordClass = 'Addressbook_Model_Contact'; break;
            case 'Tasks':       $recordClass = 'Tasks_Model_Task'; break;
            default: throw new Exception('handle this model!');
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
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
                'model'             => $recordClass
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
        
        $this->objects['devices'][$_deviceType] = Syncroton_Registry::getDeviceBackend()->create( 
            ActiveSync_TestCase::getTestDevice($_deviceType)
        );

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
            $this->_controller = Syncroton_Data_Factory::factory($this->_class, $_device, new Tinebase_DateTime(null, null, 'de_DE'));
        } 
        
        return $this->_controller;
    }
    
    /**
     * returns a test event
     * 
     * @param Tinebase_Model_Container $personalContainer
     * @return Calendar_Model_Event
     */
    public static function getTestEvent($personalContainer = NULL)
    {
        $personalContainer = ($personalContainer) ? $personalContainer : Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser(),
            'Calendar', 
            Tinebase_Core::getUser(),
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();
        
        return new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->addMonth(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->addMonth(1)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'container_id'  => $personalContainer->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            'attendee'      => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id' => Tinebase_Core::getUser()->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'status' => Calendar_Model_Attender::STATUS_ACCEPTED
                )
            ))
        ));
    }
    
    /**
     *
     * @return Syncroton_Model_Device
     */
    public static function getTestDevice($_type = null)
    {
        switch($_type) {
            case Syncroton_Model_Device::TYPE_ANDROID:
                $device = new Syncroton_Model_Device(array(
                    'deviceid'   => 'android-abcd',
                    'devicetype' => Syncroton_Model_Device::TYPE_ANDROID,
                    'policykey'  => null,
                    'policyId'   => null,
                    'ownerId'    => Tinebase_Core::getUser()->getId(),
                    'useragent'  => 'blabla',
                    'acsversion' => '12.0',
                    'remotewipe' => 0
                ));
                break;
    
            case Syncroton_Model_Device::TYPE_WEBOS:
                $device = new Syncroton_Model_Device(array(
                    'deviceid'   => 'webos-abcd',
                    'devicetype' => Syncroton_Model_Device::TYPE_ANDROID,
                    'policykey'  => null,
                    'policyId'   => null,
                    'ownerId'    => Tinebase_Core::getUser()->getId(),
                    'useragent'  => 'blabla',
                    'acsversion' => '12.0',
                    'remotewipe' => 0
                ));
                break;
                
            case Syncroton_Model_Device::TYPE_SMASUNGGALAXYS2:
                $device = new Syncroton_Model_Device(array(
                    'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                    'devicetype' => 'SAMSUNGGTI9100',
                    'policy_id'  => null,
                    'policykey'  => null,
                    'owner_id'   => Tinebase_Core::getUser()->getId(),
                    'useragent'  => 'SAMSUNG-GT-I9100/100.20304',
                    'acsversion' => '12.1',
                    'remotewipe' => 0
                ));
                break;
                
            case Syncroton_Model_Device::TYPE_IPHONE:
            default:
                $device = new Syncroton_Model_Device(array(
                    'deviceid'   => 'iphone-abcd',
                    'devicetype' => Syncroton_Model_Device::TYPE_IPHONE,
                    'policykey'  => null,
                    'policyId'   => null,
                    'ownerId'    => Tinebase_Core::getUser()->getId(),
                    'useragent'  => 'blabla',
                    'acsversion' => '12.1',
                    'remotewipe' => 0
                ));
                break;
        }
    
        return $device;
    }
    
}
