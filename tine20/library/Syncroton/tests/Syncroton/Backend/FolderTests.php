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
class Syncroton_Backend_FolderTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncroton_Model_Device
     */
    protected $_device;
    
    /**
     * @var Syncroton_Backend_Device
     */
    protected $_deviceBackend;

    /**
     * @var Syncroton_Backend_Folder
     */
    protected $_folderBackend;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton Folder backend tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->_db = getTestDatabase();
        
        $this->_db->beginTransaction();

        $this->_deviceBackend      = new Syncroton_Backend_Device($this->_db);
        $this->_folderBackend = new Syncroton_Backend_Folder($this->_db);

        $newDevice = Syncroton_Backend_DeviceTests::getTestDevice();
        $this->_device    = $this->_deviceBackend->create($newDevice);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_db->rollBack();
    }
    
    /**
     * @return Syncroton_Model_IFolder
     */
    public function testCreate(Syncroton_Model_IFolder $_folder = null)
    {
        $folder = $_folder instanceof Syncroton_Model_IFolder ? $_folder : self::getTestFolder($this->_device);
        
        $folder = $this->_folderBackend->create($folder);
                
        $this->assertTrue($folder->creationTime instanceof DateTime);
        $this->assertFalse(empty($folder->displayName));
        
        return $folder;
    }
    
    /**
     */
    public function testDelete()
    {
        $folder = $this->testCreate();
    
        $result = $this->_folderBackend->delete($folder);
        
        $this->assertTrue($result);
    }
    
    /**
     * test sync with non existing collection id
     */
    public function testResetState()
    {
        $this->_folderBackend->resetState($this->_device);
        
        $state = $this->_folderBackend->getFolderState($this->_device, 'Contact');
        
        $this->assertTrue(empty($state));
    }
    
    public function testGetFolder()
    {
        $folder = $this->testCreate();
        
        $folder = $this->_folderBackend->getFolder($folder->deviceId, $folder->serverId);
        
        $this->assertTrue($folder->creationTime instanceof DateTime);
    }
    
    public function testGetFolderState()
    {
        $folder = self::getTestFolder($this->_device);
        $folder1 = $this->testCreate($folder);
        
        $folder = self::getTestFolder($this->_device);
        $folder->serverId = '1234567891';
        $folder2 = $this->testCreate($folder);
        
        $folders = $this->_folderBackend->getFolderState($folder->deviceId, Syncroton_Data_Factory::CLASS_CONTACTS);
        
        $this->assertEquals(2, count($folders));
        $this->assertArrayHasKey($folder1->serverId, $folders);
        $this->assertArrayHasKey($folder2->serverId, $folders);
    }
    
    public function testGetExceptionNotFound()
    {
        $this->setExpectedException('Syncroton_Exception_NotFound');
    
        $this->_folderBackend->get('invalidId');
    }
    
    /**
     * 
     * @return Syncroton_Model_Device
     */
    public static function getTestFolder(Syncroton_Model_IDevice $_device)
    {
        return new Syncroton_Model_Folder(array(
            'deviceId'         => $_device,
            'class'             => Syncroton_Data_Factory::CLASS_CONTACTS,
            'serverId'          => sha1(mt_rand(). microtime()),
            'parentId'          => null,
            'displayName'       => 'test contact folder',
            'type'              => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT,
            'creationTime'     => new DateTime(null, new DateTimeZone('utc')),
            'lastfiltertype'    => null
        ));
    }
}
