<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Timetracker_Model_TimeaccountClosedFilter (showClosed)
 * 
 * @package     Timetracker
 * @subpackage  Filter
 * 
 */
class Timetracker_Model_TimeaccountClosedFilter extends Tinebase_Model_Filter_Bool
{
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
        
        // prepare value
        $value = $this->_value ? 1 : 0;
                 
        if ($value){
            // nothing to filter
        } else {
            $_select->where($db->quoteIdentifier($_backend->getTableName() . '.is_open') . ' = 1');
        }
    }
}
