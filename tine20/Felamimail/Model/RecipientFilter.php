<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching En Cheng <c.cheng@metaways.de>
 */

/**
 * Felamimail_Model_RecipientFilter
 *
 * filters for recipients
 *
 *
 * @package     Felamimail
 * @subpackage  Filter
 */
class Felamimail_Model_RecipientFilter extends Tinebase_Model_Filter_Text
{
    /**
     * add to/cc/bcc custom filters
     *
     * @param Zend_Db_Select $_select
     * @param Felamimail_Backend_Cache_Sql_Message $_backend
     * @return void
     * @throws Tinebase_Exception_Backend_Database
     * @throws Zend_Db_Select_Exception
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
        $prefix = $_backend->getTablePrefix();
        $foreignTables = $_backend->getForeignTables();
        // add conditions
        $tablename  = $foreignTables[$this->_field]['table'];
        $fieldName  = $tablename . '.name';
        $fieldEmail = $tablename . '.email';
        $from = $_select->getPart(Zend_Db_Select::FROM);
        $selectArray = [$this->_field => $this->_dbCommand->getAggregate($tablename . '.' . 'email')];

        if (!isset($from[$tablename])) {
            $_select->joinLeft(
            /* table  */ array($tablename => $prefix . $tablename),
                /* on     */ $this->_db->quoteIdentifier('felamimail_cache_message' . '.' . 'id') . ' = ' . $this->_db->quoteIdentifier($tablename . '.' . 'message_id'),
                /* select */ $selectArray
            );
        } else {
            // join is defined already => just add the column
            $_select->columns($selectArray, $tablename);
        }
        
        if (!empty($this->_value)) {
            $value      = '%' . $this->_value . '%';
            $_select->where(
                $db->quoteInto($fieldName  . ' LIKE ?', $value) . ' OR ' .
                $db->quoteInto($fieldEmail . ' LIKE ?', $value)
            );
        }
    }
}
