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
        $ts = $this->_get($id, $this->_timesheetController);
        
        $ts['timeaccount_id'] = $ts['timeaccount_id'] ? $this->_timeaccountController->get($ts['timeaccount_id'])->toArray() : $ts['timeaccount_id'];
        $ts['account_id'] = $ts['account_id'] ? Tinebase_User::getInstance()->getUserById($ts['account_id'])->toArray() : $ts['account_id'];
        
        return $ts;
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
