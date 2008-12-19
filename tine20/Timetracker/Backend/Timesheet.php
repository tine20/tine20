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
     */
    public function getSum(Timetracker_Model_TimesheetFilter $_filter)
    {
        // build query
        $select = $this->_db->select();        
        $select->from($this->_tableName, array('sum' => 'SUM(duration)'));    
        $this->_addFilter($select, $_filter);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        // get records
        $stmt = $this->_db->query($select);
        $row = $stmt->fetch();
        
        return $row['sum'];        
    }
    
    /************************ helper functions ************************/
}
