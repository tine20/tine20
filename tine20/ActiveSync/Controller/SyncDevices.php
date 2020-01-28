<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Sync devices controller for ActiveSync application
 *
 * @package     ActiveSync
 * @subpackage  Controller
 */
class ActiveSync_Controller_SyncDevices extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'ActiveSync';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'ActiveSync_Model_Device';
    
    /**
     * check for container ACLs
     *
     * @var boolean
     *
     * @todo rename to containerACLChecks
     */
    protected $_doContainerACLChecks = false;

    protected $_allowRemoteWipeFlag = false;
    
    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Controller_SyncDevices
     */
    private static $_instance = null;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_backend = new ActiveSync_Backend_Device();
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
     * @return ActiveSync_Controller_SyncDevices
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ActiveSync_Controller_SyncDevices;
        }
        
        return self::$_instance;
    }

    /**
     * set remoteWipe flag for devices
     *
     * @param   array $_ids list of devices to flag for remote wipe
     */
    public function remoteResetDevices($_ids)
    {
        $this->checkRight('RESET_DEVICES');
        $this->_allowRemoteWipeFlag = true;

        try {
            /** @var ActiveSync_Model_Device $record */
            foreach ($this->getMultiple($_ids) as $record) {
                $record->remotewipe = true;
                $this->update($record);
            }
        } finally {
            $this->_allowRemoteWipeFlag = false;
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        // spoofing protection
        $fieldsToUnset = array('id', 'deviceid', 'devicetype', 'policy_id', 'acsversion', 'useragent',
            'model', 'os', 'oslanguage', 'pinglifetime', 'pingfolder', 'calendarfilter_id',
            'contactsfilter_id', 'emailfilter_id', 'tasksfilter_id', 'lastping');
        if (false === $this->_allowRemoteWipeFlag) {
            $fieldsToUnset[] = 'remotewipe';
        }
        
        foreach ($fieldsToUnset as $field) {
            $_record->{$field} = $_oldRecord[$field];
        }
    }

    /**
     * overwrite this function to check rights / don't forget to call parent
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        switch($_action) {
            case 'get':
            case 'update':
            case 'delete':
                $this->checkRight('MANAGE_DEVICES');
                break;
            case 'create':
            default:
                throw new Tinebase_Exception_AccessDenied('this is not allowed!');
                break;
        }

        parent::_checkRight($_action);
    }
}
