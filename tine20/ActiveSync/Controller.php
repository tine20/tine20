<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for acticesync
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.html AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * main controller for ActiveSync
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
class ActiveSync_Controller extends Tinebase_Application_Controller_Abstract implements Tinebase_Events_Interface
{
    /**
     * holdes the instance of the singleton
     *
     * @var ActiveSync_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the syncstate sql backend
     *
     * @var ActiveSync_Backend_SyncState
     */
    protected $_syncStateBackend;
    
    /**
     * device backend
     *
     * @var ActiveSync_Backend_Device
     */
    protected $_deviceBackend;
    
    /**
     * constructor (get current user)
     */
    private function __construct() {
        #$this->_currentAccount   = Tinebase_Core::getUser();
        $this->_syncStateBackend    = new ActiveSync_Backend_SyncState();
        $this->_deviceBackend       = new ActiveSync_Backend_Device();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return ActiveSync_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ActiveSync_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Events_Abstract $_eventObject the eventObject
     * 
     * @todo    write test
     */
    public function handleEvents(Tinebase_Events_Abstract $_eventObject)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));        
    }

    /**
     * authenticate user against tine20 user database
     *
     * @param string $_username the username
     * @param string $_password the password
     * @param string $_ipAddress the ip address
     * @return boolean false on failure, true on success
     */
    public function authenticate($_username, $_password, $_ipAddress)
    {
        $pos = strrchr($_username, '\\');
        
        if($pos !== false) {
            $username = substr(strrchr($_username, '\\'), 1);
        } else {
            $username = $_username;
        }
        
        return Tinebase_Controller::getInstance()->login($username, $_password, $_ipAddress);
    }

    /**
     * the factory pattern for data controller
     *
     * @param string $_class the class name
     * @return ActiveSync_Controller_Abstract
     */
    public static function dataFactory($_class, Zend_Date $_syncTimeStamp) 
    {
        switch($_class) {
            case 'Contacts':
            case 'Tasks':
                $className = 'ActiveSync_Controller_' . $_class;
                $backend = new $className($_syncTimeStamp);
                break;
                
            default:
                throw new Exception('unsupported class ActiveSync_Controller_' . $_class);
                break;
        }
        
        return $backend;
    }
    
    /**
     * get sync state
     *
     * @param ActiveSync_Model_Device $_device
     * @param string $_type
     * @param string|optional $_counter
     * @return ActiveSync_Model_SyncState
     */
    public function getSyncState(ActiveSync_Model_Device $_device, $_type, $_counter = NULL)
    {
        $syncState = new ActiveSync_Model_SyncState(array(
            'device_id'  => $_device->getId(),
            'type'      => $_type
        ));
        
        if($_counter !== NULL) {
            $syncState->counter = $_counter;
        }
        
        $syncState = $this->_syncStateBackend->get($syncState);
        
        return $syncState;
    }
    
    /**
     * search device
     *
     * @param string $_deviceId
     * @param string $_type
     * @param string|optional $_counter
     * @return ActiveSync_Model_SyncState
     */
    public function searchDevice(ActiveSync_Model_DeviceFilter $_deviceFilter)
    {
        $device = $this->_deviceBackend->search($_deviceFilter);
        
        return $device;
    }
    
    /**
     * get user device
     *
     * @param string $_deviceId
     * @param string $_type
     * @param string|optional $_counter
     * @return ActiveSync_Model_Device
     */
    public function getUserDevice($_deviceId, $_userAgent, $_acsVersion)
    {
        $deviceFilter = new ActiveSync_Model_DeviceFilter(array(
            array(
                'field'     => 'deviceid',
                'operator'  => 'equals',
                'value'     => $_deviceId
            ),
            array(
                'field'     => 'owner_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Core::getUser()->accountId
            )
        ));
        
        $devices = $this->_deviceBackend->search($deviceFilter);
        
        if(count($devices) > 0) {
            // update existing device
            $device = $devices[0];
            $device->useragent = $_userAgent;
            $device->acsversion = $_acsVersion;
            
            $device = $this->_deviceBackend->update($device);
        } else {
            // create new device
            $device = new ActiveSync_Model_Device(array(
                'deviceid'  => $_deviceId,
                'owner_id'  => Tinebase_Core::getUser()->accountId,
                'policy_id' => 1,
                'useragent' => $_userAgent,
                'acsversion' => $_acsVersion,
                'policykey' => ActiveSync_Command_Provision::generatePolicyKey()
            ));

            $device = $this->_deviceBackend->create($device);
        }
        
        return $device;
    }
    
    public function updateDevice(ActiveSync_Model_Device $_device)
    {
        $device = $this->_deviceBackend->update($_device);
        
        return $device;
    }
    
    public function validateSyncKey(ActiveSync_Model_Device $_device, $_counter, $_type)
    {
        $syncState = new ActiveSync_Model_SyncState(array(
            'device_id'  => $_device->getId(),
            'counter'   => $_counter,
            'type'      => $_type
        ));
                
        try {
            $syncState = $this->_syncStateBackend->get($syncState);
            return true;
        } catch (ActiveSync_Exception_SyncStateNotFound $e) {
            // syncKey not found
            return false;
        }
    }
        
    public function updateSyncKey(ActiveSync_Model_Device $_device, $_counter, $_type, Zend_Date $_timeStamp)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' update synckey to ' . $_counter);
        
        $syncState = new ActiveSync_Model_SyncState(array(
            'device_id' => $_device->getId(),
            'counter'   => $_counter,
            'type'      => $_type,
            'lastsync'  => $_timeStamp->get(Tinebase_Record_Abstract::ISO8601LONG)
        ));
                
        try {
            // try to read current syncState
            $this->_syncStateBackend->get($syncState);
            // update syncState
            $this->_syncStateBackend->update($syncState);
        } catch (Exception $e) {
            // delete all old syncStates
            $this->_syncStateBackend->delete($syncState);
            // add new syncState
            $this->_syncStateBackend->create($syncState);
        }
    }
}
