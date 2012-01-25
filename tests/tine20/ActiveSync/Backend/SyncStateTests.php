<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Backend_SyncStateTests::main');
}

/**
 * Test class for ActiveSync_Backend_SyncState
 * 
 * @package     ActiveSync
 */
class ActiveSync_Backend_SyncStateTests extends PHPUnit_Framework_TestCase
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

    protected $_syncStateBackend;
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Backend SyncState Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected function setUp()
    {   	
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_deviceBackend    = new ActiveSync_Backend_DeviceFacade();
        $this->_folderBackend    = new ActiveSync_Backend_FolderFacade();
        $this->_syncStateBackend = new Syncope_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_');
        
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
     * @return Syncope_Model_ISyncState
     */
    public function testCreate()
    {
        $syncState = new Syncope_Model_SyncState(array(
            'device_id'   => $this->_device,
            'type'        => 'FolderSync',
            'counter'     => '0',
            'lastsync'    => new DateTime(null, new DateTimeZone('utc')),
            'pendingdata' => null
        ));
        
        $syncState = $this->_syncStateBackend->create($syncState);
        
        $this->assertTrue($syncState->lastsync instanceof DateTime);
        
        return $syncState;
    }
    
    /**
     * @return Syncope_Model_ISyncState
     */
    public function testUpdate()
    {
        $syncState = $this->testCreate();
        
        $syncState->counter++;
    
        $syncState = $this->_syncStateBackend->update($syncState);
    
        $this->assertEquals(1, $syncState->counter);
        $this->assertTrue($syncState->lastsync instanceof DateTime);
    
        return $syncState;
    }
    
    /**
     * test validating synckey
     */
    public function testValidateSyncKey()
    {
        $syncState = $this->testUpdate();
        
        $validatedSyncState =  $this->_syncStateBackend->validate($this->_device, 'FolderSync', 1);
        
        $this->assertTrue($validatedSyncState instanceof Syncope_Model_ISyncState);
        $this->assertEquals(1, $validatedSyncState->counter);
        $this->assertTrue($validatedSyncState->lastsync instanceof DateTime);
        
        
        // invalid synckey must return false
        $validatedSyncState =  $this->_syncStateBackend->validate($this->_device, 'FolderSync', 2);
        
        $this->assertFalse($validatedSyncState);
    }
        
    /**
     * test if the previous synckey gets deleted after validating the lastest synckey
     */
    public function testDeletePreviousSynckeyAfterValidate()
    {
        $syncState = new Syncope_Model_SyncState(array(
            'device_id'   => $this->_device,
            'type'        => 'FolderSync',
            'counter'     => '0',
            'lastsync'    => new DateTime(null, new DateTimeZone('utc')),
            'pendingdata' => null
        ));
        $syncState->lastsync->modify('-2 min');
        $syncState = $this->_syncStateBackend->create($syncState);
        
        $syncState = new Syncope_Model_SyncState(array(
            'device_id'   => $this->_device,
            'type'        => 'FolderSync',
            'counter'     => '1',
            'lastsync'    => new DateTime(null, new DateTimeZone('utc')),
            'pendingdata' => null
        ));
        
        $syncState = $this->_syncStateBackend->create($syncState);
    
        $syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', '1');
    
        $this->assertEquals('FolderSync', $syncState->type);
        $this->assertEquals(1,            $syncState->counter);
        
        
        // the other synckey must be deleted now
        $syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', '0');
    
        $this->assertFalse($syncState);
    }
    
    /**
     * test if the latest synckey gets deleted after validating the previous synckey
     */
    public function testDeleteLatestSynckeyAfterValidate()
    {
        $syncState = new Syncope_Model_SyncState(array(
            'device_id'   => $this->_device,
            'type'        => 'FolderSync',
            'counter'     => '0',
            'lastsync'    => new DateTime(null, new DateTimeZone('utc')),
            'pendingdata' => null
        ));
        $syncState->lastsync->modify('-2 min');
        $syncState = $this->_syncStateBackend->create($syncState);
        
        $syncState = new Syncope_Model_SyncState(array(
            'device_id'   => $this->_device,
            'type'        => 'FolderSync',
            'counter'     => '1',
            'lastsync'    => new DateTime(null, new DateTimeZone('utc')),
            'pendingdata' => null
        ));
        
        $syncState = $this->_syncStateBackend->create($syncState);
    
        $syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', '0');
    
        $this->assertEquals('FolderSync', $syncState->type);
        $this->assertEquals(0,            $syncState->counter);
        
        
        // the other synckey must be deleted now
        $syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', '1');
    
        $this->assertFalse($syncState);
    }
}
