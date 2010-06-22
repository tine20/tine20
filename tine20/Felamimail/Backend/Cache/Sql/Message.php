<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'to'    => 'felamimail_cache_message_to', 
        'cc'    => 'felamimail_cache_message_cc', 
        'bcc'   => 'felamimail_cache_message_bcc', 
        'flags' => 'felamimail_cache_message_flag'
    );

    /******************* overwritten functions *********************/

    /**
     * do something after creation of record
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return void
     */
    protected function _inspectAfterCreate(Tinebase_Record_Abstract $_newRecord, Tinebase_Record_Abstract $_recordToCreate)
    {
        // update to/cc/bcc/flags
        foreach ($this->_foreignTables as $field => $tablename) {
            $_newRecord->{$field} = $this->createForeignValues($_recordToCreate, $field, $tablename);
        }
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {        
        $select = $this->_getSelect(array('count' => 'COUNT(*)'));
        $this->_addFilter($select, $_filter);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return count($rows);        
    }    
        
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     * 
     * @todo add name (to, cc, bcc)
     * @todo try to remove deleted messages from result?
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {        
        /*
        if (! $_getDeleted) {
            $select->where($this->_db->quoteInto(
                //$this->_db->quoteIdentifier($this->_tablePrefix . $this->_foreignTables['flags'] . '.flag') . ' != ?',
                $this->_db->quoteIdentifier('flags') . ' NOT LIKE ?',
                '%\Deleted%'
            ));
        }
        */

        $select = parent::_getSelect($_cols, $_getDeleted);
        
        // add to/cc/bcc/flags
        foreach ($this->_foreignTables as $field => $tablename) {
            $fieldName = ($field == 'flags') ? 'flag' : 'email';
            $select->joinLeft(
                $this->_tablePrefix . $tablename, 
                $this->_tablePrefix . $tablename . '.message_id = ' . $this->_tableName . '.id', 
                array($field => 'GROUP_CONCAT(DISTINCT ' . $this->_tablePrefix . $tablename . '.' . $fieldName . ')')
            );
        }
        $select->group($this->_tableName . '.id');
        
        return $select;
    }

    /******************* public functions *********************/
    
    /**
     * create foreign values (to/cc/bcc/flags) 
     *
     * @param Felamimail_Model_Message $_message
     * @param string $_field
     * @param string $_tablename
     * @return array
     */
    public function createForeignValues(Felamimail_Model_Message $_message, $_field, $_tablename)
    {
        if (!isset($_message->{$_field})) {
            return array();
        }
        
        $messageId = $_message->getId();
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_field . ': ' . print_r($_message->{$_field}, TRUE));
        
        foreach ($_message->{$_field} as $data) {
            if ($_field == 'flags') {
                $data = array(
                    'flag'      => $data,
                    'folder_id' => $_message->folder_id
                );
            }
            $data['message_id'] = $messageId;
            $this->_db->insert($this->_tablePrefix . $_tablename, $data);
        }
        
        return $_message->{$_field};
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
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Not setting empty flag.');
            return FALSE;
        }
        
        $data = array(
            'flag'          => $_flag,
            'message_id'    => $_message->getId(),
            'folder_id'     => $_message->folder_id
        );
        $this->_db->insert($this->_tablePrefix . $this->_foreignTables['flags'], $data);
    }

    /**
     * remove flag from message / all messages in a folder
     *
     * @param mixed $_messages
     * @param mixed $_flag
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
        
        $this->_db->delete($this->_tablePrefix . $this->_foreignTables['flags'], $where);
    }
    
    /**
     * delete all cached messages for one folder
     *
     * @param string $_folderId
     */
    public function deleteByFolderId($_folderId)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $_folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * get count of cached messages by folder (id) 
     *
     * @param string $_folderId
     * @return integer
     */
    public function searchCountByFolderId($_folderId)
    {
        $filter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'folder_id', 'operator' => 'equals', 'value' => $_folderId)
        ));
        
        $count = $this->searchCount($filter);
        return $count;
    }
    
    /**
     * get count of seen cached messages by folder (id) 
     *
     * @param string $_folderId
     * @return integer
     * 
     */
    public function seenCountByFolderId($_folderId)
    {
        $select = $this->_db->select();
        $select->from(
            array($this->_foreignTables['flags'] => $this->_tablePrefix . $this->_foreignTables['flags']), 
            array('count' => 'COUNT(DISTINCT message_id)')
        )->where(
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $_folderId)
        )->where(
            $this->_db->quoteInto($this->_db->quoteIdentifier('flag') . ' = ?', '\Seen')
        );

        $seenCount = $this->_db->fetchOne($select);        
        return $seenCount;
    }
    
    /**
     * get messageuids by folder (id)
     *
     * @param string $_folderId
     * @return array
     */
    public function getMessageuidsByFolderId($_folderId)
    {
        $select = $this->_db->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $this->_tableName . '.messageuid')
                ->where($this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $_folderId));
                //->order($this->_tableName . '.messageuid ASC');
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = array();
        foreach ($rows as $row) {
            $result[] = $row['messageuid'];
        }

        return $result;
    }
    
    /**
     * delete messages with given messageuids by folder (id)
     *
     * @param array $_msguids
     * @param string $_folderId
     * @return integer number of deleted rows
     */
    public function deleteMessageuidsByFolderId($_msguids, $_folderId)
    {
        if (empty($_msguids) || !is_array($_msguids)) {
            return FALSE;
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('messageuid') . ' IN (?)', $_msguids),
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $_folderId)
        );
        
        return $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * get foreign table names (to, cc, ...)
     *
     * @return array
     */
    public function getForeignTableNames()
    {
        return $this->_foreignTables;
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
        if(isset($_rawData['structure'])) {
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
