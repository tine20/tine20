<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * filters for contacts that are members of given list(s)
 * 
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ListMemberFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'in',
    );

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
        $_select->joinLeft(
            /* table  */ array('members' => $db->table_prefix . 'addressbook_list_members'), 
            /* on     */ $db->quoteIdentifier('members.contact_id') . ' = ' . $db->quoteIdentifier('addressbook.id'),
            /* select */ array()
        );
        $_select->where($db->quoteIdentifier('members.list_id') . ' IN (?)', (array) $this->_value);
    }
}
