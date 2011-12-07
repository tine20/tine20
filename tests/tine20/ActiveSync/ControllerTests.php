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
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_ControllerTests::main');
}

/**
 * Test class for ActiveSync_Controller
 * 
 * @package     ActiveSync
 */
class ActiveSync_ControllerTests extends PHPUnit_Framework_TestCase
{
    
    /**
     * @var ActiveSync_Backend_SyncState backend
     */
    protected $_backend;
    
    /**
     * @var ActiveSync_Controller
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Backend SyncState Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected function setUp()
    {   	
        $this->_controller = ActiveSync_Controller::getInstance();
        
        $this->_backend = new ActiveSync_Backend_SyncState();
        
        $deviceBackend = new ActiveSync_Backend_Device();
        $this->_device = $deviceBackend->create(ActiveSync_Backend_DeviceTests::getTestDevice());
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_backend->delete(new ActiveSync_Model_SyncState(array(
        	'device_id'	=> $this->_device->getId(),
        	'type'      => 'class',
        )));
        
        $deviceBackend = new ActiveSync_Backend_Device();
        $deviceBackend->delete($this->_device->getId());
    }
    
    /**
     * @return ActiveSync_Model_SyncState
     */
    public function testCreateMultipleSyncStates()
    {
        $syncState = new ActiveSync_Model_SyncState(array(
        	'device_id'	=> $this->_device->getId(),
        	'type'      => 'class-collectiondId',
        	'counter'   => 1,
        	'lastsync'  => Tinebase_DateTime::now()->subMinute(2),
        	'pendingdata' => array('serverAdds' => array('1111', '2222'))
        ));
        $this->_controller->updateSyncState($syncState);
        
        $syncState->counter++;
        $syncState->lastsync = Tinebase_DateTime::now();
        $syncState->pendingdata = NULL;
        $this->_controller->updateSyncState($syncState);
    
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 1);
        
        $this->assertEquals('class-collectiondId', $syncState->type);
        $this->assertEquals(1,                     $syncState->counter);
        $this->assertContains('1111',              $syncState->pendingdata['serverAdds']);
    
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 2);
    
        $this->assertEquals('class-collectiondId', $syncState->type);
        $this->assertEquals(2,                     $syncState->counter);
        $this->assertEquals(null,                  $syncState->pendingdata);
    
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId');
    
        $this->assertEquals('class-collectiondId', $syncState->type);
        $this->assertEquals(2,          $syncState->counter);
    
        return $syncState;
    }
    
    /**
     * @return ActiveSync_Model_SyncState
     */
    public function testCreateMultipleSyncStatesDeprecated()
    {
        $this->_controller->updateSyncKey($this->_device, 1, Tinebase_DateTime::now()->subMinute(2), 'class', 'collectiondId');
        $this->_controller->updateSyncKey($this->_device, 2, Tinebase_DateTime::now(), 'class', 'collectiondId');
        
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 1);
        
        $this->assertEquals('class-collectiondId', $syncState->type);
        $this->assertEquals(1,          $syncState->counter);
        
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 2);
        
        $this->assertEquals('class-collectiondId', $syncState->type);
        $this->assertEquals(2,          $syncState->counter);
        
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId');
        
        $this->assertEquals('class-collectiondId', $syncState->type);
        $this->assertEquals(2,          $syncState->counter);
        
        return $syncState;
    }

    public function testValidateLatestSyncKey()
    {
        $this->testCreateMultipleSyncStates();
        
        $syncState = $this->_controller->validateSyncKey($this->_device, 2, 'class', 'collectiondId');
        
        $this->assertTrue($syncState instanceof ActiveSync_Model_SyncState);
        
        // latest synckey must still exists
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 2);
        
        $this->assertTrue($syncState instanceof ActiveSync_Model_SyncState);
        
        // previous synckey must be delete
        $this->setExpectedException('ActiveSync_Exception_SyncStateNotFound');
        
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 1);
    }
    
    public function testValidatePreviousSyncKey()
    {
        $this->testCreateMultipleSyncStates();
        
        $syncState = $this->_controller->validateSyncKey($this->_device, 1, 'class', 'collectiondId');
        
        $this->assertTrue($syncState instanceof ActiveSync_Model_SyncState);
        
        // previous synckey must still exists
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 1);
        
        $this->assertTrue($syncState instanceof ActiveSync_Model_SyncState);
        
        // latest synckey must be delete
        $this->setExpectedException('ActiveSync_Exception_SyncStateNotFound');
        
        $syncState = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 2);
    }
    
    public function testValidateInvalidSyncKey()
    {
        $this->testCreateMultipleSyncStates();
        
        $result = $this->_controller->validateSyncKey($this->_device, 3, 'class');
        
        $this->assertFalse($result);
    }
    
    public function testCreateUpdateDeleteContentState()
    {
        $this->testCreateMultipleSyncStates();
        
        $syncState1 = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 1);
        $syncState1 = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 2);
        
        // add new contentstate
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '11111',
            'creation_time' => $syncState1->lastsync
        ));
        $this->_controller->addContentState($contentState);
        
        // update contentstate
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '11111',
            'creation_time' => $syncState1->lastsync
        ));
        $this->_controller->addContentState($contentState);

        // add another contentstate
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '22222',
            'creation_time' => $syncState1->lastsync
        ));
        $this->_controller->addContentState($contentState);
        
        $contentStateBackend = new ActiveSync_Backend_ContentState();
        $allClientEntries = $contentStateBackend->getClientState($this->_device, 'class', 'collectiondId');
        
        $this->assertEquals(2, count($allClientEntries));
        
        $this->_controller->deleteContentState($contentState);

        $allClientEntries = $contentStateBackend->getClientState($this->_device, 'class', 'collectiondId');
        
        $this->assertEquals(1, count($allClientEntries));
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $this->_controller->getContentState($this->_device, $contentState->class, $contentState->collectionid, $contentState->contentid);
    }
    
    public function testCleanUpWithRecentSyncKey()
    {
        $this->testCreateMultipleSyncStates();
        
        $syncState1 = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 1);
        $syncState2 = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 2);
        
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '11111',
            'creation_time' => $syncState1->lastsync
        ));
        $this->_controller->addContentState($contentState);
        
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '22222',
            'creation_time' => $syncState1->lastsync
        ));
        $this->_controller->addContentState($contentState);
        
        $contentStateBackend = new ActiveSync_Backend_ContentState();
        $allClientEntries = $contentStateBackend->getClientState($this->_device, 'class', 'collectiondId');
        
        $this->assertEquals(2, count($allClientEntries));
        
        $this->_controller->markContentStateAsDeleted($contentState);
        
        $this->_controller->validateSyncKey($this->_device, '2', 'class', 'collectiondId');

        $allClientEntries = $contentStateBackend->getClientState($this->_device, 'class', 'collectiondId');
        
        $this->assertEquals(1,       count($allClientEntries));
        $this->assertContains('11111', $allClientEntries);
        $this->assertNotContains('22222', $allClientEntries);
    }
    
    public function testCleanUpWithPreviousSyncKey()
    {
        $this->testCreateMultipleSyncStates();
        
        $syncState1 = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 1);
        $syncState2 = $this->_controller->getSyncState($this->_device, 'class', 'collectiondId', 2);
        
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '11111',
            'creation_time' => $syncState1->lastsync
        ));
        $this->_controller->addContentState($contentState);
        
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '22222',
            'creation_time' => $syncState1->lastsync
        ));
        $this->_controller->addContentState($contentState);
        
        $contentState3 = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => 'class',
            'collectionid'  => 'collectiondId',
            'contentid'     => '33333',
            'creation_time' => $syncState2->lastsync
        ));
        $this->_controller->addContentState($contentState3);
        
        
        $contentStateBackend = new ActiveSync_Backend_ContentState();
        $allClientEntries = $contentStateBackend->getClientState($this->_device, 'class', 'collectiondId');
        $this->assertEquals(3, count($allClientEntries), 'mismatch in ' . __LINE__);
        
        $this->_controller->markContentStateAsDeleted($contentState);
        
        $this->_controller->validateSyncKey($this->_device, '1', 'class', 'collectiondId');

        $allClientEntries = $contentStateBackend->getClientState($this->_device, 'class', 'collectiondId');
        
        $this->assertEquals(2,         count($allClientEntries), 'mismatch in ' . __LINE__);
        $this->assertContains('11111', $allClientEntries, 'mismatch in ' . __LINE__);
        $this->assertContains('22222', $allClientEntries, 'mismatch in ' . __LINE__);
        $this->assertNotContains('33333', $allClientEntries, 'mismatch in ' . __LINE__);
    }
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Controller::main') {
    ActiveSync_Controller::main();
}
