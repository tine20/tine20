<?php
/**
 * Tine 2.0
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Timetracker';
        $this->_timesheetController = Timetracker_Controller_Timesheet::getInstance();
    }
    
    /**
     * Returns all records
     *
     * @return  array record data
     * 
     * @todo    add sort/dir params here?
     */
    public function getAllCategories()
    {
        $controller = Timetracker_Controller_Category::getInstance();
        return $this->_getAll($controller);
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
     * @param   string $uid
     * @return  array record data
     */
    public function getTimesheet($uid)
    {
        return $this->_get($uid, $this->_timesheetController);
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
}
