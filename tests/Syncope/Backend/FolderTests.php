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
     * @var Syncope_Model_IDevice
     */
    protected $_device;
    
    /**
     * @var Syncope_Backend_IDevice
     */
    protected $_deviceBackend;

    /**
     * @var Syncope_Backend_IFolder
     */
    protected $_folderStateBackend;
    
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
        $this->_folderStateBackend = new Syncope_Backend_Folder($this->_db);

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
    public function testCreate()
    {
        $folderState = new Syncope_Model_Folder(array(
            'device_id'         => $this->_device,
            'class'             => Syncope_Data_Factory::CONTACTS,
            'folderid'          => '1234567890',
            'creation_time'     => new DateTime(null, new DateTimeZone('utc')),
            'lastfiltertype'    => null
        ));
        
        $folderState = $this->_folderStateBackend->create($folderState);
        
        $this->assertTrue($folderState->creation_time instanceof DateTime);
        
        return $folderState;
    }
    
    /**
     * test sync with non existing collection id
     */
    public function testResetState()
    {
        $this->_folderStateBackend->resetState($this->_device);
        
        $state = $this->_folderStateBackend->getClientState($this->_device, 'Contact');
        
        $this->assertEmpty($state);
    }
    
    public function testGetFolder()
    {
        $folderState = $this->testCreate();
        
        $folderState = $this->_folderStateBackend->getFolder($folderState->device_id, $folderState->folderid);
        
        $this->assertTrue($folderState->creation_time instanceof DateTime);
    }
}
