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
    const SHOW_HIDDEN_ACTIVE = 'showHiddenActive';

    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();

        if (self::SHOW_HIDDEN_ACTIVE === $this->_value) {
            $this->_addAccountQuery($db, $_select,
                $db->quoteInto($db->quoteIdentifier('accounts.status') . ' != ?',
                    Tinebase_Model_User::ACCOUNT_STATUS_DISABLED), '');
            return;
        }

        // prepare value
        $value = $this->_value ? true : false;

        if ($value) {
            // nothing to do -> show all contacts!
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Query all account contacts.');

            return;

        } else {
            $criteria = strtolower(Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_HIDDEN_CRITERIA));

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Query account contacts (hide if status = ' . $criteria . ')');

            if ($criteria === 'disabled') {
                $hideContactSql = $db->quoteInto($db->quoteIdentifier('accounts.status') . ' != ?', Tinebase_Model_User::ACCOUNT_STATUS_DISABLED);
            } else if ($criteria === 'expired') {
                $hideContactSql = '(' . $db->quoteIdentifier('accounts.expires_at') . ' > NOW() OR ' . $db->quoteIdentifier('accounts.expires_at') . ' IS NULL)';
            } else {
                $hideContactSql = '';
            }

            $this->_addAccountQuery($db, $_select, $hideContactSql,
                $db->quoteInto($db->quoteIdentifier('accounts.visibility') . ' = ?', 'displayed'));
        }
    }

    protected function _addAccountQuery($_db, $_select, $_condition1, $_condition2)
    {
        $where = '/* is no user */ ' . $_db->quoteIdentifier('accounts.id') . ' IS NULL OR /* is user */ ' .
            '(' . $_db->quoteIdentifier('accounts.id') . ' IS NOT NULL AND ';

        if (Tinebase_Core::getUser() instanceof Tinebase_Model_FullUser) {
            $where .= $_condition1 . (!empty($_condition1) && !empty($_condition2) ? ' AND ' : '')
                . (empty($_condition2) ? '' : '((' . $_condition2 . ') OR '
                . $_db->quoteInto($_db->quoteIdentifier('accounts.id') . ' = ?', Tinebase_Core::getUser()->getId())
                . ')');
        } else {
            $where .= $_condition1 . (!empty($_condition1) && !empty($_condition2) ? ' AND ' : '') . $_condition2;
        }

        $_select->where($where . ')');
    }
}
