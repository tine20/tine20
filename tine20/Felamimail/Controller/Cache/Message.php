<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cache controller for Felamimail messages
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Cache_Message extends Felamimail_Controller_Message
{
    /**
     * number of imported messages in one caching step
     *
     * @var integer
     */
    protected $_importCountPerStep = 50;
    
    /**
     * number of fetched messages for one step of flag sync
     *
     * @var integer
     */
    protected $_flagSyncCountPerStep = 500;
    
    /**
     * max size of message to cache body for
     * 
     * @var integer
     */
    protected $_maxMessageSizeToCacheBody = 2097152;
    
    /**
     * initial cache status (used by updateCache and helper funcs)
     * 
     * @var string
     */
    protected $_initialCacheStatus = NULL;

    /**
     * message sequence in cache (used by updateCache and helper funcs)
     * 
     * @var integer
     */
    protected $_cacheMessageSequence = NULL;

    /**
     * message sequence on imap server (used by updateCache and helper funcs)
     * 
     * @var integer
     */
    protected $_imapMessageSequence = NULL;

    /**
     * start of cache update in seconds+microseconds/unix timestamp (used by updateCache and helper funcs)
     * 
     * @var float
     */
    protected $_timeStart = NULL;
    
    /**
     * time elapsed in seconds (used by updateCache and helper funcs)
     * 
     * @var integer
     */
    protected $_timeElapsed = 0;

    /**
     * time for update in seconds (used by updateCache and helper funcs)
     * 
     * @var integer
     */
    protected $_availableUpdateTime = 0;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Cache_Message
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Cache_Message
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Cache_Message();
        }
        
        return self::$_instance;
    }
    
    /**
    * get folder status and return all folders where something needs to be done
    *
    * @param Felamimail_Model_FolderFilter  $_filter
    * @return Tinebase_Record_RecordSet
    */
    public function getFolderStatus(Felamimail_Model_FolderFilter $_filter)
    {
        $this->_availableUpdateTime = NULL;
        
        // add user account ids to filter and use the folder backend to search as the folder controller has some special handling in its search function
        $accountIdFilter = $_filter->createFilter(array('field' => 'account_id', 'operator' => 'in', 'value' => Felamimail_Controller_Account::getInstance()->search()->getArrayOfIds()));
        $_filter->addFilter($accountIdFilter);
        $folderBackend = new Felamimail_Backend_Folder();
        $folders = $folderBackend->search($_filter);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .  ' ' . print_r($_filter->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .  " Checking status of " . count($folders) . ' folders.');
        
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Folder');
        foreach ($folders as $folder) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  ' Checking folder ' . $folder->globalname);
            
            if ($this->_doNotUpdateCache($folder, FALSE)) {
                continue;
            }
            
            $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);
            
            try {
                $folder = Felamimail_Controller_Cache_Folder::getInstance()->getIMAPFolderCounter($folder);
            } catch (Zend_Mail_Storage_Exception $zmse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .  ' ' . $zmse->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                    . ' Removing folder and contained messages from cache.');
                Felamimail_Controller_Message::getInstance()->deleteByFolder($folder);
                Felamimail_Controller_Cache_Folder::getInstance()->delete($folder->getId());
                continue;
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ .  ' Skip folder - there might be a temporary problem (' . $zmpe->getMessage() . ')');
            }
            
            if ($this->_cacheIsInvalid($folder) || $this->_messagesInCacheButNotOnIMAP($folder)) {
                $result->addRecord($folder);
                continue;
            }
            
            if ($folder->imap_totalcount > 0) {
                try {
                    $this->_updateMessageSequence($folder, $imap);
                } catch (Felamimail_Exception_IMAPMessageNotFound $feimnf) {
                    $result->addRecord($folder);
                    continue;
                }
                
                if ($this->_messagesDeletedOnIMAP($folder) || $this->_messagesToBeAddedToCache($folder) || $this->_messagesMissingFromCache($folder) ) {
                    $result->addRecord($folder);
                    continue;
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .  " Found " . count($result) . ' folders that need an update.');
        
        return $result;
    }
    
    /**
     * returns true on uidvalidity mismatch
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     * 
     * @todo remove int casting when http://forge.tine20.org/mantisbt/view.php?id=5764 is resolved
     */
    protected function _cacheIsInvalid($_folder)
    {
        return (isset($_folder->cache_uidvalidity) && (int) $_folder->imap_uidvalidity !== (int) $_folder->cache_uidvalidity);
    }
    
    /**
     * returns true if there are messages in cache but not in folder on IMAP
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     */
    protected function _messagesInCacheButNotOnIMAP($_folder)
    {
        return ($_folder->imap_totalcount == 0 && $_folder->cache_totalcount > 0);
    }
    
    /**
     * returns true if there are messages deleted on IMAP but not in cache
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     */
    protected function _messagesDeletedOnIMAP($_folder)
    {
        return ($_folder->imap_totalcount > 0 && $this->_cacheMessageSequence > $this->_imapMessageSequence);
    }
    
    /**
     * returns true if there are new messages on IMAP
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     */
    protected function _messagesToBeAddedToCache($_folder)
    {
        return ($_folder->imap_totalcount > 0 && $this->_imapMessageSequence < $_folder->imap_totalcount);
    }
    
    /**
     * returns true if there are messages on IMAP that are missing from the cache
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     */
    protected function _messagesMissingFromCache($_folder)
    {
        return ($_folder->imap_totalcount > 0 && $_folder->cache_totalcount < $_folder->imap_totalcount);
    }
    
    /**
     * update message cache
     * 
     * @param string $_folder
     * @param integer $_time in seconds
     * @param integer $_updateFlagFactor 1 = update flags every time, x = update flags roughly each xth run (10 by default)
     * @return Felamimail_Model_Folder folder status (in cache)
     * @throws Felamimail_Exception
     */
    public function updateCache($_folder, $_time = 10, $_updateFlagFactor = 10)
    {
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // always read folder from database
        $folder = Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        if ($this->_doNotUpdateCache($folder)) {
            return $folder;
        }
        
        $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        
        $this->_availableUpdateTime = $_time;
        $this->_timeStart = microtime(true);
        $this->_timeElapsed = 0;
       
        try {
            $this->_expungeCacheFolder($folder, $imap);
        } catch (Felamimail_Exception_IMAPFolderNotFound $feifnf) {
            return $folder;
        }
        
        $this->_initUpdate($folder);
        $this->_updateMessageSequence($folder, $imap);
        $this->_deleteMessagesInCache($folder, $imap);
        $this->_addMessagesToCache($folder, $imap);
        $this->_checkForMissingMessages($folder, $imap);
        $this->_updateFolderStatus($folder);
        
        if ($folder->supports_condstore || rand(1, $_updateFlagFactor) == 1) {
            $folder = $this->updateFlags($folder);
        }
        
        $this->_updateFolderQuota($folder, $imap);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Folder status of ' . $folder->globalname . ' after updateCache(): ' . $folder->cache_status);
        
        return $folder;
    }
    
    /**
     * checks if cache update should not commence / fencing
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param boolean $_lockFolder
     * @return boolean
     */
    protected function _doNotUpdateCache(Felamimail_Model_Folder $_folder, $_lockFolder = TRUE)
    {
        if ($_folder->is_selectable == false) {
            // nothing to be done
            return false;
        }
        
        if (Felamimail_Controller_Cache_Folder::getInstance()->updateAllowed($_folder, $_lockFolder) !== true) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ .  " update of folder {$_folder->globalname} currently not allowed. do nothing!");
            return false;
        }
    }
    
    /**
     * expunge cache folder
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     * @throws Felamimail_Exception_IMAPFolderNotFound
     */
    protected function _expungeCacheFolder(Felamimail_Model_Folder $_folder, Felamimail_Backend_ImapProxy $_imap)
    {
        try {
            $_imap->expunge(Felamimail_Model_Folder::encodeFolderName($_folder->globalname));
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' ' . $zmse);

            $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
            $_folder->is_selectable = 0;

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Finish cache update and mark folder as not selectable: ' . print_r($_folder->toArray(), true));

            if (! $_folder->has_children) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Removing folder from database');
                Felamimail_Controller_Cache_Folder::getInstance()->delete($_folder->getId());
            } else {
                $_folder = Felamimail_Controller_Folder::getInstance()->update($_folder);
            }
            
            throw new Felamimail_Exception_IMAPFolderNotFound('Folder not found / is not selectable: ' . $_folder->globalname);
        }
    }
    
    /**
     * init cache update process
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return void
     */
    protected function _initUpdate(Felamimail_Model_Folder $_folder)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " status of folder {$_folder->globalname}: {$_folder->cache_status}");
        
        $this->_initialCacheStatus = $_folder->cache_status;
        
        // reset cache counter when transitioning from Felamimail_Model_Folder::CACHE_STATUS_COMPLETE or 
        if ($_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
            $_folder->cache_job_actions_est = 0;
            $_folder->cache_job_actions_done     = 0;
            $_folder->cache_job_startuid         = 0;
        }
        
        $_folder = Felamimail_Controller_Cache_Folder::getInstance()->getIMAPFolderCounter($_folder);
        
        if ($this->_cacheIsInvalid($_folder)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' uidvalidity changed => clear cache: ' . print_r($_folder->toArray(), TRUE));
            $_folder = $this->clear($_folder);
        }
        
        if ($this->_messagesInCacheButNotOnIMAP($_folder)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " folder is empty on imap server => clear cache of folder {$_folder->globalname}");
            $_folder = $this->clear($_folder);
        }
        
        $_folder->cache_status    = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
        $_folder->cache_timestamp = Tinebase_DateTime::now();
        
        $this->_timeStart = microtime(true);
    }
    
    /**
     * at which sequence is the message with the highest messageUid (cache + imap)?
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     * @param boolean $_updateFolder
     * @throws Felamimail_Exception
     * @throws Felamimail_Exception_IMAPMessageNotFound
     */
    protected function _updateMessageSequence(Felamimail_Model_Folder $_folder, Felamimail_Backend_ImapProxy $_imap, $_updateFolder = TRUE)
    {
        if ($_folder->imap_totalcount > 0) {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            try {
                $lastFailedUid = null;
                $messageSequence = null;
                $decrementMessagesCounter = 0;
                $decrementUnreadCounter = 0;

                while ($messageSequence === null) {
                    $latestMessageUidArray = $this->_getLatestMessageUid($_folder);

                    if (is_array($latestMessageUidArray)) {
                        $latestMessageId = key($latestMessageUidArray);
                        $latestMessageUid = current($latestMessageUidArray);

                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $latestMessageId  $latestMessageUid");
                        }

                        if ($latestMessageUid === $lastFailedUid) {
                            throw new Felamimail_Exception('Failed to delete invalid messageuid from cache');
                        }

                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Check messageUid {$latestMessageUid} in folder " . $_folder->globalname);
                        }

                        try {
                            $this->_imapMessageSequence = $_imap->resolveMessageUid($latestMessageUid);
                            $this->_cacheMessageSequence = $_folder->cache_totalcount;
                            $messageSequence = $this->_imapMessageSequence + 1;
                        } catch (Zend_Mail_Protocol_Exception $zmpe) {
                            if (!$_updateFolder) {
                                throw new Felamimail_Exception_IMAPMessageNotFound('Message not found on IMAP');
                            }

                            // message does not exist on imap server anymore, remove from local cache
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " messageUid {$latestMessageUid} not found => remove from cache");
                            }

                            $lastFailedUid = $latestMessageUid;

                            $latestMessage = $this->_backend->get($latestMessageId);
                            $this->_backend->delete($latestMessage);

                            $decrementMessagesCounter++;
                            if (!$latestMessage->hasSeenFlag()) {
                                $decrementUnreadCounter++;
                            }
                        }
                    } else {
                        $this->_imapMessageSequence = 0;
                        $messageSequence = 1;
                    }

                    if (!$this->_timeLeft()) {
                        $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
                        break;
                    }
                }

                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
            } finally {
                if (null !== $transactionId) {
                    Tinebase_TransactionManager::getInstance()->rollBack();
                }
            }
            
            if ($decrementMessagesCounter > 0 || $decrementUnreadCounter > 0) {
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($_folder, array(
                    'cache_totalcount'  => "-$decrementMessagesCounter",
                    'cache_unreadcount' => "-$decrementUnreadCounter",
                ));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " Cache status cache total count: {$_folder->cache_totalcount} imap total count: {$_folder->imap_totalcount} cache sequence: $this->_cacheMessageSequence imap sequence: $this->_imapMessageSequence");
    }
    
    /**
     * get message with highest messageUid from cache 
     * 
     * @param  mixed  $_folderId
     * @return Felamimail_Model_Message
     */
    protected function _getLatestMessageUid($_folderId) 
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $filter = new Felamimail_Model_MessageFilter(array(
            array(
                'field'    => 'folder_id', 
                'operator' => 'equals', 
                'value'    => $folderId
            )
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'limit' => 1,
            'sort'  => 'messageuid',
            'dir'   => 'DESC'
        ));
        
        $result = $this->_backend->searchMessageUids($filter, $pagination);
        
        if (count($result) === 0) {
            return null;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Got last message uid: ' . print_r($result, TRUE));
        
        return $result;
    }
    
    /**
     * do we have time left for update (updates elapsed time)?
     * 
     * @return boolean
     */
    protected function _timeLeft()
    {
        if ($this->_availableUpdateTime === NULL) {
            // "infinite" time
            return TRUE;
        }
        
        $this->_timeElapsed = round(((microtime(true)) - $this->_timeStart));
        return ($this->_timeElapsed < $this->_availableUpdateTime);
    }
    
    /**
     * delete messages in cache
     * 
     *   - if the latest message on the cache has a different sequence number then on the imap server
     *     then some messages before the latest message(from the cache) got deleted
     *     we need to remove them from local cache first
     *     
     *   - $folder->cache_totalcount equals to the message sequence of the last message in the cache
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     */
    protected function _deleteMessagesInCache(Felamimail_Model_Folder $_folder, Felamimail_Backend_ImapProxy $_imap)
    {
        if ($this->_messagesDeletedOnIMAP($_folder)) {

            $messagesToRemoveFromCache = $this->_cacheMessageSequence - $this->_imapMessageSequence;
            
            if ($this->_initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $this->_initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $_folder->cache_job_actions_est += $messagesToRemoveFromCache;
            }        
            
            $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            
            if ($this->_timeLeft()) {
            
                $begin = $_folder->cache_job_startuid > 0 ? $_folder->cache_job_startuid : $_folder->cache_totalcount;
                
                $firstMessageSequence = 0;
                 
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " $messagesToRemoveFromCache message to remove from cache. starting at $begin");
                
                for ($i=$begin; $i > 0; $i -= $this->_importCountPerStep) {
                    $firstMessageSequence = ($i-$this->_importCountPerStep) >= 0 ? $i-$this->_importCountPerStep : 0;
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " fetching from $firstMessageSequence");
                    $cachedMessageUids = $this->_getCachedMessageUidsChunked($_folder, $firstMessageSequence);

                    // $cachedMessageUids can be empty if we fetch the last chunk
                    if (count($cachedMessageUids) > 0) {
                        $messageUidsOnImapServer = $_imap->messageUidExists($cachedMessageUids);

                        if (!is_array($cachedMessageUids) || !is_array($messageUidsOnImapServer)) {
                            $msg = '';
                            if (!is_array($cachedMessageUids)) {
                                $msg .= 'cachedMessageUids needs to be an array: ' . print_r($cachedMessageUids, true);
                            }
                            if (!is_array($messageUidsOnImapServer)) {
                                $msg .= 'messageUidsOnImapServer needs to be an array: ' .
                                    print_r($messageUidsOnImapServer, true);
                            }
                            throw new Tinebase_Exception_UnexpectedValue($msg);
                        }
                        $difference = array_diff($cachedMessageUids, $messageUidsOnImapServer);
                        $removedMessages = $this->_deleteMessagesByIdAndUpdateCounters(array_keys($difference), $_folder);
                        $messagesToRemoveFromCache -= $removedMessages;
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                            . " Cache status cache total count: {$_folder->cache_totalcount} imap total count: {$_folder->imap_totalcount} messages to remove: $messagesToRemoveFromCache");
                        
                        if ($messagesToRemoveFromCache <= 0) {
                            $_folder->cache_job_startuid = 0;
                            $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                            break;
                        }
                    }
                    
                    if (! $this->_timeLeft()) {
                        $_folder->cache_job_startuid = $i;
                        break;
                    }
                }
                
                if ($firstMessageSequence === 0) {
                    $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                }
            }
        }
        
        $this->_cacheMessageSequence = $_folder->cache_totalcount;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$_folder->cache_totalcount} imap total count: {$_folder->imap_totalcount} cache sequence: $this->_cacheMessageSequence imap sequence: $this->_imapMessageSequence");
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $_folder->cache_status);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache actions to be done yet: ' . ($_folder->cache_job_actions_est - $_folder->cache_job_actions_done));
    }
    
    /**
     * delete messages from cache
     * 
     * @param array $_ids
     * @param Felamimail_Model_Folder $_folder
     * @return integer number of removed messages
     */
    protected function _deleteMessagesByIdAndUpdateCounters($_ids, Felamimail_Model_Folder $_folder)
    {
        if (count($_ids) == 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No messages to delete.');
            return 0;
        }
        
        $decrementMessagesCounter = 0;
        $decrementUnreadCounter   = 0;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  
            ' Delete ' . count($_ids) . ' messages'
        );
        
        $messagesToBeDeleted = $this->_backend->getMultiple($_ids);
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {

            foreach ($messagesToBeDeleted as $messageToBeDeleted) {
                $this->_backend->delete($messageToBeDeleted);

                $_folder->cache_job_actions_done++;
                $decrementMessagesCounter++;
                if (!$messageToBeDeleted->hasSeenFlag()) {
                    $decrementUnreadCounter++;
                }
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
        
        $_folder = Felamimail_Controller_Folder::getInstance()->updateFolderCounter($_folder, array(
            'cache_totalcount'  => "-$decrementMessagesCounter",
            'cache_unreadcount' => "-$decrementUnreadCounter",
        ));
        
        return $decrementMessagesCounter;
    }
    
    /**
     * get message with highest messageUid from cache 
     * 
     * @param  mixed  $_folderId
     * @return array
     */
    protected function _getCachedMessageUidsChunked($_folderId, $_firstMessageSequnce) 
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $filter = new Felamimail_Model_MessageFilter(array(
            array(
                'field'    => 'folder_id', 
                'operator' => 'equals', 
                'value'    => $folderId
            )
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => $_firstMessageSequnce,
            'limit' => $this->_importCountPerStep,
            'sort'  => 'messageuid',
            'dir'   => 'ASC'
        ));
        
        $result = $this->_backend->searchMessageUids($filter, $pagination);
        
        return $result;
    }
    
    /**
     * add new messages to cache
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     * 
     * @todo split into smaller parts
     */
    protected function _addMessagesToCache(Felamimail_Model_Folder $_folder, Felamimail_Backend_ImapProxy $_imap)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            .  " cache sequence: {$this->_imapMessageSequence} / imap count: {$_folder->imap_totalcount}");
    
        if ($this->_messagesToBeAddedToCache($_folder)) {
            
            if ($this->_initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $this->_initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $_folder->cache_job_actions_est += ($_folder->imap_totalcount - $this->_imapMessageSequence);
            }
            
            $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            
            if ($this->_fetchAndAddMessages($_folder, $_imap)) {
                $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " Cache status cache total count: {$_folder->cache_totalcount} imap total count: {$_folder->imap_totalcount} cache sequence: $this->_cacheMessageSequence imap sequence: $this->_imapMessageSequence");
    }
    
    /**
     * fetch messages from imap server and add them to cache until timelimit is reached or all messages have been fetched
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     * @return boolean finished
     */
    protected function _fetchAndAddMessages(Felamimail_Model_Folder $_folder, Felamimail_Backend_ImapProxy $_imap)
    {
        $messageSequenceStart = $this->_imapMessageSequence + 1;
        
        // add new messages
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " Retrieve message from $messageSequenceStart to {$_folder->imap_totalcount}");
        
        while ($messageSequenceStart <= $_folder->imap_totalcount) {
            if (! $this->_timeLeft()) {
                return FALSE;
            }
            
            $messageSequenceEnd = (($_folder->imap_totalcount - $messageSequenceStart) > $this->_importCountPerStep ) 
                ? $messageSequenceStart+$this->_importCountPerStep : $_folder->imap_totalcount;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                .  " Fetch message from $messageSequenceStart to $messageSequenceEnd $this->_timeElapsed / $this->_availableUpdateTime");
            
            try {
                $messages = $_imap->getSummary($messageSequenceStart, $messageSequenceEnd, false);
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                // imap server might have gone away during update
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' IMAP protocol error during message fetching: ' . $zmpe->getMessage());
                return FALSE;
            }

            $this->_addMessagesToCacheAndIncreaseCounters($messages, $_folder);
            
            $messageSequenceStart = $messageSequenceEnd + 1;
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $_folder->cache_status);
        }
        
        return ($messageSequenceEnd == $_folder->imap_totalcount);
    }
    
    /**
     * add imap messages to cache and increase counters
     * 
     * @param array $_messages
     * @param Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Folder
     */
    protected function _addMessagesToCacheAndIncreaseCounters($_messages, $_folder)
    {
        foreach ($_messages as $uid => $message) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                .  " Add message $uid to cache");
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                .  ' ' . print_r($message, TRUE));

            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            try {
                if ($this->addMessage($message, $_folder)) {
                    $_folder->cache_job_actions_done++;
                }
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
            } finally {
                if (null !== $transactionId) {
                    Tinebase_TransactionManager::getInstance()->rollBack();
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Added some new (unread) messages to cache.");
    }
    
    /**
     * maybe there are some messages missing before $this->_imapMessageSequence
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     */
    protected function _checkForMissingMessages(Felamimail_Model_Folder $_folder, Felamimail_Backend_ImapProxy $_imap)
    {
        if ($this->_messagesMissingFromCache($_folder)) {
            
            if ($this->_initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $this->_initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $_folder->cache_job_actions_est += ($_folder->imap_totalcount - $_folder->cache_totalcount);
            }
            
            $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            
            if ($this->_timeLeft()) {
                // add missing messages
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Retrieve message from {$_folder->imap_totalcount} to 1");
                
                $begin = $_folder->cache_job_lowestuid > 0 ? $_folder->cache_job_lowestuid : $this->_imapMessageSequence;
                
                for ($i = $begin; $i > 0; $i -= $this->_importCountPerStep) {
                    
                    $messageSequenceStart = (($i - $this->_importCountPerStep) > 0 ) ? $i - $this->_importCountPerStep : 1;
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Fetch message from $messageSequenceStart to $i $this->_timeElapsed / $this->_availableUpdateTime");
                    
                    $messageUidsOnImapServer = $_imap->resolveMessageSequence($messageSequenceStart, $i);
                    
                    $missingUids = $this->_getMissingMessageUids($_folder, $messageUidsOnImapServer);
                    
                    if (count($missingUids) != 0) {
                        $messages = $_imap->getSummary($missingUids);
                        $this->_addMessagesToCacheAndIncreaseCounters($messages, $_folder);
                    }
                    
                    if ($_folder->cache_totalcount == $_folder->imap_totalcount || $messageSequenceStart == 1) {
                        $_folder->cache_job_lowestuid = 0;
                        $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                        break;
                    }
                    
                    if (! $this->_timeLeft()) {
                        $_folder->cache_job_lowestuid = $messageSequenceStart;
                        break;
                    }
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $_folder->cache_status);
                }
                
                if (defined('messageSequenceStart') && $messageSequenceStart === 1) {
                    $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$_folder->cache_totalcount} imap total count: {$_folder->imap_totalcount} cache sequence: $this->_cacheMessageSequence imap sequence: $this->_imapMessageSequence");
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $_folder->cache_status);
    }
    
    /**
     * add one message to cache
     * 
     * @param  array                    $_message
     * @param  Felamimail_Model_Folder  $_folder
     * @param  bool                     $_updateFolderCounter
     * @return Felamimail_Model_Message|bool
     */
    public function addMessage(array $_message, Felamimail_Model_Folder $_folder, $_updateFolderCounter = true)
    {
        if (! (isset($_message['header']) || array_key_exists('header', $_message)) || ! is_array($_message['header'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Email uid ' . $_message['uid'] . ' has no headers. Skipping ...');
            return false;
        }
        
        $messageToCache = $this->_createMessageToCache($_message, $_folder);
        $cachedMessage = $this->addMessageToCache($messageToCache);

        if ($cachedMessage !== false) {
            if (Felamimail_Controller_Message_Flags::getInstance()->tine20FlagEnabled($_message)) {
                Felamimail_Controller_Message_Flags::getInstance()->setTine20Flag($cachedMessage);
            }

            $this->_saveMessageInTinebaseCache($cachedMessage, $_folder, $_message);
            
            if ($_updateFolderCounter == true) {
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($_folder, array(
                    'cache_totalcount'  => '+1',
                    'cache_unreadcount' => (! $messageToCache->hasSeenFlag())   ? '+1' : '+0',
                ));
            }
        }
        
        return $cachedMessage;
    }
    
    /**
     * create new message for the cache
     * 
     * @param array $_message
     * @param Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Message
     */
    protected function _createMessageToCache(array $_message, Felamimail_Model_Folder $_folder)
    {
        $message = new Felamimail_Model_Message(array(
            'account_id'    => $_folder->account_id,
            'messageuid'    => $_message['uid'],
            'folder_id'     => $_folder->getId(),
            'timestamp'     => Tinebase_DateTime::now(),
            'received'      => Felamimail_Message::convertDate($_message['received']),
            'size'          => $_message['size'],
            'flags'         => $_message['flags'],
        ));

        $message->parseStructure($_message['structure']);
        $message->parseHeaders($_message['header']);
        $message->parseBodyParts();
        
        $attachments = $this->getAttachments($message);
        $message->has_attachment = (count($attachments) > 0) ? true : false;
        
        return $message;
    }

    /**
     * add message to cache backend
     * 
     * @param Felamimail_Model_Message $_message
     * @return Felamimail_Model_Message|bool
     */
    public function addMessageToCache(Felamimail_Model_Message $_message)
    {
        $_message->from_email = $this->_filterEmailAddressBeforeAddingToCache($_message->from_email);
        $_message->from_name  = Tinebase_Core::filterInputForDatabase(mb_substr($_message->from_name,  0, 254));
        foreach (['to', 'cc', 'bcc'] as $type) {
            $recipients = $_message->{$type};
            if (! is_array($recipients)) {
                continue;
            }
            foreach ($recipients as $key => $value) {
                if (isset($value['email'])) {
                    $recipients[$key]['email'] = $this->_filterEmailAddressBeforeAddingToCache($value['email']);
                }
            }
            $_message->{$type} = $recipients;
        }
        
        try {
            $result = $this->_backend->create($_message);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' failed to add message to cache: ' . $zdse->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Message: ' . print_r($_message->toArray(), true));

            $result = FALSE;
        }
        
        return $result;
    }

    /**
     * @param $email
     * @return string
     */
    public function _filterEmailAddressBeforeAddingToCache($email)
    {
        // TODO remove filterInputForDatabase when we finally support utf8mb4!
        return Tinebase_Core::filterInputForDatabase(mb_substr($email, 0, 254));
    }
    
    /**
     * save message in tinebase cache
     * - only cache message headers if received during the last day
     * 
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Folder $_folder
     * @param array $_messageData
     * 
     * @todo do we need the headers in the Tinebase cache?
     */
    protected function _saveMessageInTinebaseCache(Felamimail_Model_Message $_message, Felamimail_Model_Folder $_folder, $_messageData)
    {
        if (! $_message->received->isLater(Tinebase_DateTime::now()->subDay(1))) {
            return;
        }
        
        $memory = (function_exists('memory_get_peak_usage')) ? memory_get_peak_usage(true) : memory_get_usage(true);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' caching message ' . $_message->getId() . ' / memory usage: ' . $memory/1024/1024 . ' MBytes');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_message->toArray(), TRUE));

        $cacheId = 'getMessageHeaders' . $_message->getId();
        Tinebase_Core::getCache()->save($_messageData['header'], $cacheId, array('getMessageHeaders'));
    
        // prefetch body to cache
        if (Felamimail_Config::getInstance()->get(Felamimail_Config::CACHE_EMAIL_BODY, TRUE) && $_message->size < $this->_maxMessageSizeToCacheBody) {
            $account = Felamimail_Controller_Account::getInstance()->get($_folder->account_id);
            $mimeType = ($account->display_format == Felamimail_Model_Account::DISPLAY_HTML || $account->display_format == Felamimail_Model_Account::DISPLAY_CONTENT_TYPE)
                ? Zend_Mime::TYPE_HTML
                : Zend_Mime::TYPE_TEXT;
            Felamimail_Controller_Message::getInstance()->getMessageBody($_message, null, $mimeType, $account);
        }
    }
    
    /**
     * update folder status and counters
     * 
     * @param Felamimail_Model_Folder $_folder
     */
    protected function _updateFolderStatus(Felamimail_Model_Folder $_folder)
    {
        if ($_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
            $_folder->cache_status               = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
            $_folder->cache_job_actions_est      = 0;
            $_folder->cache_job_actions_done     = 0;
            $_folder->cache_job_lowestuid        = 0;
            $_folder->cache_job_startuid         = 0;
        }
        
        if ($_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            $this->_checkAndUpdateFolderCounts($_folder);
        }
        
        $_folder = Felamimail_Controller_Folder::getInstance()->update($_folder);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Folder values after import of all messages: ' . print_r($_folder->toArray(), TRUE));
    }
    
    /**
     * check and update mismatching folder counts (totalcount + unreadcount)
     * 
     * @param Felamimail_Model_Folder $_folder
     */
    protected function _checkAndUpdateFolderCounts(Felamimail_Model_Folder $_folder)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Checking foldercounts.');
        
        $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($_folder);
        
        if ($this->_countMismatch($_folder, $updatedCounters)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                ' something went wrong while in/decrementing counters => recalculate cache counters by counting rows in database.' .
                " Cache status cache total count: {$_folder->cache_totalcount} imap total count: {$_folder->imap_totalcount}");
                        
            Felamimail_Controller_Folder::getInstance()->updateFolderCounter($_folder, $updatedCounters);
        }
        
        if ($updatedCounters['cache_totalcount'] != $_folder->imap_totalcount) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' There are still messages missing in the cache: setting status to INCOMPLETE');
            
            $_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
        }
    }
    
    /**
     * returns true if one if these counts mismatch: 
     *     - imap_totalcount/cache_totalcount
     *  - $_updatedCounters_totalcount/cache_totalcount
     *  - $_updatedCounters_unreadcount/cache_unreadcount
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param array $_updatedCounters
     * @return boolean
     */
    protected function _countMismatch($_folder, $_updatedCounters)
    {
        return ($_folder->cache_totalcount != $_folder->imap_totalcount
            || $_updatedCounters['cache_totalcount'] != $_folder->cache_totalcount 
            || $_updatedCounters['cache_unreadcount'] != $_folder->cache_unreadcount
        );
    }
    
    /**
     * get uids missing from cache
     * 
     * @param  mixed  $_folderId
     * @param  array $_messageUids
     * @return array
     */
    protected function _getMissingMessageUids($_folderId, array $_messageUids) 
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $filter = new Felamimail_Model_MessageFilter(array(
            array(
                'field'    => 'folder_id', 
                'operator' => 'equals', 
                'value'    => $folderId
            ),
            array(
                'field'    => 'messageuid', 
                'operator' => 'in', 
                'value'    => $_messageUids
            )
        ));
        
        $messageUidsInCache = $this->_backend->search($filter, NULL, array('messageuid'));
        
        $result = array_diff($_messageUids, array_keys($messageUidsInCache));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, TRUE));
        
        return $result;
    }
    
    /**
     * remove all cached messages for this folder and reset folder values / folder status is updated in the database
     *
     * @param string|Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Folder
     */
    public function clear($_folder)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing cache of ' . $folder->globalname);
        
        $this->deleteByFolder($folder);
        
        $folder->cache_timestamp        = Tinebase_DateTime::now();
        $folder->cache_status           = Felamimail_Model_Folder::CACHE_STATUS_EMPTY;
        $folder->cache_job_actions_est = 0;
        $folder->cache_job_actions_done = 0;
        
        Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
            'cache_totalcount'  => 0,
            'cache_recentcount' => 0,
            'cache_unreadcount' => 0
        ));
        
        $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
        
        return $folder;
    }
    
    /**
     * update/synchronize flags
     * 
     * @param string|Felamimail_Model_Folder $_folder
     * @param integer $_time
     * @return Felamimail_Model_Folder
     * 
     * @todo only get flags of current batch of messages from imap?
     * @todo add status/progress to start at later messages when this is called next time?
     */
    public function updateFlags($_folder, $_time = 60)
    {
        // always read folder from database
        $folder  = Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        if ($folder->cache_status !== Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Do not update flags of incomplete folder ' . $folder->globalname
            );
            return $folder;
        }
        
        if ($this->_availableUpdateTime == 0) {
            $this->_availableUpdateTime = $_time;
            $this->_timeStart = microtime(true);
            $this->_timeElapsed = 0;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Updating flags of folder ' . $folder->globalname .
            ' / start time: ' . Tinebase_DateTime::now()->toString() .
            ' / available seconds: ' . ($this->_availableUpdateTime - $this->_timeElapsed)
        );
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Folder: ' . print_r($folder->toArray(), true));
        
        $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        
        // switch to folder (read-only)
        $imap->examineFolder(Felamimail_Model_Folder::encodeFolderName($folder->globalname));
        
        if ($folder->supports_condstore) {
            $this->_updateCondstoreFlags($imap, $folder);
        } else {
            $this->_updateAllFlags($imap, $folder);
        }
        
        $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
        $folder = Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
            'cache_unreadcount' => $updatedCounters['cache_unreadcount'],
        ));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' New unreadcount after flags update: ' . $updatedCounters['cache_unreadcount']);
        
        return $folder;
    }
    
    /**
     * update folder flags using condstore
     * 
     * @param Felamimail_Backend_ImapProxy $imap
     * @param Felamimail_Model_Folder $folder
     */
    protected function _updateCondstoreFlags($imap, Felamimail_Model_Folder $folder)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Folder supports condstore, fetching flags since last mod seq ' . $folder->imap_lastmodseq);
        
        $flags = $imap->getChangedFlags($folder->imap_lastmodseq);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' got ' . count($flags) . ' changed flags');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Flags: ' . print_r($flags, true));
        
        if (! empty($flags)) {
            
            if (count($flags) <= $this->_flagSyncCountPerStep) {
                $filter = new Felamimail_Model_MessageFilter(array(
                    array(
                        'field' => 'account_id', 'operator' => 'equals', 'value' => $folder->account_id
                    ),
                    array(
                        'field' => 'folder_id',  'operator' => 'equals', 'value' => $folder->getId()
                    ),
                    array(
                        'field' => 'messageuid', 'operator' => 'in', 'value' => array_keys($flags)
                    )
                ));
                $messages = $this->_backend->search($filter);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' got ' . count($messages) . ' messages.');
                
                $this->_setFlagsOnCache($flags, $folder, $messages, false);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Got too many changed flags. Maybe this is the initial load of the cache. Just updating last mod seq ...');
            }
            
            foreach ($flags as $flag) {
                if ($folder->imap_lastmodseq < $flag['modseq']) {
                    $folder->imap_lastmodseq = $flag['modseq'];
                }
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Got ' . count($flags) . ' changed flags and updated last mod seq to ' . $folder->imap_lastmodseq);
            
            $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
        }
    }
    
    /**
     * update all flags of folder
     * 
     * @param Felamimail_Backend_ImapProxy $imap
     * @param Felamimail_Model_Folder $folder
     */
    protected function _updateAllFlags($imap, Felamimail_Model_Folder $folder)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Get all flags for folder');
        
        $flags = $imap->getFlags(1, INF);
        
        for ($i = $folder->cache_totalcount; $i > 0; $i -= $this->_flagSyncCountPerStep) {
            $firstMessageSequence = ($i - $this->_flagSyncCountPerStep) >= 0 ? $i - $this->_flagSyncCountPerStep : 0;
            $messagesWithFlags = $this->_backend->getFlagsForFolder($folder->getId(), $firstMessageSequence, $this->_flagSyncCountPerStep);
            $this->_setFlagsOnCache($flags, $folder, $messagesWithFlags);
        
            if(! $this->_timeLeft()) {
                break;
            }
        }
    }
    
    /**
     * set flags on cache if different
     * 
     * @param array $flags
     * @param Felamimail_Model_Folder $folder
     * @param Tinebase_Record_RecordSet $messages
     * @param boolean $checkDiff
     */
    protected function _setFlagsOnCache($flags, $folder, $messages, $checkDiff = true)
    {
        $supportedFlags = array_keys(Felamimail_Controller_Message_Flags::getInstance()->getSupportedFlags(FALSE));
        
        $updateCount = 0;
        foreach ($messages as $cachedMessage) {
            if (isset($flags[$cachedMessage->messageuid]) || array_key_exists($cachedMessage->messageuid, $flags)) {
                $newFlags = array_intersect($flags[$cachedMessage->messageuid]['flags'], $supportedFlags);
                
                if ($checkDiff) {
                    $cachedFlags = array_intersect($cachedMessage->flags, $supportedFlags);
                    $diff1 = array_diff($cachedFlags, $newFlags);
                    $diff2 = array_diff($newFlags, $cachedFlags);
                }
                
                if (! $checkDiff || count($diff1) > 0 || count($diff2) > 0) {
                    try {
                        $this->_backend->setFlags(array($cachedMessage->getId()), $newFlags, $folder->getId());
                        $updateCount++;
                    } catch (Zend_Db_Statement_Exception $zdse) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                            . ' Could not update flags, maybe message was deleted or is not in the cache yet.');
                    }
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updated ' . $updateCount . ' messages.');
    }
    
    /**
     * update folder quota (check if server supports QUOTA first)
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     */
    protected function _updateFolderQuota(Felamimail_Model_Folder $_folder, Felamimail_Backend_ImapProxy $_imap)
    {
        // only do it for INBOX
        if ($_folder->localname !== 'INBOX') {
            return;
        }
        
        $account = Felamimail_Controller_Account::getInstance()->get($_folder->account_id);
        if (! $account->hasCapability('QUOTA')) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Account ' . $account->name . ' has no QUOTA capability');
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Getting quota for INBOX ' . $_folder->getId());
            
        // get quota and save in folder
        $quota = $_imap->getQuota($_folder->localname);
        
        if (! empty($quota) && isset($quota['STORAGE'])) {
            $_folder->quota_usage = $quota['STORAGE']['usage'];
            $_folder->quota_limit = $quota['STORAGE']['limit'] * 1024 * 1024; // is this value always in MB?
        } else {
            $_folder->quota_usage = 0;
            $_folder->quota_limit = 0;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($quota, TRUE));
    }
    
    /**
     * fetch message summary from IMAP server
     * 
     * @param string $messageUid
     * @param string $accountId
     * @param string $folderId
     * @return array
     */
    public function getMessageSummary($messageUid, $accountId, $folderId = NULL)
    {
        $imap = Felamimail_Backend_ImapFactory::factory($accountId);
        
        if ($folderId !== null) {
            $folder = null;
            try {
                $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);
                $imap->selectFolder(Felamimail_Model_Folder::encodeFolderName($folder->globalname));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                    . ' Could not select folder ' . (is_object($folder) ? $folder->globalname : $folderId) . ': ' . $e->getMessage());
            }
        }
        
        $summary = $imap->getSummary($messageUid, NULL, TRUE);
        
        return $summary;
    }
}
