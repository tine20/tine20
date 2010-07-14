<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
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
     * @var default charset
     */
    protected $_encoding = 'UTF-8';
    
    /**
     * stepwidth of uid in one caching step
     *
     * @var integer
     */
    protected $_uidStepWidth = NULL;
    
    /**
     * number of imported messages in one caching step
     *
     * @var integer
     */
    protected $_importCountPerStep = 50;
    
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
        $this->_currentAccount = Tinebase_Core::getUser();
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
    
    /***************************** public funcs *******************************/

    /**
     * update message cache
     * 
     * @param string|Felamimail_Model_Folder $_folder
     * @param integer $_time in seconds
     * @return Felamimail_Model_Folder folder status (in cache)
     * @throws Felamimail_Exception_IMAPFolderNotFound
     * @throws Felamimail_Exception
     * 
     * @todo split this in multiple functions
     */
    public function updateCache($_folder, $_time = 10)
    {
        // always read folder from database
        $folder = Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        if($folder->is_selectable == false) {
            // nothing to be done
            return $folder;
        }
        
        if (Felamimail_Controller_Cache_Folder::getInstance()->updateAllowed($folder) !== true) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " update of folder {$folder->globalname} currently not allowed. do nothing!");
            return $folder;
        }
        
        // get imap connection, select folder and purge messages with \Deleted flag 
        $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        
        try {
            $imap->expunge($folder->globalname);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Removing no longer existing folder ' . $folder->globalname . ' from cache. ' .$zmse->getMessage() );
            Felamimail_Controller_Cache_Folder::getInstance()->delete($folder->getId());
            throw new Felamimail_Exception_IMAPFolderNotFound();
        }
                
        $folderCache = Felamimail_Controller_Cache_Folder::getInstance();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " status of folder {$folder->globalname}: {$folder->cache_status}");
        
        $initialCacheStatus = $folder->cache_status;
        
        // reset cache counter when transitioning from Felamimail_Model_Folder::CACHE_STATUS_COMPLETE or 
        if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
            $folder->cache_job_actions_estimate = 0;
            $folder->cache_job_actions_done     = 0;
        }
        
        // update imap informations
        $folder = $folderCache->getIMAPFolderCounter($folder);
        
        if(isset($folder->cache_uidvalidity) && $folder->imap_uidvalidity != $folder->cache_uidvalidity) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' uidvalidity changed => clear cache: ' . print_r($folder->toArray(), TRUE));
            $folder = $this->clear($folder);
        }
        
        if($folder->imap_totalcount == 0 && $folder->cache_totalcount > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " folder is empty on imap server => clear cache of folder {$folder->globalname}");
            $folder = $this->clear($folder);
        }
        
        
        $folder->cache_status    = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
        $folder->cache_timestamp = Zend_Date::now();
        $folder->cache_uidnext   = $folder->imap_uidnext;
        
        $cacheMessageSequence = null;
        $imapMessageSequence  = null;
                
        $timeStart   = microtime(true);
        $timeElapsed = 0;
        
        // at which sequence is the message with the highest messageUid?
        if ($folder->imap_totalcount > 0) { 
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
            $lastFailedUid   = null;
            $messageSequence = null;
            $decrementMessagesCounter = 0;
            $decrementUnreadCounter   = 0;
            
            while ($messageSequence === null) {
                $latestMessage = $this->_getLatestMessage($folder);
                
                if($latestMessage instanceof Felamimail_Model_Message) {
                    if($latestMessage->messageuid === $lastFailedUid) {
                        throw new Felamimail_Exception('failed to delete invalid messageuid from cache');
                    }
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Check messageUid {$latestMessage->messageuid} in folder " . $folder->globalname);
                    
                    try {
                        $imapMessageSequence  = $imap->resolveMessageUid($latestMessage->messageuid);
                        $cacheMessageSequence = $folder->cache_totalcount;
                        $messageSequence      = $imapMessageSequence + 1;
                    } catch (Zend_Mail_Protocol_Exception $zmpe) {
                        // message does not exist on imap server anymore, remove from local cache
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " messageUid {$latestMessage->messageuid} not found => remove from cache");
                        
                        $lastFailedUid = $latestMessage->messageuid;
                        $this->_backend->delete($latestMessage);
                        
                        $folder->cache_totalcount--;
                        $decrementMessagesCounter++;
                        if (! $this->_hasSeenFlag($latestMessage)) {
                            $decrementUnreadCounter++;
                        }
                    }
                } else {
                    $imapMessageSequence = 0;
                    $messageSequence = 1;
                }
                
                $timeElapsed = round(((microtime(true)) - $timeStart));
                if($timeElapsed > $_time) {
                    $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
                    break;
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                'cache_totalcount'  => "-$decrementMessagesCounter",
                'cache_unreadcount' => "-$decrementUnreadCounter",
            ));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} cache sequence: $cacheMessageSequence imap sequence: $imapMessageSequence");

        
        // if the latest message on the cache has a different sequence number then on the imap server
        // then some messages before the latest message(from the cache) got deleted
        // we need to remove them from local cache first
        // $folder->cache_totalcount equals to the message sequence of the last message in the cache
        if ($folder->imap_totalcount > 0 && $cacheMessageSequence > $imapMessageSequence) {
            // how many messages to remove?
            $messagesToRemoveFromCache = $cacheMessageSequence - $imapMessageSequence;
            
            if ($initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $folder->cache_job_actions_estimate += $messagesToRemoveFromCache;
            }
            
            $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            
            if ($timeElapsed < $_time) {
            
                $begin = $folder->cache_job_startuid > 0 ? $folder->cache_job_startuid : $folder->cache_totalcount;
                 
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " $messagesToRemoveFromCache message to remove from cache. starting at $begin");
                
                for ($i=$begin; $i > 0; $i -= $this->_importCountPerStep) {
                    $firstMessageSequence = ($i-$this->_importCountPerStep) >= 0 ? $i-$this->_importCountPerStep : 0;
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " fetching from $firstMessageSequence");
                    $cachedMessages = $this->_getCachedMessagesChunked($folder, $firstMessageSequence);
                    $cachedMessages->addIndices(array('messageuid'));
                    
                    $messageUidsOnImapServer = $imap->messageUidExists($cachedMessages->messageuid);
                    
                    #$toBeDeleted = array();
                    
                    $difference = array_diff($cachedMessages->messageuid, $messageUidsOnImapServer);
                    
                    if (count($difference) > 0) {
                        $decrementMessagesCounter = 0;
                        $decrementUnreadCounter   = 0;
                        
                        $cachedMessages->addIndices(array('messageuid'));
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  
                            ' Delete ' . count($difference) . ' messages'
                        );
                        
                        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                        
                        foreach ($difference as $deletedMessageUid) {
                            $messageToBeDeleted = $cachedMessages->find('messageuid', $deletedMessageUid);
                            
                            $this->_backend->delete($messageToBeDeleted);
                            
                            $folder->cache_job_actions_done++;
                            $folder->cache_totalcount--;
                            $decrementMessagesCounter++;
                            if (! $this->_hasSeenFlag($messageToBeDeleted)) {
                                $decrementUnreadCounter++;
                            }
                            
                            $messagesToRemoveFromCache--;
                        }
                        
                        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                        
                        Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                            'cache_totalcount'  => "-$decrementMessagesCounter",
                            'cache_unreadcount' => "-$decrementUnreadCounter",
                        ));
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} messages to remove: $messagesToRemoveFromCache");
                    }
                    
                    if ($messagesToRemoveFromCache <= 0) {
                        $folder->cache_job_startuid = 0;
                        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                        break;
                    }
                     
                    $timeElapsed = round(((microtime(true)) - $timeStart));
                    if($timeElapsed > $_time) {
                        $folder->cache_job_startuid = $i;
                        break;
                    }
                }
                
                if ($firstMessageSequence === 0) {
                    $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                }
            }
        }
        
        $cacheMessageSequence = $folder->cache_totalcount;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} cache sequence: $cacheMessageSequence imap sequence: $imapMessageSequence");
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);      
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache actions to be done yet: ' . ($folder->cache_job_actions_estimate - $folder->cache_job_actions_done));        
        
        // add new messages to cache
        if ($folder->imap_totalcount > 0 && $imapMessageSequence < $folder->imap_totalcount) {
                        
            if ($initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $folder->cache_job_actions_estimate += ($folder->imap_totalcount - $imapMessageSequence);
            }
            
            $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            
            if ($timeElapsed < $_time) {
                $messageSequenceStart = $imapMessageSequence + 1;
                
                // add new messages
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Retrieve message from $messageSequenceStart to {$folder->imap_totalcount}");
                
                while ($messageSequenceStart <= $folder->imap_totalcount) {
                    
                    $messageSequenceEnd = (($folder->imap_totalcount - $messageSequenceStart) > $this->_importCountPerStep ) ? $messageSequenceStart+$this->_importCountPerStep : $folder->imap_totalcount;
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Fetch message from $messageSequenceStart to $messageSequenceEnd $timeElapsed / $_time");
                    
                    $messages = $imap->getSummary($messageSequenceStart, $messageSequenceEnd, false);

                    $incrementMessagesCounter = 0;
                    $incrementUnreadCounter   = 0;
                    
                    $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                    
                    foreach ($messages as $uid => $message) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Add message $uid to cache " . $folder->cache_totalcount);
                        $addedMessage = $this->addMessage($message, $folder, false);
                        
                        $folder->cache_totalcount++;
                        $folder->cache_job_actions_done++;
                        $incrementMessagesCounter++;
                        if (! $this->_hasSeenFlag($addedMessage)) {
                            $incrementUnreadCounter++;
                        }
                    }
                    
                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                    
                    Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                        'cache_totalcount'  => "+$incrementMessagesCounter",
                        'cache_unreadcount' => "+$incrementUnreadCounter",
                    ));
                    
                    $messageSequenceStart = $messageSequenceEnd + 1;
                    
                    $timeElapsed = round(((microtime(true)) - $timeStart));
                    if($timeElapsed > $_time) {
                        break;
                    }
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);           
                }
                
                if ($messageSequenceEnd == $folder->imap_totalcount) {
                    $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} cache sequence: $cacheMessageSequence imap sequence: $imapMessageSequence");
        
        // maybe there are some messages missing before $imapMessageSequence
        if ($folder->imap_totalcount > 0 && $folder->cache_totalcount < $folder->imap_totalcount) {
            
            if ($initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $folder->cache_job_actions_estimate += ($folder->imap_totalcount - $folder->cache_totalcount);
            }
            
            $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            
            if ($timeElapsed < $_time) { 
                // add missing messages
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Retrieve message from {$folder->imap_totalcount} to 1");
                
                $begin = $folder->cache_job_lowestuid > 0 ? $folder->cache_job_lowestuid : $imapMessageSequence; 
                
                for ($i=$begin; $i > 0; $i -= $this->_importCountPerStep) { 
                    
                    $messageSequenceStart = (($i - $this->_importCountPerStep) > 0 ) ? $i - $this->_importCountPerStep : 1;
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Fetch message from $messageSequenceStart to $i $timeElapsed / $_time");
                    
                    $messageUidsOnImapServer = $imap->resolveMessageSequence($messageSequenceStart, $i);
                    
                    $missingUids = $this->_getMissingMessageUids($folder, $messageUidsOnImapServer);
                    
                    if (count($missingUids) != 0) {
                        $incrementMessagesCounter = 0;
                        $incrementUnreadCounter   = 0;
                        
                        $messages = $imap->getSummary($missingUids);
                        
                        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                        
                        foreach ($messages as $uid => $message) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Add message $uid to cache");
                            $addedMessage = $this->addMessage($message, $folder, false);
                            
                            $folder->cache_totalcount++;
                            $folder->cache_job_actions_done++;
                            $incrementMessagesCounter++;
                            if (! $this->_hasSeenFlag($addedMessage)) {
                                $incrementUnreadCounter++;
                            }
                        }
                        
                        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                        
                        Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                            'cache_totalcount'  => "+$incrementMessagesCounter",
                            'cache_unreadcount' => "+$incrementUnreadCounter",
                        ));
                    }
                    
                    if($folder->cache_totalcount == $folder->imap_totalcount) {
                        $folder->cache_job_lowestuid = 0;
                        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                        break;
                    }
                    
                    $timeElapsed = round(((microtime(true)) - $timeStart));
                    if($timeElapsed > $_time) {
                        $folder->cache_job_lowestuid = $messageSequenceStart;
                        break;
                    }
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);           
                }
                
                if ($messageSequenceStart === 1) {
                    $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} cache sequence: $cacheMessageSequence imap sequence: $imapMessageSequence");
        
        // @todo move this to another place (updateFlags)
//        // lets update message flags if some time is left
//        if ($timeElapsed < $_time) {
//            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
//                ' start updating flags'
//            );
//            for ($i=$folder->cache_totalcount; $i > $folder->cache_totalcount - 100; $i -= $this->_importCountPerStep) {
//                $firstMessageSequence = ($i-$this->_importCountPerStep) >= 0 ? $i-$this->_importCountPerStep : 0;
//                $cachedMessages = $this->_getCachedMessagesChunked($folder, $firstMessageSequence);
//
//                $flags = $imap->getFlags($cachedMessages->messageuid);
//
//                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
//                
//                foreach ($cachedMessages as $cachedMessage) {
//                    if (array_key_exists($cachedMessage->messageuid, $flags)) {
//                        $newFlags = array_key_exists('flags', $flags[$cachedMessage->messageuid]) ? $flags[$cachedMessage->messageuid]['flags'] : arary();
//                        $this->_backend->setFlags($cachedMessage, $newFlags);
//                    }
//                }
//                
//                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
//                
//                $timeElapsed = round(((microtime(true)) - $timeStart));
//                if($timeElapsed > $_time) {
//                    break;
//                }
//            }
//            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
//                ' updating flags finished'
//            );
//        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);     
           
        if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
            $folder->cache_status               = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
            $folder->cache_uidnext              = $folder->imap_uidnext;
            $folder->cache_job_actions_estimate = 0;
            $folder->cache_job_actions_done     = 0;
            $folder->cache_job_lowestuid        = 0;
            $folder->cache_job_startuid         = 0;
        }
        
        if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && ($folder->cache_totalcount != $folder->imap_totalcount)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                " something went wrong while in/decrementing counters => recalculate cache counters by counting rows in database. Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount}"
            );
                        
            $updatedCounters = $folderCache->getCacheFolderCounter($folder);
            Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
            
            if ($updatedCounters['cache_totalcount'] != $folder->imap_totalcount) {
                // there are still messages missing in the cache
                $folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            }
        }
        
        $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder values after import of all messages: ' . print_r($folder->toArray(), TRUE));
        
        return $folder;
    }
    
    
    /**
     * get message with highest messageUid from cache 
     * 
     * @param  mixed  $_folderId
     * @return Felamimail_Model_Message
     */
    protected function _getLatestMessage($_folderId) 
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
        
        $result = $this->_backend->search($filter, $pagination);
        
        if(count($result) === 0) {
            return null;
        }
        
        return $result[0];
    }
    
    /**
     * get message with highest messageUid from cache 
     * 
     * @param  mixed  $_folderId
     * @return array
     */
    protected function _getCachedMessagesChunked($_folderId, $_firstMessageSequnce) 
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
        
        $result = $this->_backend->search($filter, $pagination);
        
        return $result;
    }
    
    /**
     * get message with highest messageUid from cache 
     * 
     * @param  mixed  $_folderId
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
        
        $messageUidsInCache = $this->_backend->search($filter)->messageuid;
        
        $result = array_diff($_messageUids, $messageUidsInCache);
        
        return $result;
    }
    
    /**
     * this function returns all messsageUids which are not yet in the local cache
     * 
     * @param  Felamimail_Model_Folder  $_folder
     * @param  array                    $_uids
     * @return array  the missing messageUids in local cache
     */
    protected function _getMissingUids(Felamimail_Model_Folder $_folder, $_uids)
    {
        $uids = (array) $_uids;
        
        if (empty($uids)) {
            return $uids;
        }
        
        $filter = new Felamimail_Model_MessageFilter(array(
            array(
                'field'    => 'messageuid',
                'operator' => 'in',
                'value'    => $uids
            ),
            array(
                'field'    => 'folder_id',
                'operator' => 'equals',
                'value'    => $_folder->getId()
            )
        ));
        
        $foundUids = $this->_backend->search($filter)->messageuid;
        
        $missingUids = array_diff($_uids, $foundUids);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' missing uids in local cache: ' . print_r($missingUids, TRUE));
        
        return $missingUids;
    }
    
    /**
     * remove all cached messages for this folder and reset folder values / folder status is updated in the database
     *
     * @param string|Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Folder
     * 
     * @todo rename to clearCache
     */
    public function clear($_folder)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing cache of ' . $folder->globalname);
        
        $this->_backend->deleteByFolderId($folder);
        
        $folder->cache_timestamp        = Zend_Date::now();
        $folder->cache_uidnext          = 1;
        $folder->cache_status           = Felamimail_Model_Folder::CACHE_STATUS_EMPTY;
        $folder->cache_job_actions_estimate = 0;
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
     * add one message to cache
     * 
     * @param  array                    $_message
     * @param  Felamimail_Model_Folder  $_folder
     * @param  bool                     $_updateFolderCounter
     * @return Felamimail_Model_Message
     */
    public function addMessage(array $_message, Felamimail_Model_Folder $_folder, $_updateFolderCounter = true)
    {
        // remove duplicate headers (which can't be set twice in real life)
        foreach (array('date', 'from', 'to', 'cc', 'bcc', 'subject') as $field) {
            if (isset($_message['header'][$field]) && is_array($_message['header'][$field])) {
                $_message['header'][$field] = $_message['header'][$field][0];
            }
        }
        
        $messageData = array(
            'messageuid'    => $_message['uid'],
            'folder_id'     => $_folder->getId(),
            'timestamp'     => Zend_Date::now(),
            'received'      => $this->_convertDate($_message['received'], Felamimail_Model_Message::DATE_FORMAT_RECEIVED),
            'size'          => $_message['size'],
            'flags'         => $_message['flags'],
            'structure'     => $_message['structure'],
            'content_type'  => isset($_message['structure']['contentType']) ? $_message['structure']['contentType'] : Zend_Mime::TYPE_TEXT,
            'subject'       => isset($_message['header']['subject']) ? Felamimail_Message::convertText($_message['header']['subject']) : '',
            'from'          => isset($_message['header']['from']) ? Felamimail_Message::convertText($_message['header']['from'], TRUE, 256) : null
        );
        
        if (array_key_exists('date', $_message['header'])) {
            $messageData['sent'] = $this->_convertDate($_message['header']['date']);
        } elseif (array_key_exists('resent-date', $_message['header'])) {
            $messageData['sent'] = $this->_convertDate($_message['header']['resent-date']);
        }
        
        foreach (array('to', 'cc', 'bcc') as $field) {
            if (isset($_message['header'][$field])) {
                // if sender set the headers twice we only use the first
                $messageData[$field] = $this->_convertAddresses($_message['header'][$field]);
            }
        }
        
        $bodyParts = $this->getBodyPartIds($_message['structure']);
        
        if (isset($bodyParts['text'])) {
            $messageData['text_partid'] = $bodyParts['text'];
        }
        if (isset($bodyParts['html'])) {
            $messageData['html_partid'] = $bodyParts['html'];
        }
        
        $cachedMessage = new Felamimail_Model_Message($messageData);
        
        $attachments = $this->getAttachments($cachedMessage);
        
        $cachedMessage->has_attachment = (count($attachments) > 0) ? true : false;
        
        $createdMessage = $this->_backend->create($cachedMessage);

        // store haeders in cache / we need them later anyway
        $cacheId = 'getMessageHeaders' . $createdMessage->getId();
        Tinebase_Core::get('cache')->save($_message['header'], $cacheId, array('getMessageHeaders'));
        
        #if (! $this->_hasSeenFlag($cachedMessage)) {
        #    $this->_backend->addFlag($createdMessage, Zend_Mail_Storage::FLAG_RECENT);
        #}
        
        if ($_updateFolderCounter == true) {
            Felamimail_Controller_Folder::getInstance()->updateFolderCounter($_folder, array(
                'cache_totalcount'  => "+1",
                'cache_unreadcount' => (! $this->_hasSeenFlag($cachedMessage)) ? '+1' : '+0',
            ));
        }
        
        /*
        # store in local cache if received during the last day
        # disabled again for performance reason
        if($createdMessage->received->compare(Zend_Date::now()->subDay(1)) == 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
                ' prefetch imap message to local cache ' . $createdMessage->getId()
            );            
            $this->getCompleteMessage($createdMessage);
        }
        */

        return $createdMessage;
    }
}
