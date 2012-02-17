<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Syncope_Command_Sync
 * 
 * @package     Backend
 */
class Syncope_Backend_FolderTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncope_Model_Device
     */
    protected $_device;
    
    /**
     * @var Syncope_Backend_Device
     */
    protected $_deviceBackend;

    /**
     * @var Syncope_Backend_Folder
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
        $suite  = new PHPUnit_Framework_TestSuite('Syncope Folder backend tests');
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

        $this->_deviceBackend      = new Syncope_Backend_Device($this->_db);
        $this->_folderBackend = new Syncope_Backend_Folder($this->_db);

        $newDevice = Syncope_Backend_DeviceTests::getTestDevice();
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
     * @return Syncope_Model_IFolder
     */
    public function testCreate(Syncope_Model_IFolder $_folder = null)
    {
        $folder = $_folder instanceof Syncope_Model_IFolder ? $_folder : self::getTestFolder($this->_device);
        
        $folder = $this->_folderBackend->create($folder);
                
        $this->assertTrue($folder->creation_time instanceof DateTime);
        $this->assertFalse(empty($folder->displayname));
        
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
        
        $folder = $this->_folderBackend->getFolder($folder->device_id, $folder->folderid);
        
        $this->assertTrue($folder->creation_time instanceof DateTime);
    }
    
    public function testGetFolderState()
    {
        $folder = self::getTestFolder($this->_device);
        $folder1 = $this->testCreate($folder);
        
        $folder = self::getTestFolder($this->_device);
        $folder->folderid = '1234567891';
        $folder2 = $this->testCreate($folder);
        
        $folders = $this->_folderBackend->getFolderState($folder->device_id, Syncope_Data_Factory::CLASS_CONTACTS);
        
        $this->assertEquals(2, count($folders));
        $this->assertArrayHasKey($folder1->folderid, $folders);
        $this->assertArrayHasKey($folder2->folderid, $folders);
    }
    
    public function testGetExceptionNotFound()
    {
        $this->setExpectedException('Syncope_Exception_NotFound');
    
        $this->_folderBackend->get('invalidId');
    }
    
    /**
     * 
     * @return Syncope_Model_Device
     */
    public static function getTestFolder(Syncope_Model_IDevice $_device)
    {
        return new Syncope_Model_Folder(array(
            'device_id'         => $_device,
            'class'             => Syncope_Data_Factory::CLASS_CONTACTS,
            'folderid'          => sha1(mt_rand(). microtime()),
            'parentid'          => null,
            'displayname'       => 'test contact folder',
            'type'              => Syncope_Command_FolderSync::FOLDERTYPE_CONTACT,
            'creation_time'     => new DateTime(null, new DateTimeZone('utc')),
            'lastfiltertype'    => null
        ));
    }
}
