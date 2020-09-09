<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * controller for ActiveSync
 *
 * @package     ActiveSync
 */
class ActiveSync_Controller extends Tinebase_Controller_Event
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'ActiveSync';

    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Controller
     */
    private static $_instance = NULL;
    
    /**
     * constructor
     */
    private function __construct() 
    {
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
     * reset sync for user
     *
     * @param mixed $user
     * @param array|string $classesToReset
     * @return boolean
     */
    public function resetSyncForUser($user, $classesToReset)
    {
        if (is_string($classesToReset)) {
            $classesToReset = array($classesToReset);
        }

        if (! $user instanceof Tinebase_Model_User) {
            try {
                $user = Tinebase_User::getInstance()->getFullUserById($user);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountLoginName', $user);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Resetting sync for user ' . $user->accountDisplayName . ' collections: ' . print_r($classesToReset, true));

        self::initSyncrotonRegistry();
        
        $devices = $this->_getDevicesForUser($user->getId());
        
        foreach ($devices as $device) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Resetting device' . $device->friendlyname . ' / id: ' . $device->getId());
            
            foreach ($classesToReset as $class) {
                $folderToReset = $this->_getFoldersForDeviceAndClass($device, $class);
                
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Resetting ' . count($folderToReset) . ' folder(s) for class ' . $class);
                
                foreach ($folderToReset as $folderState) {
                    Syncroton_Registry::getSyncStateBackend()->resetState($device->getId(), $folderState->id);
                }
            }
        }
        
        return true;
    }
    
    /**
     * fetch devices for user
     * 
     * @param string $userId
     * @return Tinebase_Record_RecordSet of ActiveSync_Model_Device
     */
    protected function _getDevicesForUser($userId)
    {
        $deviceBackend = new ActiveSync_Backend_Device();
        $deviceFilter = new ActiveSync_Model_DeviceFilter(array(
            array('field' => 'owner_id', 'operator' => 'equals', 'value' => $userId)
        ));
        $devices = $deviceBackend->search($deviceFilter);
        return $devices;
    }
    
    protected function _getFoldersForDeviceAndClass($device, $class)
    {
        $folderState = Syncroton_Registry::getFolderBackend()->getFolderState($device->getId(), $class);
        return $folderState;
    }
    
    public static function initSyncrotonRegistry()
    {
        Syncroton_Registry::setDatabase(Tinebase_Core::getDb());
        Syncroton_Registry::setTransactionManager(Tinebase_TransactionManager::getInstance());
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::LOGGERBACKEND,       Tinebase_Core::getLogger());
        Syncroton_Registry::set(Syncroton_Registry::SESSION_VALIDATOR,   function() {
            return ! Tinebase_Core::inMaintenanceMode();
        });
    }

    /**
     * event handler function
     *
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case 'Tinebase_Event_User_DeleteAccount':
                $devices = $this->_getDevicesForUser($_eventObject->account->getId());
                ActiveSync_Controller_Device::getInstance()->delete($devices->getArrayOfIds());
                break;
        }
    }
}
