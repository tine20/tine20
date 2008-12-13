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
     * get list of records
     * - update filter depending on right to see all timesheets
     *
     * @param Tinebase_Record_Interface|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Record_Interface $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE)
    {
        /*
        if (!$this->checkRight(Timetracker_Acl_Rights::VIEW_TIMESHEETS, FALSE)) {
            $_filter->account_id = array(
                'value' => $this->_currentAccount->getId(),
                'operator' => 'equals'
            );
        }
        */
        
        return parent::search($_filter, $_pagination);   
    }

    /**
     * Gets total count of search with $_filter
     * - update filter depending on right to see all timesheets
     * 
     * @param Tinebase_Record_Interface $_filter
     * @return int
     */
    public function searchCount(Tinebase_Record_Interface $_filter) 
    {
        /*
        if (!$this->checkRight(Timetracker_Acl_Rights::VIEW_TIMESHEETS, FALSE)) {
            $_filter->account_id = array(
                'value' => $this->_currentAccount->getId(),
                'operator' => 'equals'
            );
        }
        */
        
        return parent::searchCount($_filter);
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @return Tinebase_Record_Interface
     */
    public function get($_id, $_containerId = NULL)
    {
        $result = parent::get($_id, $_containerId);

        $this->_checkGrant($result, 'get', TRUE, 'No permission to read this Timesheet.');
        
        return $result;
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record)
    {        
        $this->_checkGrant($_record, 'create', TRUE, 'No permission to create this Timesheet.');
        
        return parent::create($_record);
    }

    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $this->_checkGrant($_record, 'update', TRUE, 'No permission to update this Timesheet.');
        
        return parent::update($_record);
    }
        
    /****************************** protected functions ************************/
    
    /**
     * check grant for action
     *
     * @param Timetracker_Model_Timeaccount $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkGrant($_record, $_action, $_throw = FALSE, $_errorMessage = 'No Permission.')
    {
        $hasGrant = FALSE;
        
        switch ($_action) {
            case 'create':
            case 'update':
                $hasGrant = Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::MANAGE_CLEARING);
            case 'delete':
                $hasGrant = (
                    $hasGrant
                    || (Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN)
                        && $_record->account_id == $this->_currentAccount->getId())
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL) 
                );
            case 'get':
                $hasGrant = (
                    $hasGrant   
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::VIEW_ALL)
                );
                break;
            default:
                $hasGrant = FALSE;
        }
        
        if ($_throw && !$hasGrant) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        
        return $hasGrant;
    }
}
