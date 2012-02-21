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
        
        $result = $this->_deviceBackend->delete($device);

        $this->assertTrue($result);
    }
    
    public function testGetExceptionNotFound()
    {
        $this->setExpectedException('Syncope_Exception_NotFound');
    
        $this->_deviceBackend->get('invalidId');
    }
    
    public function testGetUserDevice()
    {
        $device = $this->testCreateDevice();
        
        $userDevice = $this->_deviceBackend->getUserDevice('1234', 'iphone-abcd');
        
        $this->assertEquals($device->id, $userDevice->id);
        
        $this->setExpectedException('Syncope_Exception_NotFound');
        
        $userDevice = $this->_deviceBackend->getUserDevice('1234', 'iphone-xyz');
    }
    
    /**
     * 
     * @return Syncope_Model_Device
     */
    public static function getTestDevice($_type = null)
    {
        switch($_type) {
            case Syncope_Model_Device::TYPE_ANDROID:
                $device = new Syncope_Model_Device(array(
                    'deviceid'   => 'android-abcd',
                    'devicetype' => Syncope_Model_Device::TYPE_ANDROID,
                    'policykey'  => 1,
                    'policy_id'  => 1,
                    'owner_id'   => '1234',
                    'useragent'  => 'blabla',
                    'acsversion' => '12.0',
                    'remotewipe' => 0
                )); 
                break;
            
            case Syncope_Model_Device::TYPE_WEBOS:
                $device = new Syncope_Model_Device(array(
                    'deviceid'   => 'webos-abcd',
                    'devicetype' => Syncope_Model_Device::TYPE_ANDROID,
                    'policykey'  => 1,
                    'policy_id'  => 1,
                    'owner_id'   => '1234',
                    'useragent'  => 'blabla',
                    'acsversion' => '12.0',
                    'remotewipe' => 0
                )); 
                break;
            
            case Syncope_Model_Device::TYPE_IPHONE:
            default:
                $device = new Syncope_Model_Device(array(
                    'deviceid'   => 'iphone-abcd',
                    'devicetype' => Syncope_Model_Device::TYPE_IPHONE,
                    'policykey'  => 1,
                    'policy_id'  => 1,
                    'owner_id'   => '1234',
                    'useragent'  => 'blabla',
                    'acsversion' => '2.5',
                    'remotewipe' => 0
                )); 
                break;
        }

        return $device; 
    }
}
