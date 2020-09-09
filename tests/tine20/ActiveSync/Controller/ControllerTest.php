<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * abstract test class for activesync controller tests
 * 
 * @package     ActiveSync
 */
abstract class ActiveSync_Controller_ControllerTest extends ActiveSync_TestCase
{
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
     *
     * @return Syncroton_Model_Folder
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
    
    /**
     * testUpdateFolder (iPhone)
     *
     * @return Syncroton_Model_Folder
     */
    public function testUpdateFolder()
    {
        return $this->_testUpdateFolderForDeviceType(Syncroton_Model_Device::TYPE_IPHONE);
    }

    /**
     * @param string $type
     * @return Syncroton_Model_Folder
     */
    protected function _testUpdateFolderForDeviceType($type)
    {
        $syncrotonFolder = $this->testCreateFolder();
        $syncrotonFolder->displayName = 'RenamedTestFolder';

        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice($type), new Tinebase_DateTime(null, null, 'de_DE'));

        $updatedSyncrotonFolder = $controller->updateFolder($syncrotonFolder);

        $allFolders = $controller->getAllFolders();

        $this->assertArrayHasKey($syncrotonFolder->serverId, $allFolders);
        $this->assertEquals('RenamedTestFolder', $allFolders[$syncrotonFolder->serverId]->displayName);

        return $updatedSyncrotonFolder;
    }

    /**
     * @return Syncroton_Model_Folder
     */
    public function testUpdateFolderAndroid()
    {
        return $this->_testUpdateFolderForDeviceType(self::TYPE_ANDROID_6);
    }
    
    /**
     * test if changed folders got returned
     */
    public function testGetChangedFolders()
    {
        $syncrotonFolder = $this->testUpdateFolder();
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $changedFolders = $controller->getChangedFolders(Tinebase_DateTime::now()->subMinute(1), Tinebase_DateTime::now());
        
        $this->assertGreaterThanOrEqual(1, count($changedFolders));
        $this->assertArrayHasKey($syncrotonFolder->serverId, $changedFolders);
    }
    
    public function testDeleteFolder()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $controller->deleteFolder($syncrotonFolder);
    }
    
    public function testGetAllFoldersIPhone()
    {
        $this->_testGetAllFoldersForDeviceType(Syncroton_Model_Device::TYPE_IPHONE);
    }

    /**
     * get all folders test for given device type
     *
     * @param $type
     */
    protected function _testGetAllFoldersForDeviceType($type)
    {
        $syncrotonFolder = $this->testCreateFolder();

        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice($type),
            new Tinebase_DateTime(null, null, 'de_DE'));

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
     * @see 0012634: ActiveSync: Add android to multiple folders devices
     */
    public function testGetAllFoldersAndroid()
    {
        $this->_testGetAllFoldersForDeviceType(self::TYPE_ANDROID_6);
    }
    
    /**
     * testDeleteEntry
     */
    public function testDeleteEntry()
    {
        $syncrotonFolder = $this->testCreateFolder();
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        list($serverId, $syncrotonContact) = $this->testCreateEntry($syncrotonFolder);
        
        $controller->deleteEntry($syncrotonFolder->serverId, $serverId, null);
        
        try {
            $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), $serverId);
            $this->fail('should have thrown Syncroton_Exception_NotFound: '
                . var_export($syncrotonContact, TRUE)
                . ' tine contact: ' . print_r(Addressbook_Controller_Contact::getInstance()->get($serverId)->toArray(), TRUE));
        } catch (Syncroton_Exception_NotFound $senf) {
            $this->assertEquals('Syncroton_Exception_NotFound', get_class($senf));
        }
    }
    
    public function testGetInvalidEntry()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $this->setExpectedException('Syncroton_Exception_NotFound');
    
        $syncrotonContact = $controller->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $syncrotonFolder->serverId)), 'jdszfegd63gfrk');
    }
    
    /**
     * test get changed entries
     */
    public function testGetChangedEntries()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        list($serverId, $syncrotonContact) = $this->testUpdateEntry($syncrotonFolder);
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
    
        $changedEntries = $controller->getChangedEntries($syncrotonFolder->serverId, new DateTime('2000-01-01'));
        
        $this->assertContains($serverId, $changedEntries, 'did not get changed record id in ' . print_r($changedEntries, TRUE));
    }
    
    /**
     * test get changed entries for android
     */
    public function testGetChangedEntriesAndroid()
    {
        $syncrotonFolder = $this->testCreateFolder();
    
        list($serverId, $syncrotonContact) = $this->testUpdateEntry($syncrotonFolder);
    
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID), Tinebase_DateTime::now());
    
        $changedEntries = $controller->getChangedEntries($this->_specialFolderName, new Tinebase_DateTime('2000-01-01'));
    
        $this->assertContains($serverId, $changedEntries, 'did not get changed record id in ' . print_r($changedEntries, TRUE));
    }
    
    /**
     * test convert from XML to Tine 2.0 model
     */
    abstract public function testCreateEntry($syncrotonFolder = null);
    
    /**
     * test xml generation for sync to client
     */
    abstract public function testUpdateEntry($syncrotonFolder = null);
    
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
}
