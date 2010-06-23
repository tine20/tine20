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
 * @todo        add body of messages of last week (?) to cache?
 */

/**
 * cache controller for Felamimail messages
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Cache_Message extends Tinebase_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
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
     * message backend
     *
     * @var Felamimail_Backend_Cache_Sql_Message
     */
    protected $_backend = NULL;
    
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
     */
    public function update($_folder, $_time = 10)
    {
        $folder = Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        
        // check if we are allowed to update message cache?
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        if ($this->updateAllowed($folder) !== true) {
            Tinebase_TransactionManager::getInstance()->rollBack($transactionId);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " update of folder {$folder->globalname} currently not allowed. do nothing!");
            return $folder;
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        
        
        // get imap connection and select folder
        $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        // expunge function does also select the folder
        $imap->expunge($folder->globalname);
        
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
        $folder = $folderCache->getCacheFolderCounter($folder);
        
        $folder->cache_status    = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
        $folder->cache_timestamp = Zend_Date::now();
        $folder->cache_uidnext   = $folder->imap_uidnext;
        
        if(isset($folder->cache_uidvalidity) && $folder->imap_uidvalidity != $folder->cache_uidvalidity) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' uidvalidity changed => clear cache: ' . print_r($folder->toArray(), TRUE));
            $this->clear($folder);
            // reread cache counter
            $folder = $folderCache->getCacheFolderCounter($folder);
        }
        
        if($folder->imap_totalcount == 0 && $folder->cache_totalcount > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " folder is empty on imap server => clear cache of folder {$folder->globalname}");
            $this->clear($folder);
            // reread cache counter
            $folder = $folderCache->getCacheFolderCounter($folder);
        }
        
        $cacheMessageSequence = null;
        $imapMessageSequence  = null;
        
                
        $timeStart   = microtime(true);
        $timeElapsed = 0;
        
        if ($folder->imap_totalcount > 0) { 
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
            $lastFailedUid   = null;
            $messageSequence = null;
            
            // at which sequence is the message with the highest messageUid?
            while($messageSequence === null) {
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
            
            $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} cache sequence: $cacheMessageSequence imap sequence: $imapMessageSequence");

        
        // if the latest message on the cache has a different sequence number then on the imap server
        // then some messages before the latest message(from the cache) got deleted
        // we need remove them first from local cache
        // $folder->cache_totalcount equals to the message sequence of the last message in the cache
        if ($folder->imap_totalcount > 0 && $cacheMessageSequence > $imapMessageSequence) {
            // how many messages to remove?
            $messagesToRemoveFromCache = $cacheMessageSequence - $imapMessageSequence;
            
            if ($initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $folder->cache_job_actions_estimate += $messagesToRemoveFromCache;
            }
            
            if ($timeElapsed < $_time) {
            
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " $messagesToRemoveFromCache message to remove from cache");
                
                for ($i=$folder->cache_totalcount; $i > 0; $i -= $this->_importCountPerStep) {
                    $firstMessageSequence = ($i-$this->_importCountPerStep) >= 0 ? $i-$this->_importCountPerStep : 0;
                    $cachedMessages = $this->_getCachedMessagesChunked($folder, $firstMessageSequence);
                     
                    $messageUidsOnImapServer = $imap->messageUidExists($cachedMessages->messageuid);
                    
                    $toBeDeleted = array();
                    
                    $difference = array_diff($cachedMessages->messageuid, $messageUidsOnImapServer);
                    
                    foreach ($difference as $deletedMessageUid) {
                        $toBeDeleted[] = $cachedMessages->find('messageuid', $deletedMessageUid)->getId();
                    }
                    
                    if (!empty($toBeDeleted)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Delete " . print_r($toBeDeleted, true));
                        
                        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                        
                        $this->_backend->delete($toBeDeleted);
                        
                        $folder->cache_totalcount -= count($toBeDeleted);
                        $folder->cache_job_actions_done += count($toBeDeleted);
                        
                        $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
                        
                        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                        
                        $messagesToRemoveFromCache -= count($toBeDeleted);
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} messages to remove: $messagesToRemoveFromCache");
                        
                    }
                    
                    if ($messagesToRemoveFromCache == 0) {
                        break;
                    }
                     
                    $timeElapsed = round(((microtime(true)) - $timeStart));
                    if($timeElapsed > $_time) {
                        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
                        break;
                    }
                }
            }
        }
        
        $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
        $cacheMessageSequence = $folder->cache_totalcount;
        
        // update cache counter
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Cache status cache total count: {$folder->cache_totalcount} imap total count: {$folder->imap_totalcount} cache sequence: $cacheMessageSequence imap sequence: $imapMessageSequence");
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Cache uidnext status  cache: {$folder->cache_uidnext}  imap: {$folder->imap_uidnext}");
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);      
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache job estimate: ' . $folder->cache_job_actions_estimate);        
        
        // add new messages to cache
        if ($folder->imap_totalcount > 0 && $imapMessageSequence < $folder->imap_totalcount) {
                        
            if ($initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $folder->cache_job_actions_estimate += $folder->imap_totalcount - $imapMessageSequence;
            }
            
            if ($timeElapsed < $_time) {
                $messageSequenceStart = $imapMessageSequence + 1;
                
                // add new messages
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Retrieve message from $messageSequenceStart to {$folder->imap_totalcount}");
                
                while ($messageSequenceStart <= $folder->imap_totalcount) {
                    
                    $messageSequenceEnd = (($folder->imap_totalcount - $messageSequenceStart) > $this->_importCountPerStep ) ? $messageSequenceStart+$this->_importCountPerStep : $folder->imap_totalcount;
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Fetch message from $messageSequenceStart to $messageSequenceEnd $timeElapsed / $_time");
                    
                    $messages = $imap->getSummary($messageSequenceStart, $messageSequenceEnd, false);
                    
                    $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                    
                    foreach ($messages as $uid => $message) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Add UID: $uid");
                        $this->addMessage($message, $folder);
                        $folder->cache_job_actions_done++;
                        $folder->cache_totalcount++;
                    }
                    
                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                    
                    $messageSequenceStart = $messageSequenceEnd + 1;
                    
                    $timeElapsed = round(((microtime(true)) - $timeStart));
                    if($timeElapsed > $_time) {
                        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
                        break;
                    }
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);           
                }
            }
        }

        // maybe there are some messages missing before $imapMessageSequence
        if ($folder->imap_totalcount > 0 && $folder->cache_totalcount < $folder->imap_totalcount) {
            
            if ($initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE || $initialCacheStatus == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
                $folder->cache_job_actions_estimate += $folder->imap_totalcount - $folder->cache_totalcount;
            }
            
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
                        $messages = $imap->getSummary($missingUids);
                        
                        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                        
                        foreach ($messages as $uid => $message) {
                            $this->addMessage($message, $folder);
                            $folder->cache_job_actions_done++;
                            $folder->cache_totalcount++;
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " Added message $uid to cache");
                        }
                        
                        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                    }
                    
                    if($folder->cache_totalcount == $folder->imap_totalcount) {
                        $folder->cache_job_lowestuid = 0;
                        break;
                    }
                    
                    $timeElapsed = round(((microtime(true)) - $timeStart));
                    if($timeElapsed > $_time) {
                        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
                        $folder->cache_job_lowestuid = $messageSequenceStart;
                        break;
                    }
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);           
                }
            }
        }
        
        // lets update message flags if some time is left
        if ($timeElapsed < $_time) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' start updating flags'
            );
            for ($i=$folder->cache_totalcount; $i > 0; $i -= $this->_importCountPerStep) {
                $firstMessageSequence = ($i-$this->_importCountPerStep) >= 0 ? $i-$this->_importCountPerStep : 0;
                $cachedMessages = $this->_getCachedMessagesChunked($folder, $firstMessageSequence);

                $flags = $imap->getFlags($cachedMessages->messageuid);

                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                
                foreach ($cachedMessages as $cachedMessage) {
                    if (array_key_exists($cachedMessage->messageuid, $flags)) {
                        $newFlags = array_key_exists('flags', $flags[$cachedMessage->messageuid]) ? $flags[$cachedMessage->messageuid]['flags'] : arary();
                        $this->_backend->setFlags($cachedMessage, $newFlags);
                    }
                }
                
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                
                $timeElapsed = round(((microtime(true)) - $timeStart));
                if($timeElapsed > $_time) {
                    break;
                }
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' updating flags finished'
            );
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder cache status: ' . $folder->cache_status);     
           
        if($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
            $folder->cache_status               = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
            $folder->cache_uidnext              = $folder->imap_uidnext;
            $folder->cache_job_actions_estimate = 0;
            $folder->cache_job_actions_done     = 0;
            $folder->cache_job_lowestuid        = 0;
        }
        
        $folder = $folderCache->getCacheFolderCounter($folder);
        
        $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder values after import: ' . print_r($folder->toArray(), TRUE));
        
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
    
//    /**
//     * update message cache
//     * 
//     * @param string|Felamimail_Model_Folder $_folder
//     * @param integer $_time in seconds
//     * @return Felamimail_Model_Folder folder status (in cache)
//     */
//    public function update_old($_folder, $_time = 10)
//    {
//        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
//        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
//        
//        // check fencing and invalid cache/imap status here
//        if (! $this->updateAllowed($folder)) {
//            Tinebase_TransactionManager::getInstance()->rollBack($transactionId);
//            return $folder;
//        }
//        
//        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
//        $folder = $this->_updateFolderStatus($folder);
//        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
//        
//        
//        // fill message cache (from the top) / init time / update folder (cache_timestamp, status, estimate)
//        try {
//            ///////////////////////////// get imap backend and init stuff
//            
//            $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);
//                        
//            $folder->cache_recentcount = 0;
//            $timeLeft = TRUE;
//            
//            // @todo this should be initialized by the model/clear()
//            if (! $folder->cache_uidnext) {
//                $folder->cache_uidnext = 1;
//            }
//            
//            if (
//            // fresh import run
//                $folder->cache_job_lowestuid == 0
//            // if initial import is running, we should check if new messages arrived
//            // @todo think about adding a second job_start_uid that we can use as cache_uidnext for the recent new mails
//                || ($folder->imap_uidnext && $folder->cache_uidnext == 1 && $folder->cache_job_startuid != $folder->imap_uidnext)
//            ) {
//                $folder->cache_job_lowestuid = ($folder->imap_uidnext) ? $folder->imap_uidnext : $folder->imap_totalcount;
//                $folder->cache_job_startuid = $folder->cache_job_lowestuid; 
//            }
//            
//            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder values before import: ' . print_r($folder->toArray(), TRUE));
//            
//            // remove old \Recent flag from cached messages
//            $this->_backend->clearFlag($folder->getId(), Zend_Mail_Storage::FLAG_RECENT, 'folder');
//            
//            ///////////////////////////// get missing uids from imap
//             
//            // select folder and get all missing message uids from cache_job_lowestuid (imap_uidnext) to cache_uidnext
//            $imap->selectFolder($folder->globalname);
//            
//            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
//                . ' Try to get message uids from ' . ($folder->cache_job_lowestuid - 1) . ' to ' . $folder->cache_uidnext . ' from imap server.'
//            );
//
//            ///////////////////////////// main message import loop
//            
//            $count = 0;
//            $uids = array();
//            
//            // preset uid stepwidth;
//            $uidDensity = round($folder->imap_uidnext / max(1, $folder->imap_totalcount));
//            $this->_uidStepWidth = $this->_importCountPerStep * max(1, $uidDensity);
//            
//            if ($folder->imap_uidnext != $folder->cache_uidnext) {
//                while (
//                    // more messages to add ?
//                    $folder->cache_job_lowestuid > $folder->cache_uidnext &&
//                    $timeLeft &&
//                    // initial import (this should not run until lowest uid reaches 1)
//                    ($folder->cache_uidnext != 1 || $folder->imap_totalcount > $folder->cache_totalcount)
//                ) {
//                    // get summary and add messages
//                    if (empty($uids)) {
//                        if ($folder->imap_uidnext) {
//                            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' fetching with a stepwidth of: ' . $this->_uidStepWidth);
//                            
//                            $stepLowestUid = max($folder->cache_job_lowestuid - $this->_uidStepWidth, $folder->cache_uidnext);
//                            $stepHighestUid = $folder->cache_job_lowestuid - 1;
//                            $uids = $imap->getUidbyUid($stepLowestUid, $stepHighestUid);
//                            
//                            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got ' . count($uids) 
//                            //    . ' new uids from IMAP server: ' . $stepHighestUid . ' - ' . $stepLowestUid);
//                            
//                            // adjust stepwidth for next run
//                            $this->_uidStepWidth = max(2*$this->_importCountPerStep, round($this->_uidStepWidth * max(1/$this->_importCountPerStep, $this->_importCountPerStep / max(1, count($uids)))));
//                        } else {
//                            // imap servers without uidnext
//                            $stepLowestUid = $folder->cache_uidnext;
//                            $uids = $imap->getUid($folder->cache_uidnext, $folder->cache_job_lowestuid);
//                            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got ' . count($uids) 
//                            //    . ' new uids from IMAP server: ' . ($folder->cache_uidnext) . ' - ' . $folder->cache_job_lowestuid);
//                        }
//                        
//                        $uids = $this->_getMissingUids($folder, $uids);
//                        
//                        rsort($uids, SORT_NUMERIC);
//                    }
//                    
//                    if (! empty($uids)) {
//                        $nextUids = array_splice($uids, 0, min($this->_importCountPerStep, count($uids)));
//                        
//                        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Importing uids: ' . print_r($nextUids, TRUE));
//                        $messages = $imap->getSummary($nextUids);
//                        $count += $this->_addMessages($messages, $folder);
//                        
//                        $folder->cache_job_lowestuid = ($folder->imap_uidnext) ? min($nextUids) : ($folder->imap_totalcount - $folder->cache_totalcount);
//                    } else {
//                        $folder->cache_job_lowestuid = $stepLowestUid;
//                    }
//                    
//                    $timeLeft = ($folder->cache_timestamp->compare(Zend_Date::now()->subSecond($_time)) == 1);
//                }
//            }
//            
//            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Finished import run  ... Time: ' . Zend_Date::now()->toString() 
//                . ' Added messages to cache (new/recent): ' . $count . ' / ' . $folder->cache_recentcount);
//            
//            ///////////////////////////// sync deleted messages or start again to add recent messages
//                
//            if ($timeLeft) {
//                if ($folder->cache_totalcount > $folder->imap_totalcount) {
//                    // sync deleted if we still got time left and totalcounts mismatch
//                    $this->_syncDeletedMessages($folder, $_time, $imap);
//                } else {
//                    $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
//                }
//            }
//            
//            // need to start again, we did not import some messages -> invalidate cache
//            if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $folder->cache_totalcount < $folder->imap_totalcount) {
//                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Need to start import again ... :(');
//                $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INVALID;
//            }
//                
//            ///////////////////////////// finished with import run
//            
//            if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
//                $folder->cache_uidnext = $folder->imap_uidnext;
//                $folder->cache_job_lowestuid = 0;
//            } else if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
//                $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
//            }
//            
//        } catch (Zend_Mail_Protocol_Exception $zmpe) {
//            // some error with the imap server
//            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
//            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getTraceAsString());
//            $folder->imap_status = Felamimail_Model_Folder::IMAP_STATUS_DISCONNECT;
//            $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
//        }
//
//        // update and return folder
//        $folder = $this->_updateFolderStatus($folder);
//        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder values after import: ' . print_r($folder->toArray(), TRUE));
//        return $folder;
//    }
    
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
     */
    public function clear($_folder)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing cache of ' . $folder->globalname);
        
        $this->_backend->deleteByFolderId($folder->getId());
        
        // set folder uidnext + uidvalidity to 0 and reset cache status
        $folder->imap_uidnext       = 0;
        $folder->imap_uidvalidity   = 0;
        $folder->imap_totalcount    = 0;
        $folder->imap_timestamp     = NULL;
        $folder->cache_timestamp    = NULL;
        $folder->cache_uidnext      = 1;
        $folder->cache_totalcount   = 0;
        $folder->cache_recentcount  = 0;
        $folder->cache_unreadcount  = 0;
        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
        $folder->cache_job_lowestuid = 0;
        $folder->cache_job_startuid = 0;
        $folder->cache_job_actions_estimate = 0;
        $folder->cache_job_actions_done = 0;
        
        $folder = Felamimail_Controller_Cache_Folder::getInstance()->getIMAPFolderCounter($folder);
        
        $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
        
        return $folder;
    }
    
    /**
     * check if folder cache is updating atm
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     * 
     * @todo we should check the time of the last update to dynamically decide if process could have died
     */
    public function updateAllowed(Felamimail_Model_Folder $_folder)
    {
        // if cache status is CACHE_STATUS_UPDATING and timestamp is less than 5 minutes ago, don't update
        if ($_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING &&
            ($_folder->cache_timestamp instanceof Zend_Date && $_folder->cache_timestamp->compare(Zend_Date::now()->subMinute(5)) == 1)
        ) {
            return false;
        }
                        
        return true;
    }
    
    /**
     * get unread count for folder
     * 
     * @todo FIXME: result can get negative when cache is updating
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return integer
     */
    public function getUnreadCount(Felamimail_Model_Folder $_folder)
    {
        $result = $_folder->cache_totalcount - $this->_backend->seenCountByFolderId($_folder->getId());
        
        // make sure $result can't get negative
        $result = ($result < 0) ? 0 : $result;
        
        return $result;
    }
    
    /**
     * get total count for folder
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return integer
     */
    public function getTotalCount(Felamimail_Model_Folder $_folder)
    {
        $result = $this->_backend->searchCountByFolderId($_folder->getId());
        return $result;
    }
    
    /***************************** protected funcs *******************************/

//    /**
//     * check fencing and invalid cache/imap status here
//     * 
//     * @param Felamimail_Model_Folder $_folder
//     * @return boolean TRUE if update can begin / FALSE if no update required or not possible
//     */
//    protected function _iisUpdateAllowed(Felamimail_Model_Folder $_folder)
//    {
//        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Checking folder status of ' . $_folder->globalname . ' ...');
//        
//        // check imap connection
//        if ($_folder->imap_status == Felamimail_Model_Folder::IMAP_STATUS_DISCONNECT) {
//            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Lost imap connection. Do not update the cache.');
//            return false;
//        }
//        
//        if ($_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
//            return false;
//        }
//        
//        return true;
//    }
    
//    /**
//     * updates the folder status in the folder cache database and calculate estimate
//     * 
//     * @param Felamimail_Model_Folder $_folder
//     * @param Zend_Date $_timestamp
//     * @return Felamimail_Model_Folder
//     * @throws Felamimail_Exception
//     * 
//     * @todo we should save the time of update process
//     */
//    protected function _updateFolderStatus(Felamimail_Model_Folder $_folder, $_timestamp = NULL)
//    {
//        $_folder->cache_timestamp = ($_timestamp !== NULL) ? $_timestamp : Zend_Date::now();
//        
//        switch ($_folder->cache_status) {
//            case Felamimail_Model_Folder::CACHE_STATUS_UPDATING:
//                #if ($_folder->cache_job_actions_done == $_folder->cache_job_actions_estimate) {
//                #    // calc new estimate
//                #    $_folder->cache_job_actions_done = 0;
//                #    $_folder->cache_job_actions_estimate = abs($_folder->imap_totalcount - $_folder->cache_totalcount);
//                #    if ($_folder->cache_job_actions_estimate == 0) {
//                #        // messages have been deleted and new messages have been added
//                #        if ($_folder->cache_uidnext < $_folder->imap_uidnext) {
//                #            $_folder->cache_job_actions_estimate = abs($_folder->imap_uidnext - $_folder->cache_uidnext);
//                #        } else {
//                #            $_folder->cache_uidnext = $_folder->imap_uidnext;
//                #        }
//                #    }
//                #    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Actions estimate: ' . $_folder->cache_job_actions_estimate);
//                #    
//                #} else if ($_folder->cache_job_actions_done > $_folder->cache_job_actions_estimate) {
//                #    // sanitize actions
//                #    $_folder->cache_job_actions_estimate = $_folder->imap_totalcount;
//                #    $_folder->cache_job_actions_done = $_folder->cache_totalcount;
//                #}
//                $message = ' Starting cache update.';
//                break;
//            case Felamimail_Model_Folder::CACHE_STATUS_COMPLETE:
//                #$_folder->cache_job_actions_done = $_folder->cache_job_actions_estimate;
//                $message = ' Finished update.';
//                break;
//            case Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE:
//                $message = ' Update incomplete.';
//                break;
//            case Felamimail_Model_Folder::CACHE_STATUS_INVALID:
//                $message = ' Update broken. Invalidating cache.';
//                break;
//        }
//        
//        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . $message . ' Updating folder status now. Time: ' . $_folder->cache_timestamp);
//        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_folder->toArray(), TRUE));
//        
//        return Felamimail_Controller_Folder::getInstance()->update($_folder);
//    }
    
    /**
     * add one message to cache
     * 
     * @param  array                    $_message
     * @param  Felamimail_Model_Folder  $_folder
     * @return Felamimail_Model_Message
     */
    public function addMessage(array $_message, Felamimail_Model_Folder $_folder)
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
        
        #if(count($bodyParts) === 0) {var_dump($value); }
        
        if (isset($bodyParts['text'])) {
            $messageData['text_partid'] = $bodyParts['text'];
        }
        if (isset($bodyParts['html'])) {
            $messageData['html_partid'] = $bodyParts['html'];
        }
        
        $cachedMessage = new Felamimail_Model_Message($messageData);
        
        $createdMessage = $this->_backend->create($cachedMessage);
        
        if (! in_array(Zend_Mail_Storage::FLAG_SEEN, $cachedMessage->flags)) {
            $this->_backend->addFlag($createdMessage, Zend_Mail_Storage::FLAG_RECENT);
        }
        
        # store in local cache if received during the last day
        # disabled again for performance reason
        #if($createdMessage->received->compare(Zend_Date::now()->subDay(1)) == 1) {
        #    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
        #        ' prefetch imap message to local cache ' . $createdMessage->getId()
        #    );            
        #    Felamimail_Controller_Message::getInstance()->getCompleteMessage($createdMessage);
        #}

        return $createdMessage;
    }
        
//    /**
//     * sync deleted messages
//     * 
//     * @param Felamimail_Model_Folder $_folder
//     * @param integer $_time
//     * @param Felamimail_Backend_ImapProxy $_imap
//     * @return void
//     */
//    protected function _syncDeletedMessages(Felamimail_Model_Folder $_folder, $_time, Felamimail_Backend_ImapProxy $_imap)
//    {
//        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Syncing deleted messages in folder ' . $_folder->globalname);
//        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' before deleted sync: ' . print_r($_folder->toArray(), TRUE));
//        
//        // update folder with estimate / actions
//        $_folder->cache_job_actions_estimate += abs($_folder->cache_totalcount - $_folder->imap_totalcount);
//        
//        $timeLeft = TRUE;
//        $start = 0;
//        $stepSize = 50;
//        while ($timeLeft && $_folder->cache_totalcount > $_folder->imap_totalcount) {
//            
//            // get next 50 messages from cache job lowest uid
//            $messages = Felamimail_Controller_Message::getInstance()->search(new Felamimail_Model_MessageFilter(array(
//                array('field' => 'folder_id',   'operator' => 'equals', 'value' => $_folder->getId()),
//                array('field' => 'account_id',  'operator' => 'equals', 'value' => $_folder->account_id),
//                array('field' => 'messageuid',  'operator' => 'less',   'value' => $_folder->cache_job_lowestuid),
//            )), new Tinebase_Model_Pagination(array('start' => $start, 'limit' => $stepSize, 'sort' => 'messageuid')));
//            
//            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got ' . count($messages) 
//                . ' message(s) from cache beginning with uid ' . $_folder->cache_job_lowestuid);
//            
//            // get messageuids
//            if (count($messages) > 0) {
//                $cacheUids = $messages->messageuid;
//                
//                // query mailserver
//                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Query uids from imap server.');
//                $imapUids = $_imap->getUidbyUid($cacheUids);
//                
//                // delete uids not on mailserver from cache
//                $toDeleteUids = array_diff($cacheUids, $imapUids);
//                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($cacheUids, TRUE));
//                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($imapUids, TRUE));
//                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($toDeleteUids, TRUE));
//                
//                if (! empty($toDeleteUids)) {
//                    $this->_backend->deleteByProperty($toDeleteUids, 'messageuid', 'in');
//                }
//                $numberDeleted = count($toDeleteUids);
//                
//                // update folder values
//                $_folder->cache_job_actions_done += $numberDeleted;
//                $_folder->cache_totalcount -= $numberDeleted;
//                $_folder->cache_job_lowestuid = min($cacheUids);
//                
//                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleted ' . $numberDeleted . ' messages from cache.');
//            }
//            
//            // check if we are done
//            if (count($messages) < $stepSize) {
//                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No more messages in cache with uid lower than ' . $_folder->cache_job_lowestuid);
//                $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
//                if ($_folder->cache_totalcount > $_folder->imap_totalcount) {
//                    // sanitize folder totalcount
//                    $_folder->cache_totalcount = $this->getTotalCount($_folder); 
//                }
//                break;
//            }
//            
//            // check time and increase start for pagination
//            $timeLeft = ($_folder->cache_timestamp->compare(Zend_Date::now()->subSecond($_time)) == 1);
//            $start += $stepSize;
//        }
//        
//        // calc new cache unreadcount
//        $_folder->cache_unreadcount = $this->getUnreadCount($_folder);
//    }
    
    /**
     * convert date from sent/received
     *
     * @param string $_dateString
     * @param string $_format default: 'Thu, 21 Dec 2000 16:01:07 +0200' (Zend_Date::RFC_2822)
     * @return Zend_Date
     */
    protected function _convertDate($_dateString, $_format = Zend_Date::RFC_2822)
    {
        try {
            if ($_format == Zend_Date::RFC_2822) {
    
                // strip of timezone information for example: (CEST)
                $dateString = preg_replace('/( [+-]{1}\d{4}) \(.*\)$/', '${1}', $_dateString);
                
                // append dummy weekday if missing
                if(preg_match('/^(\d{1,2})\s(\w{3})\s(\d{4})\s(\d{2}):(\d{2}):{0,1}(\d{0,2})\s([+-]{1}\d{4})$/', $dateString)) {
                    $dateString = 'xxx, ' . $dateString;
                }
                
                try {
                    // Fri,  6 Mar 2009 20:00:36 +0100
                    $date = new Zend_Date($dateString, Zend_Date::RFC_2822, 'en_US');
                } catch (Zend_Date_Exception $e) {
                    // Fri,  6 Mar 2009 20:00:36 CET
                    $date = new Zend_Date($dateString, Felamimail_Model_Message::DATE_FORMAT, 'en_US');
                }
    
            } else {
                
                $date = new Zend_Date($_dateString, $_format, 'en_US');
                
                if ($_format == Felamimail_Model_Message::DATE_FORMAT_RECEIVED) {
                    
                    if (preg_match('/ ([+-]{1})(\d{2})\d{2}$/', $_dateString, $matches)) {
                        // add / sub from zend date ?
                        if ($matches[1] == '+') {
                            $date->subHour($matches[2]);
                        } else {
                            $date->addHour($matches[2]);
                        }
                        
                        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($matches, true));
                    }
                }
            }
        } catch (Zend_Date_Exception $zde) {
            $date = new Zend_Date(0, Zend_Date::TIMESTAMP);
        }
        
        return $date;
    }
    
    public function getBodyPartIds(array $_structure)
    {
        $result = array();
        
        if ($_structure['type'] == 'text') {
            $result = array_merge($result, $this->_parseText($_structure));
        } elseif($_structure['type'] == 'multipart') {
            $result = array_merge($result, $this->_parseMultipart($_structure));
        }
        
        return $result;
    }
    
    protected function _parseText(array $_structure)
    {
        $result = array();

        if (isset($_structure['disposition']['type']) && $_structure['disposition']['type'] == 'attachment') {
            return $result;
        }
        
        if ($_structure['subType'] == 'plain') {
            $result['text'] = !empty($_structure['partId']) ? $_structure['partId'] : 1;
        } elseif($_structure['subType'] == 'html') {
            $result['html'] = !empty($_structure['partId']) ? $_structure['partId'] : 1;
        }
        
        return $result;
    }
    
    protected function _parseMultipart(array $_structure)
    {
        $result = array();
        
        if ($_structure['subType'] == 'alternative' || $_structure['subType'] == 'mixed' || 
            $_structure['subType'] == 'signed' || $_structure['subType'] == 'related') {
            foreach($_structure['parts'] as $part) {
                $result = array_merge($result, $this->getBodyPartIds($part));
            }
        } else {
            // ignore other types for now
            #var_dump($_structure);
            #throw new Exception('unsupported multipart');    
        }
        
        return $result;
    }
    
    /**
     * convert addresses into array with name/address
     *
     * @param string $_addresses
     * @return array
     */
    protected function _convertAddresses($_addresses)
    {
        $result = array();
        if (!empty($_addresses)) {
            $addresses = Felamimail_Message::parseAdresslist($_addresses);
            if (is_array($addresses)) {
                foreach($addresses as $address) {
                    $result[] = array('email' => $address['address'], 'name' => $address['name']);
                }
            }
        }
        return $result;
    }
}
