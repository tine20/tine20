<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Timesheet.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 */


/**
 * backend for timesheets
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Timetracker_Backend_Timesheet extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_modlogActive = TRUE;
        parent::__construct(SQL_TABLE_PREFIX . 'timetracker_timesheet', 'Timetracker_Model_Timesheet');
    }

    /**
     * Gets total count and sum of duration of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return array with count + sum
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {        
        $select = $this->_getSelect(array('count' => 'COUNT(*)'));
        $this->_addFilter($select, $_filter);
        
        // fetch complete row here
        $result = $this->_db->fetchRow($select);
        return $result;        
    } 
    
    /************************ helper functions ************************/
    
    /**
     * get the basic select object to fetch records from the database
     * - we get the sum of the timesheet duration as well
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     * 
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {     
        if (is_array($_cols) && isset($_cols['count'])) {
            $cols = array(
                'count'         => 'COUNT(*)', 
                'countBillable' => 'SUM(is_billable)',
                'sum'           => 'SUM(duration)',
                'sumBillable'   => 'SUM(duration*is_billable)'
            );
        } else {
            $cols = $_cols;
        }
        
        return parent::_getSelect($cols, $_getDeleted);
    }
}
