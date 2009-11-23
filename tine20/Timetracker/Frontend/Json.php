<?php
/**
 * Tine 2.0
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Timetracker_Frontend_Json extends Tinebase_Frontend_Json_Abstract
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
    
    /************************************** protected helper functions **************************************/
    
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
        switch (get_class($_record)) {
            case 'Timetracker_Model_Timesheet':
                $_record['timeaccount_id'] = $_record['timeaccount_id'] ? $this->_timeaccountController->get($_record['timeaccount_id']) : $_record['timeaccount_id'];
                $_record['timeaccount_id']['account_grants'] = Timetracker_Model_TimeaccountGrants::getGrantsOfAccount(Tinebase_Core::get('currentAccount'), $_record['timeaccount_id']);
                $_record['timeaccount_id']['account_grants'] = $this->_resolveTimesheetGrantsByTimeaccountGrants($_record['timeaccount_id']['account_grants'], $_record['account_id']);
                Tinebase_User::getInstance()->resolveUsers($_record, 'account_id');
                
                $recordArray = parent::_recordToJson($_record);
                break;

            case 'Timetracker_Model_Timeaccount':
                
                $recordArray = parent::_recordToJson($_record);
                
                // When editing a single TA we send _ALL_ grants to the client
                $recordArray['grants'] = Timetracker_Model_TimeaccountGrants::getTimeaccountGrants($_record)->toArray();
                foreach($recordArray['grants'] as &$value) {
                    switch($value['account_type']) {
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                            $value['account_name'] = Tinebase_User::getInstance()->getUserById($value['account_id'])->toArray();
                            break;
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                            $value['account_name'] = Tinebase_Group::getInstance()->getGroupById($value['account_id'])->toArray();
                            break;
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                            $value['account_name'] = array('accountDisplayName' => 'Anyone');
                            break;
                        default:
                            throw new Tinebase_Exception_InvalidArgument('Unsupported accountType.');
                            break;    
                    }            
                }
                break;
        }
        
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
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter=NULL)
    {
        if (count($_records) == 0) {
            return array();
        }
        
        switch ($_records->getRecordClassName()) {
            case 'Timetracker_Model_Timesheet':
                // resolve timeaccounts
                $timeaccountIds = $_records->timeaccount_id;
                $timeaccounts = $this->_timeaccountController->getMultiple(array_unique(array_values($timeaccountIds)));
                
                Timetracker_Model_TimeaccountGrants::getGrantsOfRecords($timeaccounts, Tinebase_Core::get('currentAccount'));
                
                foreach ($_records as $record) {
                    $idx = $timeaccounts->getIndexById($record->timeaccount_id);
                    if ($idx !== FALSE) {
                        $record->timeaccount_id = $timeaccounts[$idx];
                        $record->timeaccount_id->account_grants = $this->_resolveTimesheetGrantsByTimeaccountGrants($record->timeaccount_id->account_grants, $record->account_id);
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not resolve timeaccount (id: ' . $record->timeaccount_id . '). No permission?');
                    }
                }
                
                // resolve user afterwards because we compare ids in _resolveTimesheetGrantsByTimeaccountGrants()
                Tinebase_User::getInstance()->resolveMultipleUsers($_records, 'account_id', true);
                
                break;
            case 'Timetracker_Model_Timeaccount':
                // resolve timeaccounts grants
                Timetracker_Model_TimeaccountGrants::getGrantsOfRecords($_records, Tinebase_Core::get('currentAccount'));
                $this->_resolveTimeaccountGrants($_records);
                break;
        }
        
        // we can't use parent::_multipleRecordsToJson here because of the different container handling
        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
        $_records->setTimezone(Tinebase_Core::get('userTimeZone'));
        $_records->convertDates = true;
        $result = $_records->toArray();
        
        return $result;
    }
    
    /**
     * calculate effective ts grants so the client doesn't need to calculate them
     *
     * @param  array  $TimeaccountGrantsArray
     * @param  int    $timesheetOwnerId
     * @return array
     */
    protected function _resolveTimesheetGrantsByTimeaccountGrants($timeaccountGrantsArray, $timesheetOwnerId)
    {
        $manageAllRight = Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE);
        $currentUserId = Tinebase_Core::getUser()->getId();
        
        $modifyGrant = $manageAllRight || ($timeaccountGrantsArray['book_own'] && $timesheetOwnerId == $currentUserId) || $timeaccountGrantsArray['book_all'];
            
        $timeaccountGrantsArray['readGrant']   = true;
        $timeaccountGrantsArray['editGrant']   = $modifyGrant;
        $timeaccountGrantsArray['deleteGrant'] = $modifyGrant;
        
        return $timeaccountGrantsArray;
    }
    
    /**
     * calculate effective ta grants so the client doesn't need to calculate them
     *
     * @param  array  $_timesaccounts
     * 
     * @todo remove obsolete code
     */
    protected function _resolveTimeaccountGrants(Tinebase_Record_RecordSet $_timesaccounts)
    {
         $manageAllRight = Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE);
         foreach ($_timesaccounts as $timeaccount) {
             $timeaccountGrantsArray = $timeaccount->account_grants;
             $modifyGrant = $manageAllRight || $timeaccountGrantsArray['manage_all'];
             
             $timeaccountGrantsArray['readGrant']   = true;
             $timeaccountGrantsArray['editGrant']   = $modifyGrant;
             $timeaccountGrantsArray['deleteGrant'] = $modifyGrant;
             $timeaccount->account_grants = $timeaccountGrantsArray;
             
             // also move the grants into the container_id property, as the clients expects records to 
             // be contained in some kind of container where it searches the grants in
             
             // obsolete code
             //$containerId = $timeaccount->container_id;
             //$containerId['account_grants'] = $timeaccountGrantsArray;
             //$timeaccount->container_id = $containerId;
             
             $timeaccount->container_id = array(
                'account_grants' => $timeaccountGrantsArray
             );
         }
    }

    /************************************** public API **************************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchTimesheets($filter, $paging)
    {
        $result = $this->_search($filter, $paging, $this->_timesheetController, 'Timetracker_Model_TimesheetFilter');
        
        $result['totalcountbillable'] = $result['totalcount']['countBillable'];
        $result['totalsum'] = $result['totalcount']['sum'];
        $result['totalsumbillable'] = $result['totalcount']['sumBillable'];
        $result['totalcount'] = $result['totalcount']['count'];
        
        return $result;
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
     * update some fields of multiple records
     *
     * @param string $filter
     * @param string $values
     * @return array with number of updated records
     */
    public function updateMultipleTimesheets($filter, $values)
    {
        return $this->_updateMultiple($filter, $values, $this->_timesheetController, 'Timetracker_Model_TimesheetFilter');
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteTimesheets($ids)
    {
        return $this->_delete($ids, $this->_timesheetController);
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
        return $this->_delete($ids, $this->_timeaccountController);
    }    

}
