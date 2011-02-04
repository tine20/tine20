<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo move this to Tinebase_Backend_Sql_Abstract
 */

/**
 * extended sql cache backend class for Felamimail messages with improved search
 *
 * @package     Felamimail
 */
class Felamimail_Backend_Cache_Sql_MessageSearchImproved extends Felamimail_Backend_Cache_Sql_Message
{
    /**
     * placeholder for id column for searchImproved()/_getSelectImproved()
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
     */
    const IDCOL             = '_id_';
    
    /**
     * fetch single column with db query
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
     */
    const FETCH_MODE_SINGLE = 'fetch_single';

    /**
     * fetch two columns (id + X) with db query
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
     */
    const FETCH_MODE_PAIR   = 'fetch_pair';
    
    /**
     * fetch all columns with db query
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
     */
    const FETCH_ALL         = 'fetch_all';

    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @param  Tinebase_Model_Pagination         $_pagination
     * @param  boolean                           $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        // just a wrapper to searchImproved atm
        return $this->searchImproved($_filter, $_pagination, $_onlyIds);
    }
    
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string                         $_cols columns to get, * per default / use self::IDCOL to get only ids
     * @return Tinebase_Record_RecordSet|array
     * 
     * @todo rename this to search() and adjust interface
     * @todo move this to Tinebase_Backend_Sql_Abstract
     */
    public function searchImproved(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')    
    {
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination(NULL, TRUE);
        }
        
        // legacy: $_cols param was $_onlyIds ...
        if ($_cols === TRUE) {
            $_cols = self::IDCOL;
        } else if ($_cols === FALSE) {
            $_cols = '*';
        }
        
        // (1) get ids or id/value pair
        list($colsToFetch, $getIdValuePair) = $this->_getColumnsToFetch($_cols, $_filter, $_pagination);
        $select = $this->_getSelectImproved($colsToFetch);
        if ($_filter !== NULL) {
            $this->_addFilter($select, $_filter);
        }
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
            $select = $this->_getSelectImproved($_cols);
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
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
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
        
        if ($_filter !== NULL) {
            // need to ask filter if it needs additional columns
            $filterCols = $_filter->getRequiredColumnsForSelect();
            foreach ($filterCols as $key => $filterCol) {
                if (! array_key_exists($key, $colsToFetch)) {
                    $colsToFetch[$key] = $filterCol;
                }
            }
        }
        
        if ($_pagination->sort && ! array_key_exists($_pagination->sort, $colsToFetch)) {
            $colsToFetch[$_pagination->sort] = $_pagination->sort;
        }
        
        return array($colsToFetch, $getIdValuePair);
    }
    
    /**
     * adds 'id in (...)' where stmt
     * 
     * @param Zend_Db_Select $_select
     * @param string|array $_ids
     * @return Zend_Db_Select
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
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
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
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
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
     */
    protected function _getSelectImproved($_cols = '*', $_getDeleted = FALSE)
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
                if ($_cols == '*' || array_key_exists($foreignColumn, $_cols)) {
                    // only join if field is in cols
                    $selectArray = array($foreignColumn => 'GROUP_CONCAT(DISTINCT ' . $this->_db->quoteIdentifier($join['table'] . '.' . $join['field']) . ')');
                    $_select->joinLeft(
                        /* table  */ array($join['table'] => $this->_tablePrefix . $join['table']), 
                        /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.id') . ' = ' . $this->_db->quoteIdentifier($join['table'] . '.' . $join['joinOn']),
                        /* select */ $selectArray
                    );
                }
            }
        }
    }
    
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @return array
     */
    public function searchMessageUids(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)    
    {
        return $this->searchImproved($_filter, $_pagination, array(self::IDCOL, 'messageuid'));
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {        
        $select = $this->_getSelectImproved(array('count' => 'COUNT(*)', 'flags' => 'felamimail_cache_message_flag.flag'));
        $this->_addFilter($select, $_filter);
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return count($rows);        
    }    
        
    /**
     * get all flags for a given folder id
     *
     * @param string|Felamimail_Model_Folder $_folderId
     * @param integer $_start
     * @param integer $_limit
     * @return Tinebase_Record_RecordSet
     */
    public function getFlagsForFolder($_folderId, $_start = NULL, $_limit = NULL)    
    {
        $filter = $this->_getMessageFilterWithFolderId($_folderId);
        $pagination = ($_start !== NULL || $_limit !== NULL) ? new Tinebase_Model_Pagination(array(
            'start' => $_start,
            'limit' => $_limit,
        ), TRUE) : NULL;
        
        return $this->searchImproved($filter, $pagination, array('messageuid' => 'messageuid', 'id' => self::IDCOL, 'flags' => 'felamimail_cache_message_flag.flag'));
    }
}
