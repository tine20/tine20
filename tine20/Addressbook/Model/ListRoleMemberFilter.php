<?php

/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * filters for contacts that have a certain list role
 * 
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ListRoleMemberFilter extends Tinebase_Model_Filter_ForeignId
{
    /**
     * set options
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        $_options['tablename'] = Tinebase_Record_Abstract::generateUID(30);
        $_options['field'] = 'list_role_id';
        $_options['controller'] = Addressbook_Controller_ListRole::class;
        $_options['filtergroup'] = Addressbook_Model_ListRoleFilter::class;
        parent::_setOptions($_options);
    }

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $correlationName = $this->_options['tablename'];
        $db = $_backend->getAdapter();

        if (strpos($this->_operator, 'not') === 0) {
            if ($this->_valueIsNull) {
                $_select->where($this->_getQuotedFieldName($_backend) . ' IS NOT NULL');
            } else {
                if (! is_array($this->_foreignIds) && null !== $this->_filterGroup) {
                    $this->_foreignIds = $this->_getController()->search($this->_filterGroup, null, false, true);
                }
                if (empty($this->_foreignIds)) {
                    return;
                }
                $_select->where($this->_getQuotedFieldName($_backend) . ' IS NULL');
            }
            $_select->joinLeft(
                /* table  */ [$correlationName => $_backend->getTablePrefix() . 'adb_list_m_role'],
                /* on     */ $db->quoteIdentifier($correlationName . '.contact_id') . ' = ' . $db->quoteIdentifier('addressbook.id')
                    . ($this->_valueIsNull ? '' : ' AND ' . $this->_getQuotedFieldName($_backend) . $db->quoteInto(' IN (?)', $this->_foreignIds)),
                /* select */ ['id']
            );
        } else {
            $_select->joinLeft(
                /* table  */ [$correlationName => $_backend->getTablePrefix() . 'adb_list_m_role'],
                /* on     */ $db->quoteIdentifier($correlationName . '.contact_id') . ' = ' . $db->quoteIdentifier('addressbook.id'),
                /* select */ []
            );

            parent::appendFilterSql($_select, $_backend);
        }
    }
}
