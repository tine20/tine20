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
 * @todo        parse mail body and add <a> to telephone numbers?
 * @todo        check html purifier config (allow some tags/attributes?)
 * @todo        improve handling of BIG (rfc822) messages (don't read the whole content?) -> php://temp stream
 */

/**
 * message controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message extends Tinebase_Controller_Record_Abstract
{
    /**
     * maximum file upload size (in bytes)
     * 
     * 0 -> max size = memory limit
     * 2097152 = 2MB
     */
    //const MAX_ATTACHMENT_SIZE = 2097152;
    const MAX_ATTACHMENT_SIZE = 0;
    
    /**
     * imap flags to constants translation
     * @var array
     */
    protected static $_allowedFlags = array('\Answered' => Zend_Mail_Storage::FLAG_ANSWERED,
                                            '\Seen'     => Zend_Mail_Storage::FLAG_SEEN,
                                            '\Deleted'  => Zend_Mail_Storage::FLAG_DELETED,
                                            '\Draft'    => Zend_Mail_Storage::FLAG_DRAFT,
                                            '\Flagged'  => Zend_Mail_Storage::FLAG_FLAGGED);
    
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message
     */
    private static $_instance = NULL;
    
    /**
     * cache controller
     *
     * @var Felamimail_Controller_Cache_Message
     */
    protected $_cacheController = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_modelName = 'Felamimail_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        
        $this->_currentAccount = Tinebase_Core::getUser();
        
        $this->_cacheController = Felamimail_Controller_Cache_Message::getInstance();
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
     * @return Felamimail_Controller_Message
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Message();
        }
        
        return self::$_instance;
    }
    
    /************************* other public funcs *************************/
    
    /**
     * get complete message by id
     *
     * @param string|Felamimail_Model_Message  $_id
     * @param boolean                          $_setSeen
     * @return Felamimail_Model_Message
     */
    public function getCompleteMessage($_id, $_setSeen = FALSE)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
            ' Getting message ' . $message->messageuid 
        );
            
        // get account
        $folder = Felamimail_Controller_Folder::getInstance()->get($message->folder_id);
        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        
        // add body
        $mimeType = $account->display_format == 'html' ? Zend_Mime::TYPE_HTML : Zend_Mime::TYPE_TEXT;
        $message->body = $this->getMessageBody($message, $mimeType, true);
        
        // add header
        $message->headers = $this->getMessageHeaders($_id, true);
        
        // add attachments / belongs to cache function 
        $message->attachments = $this->getAttachments($message['structure']);
        if ($message->has_attachment == false && count($message->attachments) > 0) {
            $message->has_attachment = true;
        }
        
        // set \Seen flag
        if ($_setSeen && !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Add \Seen flag to msg uid ' . $message->messageuid
            );
            $this->addFlags($message, Zend_Mail_Storage::FLAG_SEEN);
            $message->flags[] = Zend_Mail_Storage::FLAG_SEEN;
        }

        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));
        
        return $message;
    }
    
    /**
     * add flags to messages
     *
     * @param mixed                     $_message
     * @param array                     $_flags
     */
    public function addFlags($_messages, $_flags)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Add flags: ' . print_r($_flags, TRUE));
        
        // we always need to read the messages from cache to get the current flags
        if ($_messages instanceof Felamimail_Model_MessageFilter) {
            $messagesToFlag = $this->search($_messages);
        } elseif ($_messages instanceof Tinebase_Record_RecordSet) {
            $messagesToFlag = $this->_backend->getMultiple($_messages->getArrayOfIds());
        } elseif ($_messages instanceof Felamimail_Model_Message) {
            $messagesToFlag = $this->_backend->getMultiple($_messages->getId());
        } else {
            $messagesToFlag = $this->_backend->getMultiple($_messages);
        }
        
        $messagesToFlag->sort('folder_id');
        
        $flags = (array) $_flags;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' retrieved messages from cache');
                
        $lastFolderId = null;
        $imapBackend  = null;
        $folderIds    = array();
        
        // set flags on imap server
        foreach ($messagesToFlag as $message) {
            if($imapBackend !== null && ($lastFolderId != $message->folder_id || count($imapMessageUids) >= 50)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' set flags on imap server');
                $imapBackend->addFlags($imapMessageUids, array_intersect($flags, array_keys(self::$_allowedFlags)));
                $imapMessageUids = array();
            }
            
            if ($lastFolderId != $message->folder_id) {
                $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id);
                $lastFolderId             = $message->folder_id;
                $folderIds[$lastFolderId] = 0;
            }
            
            $imapMessageUids[] = $message->messageuid;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' set flags on imap server');
        
        if($imapBackend !== null && count($imapMessageUids) > 0) {
            $imapBackend->addFlags($imapMessageUids, array_intersect($flags, array_keys(self::$_allowedFlags)));
        }    

        // set flags in local database
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        foreach($messagesToFlag as $message) {
            foreach ($flags as $flag) {
                if (!is_array($message->flags) || !in_array($flag, $message->flags)) {
                    if ($flag == Zend_Mail_Storage::FLAG_DELETED) {
                        $this->delete($message->getId());
                        $messagesToFlag->removeRecord($message);
                    } else {
                        $this->_backend->addFlag($message, $flag);
                        if ($flag == Zend_Mail_Storage::FLAG_SEEN) {
                            // count messages with seen flag for the first time
                            $folderIds[$message->folder_id]++;
                        }
                    }
                }
            }
        }
        
        // mark message as changed in the cache backend
        $this->_backend->updateMultiple(
            $messagesToFlag->getArrayOfIds(), 
            array(
                'timestamp' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            )
        );
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        // @todo return list of affected folders
        foreach ($folderIds as $folderId => $decrementUnreadCounter) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);
            if ($folder->cache_unreadcount < $decrementUnreadCounter) {
                // something went wrong => recalculate unread counter
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' folder counters dont match => refresh counters'
                );
                $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
            } else {
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                    'cache_unreadcount' => "-$decrementUnreadCounter",
                ));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' set flags on cache');
    }
    
    /**
     * append a new message to given folder
     *
     * @param  string  $_folder   name of target folder
     * @param  string  $_message  full message content
     * @param  array   $_flags    flags for new message
     * @param  string  $_date     date for new message
     * @return bool success
     * @throws Zend_Mail_Protocol_Exception
     */
    public function appendMessage($_folder, $_message, $_flags = null, $_date = null)
    {
        $folder  = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        $message = (is_resource($_message)) ? stream_get_contents($_message) : $_message;
        $flags   = ($_flags !== null) ? (array) $_flags : null;
        
        Felamimail_Backend_ImapFactory::factory($folder->account_id)->appendMessage($message, $folder->globalname, $flags);
    }
    
    /**
     * clear message flag(s)
     *
     * @param mixed                     $_messages
     * @param array                     $_flags
     * @param Felamimail_Model_Folder   $_folder [optional]
     * 
     * @todo update folder status if message unread/read
     */
    public function clearFlags($_messages, $_flags, $_folder = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' clear flags: ' . print_r($_flags, TRUE));
        
        // we always need to read the messages from cache to get the current flags
        if ($_messages instanceof Felamimail_Model_MessageFilter) {
            $messagesToFlag = $this->search($_messages);
        } elseif ($_messages instanceof Tinebase_Record_RecordSet) {
            $messagesToFlag = $this->_backend->getMultiple($_messages->getArrayOfIds());
        } elseif ($_messages instanceof Felamimail_Model_Message) {
            $messagesToFlag = $this->_backend->getMultiple($_messages->getId());
        } else {
            $messagesToFlag = $this->_backend->getMultiple($_messages);
        }
        
        $messagesToFlag->sort('folder_id');
        
        $flags = (array) $_flags;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' retrieved ' . count($messagesToFlag) . ' messages from cache');
                
        $lastFolderId = null;
        $imapBackend  = null;
        $folderIds    = array();
        
        // set flags on imap server
        foreach ($messagesToFlag as $message) {
            if($imapBackend !== null && ($lastFolderId != $message->folder_id || count($imapMessageUids) >= 50)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' clear flags on imap server');
                $imapBackend->clearFlags($imapMessageUids, array_intersect($flags, array_keys(self::$_allowedFlags)));
                $imapMessageUids = array();
            }
            
            if ($lastFolderId != $message->folder_id) {
                $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id);
                $lastFolderId             = $message->folder_id;
                $folderIds[$lastFolderId] = 0;
            }
            
            $imapMessageUids[] = $message->messageuid;
            
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' clear flags on imap server');
        
        if($imapBackend !== null && count($imapMessageUids) > 0) {
            $imapBackend->clearFlags($imapMessageUids, array_intersect($flags, array_keys(self::$_allowedFlags)));
        }
            
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cleared flags on imap server');
        
        // set flags in local database
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        // store flags in local cache
        foreach($messagesToFlag as $message) {
            if (in_array(Zend_Mail_Storage::FLAG_SEEN, $flags) && in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                // count messages with seen flag for the first time
                $folderIds[$message->folder_id]++;
            }
            
            $this->_backend->clearFlag($message, $flags);
        }
        
        // mark message as changed in the cache backend
        $this->_backend->updateMultiple(
            $messagesToFlag->getArrayOfIds(), 
            array(
                'timestamp' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            )
        );
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        // @todo return list of affected folders
        foreach ($folderIds as $folderId => $incrementUnreadCounter) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);
            if ($folder->cache_unreadcount + $incrementUnreadCounter > $folder->cache_totalcount) {
                // something went wrong => recalculate unread counter
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' folder counters dont match => refresh counters'
                );
                $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
            } else {
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                    'cache_unreadcount' => "+$incrementUnreadCounter",
                ));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cleared flags on cache');
    }
    
    /**
     * delete messages in cache backend and on imap server
     * @param  mixed  $_ids
     */
    public function delete($_ids)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete messages: ' . count($_ids));
        
        // we always need to read the messages from cache to get the current flags
        if ($_ids instanceof Felamimail_Model_MessageFilter) {
            $messages = $this->search($_ids);
        } elseif ($_ids instanceof Tinebase_Record_RecordSet) {
            $messages = $this->_backend->getMultiple($_ids->getArrayOfIds());
        } elseif ($_ids instanceof Felamimail_Model_Message) {
            $messages = $this->_backend->getMultiple($_ids->getId());
        } else {
            $messages = $this->_backend->getMultiple($_ids);
        }
                
        $messages->sort('folder_id');
        
        $lastAccountId = null;
        $lastFolderId  = null;
        $imapBackend   = null;
        $imapAccount   = null;
        $folderIds    = array();
        
        // delete messages on imap server
        foreach ($messages as $message) {
            if($imapBackend !== null && ($lastFolderId != $message->folder_id || count($imapMessageUids) >= 50)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete messages on imap server');
                if (!empty($imapAccount->trash_folder)) {
                    try {
                        $trashFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($imapAccount, $imapAccount->trash_folder);
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                            ' move messages to trash.'
                        );
                        $imapBackend->copyMessage($imapMessageUids, $imapAccount->trash_folder);
                    } catch (Tinebase_Exception_NotFound $ten) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                            ' trash folder does not exist! cant move messages.'
                        );
                    }
                }
                $imapBackend->removeMessage($imapMessageUids);
                
                $imapMessageUids = array();
            }
            
            if ($lastFolderId != $message->folder_id) {
                $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id, $selectedFolder);
                $lastFolderId             = $message->folder_id;
                $folderIds[$lastFolderId] = array(
                    'decrementMessagesCounter' => 0, 
                    'decrementUnreadCounter' => 0
                );
                
                if ($lastAccountId != $selectedFolder->account_id) {
                    $imapAccount = $account = Felamimail_Controller_Account::getInstance()->get($selectedFolder->account_id);
                    $lastAccountId = $selectedFolder->account_id;
                }
            }
            
            $imapMessageUids[] = $message->messageuid;
            
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete messages on imap server');
        
        if($imapBackend !== null && count($imapMessageUids) > 0) {
            if (!empty($imapAccount->trash_folder)) {
                try {
                    $trashFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($imapAccount, $imapAccount->trash_folder);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                        ' move messages to trash.'
                    );
                    $imapBackend->copyMessage($imapMessageUids, $imapAccount->trash_folder);
                } catch (Tinebase_Exception_NotFound $ten) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                        ' trash folder does not exist! cant move messages.'
                    );
                }
            }
            $imapBackend->removeMessage($imapMessageUids);
        }    

        // delete messages in local cache
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        foreach($messages as $message) {
            if (!in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                // count messages with seen flag for the first time
                $folderIds[$message->folder_id]['decrementUnreadCounter']++;
            }
            $folderIds[$message->folder_id]['decrementMessagesCounter']++;
            
            $this->_backend->delete($messages->getId());
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        // @todo return list of affected folders
        foreach ($folderIds as $folderId => $counter) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);
            if ($folder->cache_unreadcount < $counter['decrementUnreadCounter'] || $folder->cache_totalcount < $counter['decrementMessagesCounter']) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' folder counters dont match => refresh counters'
                );
                $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
            } else {
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                    'cache_totalcount'  => "-" . $counter['decrementMessagesCounter'],
                    'cache_unreadcount' => "-" . $counter['decrementUnreadCounter']
                ));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' deleted messages on cache');
        
    }
    
    /**
     * move messages to folder
     *
     * @param  mixed  $_messages
     * @param  mixed  $_targetFolder
     * @return Felamimail_Model_Folder
     */
    public function moveMessages($_messages, $_targetFolder)
    {
        // we always need to read the messages from cache to get the current flags
        if ($_messages instanceof Felamimail_Model_MessageFilter) {
            $messages = $this->search($_messages);
        } elseif ($_messages instanceof Tinebase_Record_RecordSet) {
            $messages = $this->_backend->getMultiple($_messages->getArrayOfIds());
        } elseif ($_messages instanceof Felamimail_Model_Message) {
            $messages = $this->_backend->getMultiple($_messages->getId());
        } else {
            $messages = $this->_backend->getMultiple($_messages);
        }
                
        $targetFolder = ($_targetFolder instanceof Felamimail_Model_Folder) ? $_targetFolder : Felamimail_Controller_Folder::getInstance()->get($_targetFolder);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' move ' . count($messages) . ' messages to ' . $targetFolder->globalname
        );
        
        $messages->sort('folder_id');
        
        $lastAccountId = null;
        $lastFolderId  = null;
        $imapBackend   = null;
        $folderIds    = array();
        
        // delete messages on imap server
        foreach ($messages as $message) {
            if($imapBackend !== null && ($lastFolderId != $message->folder_id || count($imapMessageUids) >= 50)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' move messages on imap server');
                $imapBackend->copyMessage($imapMessageUids, $targetFolder->globalname);
                $imapBackend->removeMessage($imapMessageUids);
                
                $imapMessageUids = array();
            }
            
            if ($lastFolderId != $message->folder_id) {
                $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id, $selectedFolder);
                $lastFolderId             = $message->folder_id;
                $folderIds[$lastFolderId] = array(
                    'decrementMessagesCounter' => 0, 
                    'decrementUnreadCounter' => 0
                );
            }
            
            $imapMessageUids[] = $message->messageuid;
            
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' moved messages on imap server');
        
        if($imapBackend !== null && count($imapMessageUids) > 0) {
            $imapBackend->copyMessage($imapMessageUids, $targetFolder->globalname);
            $imapBackend->removeMessage($imapMessageUids);
        }    

        // delete messages in local cache
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        foreach($messages as $message) {
            if (!is_array($message->flags) || !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                // count messages with seen flag for the first time
                $folderIds[$message->folder_id]['decrementUnreadCounter']++;
            }
            $folderIds[$message->folder_id]['decrementMessagesCounter']++;
            
            $this->_backend->delete($messages->getId());
        }
                        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        // @todo return list of affected folders
        foreach ($folderIds as $folderId => $counter) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);
            if ($folder->cache_unreadcount < $counter['decrementUnreadCounter'] || $folder->cache_totalcount < $counter['decrementMessagesCounter']) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' folder counters dont match => refresh counters'
                );
                $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
            } else {
                Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, array(
                    'cache_totalcount'  => "-" . $counter['decrementMessagesCounter'],
                    'cache_unreadcount' => "-" . $counter['decrementUnreadCounter']
                ));
            }
        }
                
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' deleted messages on cache');
        
        return Felamimail_Controller_Folder::getInstance()->get($targetFolder);
    }
    
    /**
     * send one message through smtp
     * 
     * @param Felamimail_Model_Message $_message
     * 
     * @todo what has to be set in the 'In-Reply-To' header?
     * @todo add name for to/cc/bcc
     * @todo move $mail creation to extra function
     */
    public function sendMessage(Felamimail_Model_Message $_message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));

        // increase execution time (sending message with attachments can take a long time)
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // get account
        $account = Felamimail_Controller_Account::getInstance()->get($_message->from);
        
        // get original message
        $originalMessage = ($_message->original_id) ? $this->get($_message->original_id) : NULL;

        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($_message->toArray(), TRUE));
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($account->toArray(), TRUE));
        
        // create new mail to send
        $mail = new Tinebase_Mail('UTF-8');
        
        // build mail content
        if ($_message->content_type == Felamimail_Model_Message::CONTENT_TYPE_HTML) {
            $mailBodyText = $this->_removeHtml($_message->body);
            $mail->setBodyText($mailBodyText);
            $mail->setBodyHtml($this->_addHtmlMarkup($_message->body));
        } else {
            $mail->setBodyText($_message->body);
        }
        
        
        // set from
        $from = (isset($account->from) && ! empty($account->from)) 
            ? $account->from 
            : substr($account->email, 0, strpos($account->email, '@'));
        // quote meta chars such as []\ etc
        $from = quotemeta($from);
        $mail->setFrom($account->email, $from);

        // set in reply to
        if ($_message->flags && $_message->flags == Zend_Mail_Storage::FLAG_ANSWERED && $originalMessage !== NULL) {
            $mail->addHeader('In-Reply-To', $originalMessage->messageuid);
        }
        
        $nonPrivateRecipients = array();
        
        // add recipients
        if (isset($_message->to)) {
            foreach ($_message->to as $to) {
                $mail->addTo($to, $to);
                $nonPrivateRecipients[] = $to;
            }
        }
        if (isset($_message->cc)) {
            foreach ($_message->cc as $cc) {
                $mail->addCc($cc, $cc);
                $nonPrivateRecipients[] = $cc;
            }
        }
        if (isset($_message->bcc)) {
            foreach ($_message->bcc as $bcc) {
                $mail->addBcc($bcc, $bcc);
            }
        }
        
        // set subject
        $mail->setSubject($_message->subject);
        
        // add attachments
        $this->_addAttachments($mail, $_message, $originalMessage);
        
        // add user agent
        $mail->addHeader('User-Agent', 'Tine 2.0 Email Client (version ' . TINE20_CODENAME . ' - ' . TINE20_PACKAGESTRING . ')');
        
        // set organization
        if (isset($account->organization) && ! empty($account->organization)) {
            $mail->addHeader('Organization', $account->organization);
        }
        
        // add other headers
        foreach ($_message->headers as $key => $value) {
            $mail->addHeader($key, $value);
        }
        
        // set transport + send mail
        $smtpConfig = $account->getSmtpConfig();
        if (! empty($smtpConfig)) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to send message via SMTP ...');
            Tinebase_Smtp::getInstance()->sendMessage($mail, $transport);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' successful.');
            
            // add email notes to contacts (only to/cc)
            if ($_message->note) {
                $this->_addEmailNote($nonPrivateRecipients, $_message->subject, $mailBodyText);
            }
        
            // append mail to sent folder 
            $this->_saveInSent($transport, $account);
            
            // add reply/forward flags if set
            if (! empty($_message->flags) 
                && ($_message->flags == Zend_Mail_Storage::FLAG_ANSWERED || $_message->flags == Zend_Mail_Storage::FLAG_PASSED)
                && $originalMessage !== NULL
            ) {
                $this->addFlags($originalMessage, array($_message->flags));
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message, no smtp config found.');
        }
        
        return $_message;
    }
    
    /**
     * get message part
     *
     * @param string $_id
     * @param string $_partId (the part id, can look like this: 1.3.2 -> returns the second part of third part of first part...)
     * @return Zend_Mime_Part
     * 
     * @todo make it possible to get complete message (CONTENT_TYPE_MESSAGE_RFC822)
     */
    public function getMessagePart($_id, $_partId)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }
        
        $partStructure  = $this->_getPartStructure($message->structure, $_partId); 
        
        $imapBackend = $this->_getBackendAndSelectFolder($message->folder_id);
        
        $rawBody = $imapBackend->getRawContent($message->messageuid, $_partId, true);
        
        $stream = fopen("php://temp", 'r+');
        fputs($stream, $rawBody);
        rewind($stream);
        
        unset($rawBody);
        
        $part = new Zend_Mime_Part($stream);
        $part->type        = $partStructure['contentType'];
        $part->encoding    = array_key_exists('encoding', $partStructure) ? $partStructure['encoding'] : null;
        $part->id          = array_key_exists('id', $partStructure) ? $partStructure['id'] : null;
        $part->description = array_key_exists('description', $partStructure) ? $partStructure['description'] : null;
        $part->charset     = array_key_exists('charset', $partStructure['parameters']) ? $partStructure['parameters']['charset'] : 'iso-8859-15';
        $part->boundary    = array_key_exists('boundary', $partStructure['parameters']) ? $partStructure['parameters']['boundary'] : null;
        $part->location    = $partStructure['location'];
        $part->language    = $partStructure['language'];
        if (is_array($partStructure['disposition'])) {
            $part->disposition = $partStructure['disposition']['type'];
            if (array_key_exists('parameters', $partStructure['disposition'])) {
                $part->filename    = array_key_exists('filename', $partStructure['disposition']['parameters']) ? $partStructure['disposition']['parameters']['filename'] : null;
            }
        }
        
        return $part;
    }
    
    public function getMessageBody($_messageId, $_contentType, $_readOnly = false)
    {
        if ($_messageId instanceof Felamimail_Model_Message) {
            $message = $_messageId;
        } else {
            $message = $this->get($_messageId);
        }
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = 'getMessageBody' . $message->getId();
        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }
        
        $partId = null;
        
        if($_contentType == Zend_Mime::TYPE_HTML) {
            $partId = !empty($message['html_partid']) ? $message['html_partid'] : $message['text_partid'];
        } else {
            $partId = !empty($message['text_partid']) ? $message['text_partid'] : $message['html_partid'];
        }
        
        if(empty($partId)) {
            return '';
        }
        
        $partStructure = $this->_getPartStructure($message['structure'], $partId);
        
        $bodyPart = $this->getMessagePart($message, $partId);
        
        $this->_appendCharsetFilter($bodyPart, $partStructure);
        
        $body = $this->_convertContentType($partStructure['contentType'], $_contentType, $bodyPart->getDecodedContent());
        
        if($_contentType != Zend_Mime::TYPE_TEXT) {
            $body = $this->_purifyBodyContent($body);
        }
        
        $body = $this->_replaceUriAndSpaces($body);
        $body = $this->_replaceEmails($body);
        
        $cache->save($body, $cacheId, array('getMessageBody'));
        
        return $body;
    }
    
    public function getMessageHeaders($_messageId, $_readOnly = false)
    {
        if (! $_messageId instanceof Felamimail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = 'getMessageHeaders' . $message->getId();
        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }
        
        $imapBackend = $this->_getBackendAndSelectFolder($message->folder_id);
        
        if ($imapBackend === null) {
            throw new Felamimail_Exception('failed to get imap backend');
        }
        
        $rawHeaders = $imapBackend->getRawContent($message->messageuid, 'HEADER', $_readOnly);
        Zend_Mime_Decode::splitMessage($rawHeaders, $headers, $null);
        
        $cache->save($headers, $cacheId, array('getMessageHeaders'));
        
        return $headers;
    }
    
    /**
     * 
     * @param  array   $_messageStructure
     * @param  string  $_partId            the part id to search for
     * @return array
     */
    protected function _getPartStructure(array $_messageStructure, $_partId)
    {
        // maybe we want the first part
        if($_messageStructure['partId'] == $_partId) {
            return $_messageStructure;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($_messageStructure),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach($iterator as $key => $value) {
            if($key == $_partId) {
                return $value;
            }
        }
        
        throw new Felamimail_Exception("structure for partId $_partId not found");
    }
    
    /**
     * get attachments of message
     *
     * @param  array  $_structure
     * @return array
     * 
     */
    public function getAttachments(array $_structure)
    {
        $attachments = array();
        
        if ($_structure['contentType'] == Zend_Mime::MULTIPART_ALTERNATIVE) {
            // check for multipart related
            foreach ($_structure['parts'] as $part) {
                $attachments = array_merge($attachments, $this->getAttachments($part));
            }
        } elseif ($_structure['contentType'] == Zend_Mime::MULTIPART_MIXED || $_structure['contentType'] == Zend_Mime::MULTIPART_RELATED) {
            foreach ($_structure['parts'] as $part) {
                if (is_array($part['disposition']) &&
                    ($part['disposition']['type'] == Zend_Mime::DISPOSITION_ATTACHMENT || ($part['disposition']['type'] == Zend_Mime::DISPOSITION_INLINE && array_key_exists("parameters", $part['disposition'])))
                ) {
                    if (array_key_exists('parameters', $part['disposition']) && array_key_exists('filename', $part['disposition']['parameters'])) {
                        $filename = $part['disposition']['parameters']['filename'];
                    } elseif (is_array($part['parameters']) && array_key_exists('name', $part['parameters'])) {
                        $filename = $part['parameters']['name'];
                    } else {
                        $filename = 'Part ' . $part['partId'];
                    }
                    $attachments[] = array( 
                        'content-type' => $part['contentType'], 
                        'filename'     => $filename,
                        'partId'       => $part['partId'],
                        'size'         => $part['size'],
                        'description'  => $part['description']
                    );
                }
            }
        };
        
        return $attachments;
    }
    
    /************************* protected funcs *************************/
    
    /**
     * get imap backend and folder (and select folder)
     *
     * @param string                    $_folderId
     * @param Felamimail_Backend_Folder &$_folder
     * @param boolean                   $_select
     * @param Felamimail_Backend_ImapProxy   $_imapBackend
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @return Felamimail_Backend_ImapProxy
     * 
     * @todo refactor exception handling
     */
    protected function _getBackendAndSelectFolder($_folderId = NULL, &$_folder = NULL, $_select = TRUE, Felamimail_Backend_ImapProxy $_imapBackend = NULL)
    {
        if ($_folder === NULL || empty($_folder)) {
            $folderBackend  = new Felamimail_Backend_Folder();
            $_folder = $folderBackend->get($_folderId);
        }
        
        try {
            $imapBackend = ($_imapBackend === NULL) ? Felamimail_Backend_ImapFactory::factory($_folder->account_id) : $_imapBackend;
            if ($_select && $imapBackend->getCurrentFolder() != $_folder->globalname) {
                $backendFolderValues = $imapBackend->selectFolder($_folder->globalname);
            }
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection
            throw new Felamimail_Exception_IMAPServiceUnavailable();
        }
        
        return $imapBackend;
    }
    
    /**
     * extract values from folder filter
     *
     * @param Felamimail_Model_MessageFilter $_filter
     * @return array (assoc) with filter values
     */
    protected function _extractFilter(Felamimail_Model_MessageFilter $_filter)
    {
        $result = array('folder_id' => '', 'flags' => '');
        
        $filters = $_filter->toArray();
        foreach($filters as $filter) {
            if (in_array($filter['field'], array_keys($result)) || ! empty($filter['value'])) {
                $result[$filter['field']] = $filter['value'];
            }
        }
        
        return $result;
    }

    /**
     * add html markup to message body
     *
     * @param string $_body
     * @return string
     */
    protected function _addHtmlMarkup($_body)
    {
        $result = '<html>'
            . '<head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
            . '<title></title>'
            . '<style type="text/css">'
                . '.felamimail-body-blockquote {'
                    . 'margin: 5px 10px 0 3px;'
                    . 'padding-left: 10px;'
                    . 'border-left: 2px solid #000088;'
                . '} '
            . '</style>'
            . '</head>'
            . '<body>'
            . $_body
            . '</body></html>';
            
        return $result;
    }
    
    
    /**
     * use html purifier to remove 'bad' tags/attributes from html body
     *
     * @param string $_content
     * @return string
     */
    protected function _purifyBodyContent($_content)
    {
        $purifierFilename = 'HTMLPurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.auto.php'; 
        if (! file_exists(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $purifierFilename) ) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' HTML purifier not found. Mail body could not be purified. Proceed at your own risk!');
            return $_content;
        }
        
        $config = Tinebase_Core::getConfig();
        $path = ($config->caching && $config->caching->active && $config->caching->path) 
            ? $config->caching->path : Tinebase_Core::getTempDir();

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Purifying html body. (cache path: ' . $path .')');
        
        require_once $purifierFilename;
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', 'purify message body contents'); 
        $config->set('HTML.DefinitionRev', 1);
        $config->set('Cache.SerializerPath', $path);
        $config->set('HTML.ForbiddenElements', array('img'));
        
        // add target="_blank" to anchors
        $def = $config->getHTMLDefinition(true);
        $a = $def->addBlankElement('a');
        $a->attr_transform_post[] = new Felamimail_HTMLPurifier_AttrTransform_AValidator();
        
        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($_content);
        
        return $content;
    }
    
    /**
     * add attachments to mail
     *
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Message $_originalMessage
     * @throws Felamimail_Exception if max attachment size exceeded or no originalMessage available for forward
     * 
     * @todo use php://temp for BIG attachments / messages
     */
    protected function _addAttachments(Tinebase_Mail $_mail, Felamimail_Model_Message $_message, $_originalMessage = NULL)
    {
        $maxSize = (self::MAX_ATTACHMENT_SIZE == 0) ? convertToBytes(ini_get('upload_max_filesize')) : self::MAX_ATTACHMENT_SIZE;
        
        if (isset($_message->attachments)) {
            $size = 0;
            foreach ($_message->attachments as $attachment) {
                
                if ($attachment['type'] == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    
                    if ($_originalMessage === NULL) {
                        throw new Felamimail_Exception('No original message available for forward!');
                    } else {
                        // @todo use $imap->getRawContent(messageuid, NULL) or getMessagePart with content type CONTENT_TYPE_MESSAGE_RFC822
                        // @todo add test for this
                        $originalMessage = $this->getCompleteMessage($_originalMessage, false);
                    }
                    
                    // add complete original message as attachment
                    $headers = '';
                    foreach ($originalMessage->message->getHeaders() as $key => $value) {
                        $headers .= "$key: $value" . Zend_Mime::LINEEND;
                    }
                    $rawContent = $headers . Zend_Mime::LINEEND . $originalMessage->message->getContent();
                    $part = new Zend_Mime_Part($rawContent);
                    
                    //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $rawContent);
                    
                    $part->disposition = 'attachment; filename="' . $attachment['name'] . '"';
                    // @todo decode content first and remove this
                    $part->encoding = Zend_Mime::ENCODING_7BIT;
                    
                } else {
                    if (! array_key_exists('path', $attachment)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not find attachment.');
                        continue;
                    }
                    
                    // get contents from uploaded files
                    $part = new Zend_Mime_Part(file_get_contents($attachment['path']));
                    $part->filename = $attachment['name'];
                    $part->disposition = Zend_Mime::ENCODING_BASE64; // is needed for attachment filenames
                    $part->encoding = Zend_Mime::ENCODING_BASE64;
                }
                
                $part->type = $attachment['type'] . '; name="' . $attachment['name'] . '"';
                
                // check size
                $size += $attachment['size'];
                if ($size > $maxSize) {
                    throw new Felamimail_Exception('Allowed attachment size exceeded! Tried to attach ' . $size . ' bytes.');
                }
                
                $_mail->addAttachment($part);
            }
        }
    }

    /**
     * replace uris with links and more than one space with &nbsp;
     *
     * @param string $_content
     * @return string
     */
    protected function _replaceUriAndSpaces($_content) 
    {
        // uris
        $pattern = '@(https?://|ftp://)([^\s<>\)]+)@';
        $result = preg_replace($pattern, "<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>", $_content);
        
        // spaces
        #$result = preg_replace('/( {2,}|^ )/em', 'str_repeat("&nbsp;", strlen("\1"))', $result);
        
        return $result;
    }

    /**
     * replace emails with links
     *
     * @param string $_content
     * @return string
     * 
     * @todo try to skip email address that are already embedded in an url (such as unsubscription links with ?email=blabla@aha.com) 
     */
    protected function _replaceEmails($_content) 
    {
        // add anchor to email addresses (remove mailto hrefs first)
        $mailtoPattern = '/<a[="a-z\-0-9 ]*href="mailto:([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})"[^>]*>.*<\/a>/iU';
        $result = preg_replace($mailtoPattern, "\\1", $_content);
        
        //$emailPattern = '/(?<!mailto:)([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i';
        $result = preg_replace(Felamimail_Model_Message::EMAIL_ADDRESS_REGEXP, "<a href=\"#\" id=\"123:\\1\" class=\"tinebase-email-link\">\\1</a>", $result);
        
        return $result;
    }
        
    /**
     * remove all html entities
     *
     * @param string $_content
     * @return string
     */
    protected function _removeHtml($_content)
    {
        $result = strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', "\n", $_content));
        $result = html_entity_decode($result, ENT_COMPAT, 'UTF-8');
        
        return $result;
    }
    
    /**
     * convert charset
     *
     * @param  Zend_Mime_Part  $_part
     * @param  array           $_structure
     * @param  string          $_contentType
     */
    protected function _appendCharsetFilter(Zend_Mime_Part $_part, $_structure)
    {
        $charset = isset($_structure['parameters']['charset']) ? $_structure['parameters']['charset'] : 'iso-8859-15';
        
        if ($charset == 'utf8') {
            $charset = 'utf-8';
        }
        
        // the stream filter does not like charsets with a dot in its name
        // stream_filter_append(): unable to create or locate filter "convert.iconv.ansi_x3.4-1968/utf-8//IGNORE"
        if (strpos($charset, '.') !== false) {
            $charset = 'iso-8859-15';
        }
        
        // check if charset is supported by iconv
        if (iconv($charset, 'utf-8', '') === false) {
            $charset = 'iso-8859-15';
        }
        
        $_part->appendDecodeFilter("convert.iconv.$charset/utf-8//IGNORE");
    }
    
    /**
     * convert between contenttypes (text/plain => text/html for example)
     * @param unknown_type $_from
     * @param unknown_type $_to
     * @param unknown_type $_text
     */
    protected function _convertContentType($_from, $_to, $_text)
    {
        // nothing todo
        if($_from == $_to) {
            return $_text;
        }
        
        if($_from == Zend_Mime::TYPE_TEXT && $_to == Zend_Mime::TYPE_HTML) {
            $text = htmlspecialchars($_text, ENT_COMPAT, 'utf-8');
            $text = $this->_addHtmlMarkup($text);
        } else {
            $text = preg_replace('/\<br *\/*\>/', "\r\n", $_text);
            $text = strip_tags($text);
        }
        
        return $text;
    }
    
    /**
     * add email notes to contacts with email addresses in $_recipients
     *
     * @param array $_recipients
     * @param string $_subject
     * 
     * @todo add email home (when we have OR filters)
     * @todo add link to message in sent folder?
     */
    protected function _addEmailNote($_recipients, $_subject, $_body)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_recipients, TRUE));
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'email', 'operator' => 'in', 'value' => $_recipients)
            // OR: array('field' => 'email_home', 'operator' => 'in', 'value' => $_recipients)
        ));
        $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);
        
        if (count($contacts)) {
        
            $translate = Tinebase_Translation::getTranslation($this->_applicationName);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding email notes to ' . count($contacts) . ' contacts.');
            
            $noteText = $translate->_('Subject') . ':' . $_subject . "\n\n" . $translate->_('Body') . ':' . substr($_body, 0, 4096);
            
            foreach ($contacts as $contact) {
                $note = new Tinebase_Model_Note(array(
                    'note_type_id'           => Tinebase_Notes::getInstance()->getNoteTypeByName('email')->getId(),
                    'note'                   => $noteText,
                    'record_id'              => $contact->getId(),
                    'record_model'           => 'Addressbook_Model_Contact',
                ));
                
                Tinebase_Notes::getInstance()->addNote($note);
            }
        } else {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Found no contacts to add notes to.');
        }
    }
    
    /**
     * append mail to send folder
     * @param Felamimail_Transport $_transport
     * @param Felamimail_Model_Account $_account
     * @return void
     */
    protected function _saveInSent(Felamimail_Transport $_transport, Felamimail_Model_Account $_account)
    {
        try {
            $mailAsString = $_transport->getHeaders() . Zend_Mime::LINEEND . $_transport->getBody();
            
            if (($_account->sent_folder && ! empty($_account->sent_folder))) {
                $sentFolder = $_account->sent_folder;
                $this->_createFolderIfNotExists($_account, $sentFolder);
            } else {
                $sentFolder = 'Sent';
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to save message in sent folder ...');
            Felamimail_Backend_ImapFactory::factory($_account)->appendMessage($mailAsString, $sentFolder);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Saved sent message in "' . $sentFolder . '".'
            );
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                . ' Could not save sent message in "' . $sentFolder . '".'
                . ' Please check if a folder with this name exists.'
                . '(' . $zmpe->getMessage() . ')'
            );
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                . ' Could not save sent message in "' . $sentFolder . '".'
                . ' Please check if a folder with this name exists.'
                . '(' . $zmse->getMessage() . ')'
            );
        }
    }	
    
	/**
	 * insert folder in imap if not exist
	 *
	 * @param Felamimail_Model_Account $_account
	 * @return boolean
	 * 
	 * @todo add test for this
	 */
	protected function _createFolderIfNotExists(Felamimail_Model_Account $_account, $folderName){
		$imap = Felamimail_Backend_ImapFactory::factory($_account);
		if($imap->getFolderStatus($folderName) === false){
			Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Found no Sent Folder, trying to add it.');
			$Felamimail_Controller_Folder = Felamimail_Controller_Folder::getInstance()->create($_account->id, $folderName);
		}
	}
}
