<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * sql cache backend class for Felamimail messages
 *
 * @package     Felamimail
 */
class Felamimail_Backend_Cache_Sql_Message extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'felamimail_cache_message';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Felamimail_Model_Message';
    
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
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'to'    => array(
            'table'  => 'felamimail_cache_message_to',
            'joinOn' => 'message_id',
            'field'  => 'email'
        ),
        'cc'    => array(
            'table'  => 'felamimail_cache_message_cc',
            'joinOn' => 'message_id',
            'field'  => 'email'
        ),
        'bcc'    => array(
            'table'  => 'felamimail_cache_message_bcc',
            'joinOn' => 'message_id',
            'field'  => 'email'
        ),
        'flags'    => array(
            'table'         => 'felamimail_cache_message_flag',
            'joinOn'        => 'message_id',
            'field'         => 'flag',
        ),
    );

    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string                         $_cols columns to get, * per default / use self::IDCOL to get only ids
     * @return Tinebase_Record_RecordSet|array
     * 
     * @todo move this to Tinebase_Backend_Sql_Abstract
     */
    public function searchImproved(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')    
    {
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination(NULL, TRUE);
        }
        
        // (1) get ids or id/value pair
        list($colsToFetch, $getIdValuePair) = $this->_getColumnsToFetch($_cols);
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
     * @return array
     */
    protected function _getColumnsToFetch($_cols)
    {
        $getIdValuePair = FALSE;
        
        if ($_cols !== '*') {
            $cols = (array) $_cols;
            if (in_array(self::IDCOL, $cols) && count($cols) == 2) {
                // id/value pair requested
                $getIdValuePair = TRUE;
            } else if (! in_array(self::IDCOL, $cols) && count($cols) == 1) {
                // only one non-id column was requested -> add id and treat it like id/value pair
                array_push($cols, self::IDCOL);
                $getIdValuePair = TRUE;
            }
        }
        
        $colsToFetch = ($getIdValuePair) ? $cols : self::IDCOL;
        
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
    
    /******************* overwritten functions *********************/

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
        
    /******************* public functions *********************/
    
    /**
     * update foreign key values
     * 
     * @param string $_mode create|update
     * @param Tinebase_Record_Abstract $_record
     * 
     * @todo support update mode
     */
    protected function _updateForeignKeys($_mode, Tinebase_Record_Abstract $_record)
    {
        if ($_mode == 'create') {
            
            foreach ($this->_foreignTables as $key => $foreign) {
                if (!isset($_record->{$key}) || empty($_record->{$key})) {
                    continue;
                }
                
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_field . ': ' . print_r($_record->{$_field}, TRUE));
                
                foreach ($_record->{$key} as $data) {
                    if ($key == 'flags') {
                        $data = array(
                            'flag'      => $data,
                            'folder_id' => $_record->folder_id
                        );
                    }
                    $data['message_id'] = $_record->getId();
                    $this->_db->insert($this->_tablePrefix . $foreign['table'], $data);
                }
            }
        }
    }
    
    /**
     * add flag to message
     *
     * @param Felamimail_Model_Message $_message
     * @param string $_flag
     */
    public function addFlag($_message, $_flag)
    {
        if (empty($_flag)) {
            // nothing todo
            return;
        }
        
        $data = array(
            'flag'          => $_flag,
            'message_id'    => $_message->getId(),
            'folder_id'     => $_message->folder_id
        );
        $this->_db->insert($this->_tablePrefix . $this->_foreignTables['flags']['table'], $data);
    }
    
    /**
     * set flags of message
     *
     * @param  mixed         $_messages array of ids, recordset, single message record
     * @param  string|array  $_flags
     */
    public function setFlags($_messages, $_flags, $_folderId = NULL)
    {
        if ($_messages instanceof Tinebase_Record_RecordSet) {
            $messages = $_messages;
        } elseif ($_messages instanceof Felamimail_Model_Message) {
            $messages = new Tinebase_Record_RecordSet('Felamimail_Model_Message', array($_messages));
        } else if (is_array($_messages) && $_folderId !== NULL) {
            // array of ids
            $messages = $_messages;
        } else {
            throw new Tinebase_Exception_UnexpectedValue('$_messages must be instance of Felamimail_Model_Message');
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('message_id') . ' IN (?)', ($messages instanceof Tinebase_Record_RecordSet) ? $messages->getArrayOfIds() : $messages)
        );
        $this->_db->delete($this->_tablePrefix . $this->_foreignTables['flags']['table'], $where);
        
        $flags = (array) $_flags;

        foreach ($flags as $flag) {
            foreach ($messages as $message) {
                $id = ($message instanceof Felamimail_Model_Message) ? $message->getId() : $message;
                $folderId = ($message instanceof Felamimail_Model_Message) ? $message->folder_id : $_folderId;
                
                $data = array(
                    'flag'          => $flag,
                    'message_id'    => $id,
                    'folder_id'     => $folderId,
                );
                $this->_db->insert($this->_tablePrefix . $this->_foreignTables['flags']['table'], $data);
            }
        }
    }
    
    /**
     * remove flag from messages
     *
     * @param  mixed  $_messages
     * @param  mixed  $_flag
     */
    public function clearFlag($_messages, $_flag)
    {
        if ($_messages instanceof Tinebase_Record_RecordSet) {
            $messageIds = $_messages->getArrayOfIds();
        } elseif ($_messages instanceof Felamimail_Model_Message) {
            $messageIds = $_messages->getId();
        } else {
            // single id or array of ids
            $messageIds = $_messages;
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('message_id') . ' IN (?)', $messageIds),
            $this->_db->quoteInto($this->_db->quoteIdentifier('flag') . ' IN (?)', $_flag)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_foreignTables['flags']['table'], $where);
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
    
    /**
     * delete all cached messages for one folder
     *
     * @param  mixed  $_folderId
     */
    public function deleteByFolderId($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * get count of cached messages by folder (id) 
     *
     * @param  mixed  $_folderId
     * @return integer
     */
    public function searchCountByFolderId($_folderId)
    {
        $filter = $this->_getMessageFilterWithFolderId($_folderId);
        $count = $this->searchCount($filter);
        
        return $count;
    }
    
    /**
     * get folder id message filter
     * 
     * @param mixed $_folderId
     * @return Felamimail_Model_MessageFilter
     */
    protected function _getMessageFilterWithFolderId($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        $filter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'folder_id', 'operator' => 'equals', 'value' => $folderId)
        ));
        
        return $filter;
    }
    
    /**
     * get count of seen cached messages by folder (id) 
     *
     * @param  mixed  $_folderId
     * @return integer
     * 
     */
    public function seenCountByFolderId($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $select = $this->_db->select();
        $select->from(
            array('flags' => $this->_tablePrefix . $this->_foreignTables['flags']['table']), 
            array('count' => 'COUNT(DISTINCT message_id)')
        )->where(
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        )->where(
            $this->_db->quoteInto($this->_db->quoteIdentifier('flag') . ' = ?', '\Seen')
        );

        $seenCount = $this->_db->fetchOne($select);
                
        return $seenCount;
    }
    
    /**
     * delete messages with given messageuids by folder (id)
     *
     * @param  array  $_msguids
     * @param  mixed  $_folderId
     * @return integer number of deleted rows
     */
    public function deleteMessageuidsByFolderId($_msguids, $_folderId)
    {
        if (empty($_msguids) || !is_array($_msguids)) {
            return FALSE;
        }
        
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('messageuid') . ' IN (?)', $_msguids),
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        );
        
        return $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        $result = parent::_recordToRawData($_record);
        
        if(isset($result['structure'])) {
            $result['structure'] = Zend_Json::encode($result['structure']);
        }
        
        return $result;
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_rawData
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawData)
    {
        if (isset($_rawData['structure'])) {
            $_rawData['structure'] = Zend_Json::decode($_rawData['structure']);
        }
        
        $result = parent::_rawDataToRecord($_rawData);
                
        return $result;
    }
    
    /**
     * converts raw data from adapter into a set of records
     *
     * @param  array $_rawDatas of arrays
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecordSet(array $_rawDatas)
    {
        foreach($_rawDatas as &$_rawData) {
            if(isset($_rawData['structure'])) {
                $_rawData['structure'] = Zend_Json::decode($_rawData['structure']);
            }
        }
        $result = parent::_rawDataToRecordSet($_rawDatas);
        
        return $result;
    }
}
