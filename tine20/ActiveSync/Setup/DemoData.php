<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for ActiveSync initialization
 *
 * @package     Setup
 */
class ActiveSync_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Setup_DemoData
     */
    private static $_instance = NULL;
    
    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName = 'ActiveSync';
    
    /**
     * the controller
     * 
     * @var ActiveSync_Controller_Device
     */
    protected $_controller;
    
    /**
     * models to work on
     * @var array
     */
    protected $_models = array('device');
    
    /**
     * the constructor
     */
    private function __construct()
    {
        $this->_controller = ActiveSync_Controller_Device::getInstance();
    }

    /**
     * the singleton pattern
     *
     * @return ActiveSync_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }
    
    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     * @todo improve check
     */
    public static function hasBeenRun()
    {
        $c = ActiveSync_Controller_Device::getInstance();

        return ($c->getAll()->count() > 3) ? true : false;
    }
    
    /**
     * @see Tinebase_Setup_DemoData_Abstract
     * 
     */
    protected function _onCreate()
    {
        $this->_createDevices();
    }
    
    /**
     * creates a contact and the image, if given
     *
     * @todo use demodata "magic" methods?
     */
    protected function _createDevices()
    {
        $devices = array(
            new ActiveSync_Model_Device(array(
                'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                'devicetype' => Syncroton_Model_Device::TYPE_ANDROID,
                'owner_id'   => Tinebase_Core::getUser()->getId(),
                'policy_id'  => null,
                'useragent'  => 'Android/4.1-EAS-1.3',
                'policykey'  => 1,
                'acsversion' => '12.1',
                'remotewipe' => 0
            )),
            new ActiveSync_Model_Device(array(
                'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                'devicetype' => Syncroton_Model_Device::TYPE_ANDROID,
                'owner_id'   => Tinebase_Core::getUser()->getId(),
                'policy_id'  => null,
                'useragent'  => 'Android/4.1-EAS-1.3',
                'policykey'  => 1,
                'acsversion' => '14.1',
                'remotewipe' => 0
            )),
            new ActiveSync_Model_Device(array(
                'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                'devicetype' => Syncroton_Model_Device::TYPE_ANDROID,
                'owner_id'   => Tinebase_Core::getUser()->getId(),
                'policy_id'  => null,
                'useragent'  => 'blabla',
                'policykey'  => 1,
                'acsversion' => '12.0',
                'remotewipe' => 0
            )),
            new ActiveSync_Model_Device(array(
                'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                'devicetype' => 'SAMSUNGGTI9100',
                'owner_id'   => Tinebase_Core::getUser()->getId(),
                'policy_id'  => null,
                'useragent'  => 'SAMSUNG-GT-I9100/100.20304',
                'policykey'  => 1,
                'acsversion' => '12.1',
                'remotewipe' => 0
            )),
            new ActiveSync_Model_Device(array(
                'deviceid'   => Tinebase_Record_Abstract::generateUID(64),
                'devicetype' => Syncroton_Model_Device::TYPE_IPHONE,
                'owner_id'   => Tinebase_Core::getUser()->getId(),
                'policy_id'  => null,
                'useragent'  => 'blabla',
                'policykey'  => 1,
                'acsversion' => '12.1',
                'remotewipe' => 0
            )),
        );

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating ' . count($devices) . ' test devices');

        foreach ($devices as $device) {
            ActiveSync_Controller_Device::getInstance()->create($device);
        }
    }
}
