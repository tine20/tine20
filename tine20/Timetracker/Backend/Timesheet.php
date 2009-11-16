<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Timesheet.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 */


/**
 * backend for timesheets
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Timetracker_Backend_Timesheet extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'timetracker_timesheet';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Timetracker_Model_Timesheet';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    /**
    * Search for records matching given filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)    
    {
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination();
        }
        
        // always add creation time as second sort criteria
        if (! empty($_pagination->sort) && ! is_array($_pagination->sort)) {
            $_pagination->sort = array($_pagination->sort, 'creation_time');
        }
        //Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_pagination->toArray(), TRUE));
        
        return parent::search($_filter, $_pagination, $_onlyIds);
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
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {     
        $select = $this->_db->select();    
        
        if (is_array($_cols) && isset($_cols['count'])) {
            $cols = array(
                'count'                => 'COUNT(*)', 
                'countBillable'        => 'SUM(' . $this->_tableName . '.is_billable*ta.is_billable)',
                'sum'                  => 'SUM(duration)',
                'sumBillable'          => 'SUM(duration*' . $this->_tableName . '.is_billable*ta.is_billable)',
            );
            
        } else {
            $cols = array_merge(
                (array)$_cols, 
                array('is_billable_combined'    => '(' . $this->_tableName . '.is_billable*ta.is_billable)'),
                // ts it is cleared if ts is_cleared or ta status is 'billed'
                array('is_cleared_combined'     => 
                    '(' . $this->_tableName . ".is_cleared|(IF(STRCMP(ta.status, 'billed'),0,1)))")
            );
        }

        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $cols);
        
        // join with timeaccounts to get combined is_billable / is_cleared
        $select->joinLeft(array('ta' => $this->_tablePrefix . 'timetracker_timeaccount'),
                    $this->_db->quoteIdentifier($this->_tableName . '.timeaccount_id') . ' = ' . $this->_db->quoteIdentifier('ta.id'),
                    array());        
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');                        
        }        
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        return $select; 
    }
}
