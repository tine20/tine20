<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        move this to Tinebase_Backend_Sql_Abstract and replace old search/searchCount functions
 */

/**
 * extended sql cache backend class for improved search
 *
 * @package     Tinebase
 */
class Tinebase_Backend_Sql_SearchImproved extends Tinebase_Backend_Sql_Abstract
{
    /**
     * placeholder for id column for searchImproved()/_getSelect()
     */
    const IDCOL             = '_id_';
    
    /**
     * fetch single column with db query
     */
    const FETCH_MODE_SINGLE = 'fetch_single';

    /**
     * fetch two columns (id + X) with db query
     */
    const FETCH_MODE_PAIR   = 'fetch_pair';
    
    /**
     * fetch all columns with db query
     */
    const FETCH_ALL         = 'fetch_all';

    /**
     * additional search count columns
     * 
     * @var array
     */
    protected $_additionalSearchCountCols = array();
    
    /**
     * use subselect in searchCount fn
     *
     * @var boolean
     */
    protected $_useSubselectForCount = TRUE;
    
    /**
     * default secondary sort criteria
     * 
     * @var string
     */
    protected $_defaultSecondarySort = NULL;
        
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @param  Tinebase_Model_Pagination         $_pagination
     * @param  boolean|array                     $_onlyIds | $_cols
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        // just a wrapper to searchImproved atm
        return $this->searchImproved($_filter, $_pagination, $_onlyIds);
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $searchCountCols = array_merge(array('count' => 'COUNT(*)'), $this->_additionalSearchCountCols);
        
        if ($this->_useSubselectForCount) {
            // use normal search query as subselect to get count -> select count(*) from (select [...]) as count
            $select = $this->_getSelect();
            $this->_addFilter($select, $_filter);
            $countSelect = $this->_db->select()->from($select, $searchCountCols);
            
        } else {
            $countSelect = $this->_getSelect($searchCountCols);
            $this->_addFilter($countSelect, $_filter);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $countSelect);
        
        if (! empty($this->_additionalSearchCountCols)) {
            $result = $this->_db->fetchRow($countSelect);
        } else {
            $result = $this->_db->fetchOne($countSelect);
        }
        
        return $result;
    }
    
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string                         $_cols columns to get, * per default / use self::IDCOL to get only ids
     * @return Tinebase_Record_RecordSet|array
     * 
     * @todo rename this to search() and adjust interface when we replace the old search function
     */
    public function searchImproved(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')    
    {
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination(NULL, TRUE);
        }
        
        // legacy: $_cols param was $_onlyIds (boolean) ...
        if ($_cols === TRUE) {
            $_cols = self::IDCOL;
        } else if ($_cols === FALSE) {
            $_cols = '*';
        }
        
        // (1) get ids or id/value pair
        list($colsToFetch, $getIdValuePair) = $this->_getColumnsToFetch($_cols, $_filter, $_pagination);
        $select = $this->_getSelect($colsToFetch);
        if ($_filter !== NULL) {
            $this->_addFilter($select, $_filter);
        }
        $this->_addSecondarySort($_pagination);
        $_pagination->appendPaginationSql($select);
        
        if ($getIdValuePair) {
            return $this->_fetch($select, self::FETCH_MODE_PAIR);
        } else {
            $ids = $this->_fetch($select);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Fetched ' . count($ids) .' ids.');
        
        if ($_cols === self::IDCOL) {
            return $ids;
        } else if (empty($ids)) {
            return new Tinebase_Record_RecordSet($this->_modelName);
        } else {
            // (2) get other columns and do joins
            $select = $this->_getSelect($_cols);
            $this->_addWhereIdIn($select, $ids);
            
            $rows = $this->_fetch($select, self::FETCH_ALL);
            return $this->_rawDataToRecordSet($rows);
        }
    }
    
    /**
     * returns columns to fetch in first query and if an id/value pair is requested 
     * 
     * @param array|string $_cols
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array
     */
    protected function _getColumnsToFetch($_cols, Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Model_Pagination $_pagination = NULL)
    {
        $getIdValuePair = FALSE;

        if ($_cols === '*') {
            $colsToFetch = array('id' => self::IDCOL);
        } else {
            $colsToFetch = (array) $_cols;
            
            if (in_array(self::IDCOL, $colsToFetch) && count($colsToFetch) == 2) {
                // id/value pair requested
                $getIdValuePair = TRUE;
            } else if (! in_array(self::IDCOL, $colsToFetch) && count($colsToFetch) == 1) {
                // only one non-id column was requested -> add id and treat it like id/value pair
                array_push($colsToFetch, self::IDCOL);
                $getIdValuePair = TRUE;
            } else {
                $colsToFetch = array('id' => self::IDCOL);
            }
        }
        
        $colsToFetch = $this->_addFilterColumns($colsToFetch, $_filter);
        
        foreach((array) $_pagination->sort as $sort) {
            if (! array_key_exists($sort, $colsToFetch)) {
                $colsToFetch[$sort] = $this->_tableName . '.' . $sort;
            }
        }
        
        return array($colsToFetch, $getIdValuePair);
    }
    
    /**
     * add columns from filter
     * 
     * @param array $_colsToFetch
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return array
     */
    protected function _addFilterColumns($_colsToFetch, Tinebase_Model_Filter_FilterGroup $_filter)
    {
        if ($_filter !== NULL) {
            // need to ask filter if it needs additional columns
            $filterCols = $_filter->getRequiredColumnsForSelect();
            foreach ($filterCols as $key => $filterCol) {
                if (! array_key_exists($key, $_colsToFetch)) {
                    $_colsToFetch[$key] = $filterCol;
                }
            }
        }
        
        return $_colsToFetch;
    }
    
    /**
     * add default secondary sort criteria
     * 
     * @param Tinebase_Model_Pagination $_pagination
     */
    protected function _addSecondarySort(Tinebase_Model_Pagination $_pagination)
    {
        if (! empty($this->_defaultSecondarySort)) {
            $_pagination->sort = array_merge((array)$_pagination->sort, array($this->_defaultSecondarySort));
        }
    }
    
    /**
     * adds 'id in (...)' where stmt
     * 
     * @param Zend_Db_Select $_select
     * @param string|array $_ids
     * @return Zend_Db_Select
     */
    protected function _addWhereIdIn(Zend_Db_Select $_select, $_ids)
    {
        $_select->where($this->_db->quoteInto($this->_db->quoteIdentifier($this->_tableName . '.' . $this->_identifier) . ' in (?)', (array) $_ids));
        
        return $_select;
    }
    
    /**
     * fetch rows from db
     * 
     * @param Zend_Db_Select $_select
     * @param string $_mode
     * @return array
     */
    protected function _fetch(Zend_Db_Select $_select, $_mode = self::FETCH_MODE_SINGLE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
        
        $stmt = $this->_db->query($_select);
        
        if ($_mode === self::FETCH_ALL) {
            $result = (array) $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        } else {
            $result = array();
            while ($row = $stmt->fetch(Zend_Db::FETCH_NUM)) {
                if ($_mode === self::FETCH_MODE_SINGLE) {
                    $result[] = $row[0];
                } else if ($_mode === self::FETCH_MODE_PAIR) {
                    $result[$row[0]] = $row[1];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        if ($_cols !== '*' ) {
            $cols = array();
            // make sure cols is an array, prepend tablename and fix keys
            foreach ((array) $_cols as $id => $col) {
                $key = (is_numeric($id)) ? ($col === self::IDCOL) ? $this->_identifier : $col : $id;
                $cols[$key] = ($col === self::IDCOL) ? $this->_tableName . '.' . $this->_identifier : $col;
            }
        } else {
            $cols = '*';
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($cols, TRUE));
        
        $select = $this->_db->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $cols);
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');                        
        }
        
        $this->_addForeignTableJoins($select, $cols);
        
        return $select;
    }
    
    /**
     * add foreign table joins
     * 
     * @param Zend_Db_Select $_select
     * @param array|string $_cols columns to get, * per default
     */
    protected function _addForeignTableJoins(Zend_Db_Select $_select, $_cols, $_groupBy = NULL)
    {
        if (! empty($this->_foreignTables)) {
            $groupBy = ($_groupBy !== NULL) ? $_groupBy : $this->_tableName . '.' . $this->_identifier;
            $_select->group($groupBy);
            
            foreach ($this->_foreignTables as $foreignColumn => $join) {
                // only join if field is in cols
                if ($_cols == '*' || array_key_exists($foreignColumn, $_cols)) {
                    $selectArray = (array_key_exists('select', $join))
                        ? $join['select'] 
                        : ((array_key_exists('field', $join) && (! array_key_exists('singleValue', $join) || ! $join['singleValue']))
                            ? array($foreignColumn => 'GROUP_CONCAT(DISTINCT ' . $this->_db->quoteIdentifier($join['table'] . '.' . $join['field']) . ')')
                            : array($foreignColumn => $join['table'] . '.id'));
                    $joinId = (array_key_exists('joinId', $join)) ? $join['joinId'] : $this->_identifier;
                    
                    try {
                        $_select->joinLeft(
                            /* table  */ array($join['table'] => $this->_tablePrefix . $join['table']), 
                            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.' . $joinId) . ' = ' . $this->_db->quoteIdentifier($join['table'] . '.' . $join['joinOn']),
                            /* select */ $selectArray
                        );
                    } catch (Zend_Db_Select_Exception $zdse) {
                        // @todo get joins from Zend_Db_Select before trying to join the same tables twice
                        $_select->columns($selectArray, $join['table']);
                    }
                }
            }
        }
    }
}
