<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * device controller for ActiveSync
 *
 * @package     ActiveSync
 * @subpackage  Controller
 */
class ActiveSync_Controller_Device extends Tinebase_Controller_Record_Abstract
{
    /**
     * the salutation backend
     *
     * @var ActiveSync_Backend_Device
     */
    protected $_backend;
    
    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Controller_Device
     */
    private static $_instance = NULL;

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'ActiveSync_Model_Device';
    
    /**
     * the singleton pattern
     *
     * @return ActiveSync_Controller_Device
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ActiveSync_Controller_Device();
        }
        
        return self::$_instance;
    }
            
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend         = new ActiveSync_Backend_Device();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo use this function in other create + update functions
     * @todo invent concept for simple adding of grants (plugins?) 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        $hasGrant = false;
        
        if (Tinebase_Core::getUser()->hasRight('ActiveSync', Tinebase_Acl_Rights::ADMIN) || $_record->owner_id == Tinebase_Core::getUser()->getId()) {
            $hasGrant = true;
        }
                
        if ($hasGrant !== true) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No permissions to ' . $_action);
            }
        }
        
        return $hasGrant;
    }
    
    /**
     * set filter for different ActiveSync content types
     * 
     * @param unknown_type $_deviceId
     * @param unknown_type $_class
     * @param unknown_type $_filterId
     * 
     * @return ActiveSync_Model_Device
     */
    public function setDeviceContentFilter($_deviceId, $_class, $_filterId)
    {
        $device = $this->_backend->get($_deviceId);
        
        if($device->owner_id != Tinebase_Core::getUser()->getId()) {
            throw new Tinebase_Exception_AccessDenied('not owner of device ' . $_deviceId);
        }
        
        $filterId = empty($_filterId) ? null : $_filterId;
        
        switch($_class) {
            case Syncroton_Data_Factory::CLASS_CALENDAR:
                $device->calendarfilter_id = $filterId;
                break;
                
            case Syncroton_Data_Factory::CLASS_CONTACTS:
                $device->contactsfilter_id = $filterId;
                break;
                
            case Syncroton_Data_Factory::CLASS_EMAIL:
                $device->emailfilter_id = $filterId;
                break;
                
            case Syncroton_Data_Factory::CLASS_TASKS:
                $device->tasksfilter_id = $filterId;
                break;
                
            default:
                throw new ActiveSync_Exception('unsupported class ' . $_class);
        }
        
        $device = $this->_backend->update($device);
        
        return $device;
    }
}
