<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Addressbook_Model_ContactDisabledFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 */
class Addressbook_Model_ContactDisabledFilter extends Tinebase_Model_Filter_Bool
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
            // nothing to do -> show all contacts!
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Query all account contacts.');
        
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Only query visible and enabled account contacts.');
            
            if (Tinebase_Core::getUser() instanceof Tinebase_Model_FullUser) {
                $where = "/* is no user */ ISNULL(accounts.id) OR /* is user */ (NOT ISNULL(accounts.id) AND " . 
                    $db->quoteInto($db->quoteIdentifier('accounts.status') . ' = ?', 'enabled') . 
                    " AND " . 
                    '('. $db->quoteInto($db->quoteIdentifier('accounts.visibility') . ' = ?', 'displayed') . 
                    ' OR ' . $db->quoteInto($db->quoteIdentifier('accounts.id') . ' = ?', Tinebase_Core::getUser()->getId()) . ')' .
                ")";
            } else {
                $where = "/* is no user */ ISNULL(accounts.id) OR /* is user */ (NOT ISNULL(accounts.id) AND " . 
                    $db->quoteInto($db->quoteIdentifier('accounts.status') . ' = ?', 'enabled') . 
                    " AND " . 
                    $db->quoteInto($db->quoteIdentifier('accounts.visibility') . ' = ?', 'displayed') . 
                ")";
            }
            
            $_select->where($where);
        }
    }
}
