<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for acticesync
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * main controller for ActiveSync
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
class ActiveSync_Controller extends Tinebase_Controller_Abstract
{
    const CLASS_CONTACTS = 'Contacts';
    const CLASS_CALENDAR = 'Calendar';
    const CLASS_TASKS    = 'Tasks';
    const CLASS_EMAIL    = 'Email';
    const STORE_MAILBOX  = 'Mailbox';
    
    /**
     * holds the instance of the singleton
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
     * @var ActiveSync_Backend_ContentState
     */
    protected $_contentStateBackend;
    
    /**
     * constructor (get current user)
     */
    private function __construct() {
        #$this->_currentAccount   = Tinebase_Core::getUser();
        $this->_syncStateBackend    = new ActiveSync_Backend_SyncState();
        $this->_deviceBackend       = new ActiveSync_Backend_Device();
        $this->_contentStateBackend = new ActiveSync_Backend_ContentState();
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
     * the factory pattern for data controller
     *
     * @param string $_class the class name
     * @param ActiveSync_Model_Device $_device
     * @param Tinebase_DateTime $_syncTimeStamp
     * @return ActiveSync_Controller_Abstract
     */
    public static function dataFactory($_class, ActiveSync_Model_Device $_device, Tinebase_DateTime $_syncTimeStamp) 
    {
        switch($_class) {
            case self::CLASS_CONTACTS:
            case self::CLASS_CALENDAR:
            case self::CLASS_TASKS:
                $className = 'ActiveSync_Controller_' . $_class;
                $backend = new $className($_device, $_syncTimeStamp);
                break;
                
            case self::CLASS_EMAIL:
            case self::STORE_MAILBOX:
                $className = 'ActiveSync_Controller_' . self::CLASS_EMAIL;
                $backend = new $className($_device, $_syncTimeStamp);
                break;
                
            default:
                throw new Exception('unsupported class ActiveSync_Controller_' . $_class);
                break;
        }
        
        return $backend;
    }
    
    public function addContentState(ActiveSync_Model_ContentState $_state)
    {
        /**
         * if the entry got added earlier, and there was an error, the entry gets added again
         */
        try {
            $this->_contentStateBackend->create($_state);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->deleteContentState($_state);
            $this->_contentStateBackend->create($_state);
        }
    }
    
    public function deleteContentState(ActiveSync_Model_ContentState $_state)
    {
        // finaly delete all entries marked for removal
        $contentStateFilter = new ActiveSync_Model_ContentStateFilter(array(
            array(
                'field'     => 'device_id',
                'operator'  => 'equals',
                'value'     => $_state->device_id
                #'value'     => $_state->device_id instanceof ActiveSync_Model_Device ? $_state->device_id->getId() : $_state->device_id
            ),
            array(
                'field'     => 'class',
                'operator'  => 'equals',
                'value'     => $_state->class
            ),
            array(
                'field'     => 'collectionid',
                'operator'  => 'equals',
                'value'     => $_state->collectionid
            ),
            array(
                'field'     => 'contentid',
                'operator'  => 'equals',
        		'value'     => $_state->contentid
            )
        ));
        $stateIds = $this->_contentStateBackend->search($contentStateFilter, null, true);
        
        if(count($stateIds) > 0) {
            $this->_contentStateBackend->delete($stateIds);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " no contentstate found for " . print_r($contentStateFilter->toArray(), true));
        }
    }
    
    public function getContentState(ActiveSync_Model_Device $_device, $_class, $_collectionId, $_contentId)
    {
        $contentStateFilter = new ActiveSync_Model_ContentStateFilter(array(
            array(
                'field'     => 'device_id',
                'operator'  => 'equals',
                'value'     => $_device->getId()
            ),
            array(
                'field'     => 'class',
                'operator'  => 'equals',
                'value'     => $_class
            ),
            array(
                'field'     => 'collectionid',
                'operator'  => 'equals',
                'value'     => $_collectionId
            ),
            array(
                'field'     => 'contentid',
                'operator'  => 'equals',
        		'value'     => $_contentId
            )
        ));
        $state = $this->_contentStateBackend->search($contentStateFilter)->getFirstRecord();
    
        if (! $state instanceof ActiveSync_Model_ContentState) {
            throw new Tinebase_Exception_NotFound('state not found');
        }
        
        return $state;
    }
    
    /**
     * get sync state
     *
     * @param ActiveSync_Model_Device $_device
     * @param string $_type
     * @param string|optional $_counter
     * @return ActiveSync_Model_SyncState
     */
    public function getSyncState(ActiveSync_Model_Device $_device, $_class, $_collectionId = null, $_counter = NULL)
    {
        $type = $_collectionId !== NULL ? $_class . '-' . $_collectionId : $_class;
        
        $syncState = new ActiveSync_Model_SyncState(array(
            'device_id'  => $_device->getId(),
            'type'      => $type
        ));
        
        if($_counter !== NULL) {
            $syncState->counter = $_counter;
        }
        
        $syncState = $this->_syncStateBackend->get($syncState);
        
        if (!empty($syncState->pendingdata)) {
            $syncState->pendingdata = Zend_Json::decode($syncState->pendingdata);
        }
        
        return $syncState;
    }
    
    public function resetSyncState(ActiveSync_Model_SyncState $_syncState)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " reset sync state");
        
        $this->_syncStateBackend->delete($_syncState);
    }
    
    /**
     * delete contentstate (aka: forget that we have sent the entry to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_collectionId the collection id from the xml
     * @param string $_contentId the Tine 2.0 id of the entry
     */
    public function markContentStateAsDeleted(ActiveSync_Model_ContentState $_state)
    {
        $contentStateFilter = new ActiveSync_Model_ContentStateFilter(array(
            array(
                'field'     => 'device_id',
                'operator'  => 'equals',
                'value'     => $_state->device_id instanceof ActiveSync_Model_Device ? $_state->device_id->getId() : $_state->device_id
            ),
            array(
                'field'     => 'class',
                'operator'  => 'equals',
                'value'     => $_state->class
            ),
            array(
                'field'     => 'collectionid',
                'operator'  => 'equals',
                'value'     => $_state->collectionid
            ),
            array(
                'field'     => 'contentid',
                'operator'  => 'equals',
                'value'     => $_state->contentid
            )
        ));
        $state = $this->_contentStateBackend->search($contentStateFilter)->getFirstRecord();
    
        if($state != null) {
            $state->is_deleted = 1;
            $this->_contentStateBackend->update($state);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " no contentstate found for " . print_r($contentStateFilter->toArray(), true));
        }
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
     * @param string $_deviceType
     * @param string $_userAgent
     * @param string $_acsVersion
     * @return ActiveSync_Model_Device
     */
    public function getUserDevice($_deviceId, $_deviceType, $_userAgent, $_acsVersion)
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
        
        $devices = $this->searchDevice($deviceFilter);
        
        if(count($devices) > 0) {
            // update existing device
            $device = $devices[0];
            $device->useragent  = $_userAgent;
            $device->acsversion = $_acsVersion;
            $device->devicetype = $_deviceType;
            
            $device = $this->updateDevice($device);
        } else {
            // create new device
            $device = new ActiveSync_Model_Device(array(
                'deviceid'   => $_deviceId,
                'devicetype' => $_deviceType,
                'owner_id'   => Tinebase_Core::getUser()->accountId,
                'policy_id'  => 1,
                'useragent'  => $_userAgent,
                'acsversion' => $_acsVersion,
                'policykey'  => Syncope_Command_Provision::generatePolicyKey()
            ));

            $device = $this->createDevice($device);
        }
        
        return $device;
    }
    
    /**
     * update device information
     * 
     * @param ActiveSync_Model_Device $_device
     * @return ActiveSync_Model_Device
     */
    public function updateDevice(ActiveSync_Model_Device $_device)
    {
        $device = $this->_deviceBackend->update($_device);
        
        return $device;
    }
    
    /**
     * store device information
     * 
     * @param ActiveSync_Model_Device $_device
     * @return ActiveSync_Model_Device
     */
    public function createDevice(ActiveSync_Model_Device $_device)
    {
        $device = $this->_deviceBackend->create($_device);
        
        return $device;
    }
    
    /**
     * validate sync key
     * 
     * @param ActiveSync_Model_Device $_device
     * @param $_counter
     * @param $_class
     * @param $_collectionId
     * @return boolean
     */
    public function validateSyncKey(ActiveSync_Model_Device $_device, $_counter, $_class, $_collectionId = NULL)
    {
        $type = $_collectionId !== NULL ? $_class . '-' . $_collectionId : $_class;
        
        $syncState = new ActiveSync_Model_SyncState(array(
            'device_id'  => $_device->getId(),
            'counter'   => $_counter,
            'type'      => $type
        ));

        try {
            $syncState = $this->_syncStateBackend->get($syncState);
        } catch (ActiveSync_Exception_SyncStateNotFound $asessnf) {
            return false;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($syncState->toArray(), true));

        // check if this was the latest syncKey
        try {
            $otherSyncState = clone $syncState;
            $otherSyncState->counter++;
            $otherSyncState = $this->_syncStateBackend->get($otherSyncState);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' found more recent synckey');
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . print_r($otherSyncState->toArray(), true));
            
            // undelete marked entries 
            $contentStateFilter = new ActiveSync_Model_ContentStateFilter(array(
                array(
                    'field'     => 'device_id',
                    'operator'  => 'equals',
                    'value'     => $_device->getId()
                ),
                array(
                    'field'     => 'class',
                    'operator'  => 'equals',
                    'value'     => $_class
                ),
                array(
                    'field'     => 'collectionid',
                    'operator'  => 'equals',
                    'value'     => $_collectionId
                ),
                array(
                    'field'     => 'is_deleted',
                    'operator'  => 'equals',
                    'value'     => true
                )
            ));
            $stateIds = $this->_contentStateBackend->search($contentStateFilter, null, true);
            $this->_contentStateBackend->updateMultiple($stateIds, array('is_deleted' => 0));
                        
            // remove entries added during latest sync
            $contentStateFilter = new ActiveSync_Model_ContentStateFilter(array(
                array(
                    'field'     => 'device_id',
                    'operator'  => 'equals',
                    'value'     => $_device->getId()
                ),
                array(
                    'field'     => 'class',
                    'operator'  => 'equals',
                    'value'     => $_class
                ),
                array(
                    'field'     => 'collectionid',
                    'operator'  => 'equals',
                    'value'     => $_collectionId
                ),
                array(
                    'field'     => 'creation_time',
                    'operator'  => 'after',
                    'value'     => $syncState->lastsync
                )
            ));
            $stateIds = $this->_contentStateBackend->search($contentStateFilter, null, true);
            $this->_contentStateBackend->delete($stateIds);
            
        } catch (ActiveSync_Exception_SyncStateNotFound $asessnf) {
            // finaly delete all entries marked for removal 
            $contentStateFilter = new ActiveSync_Model_ContentStateFilter(array(
                array(
                    'field'     => 'device_id',
                    'operator'  => 'equals',
                    'value'     => $_device->getId()
                ),
                array(
                    'field'     => 'class',
                    'operator'  => 'equals',
                    'value'     => $_class
                ),
                array(
                    'field'     => 'collectionid',
                    'operator'  => 'equals',
                    'value'     => $_collectionId
                ),
                array(
                    'field'     => 'is_deleted',
                    'operator'  => 'equals',
                    'value'     => true
                )
            ));
            $stateIds = $this->_contentStateBackend->search($contentStateFilter, null, true);
            
            $this->_contentStateBackend->delete($stateIds);
            
        }
        
        // remove all other synckeys
        $this->_syncStateBackend->deleteOther($syncState);
        
        if (!empty($syncState->pendingdata)) {
            $syncState->pendingdata = Zend_Json::decode($syncState->pendingdata);
        }
        
        return $syncState;
    }
        
    /**
     * update sync key
     * 
     * @param ActiveSync_Model_Device $_device
     * @param Tinebase_DateTime $_counter
     * @param $_timeStamp
     * @param $_class
     * @param $_collectionId
     * @deprecated
     * @return void
     */
    public function updateSyncKey(ActiveSync_Model_Device $_device, $_counter, Tinebase_DateTime $_timeStamp, $_class, $_collectionId = NULL, $_keepPreviousSyncKey = true)
    {
        $type = $_collectionId !== NULL ? $_class . '-' . $_collectionId : $_class;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' update synckey to ' . $_counter . ' for type: ' . $type);
        
        $newSyncState = new ActiveSync_Model_SyncState(array(
            'device_id' => $_device->getId(),
            'counter'   => $_counter,
            'type'      => $type,
            'lastsync'  => $_timeStamp->get(Tinebase_Record_Abstract::ISO8601LONG)
        ));
                
        try {
            // check if we need to update synckey timestamps
            $this->_syncStateBackend->get($newSyncState);
            $this->_syncStateBackend->update($newSyncState);
        } catch (ActiveSync_Exception_SyncStateNotFound $asessnf) {
            // otherwise add new synckey
            $this->_syncStateBackend->create($newSyncState);
        }
        
        if ($_keepPreviousSyncKey !== true) {
            // remove all other synckeys
            $this->_syncStateBackend->deleteOther($newSyncState);
        }
        
        return $newSyncState;
    }
    
    /**
     * update sync state
     * 
     * @param ActiveSync_Model_SyncState $_state
     * @package bool $_keepPreviousSyncState
     * @return ActiveSync_Model_SyncState
     */
    public function updateSyncState(ActiveSync_Model_SyncState $_state, $_keepPreviousSyncState = true)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' update synckey to ' . $_state->counter . ' for type: ' . $_state->type);
        
        $state = clone $_state;
        
        if (is_array($state->pendingdata)) {
            $state->pendingdata = Zend_Json::encode($state->pendingdata);
        }
        
        try {
            // check if we need to update synckey timestamps
            $this->_syncStateBackend->get($state);
            $this->_syncStateBackend->update($state);
        } catch (ActiveSync_Exception_SyncStateNotFound $asessnf) {
            // otherwise add new synckey
            $this->_syncStateBackend->create($state);
        }
        
        if ($_keepPreviousSyncState !== true) {
            // remove all other synckeys
            $this->_syncStateBackend->deleteOther($state);
        }
        
        return $_state;
    }
}
