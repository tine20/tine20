<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * filters for contacts that have a certain list role
 * 
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ListRoleMemberFilter extends Tinebase_Model_Filter_Abstract 
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
        $correlationName = Tinebase_Record_Abstract::generateUID(30);
        $db = $_backend->getAdapter();
        $_select->joinLeft(
            /* table  */ array($correlationName => $db->table_prefix . 'adb_list_m_role'),
            /* on     */ $db->quoteIdentifier($correlationName . '.contact_id') . ' = ' . $db->quoteIdentifier('addressbook.id'),
            /* select */ array()
        );
        $_select->where($db->quoteIdentifier($correlationName . '.list_role_id') . ' IN (?)', (array) $this->_value);
    }
    
    /**
     * returns array with the filter settings of this filter group
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        if (is_string($this->_value)) {
            $this->_value = Addressbook_Controller_ListRole::getInstance()->get($this->_value)->toArray();
        }
        
        return parent::toArray($_valueToJson);
    }
}
