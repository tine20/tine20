<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Crm_Model_LeadClosedFilter
 * 
 * @package     Crm
 * @subpackage  Filter
 * 
 */
class Crm_Model_LeadClosedFilter extends Tinebase_Model_Filter_Bool
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
            $_select->where($db->quoteIdentifier($_backend->getTableName() . '.end') . ' IS NULL');
        }
    }
}
