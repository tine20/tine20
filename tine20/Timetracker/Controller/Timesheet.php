<?php
/**
 * Timesheet controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Timesheet.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 * @todo        check manage_clearing grant in create/update functions
 */

/**
 * Timesheet controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Timesheet extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_Timesheet();
        $this->_modelName = 'Timetracker_Model_Timesheet';
        $this->_currentAccount = Tinebase_Core::getUser();   
        
        // disable container ACL checks as we don't init the 'Shared Timesheets' grants in the setup
        $this->_doContainerACLChecks = FALSE; 
        
        // use modlog and don't completely delete records
        $this->_purgeRecords = FALSE;
    }    
    
    /**
     * holdes the instance of the singleton
     *
     * @var Timetracker_Controller_Timesheet
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timetracker_Controller_Timesheet
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Timetracker_Controller_Timesheet();
        }
        
        return self::$_instance;
    }        

    /****************************** functions ************************/

    /**
     * get all timesheets for a timeaccount
     *
     * @param string $_timeaccountId
     * @return Tinebase_Record_RecordSet of Timetracker_Model_Timesheet records
     */
    public function getTimesheetsByTimeaccountId($_timeaccountId)
    {
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array(
                'field' => 'timeaccount_id', 
                'operator' => 'equals', 
                'value' => $_timeaccountId
            ),             
        ));
        
        $records = $this->search($filter);
        
        return $records;
    }
    
    /****************************** overwritten functions ************************/    
            
    /**
     * check grant for action
     *
     * @param Timetracker_Model_Timeaccount $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Timetracker_Model_Timesheet $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::MANAGE_ALL)) {
            return TRUE;
        }
        
        $hasGrant = FALSE;
        
        switch ($_action) {
            case 'get':
                $hasGrant = (
                    Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::VIEW_ALL)
                    || (Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN)
                        && $_record->account_id == $this->_currentAccount->getId())
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL) 
                    );
                break;
            case 'create':
                $hasGrant = (
                    (Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN)
                        && $_record->account_id == $this->_currentAccount->getId())
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL) 
                );
                // check manage clearing grant
                $hasGrant = (
                    $hasGrant
                    && (($_record->is_billable == 1 && $_record->is_cleared == 0)
                        || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::MANAGE_CLEARING)
                    )
                );
                break;
            case 'update':
                $hasGrant = (
                    (Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN)
                        && $_record->account_id == $this->_currentAccount->getId())
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL) 
                );
                // check manage clearing grant
                $hasGrant = (
                    $hasGrant
                    && (($_record->is_billable == $_oldRecord->is_billable && $_record->is_cleared == $_oldRecord->is_cleared)
                        || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::MANAGE_CLEARING)
                    )
                );
                break;
            case 'delete':
                $hasGrant = (
                    (Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN)
                        && $_record->account_id == $this->_currentAccount->getId())
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL) 
                );
                break;
        }
        
        if ($_throw && !$hasGrant) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        
        return $hasGrant;
    }
}
