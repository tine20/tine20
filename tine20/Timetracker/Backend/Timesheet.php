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
     * get sum for duration of multiple timesheets
     *
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @return integer
     * 
     * @deprecated
     */
    public function getSum(Timetracker_Model_TimesheetFilter $_filter)
    {
        // build query
        $select = $this->_db->select();        
        $select->from($this->_tableName, array('sum' => 'SUM(duration)'));    
        $select->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');
        $this->_addFilter($select, $_filter);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        // get records
        $stmt = $this->_db->query($select);
        $row = $stmt->fetch();
        
        return $row['sum'];        
    }
    
    /**
     * Gets total count and sum of duration of search with $_filter
     * 
     * @param Tinebase_Record_Interface $_filter
     * @return array with count + sum
     */
    public function searchCount(Tinebase_Record_Interface $_filter)
    {        
        if (isset($_filter->container) && count($_filter->container) === 0) {
            return 0;
        }        
        
        $select = $this->_getSelect(TRUE);
        $this->_addFilter($select, $_filter);
        
        $result = $this->_db->fetchRow($select);
        return $result;        
    }    
    
    /************************ helper functions ************************/
    
    /**
     * get the basic select object to fetch records from the database
     * - we get the sum of the timesheet duration as well
     *  
     * @param $_getCount only get the count
     * @param $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     * 
     */
    protected function _getSelect($_getCount = FALSE, $_getDeleted = FALSE)
    {        
        $select = $this->_db->select();
        
        if ($_getCount) {
            $select->from($this->_tableName, array('count' => 'COUNT(*)', 'sum' => 'SUM(duration)'));    
        } else {
            $select->from($this->_tableName);
        }
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');                        
        }
        
        return $select;
    }
}
