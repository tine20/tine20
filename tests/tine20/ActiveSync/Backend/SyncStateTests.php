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
     * @var ActiveSync_Backend_SyncState backend
     */
    protected $_backend;
    
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
        	'type'      => 'testsync',
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
        	'type'      => 'testsync',
        	'counter'   => 1,
        	'lastsync'  => Tinebase_DateTime::now()->subMinute(2)->get(Tinebase_Record_Abstract::ISO8601LONG)
        ));
        
        $this->_backend->create($syncState);
        
        $syncState = new ActiveSync_Model_SyncState(array(
        	'device_id'	=> $this->_device->getId(),
        	'type'      => 'testsync',
        	'counter'   => 2,
        	'lastsync'  => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
        ));
        
        $this->_backend->create($syncState);
        
        $syncState = $this->_backend->get($syncState);
        
        $this->assertEquals('testsync', $syncState->type);
        $this->assertEquals(2,          $syncState->counter);
        
        return $syncState;
    }
    
    public function testDeleteOther()
    {
        $syncState = $this->testCreateMultipleSyncStates();

        $this->_backend->deleteOther($syncState);
        
        $syncState = $this->_backend->get($syncState);
        
        $this->assertTrue($syncState instanceof ActiveSync_Model_SyncState);
        
        $syncState->counter--;
        
        $this->setExpectedException('ActiveSync_Exception_SyncStateNotFound');
        
        $this->_backend->get($syncState);
    }
}
