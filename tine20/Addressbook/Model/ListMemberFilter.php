<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * filters for contacts that are members of given list(s) and vice versa (lists that have given contact as member)
 * - field = list => search in contacts for members
 * - field = contact => search in lists for list with member contact
 *
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ListMemberFilter extends Tinebase_Model_Filter_ForeignRecords
{
    /**
     * set options
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        Tinebase_Model_Filter_ForeignRecord::_setOptions($_options);
    }

    /**
     * get foreign filter group
     *
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _setFilterGroup()
    {
        if ($this->_field === 'contact') {
            $this->_options['filtergroup'] = Addressbook_Model_Contact::class;
        } else {
            $this->_options['filtergroup'] = Addressbook_Model_List::class;
        }

        parent::_setFilterGroup();

        if ('OR' === $this->_conditionSubFilter) {
            $outerFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                $this->_options['filtergroup'],
                [],
                'AND',
                $this->_options
            );
            $outerFilter->addFilterGroup($this->_filterGroup);
            $this->_filterGroup = $outerFilter;
        }
    }

    /**
     * get foreign controller
     *
     * @return Tinebase_Controller_Record_Abstract
     */
    protected function _getController()
    {
        if ($this->_controller === NULL) {
            if ($this->_field === 'contact') {
                $this->_controller = Addressbook_Controller_Contact::getInstance();
            } else {
                $this->_controller = Addressbook_Controller_List::getInstance();
            }
        }

        return $this->_controller;
    }

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (null !== $this->_filterGroup) {
            if (! is_array($this->_foreignIds)) {
                $this->_foreignIds = $this->_getController()->search($this->_filterGroup, null, false, true);
            }
            if (empty($this->_foreignIds)) {
                if (strpos($this->_operator, 'not') === 0) {
                    $_select->where('1 = 1');
                    return;
                } else {
                    $_select->where('1 = 0');
                    return;
                }
            }
        }

        // make filter work for lists and contacts
        if ($this->_field === 'contact') {
            $myField = 'list_id';
            $foreignField = 'contact_id';
        } else {
            $myField = 'contact_id';
            $foreignField = 'list_id';
        }
        $db = $_backend->getAdapter();
        $values = $this->_foreignIds ? ($this->_oneOfSetOperator ? [$this->_foreignIds] : $this->_foreignIds) : [null];
        $notOperator = strpos($this->_operator, 'not') === 0;

        foreach ($values as $value) {
            $correlationName = Tinebase_Record_Abstract::generateUID(30);
            $_select->joinLeft(
            /* table  */ array($correlationName => $db->table_prefix . 'addressbook_list_members'),
                /* on     */ $db->quoteIdentifier($correlationName . '.' . $myField)
                . ' = ' . $db->quoteIdentifier($_backend->getTableName() . '.id') .
                ($notOperator && null !== $value ? ' AND ' . $db->quoteInto(
                        $db->quoteIdentifier($correlationName . '.' . $foreignField) . ' IN (?)', (array)$value) : ''),
                /* select */ array()
            );

            if ($notOperator) {
                if (null === $value) {
                    $_select->where($db->quoteIdentifier($correlationName . '.' . $foreignField) . ' IS NOT NULL');
                } else {
                    $_select->where($db->quoteIdentifier($correlationName . '.' . $foreignField) . ' IS NULL');
                }
            } else {
                if (null === $value) {
                    $_select->where($db->quoteIdentifier($correlationName . '.' . $foreignField) . ' IS NULL');
                } else {
                    $_select->where($db->quoteIdentifier($correlationName . '.' . $foreignField) . ' IN (?)', (array)$value);
                }
            }
        }
    }
}
