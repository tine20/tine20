<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * filters for contacts that are members of given list(s) and vice versa (lists that have given contact as member)
 * - field = list => search in contacts for members
 * - field = contact => search in lists for list with member contact
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
        2 => 'all',
        3 => 'AND'
    );

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        // make filter work for lists and contacts
        if ($this->_field === 'contact') {
            $myField = 'list_id';
            $foreignField = 'contact_id';
            if ('AND' === $this->_operator) {
                throw new Tinebase_Exception_NotImplemented('defined_by is not supported for list searches');
            }
        } else {
            $myField = 'contact_id';
            $foreignField = 'list_id';
            if ('AND' === $this->_operator) {
                $listFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_List::class,
                    $this->_value);
                $this->_value = Addressbook_Controller_List::getInstance()->search($listFilter, null, false, true);
                if (empty($this->_value)) {
                    $this->_value = null;
                }
            }
        }

        $values = 'all' === $this->_operator ? (array)($this->_value ?: [null]) : [$this->_value];
        $db = $_backend->getAdapter();

        foreach ($values as $value) {
            $correlationName = Tinebase_Record_Abstract::generateUID(30);

            $_select->joinLeft(
            /* table  */ array($correlationName => $db->table_prefix . 'addressbook_list_members'),
                /* on     */ $db->quoteIdentifier($correlationName . '.' . $myField)
                . ' = ' . $db->quoteIdentifier($_backend->getTableName() . '.id'),
                /* select */ array()
            );
            if (null === $value) {
                $_select->where($db->quoteIdentifier($correlationName . '.' . $foreignField) . ' IS NULL');
            } else {
                $_select->where($db->quoteIdentifier($correlationName . '.' . $foreignField) . ' IN (?)', (array)$value);
            }
        }
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
            try {
                if ($this->_field === 'list') {
                    $this->_value = Addressbook_Controller_List::getInstance()->get($this->_value)->toArray();
                } else {
                    $this->_value = Addressbook_Controller_Contact::getInstance()->get($this->_value)->toArray();
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__
                    . " Failed to expand filter. Exception: \n". $tenf);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__
                    . " Failed to expand filter. Exception: \n". $tead);
            }
        }
        
        return parent::toArray($_valueToJson);
    }
}
