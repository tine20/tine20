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
        
        // set identifier with table name because we join tables in _getSelect()
        $this->_identifier = 'ts.id';
        
        parent::__construct(SQL_TABLE_PREFIX . 'timetracker_timesheet', 'Timetracker_Model_Timesheet');
    }

    /**
     * Gets total count and sum of duration of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return array with count + sum
     */
    public function searchCount(/*Tinebase_Model_Filter_FilterGroup*/ $_filter)
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
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {     
        $select = $this->_db->select();    
        
        if (is_array($_cols) && isset($_cols['count'])) {
            $cols = array(
                'count'         => 'COUNT(*)', 
                'countBillable' => 'SUM(ts.is_billable*ta.is_billable)',
                'sum'           => 'SUM(duration)',
                'sumBillable'   => 'SUM(duration*ts.is_billable*ta.is_billable)'
            );
            
        } else {
            $cols = array_merge((array)$_cols, array('is_billable_combined' => '(ts.is_billable*ta.is_billable)'));            
        }

        $select->from(array('ts' => $this->_tableName), $cols);
        
        // join with timeaccounts to get combined is_billable
        $select->joinLeft(array('ta' => SQL_TABLE_PREFIX . 'timetracker_timeaccount'),
                    $this->_db->quoteIdentifier('ts.timeaccount_id') . ' = ' . $this->_db->quoteIdentifier('ta.id'),
                    array());        
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier('ts.is_deleted') . ' = 0');                        
        }        
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        return $select; 
    }
}
