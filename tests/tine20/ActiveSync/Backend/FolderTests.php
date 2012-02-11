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
* Test helper
*/
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Syncope_Command_Sync
 * 
 * @package     Backend
 */
class ActiveSync_Backend_FolderTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
    /**
     * @var ActiveSync_Backend_DeviceFacade
     */
    protected $_deviceBackend;

    /**
     * @var ActiveSync_Backend_FolderFacade
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $this->_deviceBackend = new ActiveSync_Backend_DeviceFacade();
        $this->_folderBackend = new ActiveSync_Backend_FolderFacade();

        $this->_device = $this->_deviceBackend->create(
            ActiveSync_Backend_DeviceTests::getTestDevice()
        );
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * @return Syncope_Model_IFolder
     */
    public function testCreate(Syncope_Model_IFolder $_folder = null)
    {
        $folder = $_folder instanceof Syncope_Model_IFolder ? $_folder : self::getTestFolder($this->_device);
        
        $folder = $this->_folderBackend->create($folder);
                
        $this->assertTrue($folder->creation_time instanceof DateTime);
        $this->assertTrue(!empty($folder->displayname));
        
        return $folder;
    }
    
    /**
     */
    public function testDelete()
    {
        $folder = $this->testCreate();
    
        $this->_folderBackend->delete($folder);
        
        $this->setExpectedException('Syncope_Exception_NotFound');
        
        $folder = $this->_folderBackend->get($folder->id);
    }
    
    /**
     * test sync with non existing collection id
     */
    public function testResetState()
    {
        $this->_folderBackend->resetState($this->_device);
        
        $state = $this->_folderBackend->getFolderState($this->_device, 'Contact');
        
        $this->assertEmpty($state);
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
    
    /**
     * 
     * @return Syncope_Model_Device
     */
    public static function getTestFolder(Syncope_Model_IDevice $_device)
    {
        return new ActiveSync_Model_Folder(array(
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
