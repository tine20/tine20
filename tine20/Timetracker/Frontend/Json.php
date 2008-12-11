<?php
/**
 * Tine 2.0
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Json.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 */

/**
 *
 * This class handles all Json requests for the Timetracker application
 *
 * @package     Timetracker
 * @subpackage  Frontend
 */
class Timetracker_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{    
    /**
     * timesheet controller
     *
     * @var Timetracker_Controller_Timesheet
     */
    protected $_timesheetController = NULL;

    /**
     * timesheet controller
     *
     * @var Timetracker_Controller_Timeaccount
     */
    protected $_timeaccountController = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Timetracker';
        $this->_timesheetController = Timetracker_Controller_Timesheet::getInstance();
        $this->_timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
    }
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     * 
     * @todo move that to Tinebase_Record_Abstract
     */
    protected function _recordToJson($_record)
    {
        $_record->bypassFilters = true;
        //$recordArray = parent::_recordToJson($_record);
        
        if ($_record instanceof Timetracker_Model_Timesheet) {
            $_record['timeaccount_id'] = $_record['timeaccount_id'] ? $this->_timeaccountController->get($_record['timeaccount_id']) : $_record['timeaccount_id'];
            $_record['account_id'] = $_record['account_id'] ? Tinebase_User::getInstance()->getUserById($_record['account_id']) : $_record['account_id'];
        }
        
        return parent::_recordToJson($_record);
        /*
        $_record->setTimezone(Tinebase_Core::get('userTimeZone'));
        $_record->bypassFilters = true;
        $recordArray = $_record->toArray();
        
        //if ($_resolveContainer) {
        if ($_record->has('container_id')) {
            $recordArray['container_id'] = Tinebase_Container::getInstance()->getContainerById($_record->container_id)->toArray();
            $recordArray['container_id']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $_record->container_id)->toArray();
        }
        */
        return $recordArray;
    }

    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_leads Crm_Model_Lead
     * @return array data
     * 
     * @todo move that to Tinebase_Record_RecordSet
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records)
    {       
        if (count($_records) == 0) {
            return array();
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_records, true));
        
        // get acls for records
        if ($_records[0]->has('container_id')) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Zend_Registry::get('currentAccount'));
        }
        
        $_records->setTimezone(Zend_Registry::get('userTimeZone'));
        $_records->convertDates = true;
        
        $result = $_records->toArray();
        
        return $result;
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchTimesheets($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_timesheetController, 'Timetracker_Model_TimesheetFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getTimesheet($id)
    {
        return $this->_get($id, $this->_timesheetController);
        
        /*
        $ts['timeaccount_id'] = $ts['timeaccount_id'] ? $this->_timeaccountController->get($ts['timeaccount_id'])->toArray() : $ts['timeaccount_id'];
        $ts['account_id'] = $ts['account_id'] ? Tinebase_User::getInstance()->getUserById($ts['account_id'])->toArray() : $ts['account_id'];
        
        return $ts;
        */
    }

    /**
     * creates/updates a record
     *
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveTimesheet($recordData)
    {
        return $this->_save($recordData, $this->_timesheetController, 'Timesheet');
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteTimesheets($ids)
    {
        $this->_delete($ids, $this->_timesheetController);
    }

    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchTimeaccounts($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_timeaccountController, 'Timetracker_Model_TimeaccountFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getTimeaccount($id)
    {
        return $this->_get($id, $this->_timeaccountController);
    }

    /**
     * creates/updates a record
     *
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveTimeaccount($recordData)
    {
        return $this->_save($recordData, $this->_timeaccountController, 'Timeaccount');
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteTimeaccounts($ids)
    {
        $this->_delete($ids, $this->_timeaccountController);
    }    
}
