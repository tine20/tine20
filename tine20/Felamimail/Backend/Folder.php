<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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


    public static function releaseFolderLock($id)
    {
        $lockKey = 'FelamimailFolderLock#~#' . $id;
        if (null !== ($lock = Tinebase_Core::getMultiServerLock($lockKey))) {
            if ($lock->isLocked()) {
                $lock->release();
            }
        }
    }

    public static function lockFolderInTransaction($id, $onlyTrans = true)
    {
        $transactionMgr = Tinebase_TransactionManager::getInstance();
        if (!$onlyTrans || $transactionMgr->hasOpenTransactions()) {
            $lockKey = 'FelamimailFolderLock#~#' . $id;
            if (null !== ($lock = Tinebase_Core::getMultiServerLock($lockKey))) {
                if (!$lock->isLocked() && $lock->tryAcquire(2)) {
                    if ($onlyTrans) {
                        $transactionMgr->registerAfterCommitCallback(
                            function ($lockKey) {
                                Tinebase_Core::releaseMultiServerLock($lockKey);
                            },
                            [$lockKey]
                        );

                        $transactionMgr->registerOnRollbackCallback(
                            function ($lockKey) {
                                Tinebase_Core::releaseMultiServerLock($lockKey);
                            },
                            [$lockKey]
                        );
                    }
                    return true;
                } elseif (!$lock->isLocked()) {
                    return false;
                } else {
                    return true;
                }
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' could not get lock');
            }
        }
        // this true is for installations that dont support proper locking!
        return true;
    }

    /**
     * get folder cache counter like total and unseen
     *  
     * @param  string  $_folderId  the folderid
     * @return array
     */
    public function getFolderCounter($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;

        static::lockFolderInTransaction($folderId);
        
        // fetch total count
        $select = $this->_db->select()
            ->from(array(
                'felamimail_cache_message' => $this->_tablePrefix . 'felamimail_cache_message'),
                array('cache_totalcount' => new Zend_Db_Expr('COUNT(*)'))
            )
            ->where($this->_db->quoteIdentifier('felamimail_cache_message.folder_id') . ' = ?', $folderId);
        
        $stmt = $this->_db->query($select);
        $totalCount = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        $stmt = NULL;
        
        // get seen count
        $select = $this->_db->select()
            ->from(array(
                'felamimail_cache_msg_flag' => $this->_tablePrefix . 'felamimail_cache_msg_flag'), 
                array('cache_totalcount' => new Zend_Db_Expr('COUNT(DISTINCT(' . $this->_db->quoteIdentifier('felamimail_cache_msg_flag.message_id') . '))'))
            )
            ->where($this->_db->quoteIdentifier('felamimail_cache_msg_flag.folder_id') . ' = ?', $folderId)
            ->where($this->_db->quoteIdentifier('felamimail_cache_msg_flag.flag') . ' = ?', Zend_Mail_Storage::FLAG_SEEN);
        
        $stmt = $this->_db->query($select);
        $seenCount = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        $stmt = NULL;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' totalcount:' . $totalCount . ' / seencount:' . $seenCount);
        
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
            'cache_timestamp' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'cache_status'    => Felamimail_Model_Folder::CACHE_STATUS_UPDATING
        );

        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $folderData['id']),
            $this->_db->quoteInto($this->_db->quoteIdentifier('cache_status') . ' = ?', $folderData['cache_status']),
        );

        if (!empty($folderData['cache_timestamp'])) {
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('cache_timestamp') . ' = ?', $folderData['cache_timestamp']);
        }

        try {
            $affectedRows = $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);
        } catch (PDOException $pdoe) {
            return false;
        }

        if ($affectedRows !== 1) {
            return false;
        }

        return true;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Felamimail_Model_Folder $_record
     * @return array
     */
    protected function _recordToRawData(Tinebase_Record_Interface $_record)
    {
        static::lockFolderInTransaction($_record->getId());

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
     * @return Felamimail_Model_Folder
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function updateFolderCounter($_folderId, array $_counters)
    {
        if (empty($_folderId)) {
            throw new Tinebase_Exception_InvalidArgument('Missing folder or folder id.');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' folder: ' . $_folderId . ' - ' . print_r($_counters, true));
        $folder = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId : $this->get($_folderId);
        if (empty($_counters)) {
            return $folder; // nothing todo
        }

        static::lockFolderInTransaction($folder->getId());

        $data = array();
        foreach ($_counters as $counter => $value) {
            if ($value{0} == '+' || $value{0} == '-') {
                // increment or decrement values
                $intValue = (int) substr($value, 1);
                $quotedIdentifier = $this->_db->quoteIdentifier($counter);
                if ($value{0} == '-') {
                    $data[$counter] = $this->_dbCommand->getIfElse($quotedIdentifier . ' >= ' . $intValue, $quotedIdentifier . ' - ' . $intValue, '0');
                    $folder->{$counter} = ($folder->{$counter} >= $intValue) ? $folder->{$counter} - $intValue : 0;
                } else {
                    $data[$counter] = new Zend_Db_Expr($this->_db->quoteIdentifier($counter) . ' + ' . $intValue);
                    $folder->{$counter} += $intValue;
                }
            } else {
                // set values
                $data[$counter] = ($value >= 0) ? (int)$value : 0;
                $folder->{$counter} = ($value >= 0) ? (int)$value : 0;
            }
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $folder->getId())
        );
        
        try {
            $this->_db->update($this->_tablePrefix . $this->_tableName, $data, $where);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' Could not update folder counts: ' . $zdse->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' ' . $zdse->getTraceAsString());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' data: ' . print_r($data, TRUE) . ' where: ' . print_r($where, TRUE));
        }
        
        // sanitize unreadcount
        $updatedFolder = $this->get($folder->getId());
        if ($updatedFolder->cache_totalcount === 0 && $updatedFolder->cache_unreadcount > 0) {
            $this->updateFolderCounter($folder, array('cache_unreadcount' => 0));
        }
        
        return $folder;
    }
}
