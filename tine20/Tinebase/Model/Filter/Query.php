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
        3 => 'startswith',
        4 => 'endswith',
        5 => 'not',
        6 => 'notcontains',
        7 => 'notin',
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
     * @param Zend_Db_Select $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (empty($this->_value)) {
            $_select->where('1=1/* empty query */');
            return;
        }

        $db = $_backend->getAdapter();

        $sqlCommand = Tinebase_Backend_Sql_Command::factory($db);
        if (0 === strpos($this->_operator, 'not')) {
            $not = true;
        } else {
            $not = false;
        }

        switch ($this->_operator) {
            case 'contains':
            case 'notcontains':
            case 'equals':
            case 'not':
            case 'startswith':
            case 'endswith':
                $queries = explode(' ', $this->_value);

                foreach ($queries as $query) {
                    $whereParts = array();
                    foreach ($this->_options['fields'] as $qField) {
                        // if field has . in name, then we already have tablename
                        if (strpos($qField, '.') !== FALSE) {
                            $whereParts[] = $sqlCommand->prepareForILike($sqlCommand->getUnaccent($db->quoteIdentifier($qField))) . ' ' . ($not?'NOT ':'') . $sqlCommand->getLike() . $sqlCommand->prepareForILike($sqlCommand->getUnaccent('(?)'));
                        }
                        else {
                            $whereParts[] = $sqlCommand->prepareForILike($sqlCommand->getUnaccent($db->quoteIdentifier($_backend->getTableName() . '.' . $qField))) . ' ' . ($not?'NOT ':'') . $sqlCommand->getLike() . $sqlCommand->prepareForILike($sqlCommand->getUnaccent('(?)'));
                        }
                    }
                    $whereClause = '';
                    if (!empty($whereParts)) {
                        if ($not) {
                            $whereClause = implode(' AND ', $whereParts);
                        } else {
                            $whereClause = implode(' OR ', $whereParts);
                        }
                    }

                    if (!empty($whereClause)) {
                        $query = trim($query);
                        if ($this->_operator === 'startswith') {
                            $query .= '%';
                        } else if ($this->_operator === 'contains' || $this->_operator === 'notcontains'){
                            $query = '%' . $query . '%';
                        } else if ($this->_operator === 'endswith') {
                            $query = '%' . $query;
                        }

                        $_select->where($db->quoteInto($whereClause, $query));
                    }
                }
                break;

            case 'notin':
            case 'in':
                foreach ($this->_options['fields'] as $qField) {
                    // if field has . in name, then we allready have tablename
                    if (strpos($qField, '.') !== FALSE) {
                        $whereParts[] = $db->quoteInto($db->quoteIdentifier($qField) . ($not?' NOT':'') . ' IN (?)', (array) $this->_value);
                    }
                    else {
                         $whereParts[] = $db->quoteInto($db->quoteIdentifier($_backend->getTableName() . '.' . $qField) . ($not?' NOT':'') . ' IN (?)', (array) $this->_value);
                    }
                }
                if (! empty($whereParts)) {
                    if ($not) {
                        $whereClause = implode(' AND ', $whereParts);
                    } else {
                        $whereClause = implode(' OR ', $whereParts);
                    }
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