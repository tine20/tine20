<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Addressbook_Model_ContactHiddenFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 */
class Addressbook_Model_ContactHiddenFilter extends Tinebase_Model_Filter_Bool
{
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db        = $_backend->getAdapter();

        // prepare value
        $value = $this->_value ? 1 : 0;

        if ($value) {
            // nothing to do -> show all contacts!
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Query all account contacts.');

        } else {
            $criteria = strtolower(Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_HIDDEN_CRITERIA));

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Query account contacts (hide if status = ' . $criteria . ')');

            if ($criteria === 'disabled') {
                $hideContactSql = ' AND ' . $db->quoteInto($db->quoteIdentifier('accounts.status') . ' != ?', Tinebase_Model_User::ACCOUNT_STATUS_DISABLED);
            } else if ($criteria === 'expired') {
                $hideContactSql = ' AND (' . $db->quoteIdentifier('accounts.expires_at') . ' > NOW() OR ' . $db->quoteIdentifier('accounts.expires_at') . ' IS NULL)';
            } else {
                $hideContactSql = '';
            }

            $where = '/* is no user */ ' . $db->quoteIdentifier('accounts.id') . ' IS NULL OR /* is user */ ' .
                '(' . $db->quoteIdentifier('accounts.id') . ' IS NOT NULL ';

            if (Tinebase_Core::getUser() instanceof Tinebase_Model_FullUser) {
                $where .= $hideContactSql
                    . " AND " .
                    '('. $db->quoteInto($db->quoteIdentifier('accounts.visibility') . ' = ?', 'displayed') .   
                    ' OR ' . $db->quoteInto($db->quoteIdentifier('accounts.id') . ' = ?', Tinebase_Core::getUser()->getId()) . ')' .
                ")";
            } else {
                $where .= $hideContactSql
                    . " AND " .
                    $db->quoteInto($db->quoteIdentifier('accounts.visibility') . ' = ?', 'displayed') . 
                ")";
            }

            $_select->where($where);
        }
    }
}
