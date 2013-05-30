<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Addressbook_Model_ListHiddenFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 */
class Addressbook_Model_ListHiddenFilter extends Tinebase_Model_Filter_Bool
{
    /**
     * appends sql to given select statement
     *
     * @param Tinebase_Backend_Sql_Filter_GroupSelect $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
         
        $value = $this->_value ? 1 : 0;
        
        if ($value){
            // nothing to do -> show all lists!
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Query all lists.');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Only query visible lists.');
            
            $_select->join(
                /* table  */ array('groupvisibility' => $db->table_prefix . 'groups'), 
                /* on     */ $db->quoteIdentifier('groupvisibility.list_id') . ' = ' . $db->quoteIdentifier('addressbook_lists.id'),
                /* select */ array()
            );
            $_select->where($db->quoteIdentifier('groupvisibility.visibility').' = ?', 'displayed');
        }
    }
}
