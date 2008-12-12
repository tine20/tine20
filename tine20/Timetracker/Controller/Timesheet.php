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
}
