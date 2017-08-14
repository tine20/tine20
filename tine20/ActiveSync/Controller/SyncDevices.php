<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * get list of access log entries
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $this->checkRight('MANAGE_DEVICES');
        
        return parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
    }
    
    /**
     * returns the total number of access logs
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $this->checkRight('MANAGE_DEVICES');
        
        return parent::searchCount($_filter, $_action);
    }
    
    /**
     * delete access log entries
     *
     * @param   array $_ids list of logIds to delete
     * @return Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $this->checkRight('MANAGE_DEVICES');
        
        return parent::delete($_ids);
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
            $_record->{$field} = $_oldRecord{$field};
        }
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        throw new Tinebase_Exception_AccessDenied('this is not allowed!');
    }
}
