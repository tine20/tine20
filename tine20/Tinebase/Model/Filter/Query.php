<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Query
 * 
 * filters for all of the given filterstrings if it is contained in at least 
 * one of the defined fields
 * 
 * -> allow search for all Müllers who live in Munich but not all Müllers and all people who live in Munich
 * 
 * The fields to query in _must_ be defined in the options key 'fields'
 * The value string is space-exploded into multiple filterstrings
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Query extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'contains',
        1 => 'in',
        2 => 'equals',
        3 => 'startswith'
    );

    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     */
    protected function _setOptions(array $_options)
    {
        if (empty($_options['fields'])) {
            throw new Tinebase_Exception_Record_NotDefined('Fields must be defined in the options of a query filter');
        }
        
        $this->_options = $_options;
    }

    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
         if (empty($this->_value)) {
             $_select->where('1=1/* empty query */');
             return;
         }

         $db = $_backend->getAdapter();

         switch ($this->_operator) {
             case 'contains':
             case 'equals':
             case 'startswith':
                 $queries = explode(' ', $this->_value);
                 foreach ($queries as $query) {
                     $whereParts = array();
                     foreach ($this->_options['fields'] as $qField) {
                         // if field has . in name, then we already have tablename
                        if (strpos($qField, '.') !== FALSE) {
                            $whereParts[] = Tinebase_Backend_Sql_Command::factory($db)->prepareForILike(Tinebase_Backend_Sql_Command::factory($db)->getUnaccent($db->quoteIdentifier($qField))) . ' ' . Tinebase_Backend_Sql_Command::factory($db)->getLike() . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike(Tinebase_Backend_Sql_Command::factory($db)->getUnaccent('(?)'));
                        }
                        else {
                            $whereParts[] = Tinebase_Backend_Sql_Command::factory($db)->prepareForILike(Tinebase_Backend_Sql_Command::factory($db)->getUnaccent($db->quoteIdentifier($_backend->getTableName() . '.' . $qField))) . ' ' . Tinebase_Backend_Sql_Command::factory($db)->getLike() . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike(Tinebase_Backend_Sql_Command::factory($db)->getUnaccent('(?)'));
                        }
                     }
                     $whereClause = '';
                     if (!empty($whereParts)) {
                         $whereClause = implode(' OR ', $whereParts);
                     }
                      
                     if (!empty($whereClause)) {
                         if ($this->_operator == 'equals') {
                             $_select->where($db->quoteInto($whereClause, trim($query)));
                         }
                         else if ($this->_operator == 'startswith') {
                             $_select->where($db->quoteInto($whereClause, trim($query) . '%'));
                         }
                         else {
                             $_select->where($db->quoteInto($whereClause, '%' . trim($query) . '%'));
                         }
                     }
                 }
                 break;
             case 'in':
                 foreach ($this->_options['fields'] as $qField) {
                    // if field has . in name, then we allready have tablename
                    if (strpos($qField, '.') !== FALSE) {
                        $whereParts[] = $db->quoteInto($db->quoteIdentifier($qField) . ' IN (?)', (array) $this->_value);
                    }
                    else {
                         $whereParts[] = $db->quoteInto($db->quoteIdentifier($_backend->getTableName() . '.' . $qField) . ' IN (?)', (array) $this->_value);
                    }
                 }
                 if (! empty($whereParts)) {
                     $whereClause = implode(' OR ', $whereParts);
                 }
                 if (! empty($whereClause)) {
                     $_select->where($whereClause);
                 }
                 break;
             default:
                 throw new Tinebase_Exception_InvalidArgument('Operator not defined: ' . $this->_operator);
         }
     }
}