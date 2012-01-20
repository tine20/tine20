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
class Syncope_Backend_DeviceTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncope_Backend_Device
     */
    protected $_deviceBackend;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('Syncope ActiveSync Sync command tests');
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
        
        $this->_deviceBackend = new Syncope_Backend_Device($this->_db);
        
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
     * test sync with non existing collection id
     */
    public function testCreateDevice()
    {
        $newDevice = Syncope_Backend_DeviceTests::getTestDevice();
        
        $device = $this->_deviceBackend->create($newDevice);
        
        #var_dump($device);
        
        return $device;
    }
    
    public function testDeleteDevice()
    {
        $device = $this->testCreateDevice();
        
        $this->_deviceBackend->delete($device);
    }
    
    /**
     * 
     * @return Syncope_Model_Device
     */
    public static function getTestDevice()
    {
        return new Syncope_Model_Device(array(
        	'deviceid'   => '1234',
        	'devicetype' => 'iphone',
        	'policykey'  => 1,
        	'owner_id'   => '1234',
        	'acsversion' => '12.0',
        	'remotewipe' => 0
        ));
        
    }
}
