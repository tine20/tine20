<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        set timestamp field (add default to model?)
 */

/**
 * sql backend class for Felamimail folders
 *
 * @package     Felamimail
 */
class Felamimail_Backend_Folder extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'felamimail_folder';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Felamimail_Model_Folder';

    /**
     * get folder cache counter like total and unseen
     *  
     * @param  string  $_folderId  the folderid
     * @return array
     */
    public function getFolderCounter($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        // fetch total count
        $cols = array('cache_totalcount' => new Zend_Db_Expr('COUNT(*)'));
        $select = $this->_db->select()
            ->from(array('felamimail_cache_message' => $this->_tablePrefix . 'felamimail_cache_message'), $cols)
            ->where($this->_db->quoteIdentifier('felamimail_cache_message.folder_id') . ' = ?', $folderId);
        
        $stmt = $this->_db->query($select);
        $totalCount = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        
        // get seen count
        $select = $this->_db->select()
            ->from(array(
                'felamimail_cache_message_flag' => $this->_tablePrefix . 'felamimail_cache_message_flag'), 
                array('cache_totalcount' => new Zend_Db_Expr('COUNT(*)'))
            )
            ->where($this->_db->quoteIdentifier('felamimail_cache_message_flag.folder_id') . ' = ?', $folderId)
            ->where($this->_db->quoteIdentifier('felamimail_cache_message_flag.flag') . ' = ?', '\\Seen');
        
        $stmt = $this->_db->query($select);
        $seenCount = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        
        return array(
            'cache_totalcount'  => $totalCount,
            'cache_unreadcount' => $totalCount - $seenCount
        );
    }
    
    /**
     * try to lock a folder
     * 
     * @param  Felamimail_Model_Folder  $_folder  the folder to lock
     * @return bool  true if locking was successful, false if locking was not possible
     */
    public function lockFolder(Felamimail_Model_Folder $_folder)
    {
        $folderData = $_folder->toArray();
        
        $data = array(
            'cache_timestamp' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'cache_status'    => Felamimail_Model_Folder::CACHE_STATUS_UPDATING
        );
        
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $folderData['id']),
            $this->_db->quoteInto($this->_db->quoteIdentifier('cache_status') . ' = ?', $folderData['cache_status']),
        );
        
        if (!empty($folderData['cache_timestamp'])) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('cache_timestamp') . ' = ?', $folderData['cache_timestamp']);
        }
        
        $affectedRows = $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);
        
        if ($affectedRows !== 1) {
            return false;
        }
        
        return true;
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

        // don't write this value as it requires a schema update
        // see: Felamimail_Controller_Cache_Folder::getIMAPFolderCounter
        unset($result['cache_uidvalidity']);
        
        // can't be set directly, can only incremented or decremented via updateFolderCounter
        unset($result['cache_totalcount']);
        unset($result['cache_unreadcount']);
                
        return $result;
    }
    
    /**
     * increment/decrement folder counter on sql backend
     * 
     * @param  mixed  $_folderId
     * @param  array  $_counters
     */
    public function updateFolderCounter($_folderId, array $_counters)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " " . print_r($_counters, true));        
        if (empty($_counters)) {
            return; // nothing todo
        }
        
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $data = array();
        
        foreach ($_counters as $counter => $value) {
            if ($value{0} == '+' || $value{0} == '-') {
                // increment or decrement values
                $data[$counter] = new Zend_Db_Expr($this->_db->quoteIdentifier($counter) . ' ' . $value{0} . ' ' . substr($value, 1));
            } else {
                // set values
                $data[$counter] = (int)$value;
            }
        }
        
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $folderId),
        );
        
        $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);
    }
}
