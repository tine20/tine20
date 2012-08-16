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
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Backend_DeviceTests::main');
}

/**
 * Test class for ActiveSync_Backend_Device
 * 
 * @package     ActiveSync
 */
class ActiveSync_Backend_DeviceTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveSync_Backend_Device
     */
    protected $_deviceBackend;
    
    /**
     * @var ActiveSync_Backend_Device backend
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Backend Device Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_deviceBackend = new ActiveSync_Backend_Device();
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
    * test sync with non existing collection id
    */
    public function testCreateDevice()
    {
        $newDevice = ActiveSync_Backend_DeviceTests::getTestDevice();
    
        $device = $this->_deviceBackend->create($newDevice);
    
        #var_dump($device);
    
        return $device;
    }
    
    public function testDeleteDevice()
    {
        $device = $this->testCreateDevice();
    
        $this->_deviceBackend->delete($device);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $this->_deviceBackend->get($device);
    }
    
    /**
     * create ActiveSync_Model_Device to be used in tests
     * 
     * @return ActiveSync_Model_Device
     */
    public static function getTestDevice($_type = null)
    {
        switch($_type) {
            case Syncroton_Model_Device::TYPE_ANDROID:
                $device = new ActiveSync_Model_Device(array(
                    'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                    'devicetype' => $_type,
                    'owner_id'   => Tinebase_Core::getUser()->getId(),
                    'policy_id'  => null,
                    'useragent'  => 'Android/4.1-EAS-1.3',
                    'policykey'  => 1,
                    'acsversion' => '12.1',
                    'remotewipe' => 0
                ));
                break;
            
            case Syncroton_Model_Device::TYPE_ANDROID_40:
                $device = new ActiveSync_Model_Device(array(
                    'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                    'devicetype' => Syncroton_Model_Device::TYPE_ANDROID,
                    'owner_id'   => Tinebase_Core::getUser()->getId(),
                    'policy_id'  => null,
                    'useragent'  => 'Android/4.1-EAS-1.3',
                    'policykey'  => 1,
                    'acsversion' => '14.1',
                    'remotewipe' => 0
                ));
                break;
            
            case Syncroton_Model_Device::TYPE_WEBOS:
                $device = new ActiveSync_Model_Device(array(
                    'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                    'devicetype' => $_type,
                    'owner_id'   => Tinebase_Core::getUser()->getId(),
                    'policy_id'  => null,
                    'useragent'  => 'blabla',
                    'policykey'  => 1,
                    'acsversion' => '12.0',
                    'remotewipe' => 0
                ));
                break;
                
            case Syncroton_Model_Device::TYPE_SMASUNGGALAXYS2:
                $device = new ActiveSync_Model_Device(array(
                    'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                    'devicetype' => 'SAMSUNGGTI9100',
                    'owner_id'   => Tinebase_Core::getUser()->getId(),
                    'policy_id'  => null,
                    'useragent'  => 'SAMSUNG-GT-I9100/100.20304',
                    'policykey'  => 1,
                    'acsversion' => '12.1',
                    'remotewipe' => 0
                ));
                break;
                
            case Syncroton_Model_Device::TYPE_IPHONE:
            default:
                $device = new ActiveSync_Model_Device(array(
                    'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                    'devicetype' => Syncroton_Model_Device::TYPE_IPHONE,
                    'owner_id'   => Tinebase_Core::getUser()->getId(),
                    'policy_id'  => null,
                    'useragent'  => 'blabla',
                    'policykey'  => 1,
                    'acsversion' => '12.1',
                    'remotewipe' => 0
                ));
                break;
        }

        return $device;
    }
}
