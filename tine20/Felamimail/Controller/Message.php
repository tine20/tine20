<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        parse mail body and add <a> to telephone numbers?
 * @todo        check html purifier config (allow some tags/attributes?)
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
     * imap flags to constants translation
     * @var array
     */
    protected static $_allowedFlags = array('\Answered' => Zend_Mail_Storage::FLAG_ANSWERED,    // _("Answered")
                                            '\Seen'     => Zend_Mail_Storage::FLAG_SEEN,        // _("Seen")
                                            '\Deleted'  => Zend_Mail_Storage::FLAG_DELETED,     // _("Deleted")
                                            '\Draft'    => Zend_Mail_Storage::FLAG_DRAFT,       // _("Draft")
                                            '\Flagged'  => Zend_Mail_Storage::FLAG_FLAGGED);    // _("Flagged")
    
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
     * message backend
     *
     * @var Felamimail_Backend_Cache_Sql_Message
     */
    protected $_backend = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_modelName = 'Felamimail_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        //$this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        $this->_backend = new Felamimail_Backend_Cache_Sql_MessageSearchImproved();
        
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
    
    /**
     * Removes accounts where current user has no access to
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * 
     * @todo move logic to Felamimail_Model_MessageFilter
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $accountFilter = $_filter->getFilter('account_id');
        
        // force a $accountFilter filter (ACL) / all accounts of user
        if ($accountFilter === NULL || $accountFilter['operator'] !== 'equals' || ! empty($accountFilter['value'])) {
            $_filter->createFilter('account_id', 'equals', array());
        }
    }

    /**
     * get complete message by id
     *
     * @param string|Felamimail_Model_Message  $_id
     * @param boolean                          $_setSeen
     * @return Felamimail_Model_Message
     */
    public function getCompleteMessage($_id, $_partId = null, $_setSeen = FALSE)
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
        $mimeType = ($account->display_format == Felamimail_Model_Account::DISPLAY_HTML || $account->display_format == Felamimail_Model_Account::DISPLAY_CONTENT_TYPE) 
            ? Zend_Mime::TYPE_HTML 
            : Zend_Mime::TYPE_TEXT;
        
        $headers     = $this->getMessageHeaders($message, $_partId, true);
        $body        = $this->getMessageBody($message, $_partId, $mimeType, $account, true);
        $attachments = $this->getAttachments($message, $_partId);
        
        // set \Seen flag
        if ($_setSeen && !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Add \Seen flag to msg uid ' . $message->messageuid
            );
            $this->addFlags($message, Zend_Mail_Storage::FLAG_SEEN);
            $message->flags[] = Zend_Mail_Storage::FLAG_SEEN;
        }
        
        if ($_partId === null) {
            $message->body        = $body;
            $message->headers     = $headers;
            $message->attachments = $attachments;
        } else {
            // create new object for rfc822 message
            $structure = $message->getPartStructure($_partId, FALSE);
            
            $message = new Felamimail_Model_Message(array(
                'messageuid'  => $message->messageuid,
                'folder_id'   => $message->folder_id,
                'received'    => $message->received,
                'size'        => (array_key_exists('size', $structure)) ? $structure['size'] : 0,
                'partid'      => $_partId,
                'body'        => $body,
                'headers'     => $headers,
                'attachments' => $attachments
            ));

            $message->parseHeaders($headers);
            
            $structure = array_key_exists('messageStructure', $structure) ? $structure['messageStructure'] : $structure;
            $message->parseStructure($structure);
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));
        
        return $message;
    }
    
    /**
     * add flags to messages
     *
     * @param mixed                     $_message
     * @param array                     $_flags
     * @return Tinebase_Record_RecordSet with affected folders
     */
    public function addFlags($_messages, $_flags)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Add flags: ' . print_r($_flags, TRUE));
        
        // we always need to read the messages from cache to get the current flags
        $messagesToFlag = $this->_convertToRecordSet($_messages, TRUE);
        $messagesToFlag->sort('folder_id');
        
        $flags = (array) $_flags;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Retrieved ' . count($messagesToFlag) . ' messages from cache.');
                
        $lastFolderId = null;
        $imapBackend  = null;
        $folderCounterById    = array();
        
        // set flags on imap server
        foreach ($messagesToFlag as $message) {
            //if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), TRUE));
            
            // write flags on imap (if folder changes or count messages > 50)
            if ($imapBackend !== null && ($lastFolderId != $message->folder_id || count($imapMessageUids) >= 50)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Folder changed, writing flags.');
                $this->_addFlagsOnImap($imapMessageUids, $flags, $imapBackend);
                $imapMessageUids = array();
            }
            
            // init new folder
            if ($lastFolderId != $message->folder_id) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Getting new IMAP backend for folder ' . $message->folder_id);
                $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id);
                $lastFolderId             = $message->folder_id;
                $folderCounterById[$lastFolderId] = array(
                    'decrementMessagesCounter' => 0, 
                    'decrementUnreadCounter'   => 0
                );
            }
            
            $imapMessageUids[] = $message->messageuid;
        }
        
        // write remaining flags
        if ($imapBackend !== null && count($imapMessageUids) > 0) {
            $this->_addFlagsOnImap($imapMessageUids, $flags, $imapBackend);
        }    

        $folderCounts = $this->_addFlagsOnCache($messagesToFlag, $flags, $folderCounterById);
        $affectedFolders = $this->_updateFolderCounts($folderCounts);
        
        return $affectedFolders;
    }
    
    /**
     * add flags on imap server
     * 
     * @param array $_uids
     * @param array $_flags
     * @param Felamimail_Backend_ImapProxy $_imap
     * @throws Felamimail_Exception_IMAP
     */
    protected function _addFlagsOnImap($_uids, $_flags, Felamimail_Backend_ImapProxy $_imap)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set flags on imap server for ' . count($_uids) . ' messages.');
        try {
            $_imap->addFlags($_uids, array_intersect($_flags, array_keys(self::$_allowedFlags)));
        } catch (Zend_Mail_Storage_Exception $zmse) {
            throw new Felamimail_Exception_IMAP($zmse->getMessage());
        }
    }
    
    /**
     * returns supported flags
     * 
     * @return array
     * 
     * @todo add gettext for flags
     */
    public function getSupportedFlags()
    {
        $result = array();
        $translate = Tinebase_Translation::getTranslation('Felamimail');
        
        foreach (self::$_allowedFlags as $flag) {
            $result[] = array('id'        => $flag,      'name'      => $translate->_(substr($flag, 1)));
        }
        
        return $result;
    }
    
    /**
     * set flags in local database
     * 
     * @param Tinebase_Record_RecordSet $_messagesToFlag
     * @param array $_flags
     * @param array $_folderCounts
     * @return array folder counts
     */
    protected function _addFlagsOnCache(Tinebase_Record_RecordSet $_messagesToFlag, $_flags, $_folderCounts)
    {
        $folderCounts = $_folderCounts;
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            $idsToDelete = array();
            foreach ($_messagesToFlag as $message) {
                foreach ($_flags as $flag) {
                    if ($flag == Zend_Mail_Storage::FLAG_DELETED) {
                        if (is_array($message->flags) && !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                            $folderCounts[$message->folder_id]['decrementUnreadCounter']++;
                        }
                        $folderCounts[$message->folder_id]['decrementMessagesCounter']++;
                        $idsToDelete[] = $message->getId();
                    } elseif (!is_array($message->flags) || !in_array($flag, $message->flags)) {
                        $this->_backend->addFlag($message, $flag);
                        if ($flag == Zend_Mail_Storage::FLAG_SEEN) {
                            // count messages with seen flag for the first time
                            $folderCounts[$message->folder_id]['decrementUnreadCounter']++;
                        }
                    }
                }
            }
            
            $this->_backend->delete($idsToDelete);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $idsToMarkAsChanged = array_diff($_messagesToFlag->getArrayOfIds(), $idsToDelete);
            $this->_backend->updateMultiple($idsToMarkAsChanged, array(
                'timestamp' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            ));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Set flags on cache:'
                . ' Deleted records -> ' . count($idsToDelete)
                . ' Updated records -> ' . count($idsToMarkAsChanged)
            );
                
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            return $_folderCounts;
        }
        
        return $folderCounts;
    }
    
    /**
     * clear message flag(s)
     *
     * @param mixed                     $_messages
     * @param array                     $_flags
     * @param Felamimail_Model_Folder   $_folder [optional]
     * @return Tinebase_Record_RecordSet with affected folders
     */
    public function clearFlags($_messages, $_flags, $_folder = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' clear flags: ' . print_r($_flags, TRUE));
        
        // we always need to read the messages from cache to get the current flags
        $messagesToUnflag = $this->_convertToRecordSet($_messages, TRUE);
        
        $messagesToUnflag->sort('folder_id');
        
        $flags = (array) $_flags;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' retrieved ' . count($messagesToUnflag) . ' messages from cache');
                
        $lastFolderId = null;
        $imapBackend  = null;
        $folderCounterById    = array();
        
        // set flags on imap server
        foreach ($messagesToUnflag as $message) {
            if($imapBackend !== null && ($lastFolderId != $message->folder_id || count($imapMessageUids) >= 50)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' clear flags on imap server');
                $imapBackend->clearFlags($imapMessageUids, array_intersect($flags, array_keys(self::$_allowedFlags)));
                $imapMessageUids = array();
            }
            
            if ($lastFolderId != $message->folder_id) {
                $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id);
                $lastFolderId             = $message->folder_id;
                $folderCounterById[$lastFolderId] = array(
                    'incrementUnreadCounter' => 0
                );
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
        foreach($messagesToUnflag as $message) {
            if (in_array(Zend_Mail_Storage::FLAG_SEEN, $flags) && in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                // count messages with seen flag for the first time
                $folderCounterById[$message->folder_id]['incrementUnreadCounter']++;
            }
            
            $this->_backend->clearFlag($message, $flags);
        }
        
        // mark message as changed in the cache backend
        $this->_backend->updateMultiple(
            $messagesToUnflag->getArrayOfIds(), 
            array(
                'timestamp' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            )
        );
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cleared flags on cache');
        
        $affectedFolders = $this->_updateFolderCounts($folderCounterById);
        return $affectedFolders;
    }
    
    /**
     * move messages to folder
     *
     * @param  mixed  $_messages
     * @param  mixed  $_targetFolder can be one of: folder_id, Felamimail_Model_Folder or Felamimail_Model_Folder::FOLDER_TRASH (constant)
     * @return Tinebase_Record_RecordSet
     */
    public function moveMessages($_messages, $_targetFolder)
    {
        // we always need to read the messages from cache to get the current flags
        $messages = $this->_convertToRecordSet($_messages, TRUE);
        $messages->addIndices(array('folder_id'));
        
        if ($_targetFolder !== Felamimail_Model_Folder::FOLDER_TRASH) {
            $targetFolder = ($_targetFolder instanceof Felamimail_Model_Folder) ? $_targetFolder : Felamimail_Controller_Folder::getInstance()->get($_targetFolder);
        }
        
        foreach (array_unique($messages->folder_id) as $folderId) {
            $messagesInFolder = $messages->filter('folder_id', $folderId);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' moving messages: ' . print_r($messagesInFolder->getArrayOfIds(), TRUE));
            
            if ($_targetFolder === Felamimail_Model_Folder::FOLDER_TRASH) {
                // messages should be moved to trash -> need to determine the trash folder for the account of the folder that contains the messages
                try {
                    $targetFolder = Felamimail_Controller_Account::getInstance()->getTrashFolder($messagesInFolder->getFirstRecord()->account_id);
                    $this->_moveMessagesInFolderOnSameAccount($messagesInFolder, $targetFolder);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No trash folder found - skipping messages in this folder.');
                    $messages->removeRecords($messagesInFolder);
                }
            } else if ($messagesInFolder->getFirstRecord()->account_id == $targetFolder->account_id) {
                $this->_moveMessagesInFolderOnSameAccount($messagesInFolder, $targetFolder);
            } else {
                $this->_moveMessagesToAnotherAccount($messagesInFolder, $targetFolder);
            }
        }

        // delete messages in local cache
        $number = $this->_backend->delete($messages->getArrayOfIds());
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleted ' . $number .' messages from cache');
        
        return $this->_updateCountsAfterMove($messages);
    }
    
    /**
     * move messages from one folder to another folder within the same email account
     * 
     * @param Tinebase_Record_RecordSet $_messages
     * @param Felamimail_Model_Folder $_targetFolder
     */
    protected function _moveMessagesInFolderOnSameAccount(Tinebase_Record_RecordSet $_messages, Felamimail_Model_Folder $_targetFolder)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Move ' . count($_messages) . ' message(s) to ' . $_targetFolder->globalname
        );
        
        $firstMessage = $_messages->getFirstRecord();
        $folder = Felamimail_Controller_Folder::getInstance()->get($firstMessage->folder_id);
        $imapBackend = Felamimail_Backend_ImapFactory::factory($firstMessage->account_id);
        $imapBackend->selectFolder(Felamimail_Model_Folder::encodeFolderName($folder->globalname));
        
        $imapMessageUids = array();
        foreach ($_messages as $message) {
            $imapMessageUids[] = $message->messageuid;
            
            if (count($imapMessageUids) >= 50) {
                $this->_moveBatchOfMessages($imapMessageUids, $_targetFolder->globalname, $imapBackend);
                $imapMessageUids = array();
            }
        }
        
        // the rest
        if (count($imapMessageUids) > 0) {
            $this->_moveBatchOfMessages($imapMessageUids, $_targetFolder->globalname, $imapBackend);
        }
    }

    /**
     * move messages to another email account
     * 
     * @param Tinebase_Record_RecordSet $_messages
     * @param Felamimail_Model_Folder $_targetFolder
     */
    protected function _moveMessagesToAnotherAccount(Tinebase_Record_RecordSet $_messages, Felamimail_Model_Folder $_targetFolder)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Move ' . count($_messages) . ' message(s) to ' . $_targetFolder->globalname . ' in account ' . $_targetFolder->account_id
        );
        
        foreach ($_messages as $message) {
            $part = Felamimail_Controller_Message::getInstance()->getMessagePart($message);
            $this->appendMessage($_targetFolder, $part->getRawStream(), $message->flags);
        }
    }
    
    /**
     * update folder count after moving messages
     * 
     * @param Tinebase_Record_RecordSet $_messages
     * @param array $_folderCounterById
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     */
    protected function _updateCountsAfterMove(Tinebase_Record_RecordSet $_messages)
    {
        $folderCounterById = array();
        foreach($_messages as $message) {
            if (! array_key_exists($message->folder_id, $folderCounterById)) {
                $folderCounterById[$message->folder_id] = array(
                    'decrementUnreadCounter'    => 0,
                    'decrementMessagesCounter'  => 0,
                );
            }
            
            if (!is_array($message->flags) || !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                // count messages with seen flag for the first time
                $folderCounterById[$message->folder_id]['decrementUnreadCounter']++;
            }
            $folderCounterById[$message->folder_id]['decrementMessagesCounter']++;
        }
        $affectedFolders = $this->_updateFolderCounts($folderCounterById);

        return $affectedFolders;
    }
    
    /**
     * move messages on imap server
     * 
     * @param array $_uids
     * @param string $_targetFolderName
     * @param Felamimail_Backend_ImapProxy $_imap
     * 
     * @todo perhaps we should check the existance of the messages on the imap instead of catching the exception here
     */
    protected function _moveBatchOfMessages($_uids, $_targetFolderName, Felamimail_Backend_ImapProxy $_imap)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Move ' . count($_uids) . ' messages to folder ' . $_targetFolderName . ' on imap server');
        try {
            $_imap->copyMessage($_uids, Felamimail_Model_Folder::encodeFolderName($_targetFolderName));
            $_imap->addFlags($_uids, array(Zend_Mail_Storage::FLAG_DELETED));
        } catch (Felamimail_Exception_IMAP $fei) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $fei->getMessage()); 
        }
    }
    
    /**
     * update folder counts and returns list of affected folders
     * 
     * @param array $_folderCounter (folderId => unreadcounter)
     * @return Tinebase_Record_RecordSet of affected folders
     * @throws Felamimail_Exception
     */
    protected function _updateFolderCounts($_folderCounter)
    {
        foreach ($_folderCounter as $folderId => $counter) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);
            
            // get error condition and update array by checking $counter keys
            if (array_key_exists('incrementUnreadCounter', $counter)) {
                // this is only used in clearFlags() atm
                $errorCondition = ($folder->cache_unreadcount + $counter['incrementUnreadCounter'] > $folder->cache_totalcount);
                $updatedCounters = array(
                    'cache_unreadcount' => '+' . $counter['incrementUnreadCounter'],
                );
            } else if (array_key_exists('decrementMessagesCounter', $counter) && array_key_exists('decrementUnreadCounter', $counter)) {
                $errorCondition = ($folder->cache_unreadcount < $counter['decrementUnreadCounter'] || $folder->cache_totalcount < $counter['decrementMessagesCounter']);
                $updatedCounters = array(
                    'cache_totalcount'  => '-' . $counter['decrementMessagesCounter'],
                    'cache_unreadcount' => '-' . $counter['decrementUnreadCounter']
                );
            } else {
                throw new Felamimail_Exception('Wrong folder counter given: ' . print_r($_folderCounter, TRUE));
            }
            
            if ($errorCondition) {
                // something went wrong => recalculate counter
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' folder counters dont match => refresh counters'
                );
                $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
            }
            
            Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
        }
        
        return Felamimail_Controller_Folder::getInstance()->getMultiple(array_keys($_folderCounter));
    }
    
    /**
     * save message in folder (target folder can be within a different account)
     * 
     * @param string|Felamimail_Model_Folder $_folder globalname or folder record
     * @param Felamimail_Model_Message $_message
     * @return Felamimail_Model_Message
     */
    public function saveMessageInFolder($_folder, $_message)
    {
        $sourceAccount = Felamimail_Controller_Account::getInstance()->get($_message->account_id);
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($_message->account_id, $_folder);
        $targetAccount = ($_message->account_id == $folder->account_id) ? $sourceAccount : Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        
        $mailToAppend = $this->_createMailForSending($_message, $sourceAccount);
        
        $transport = new Felamimail_Transport();
        $mailAsString = $transport->getRawMessage($mailToAppend);
        $flags = ($folder->globalname === $targetAccount->drafts_folder) ? array(Zend_Mail_Storage::FLAG_DRAFT) : null;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Appending message ' . $_message->subject . ' to folder ' . $folder->globalname . ' in account ' . $targetAccount->name);
        Felamimail_Backend_ImapFactory::factory($targetAccount)->appendMessage($mailAsString, $folder->globalname, $flags);
        
        return $_message;
    }
    
    /**
     * append a new message to given folder
     *
     * @param  string|Felamimail_Model_Folder  $_folder   id of target folder
     * @param  string|resource  $_message  full message content
     * @param  array   $_flags    flags for new message
     */
    public function appendMessage($_folder, $_message, $_flags = null)
    {
        $folder  = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        $message = (is_resource($_message)) ? stream_get_contents($_message) : $_message;
        $flags   = ($_flags !== null) ? (array) $_flags : null;
        
        Felamimail_Backend_ImapFactory::factory($folder->account_id)->appendMessage($message, $folder->globalname, $flags);
    }
    
    /**
     * send one message through smtp
     * 
     * @param Felamimail_Model_Message $_message
     * @return Felamimail_Model_Message
     */
    public function sendMessage(Felamimail_Model_Message $_message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_message->subject . ' to ' . print_r($_message->to, TRUE));

        $oldMaxExcecutionTime = ini_get('max_execution_time');
        
        // increase execution time (sending message with attachments can take a long time)
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // get account
        $account = Felamimail_Controller_Account::getInstance()->get($_message->account_id);
        
        // get original message
        try {
            $originalMessage = ($_message->original_id) ? $this->get($_message->original_id) : NULL;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Did not find original message.');
            $originalMessage = NULL;
        }

        $mail = $this->_createMailForSending($_message, $account, $nonPrivateRecipients, $originalMessage);
        
        // set transport + send mail
        $smtpConfig = $account->getSmtpConfig();
        if (! empty($smtpConfig) && array_key_exists('hostname', $smtpConfig)) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to send message via SMTP ...');
            Tinebase_Smtp::getInstance()->sendMessage($mail, $transport);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' successful.');
            
            // add email notes to contacts (only to/cc)
            if ($_message->note) {
                $this->_addEmailNote($nonPrivateRecipients, $_message->subject, $_message->getPlainTextBody());
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
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $_message;
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
     * 
     * @param Felamimail_Transport $_transport
     * @param Felamimail_Model_Account $_account
     * @return void
     */
    protected function _saveInSent(Felamimail_Transport $_transport, Felamimail_Model_Account $_account)
    {
        try {
            $mailAsString = $_transport->getRawMessage();
            $sentFolder = ($_account->sent_folder && ! empty($_account->sent_folder)) ? $_account->sent_folder : 'Sent';
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to save message in sent folder (' . $sentFolder . ') ...');
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
     * send Zend_Mail message via smtp
     * 
     * @param  mixed      $_accountId
     * @param  Zend_Mail  $_message
     * @param  bool       $_saveInSent
     * @return Zend_Mail
     * 
     * @todo remove code duplication by generalizing the send functions 
     */
    public function sendZendMail($_accountId, Zend_Mail $_mail, $_saveInSent = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_mail->getSubject() 
        );

        $oldMaxExcecutionTime = ini_get('max_execution_time');
        
        // increase execution time (sending message with attachments can take a long time)
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // get account
        $account = ($_accountId instanceof Felamimail_Model_Account) ? $_accountId : Felamimail_Controller_Account::getInstance()->get($_accountId);
        
        // set from
        $_mail->clearFrom();
        $from = (isset($account->from) && ! empty($account->from)) 
            ? $account->from 
            : Tinebase_Core::getUser()->accountFullName;
        $_mail->setFrom($account->email, $from);
        
        // add user agent
        $_mail->addHeader('User-Agent', 'Tine 2.0 Email Client (version ' . TINE20_CODENAME . ' - ' . TINE20_PACKAGESTRING . ')');
        
        // set organization
        if (isset($account->organization) && ! empty($account->organization)) {
            $_mail->addHeader('Organization', $account->organization);
        }
        
        // set transport + send mail
        $smtpConfig = $account->getSmtpConfig();
        if (! empty($smtpConfig)) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to send message via SMTP ...');
            Tinebase_Smtp::getInstance()->sendMessage($_mail, $transport);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' successful.');
            
            // append mail to sent folder
            if ($_saveInSent == true) {
                $this->_saveInSent($transport, $account);
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message, no smtp config found.');
        }
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $_mail;
    }
    
    /**
     * create new mail for sending via SMTP
     * 
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Account $_account
     * @param array $_nonPrivateRecipients
     * @param Felamimail_Model_Message $_originalMessage
     * @return Tinebase_Mail
     * 
     * @todo what has to be set in the 'In-Reply-To' header?
     * @todo add name for to/cc/bcc
     */
    protected function _createMailForSending(Felamimail_Model_Message $_message, Felamimail_Model_Account $_account, &$_nonPrivateRecipients = array(), Felamimail_Model_Message $_originalMessage = NULL)
    {
        // create new mail to send
        $mail = new Tinebase_Mail('UTF-8');
        
        // build mail content
        if ($_message->content_type == Felamimail_Model_Message::CONTENT_TYPE_HTML) {
            $plainBodyText = $_message->getPlainTextBody();
            $mail->setBodyText($plainBodyText);
            $mail->setBodyHtml(Felamimail_Message::addHtmlMarkup($_message->body));
        } else {
            $mail->setBodyText($_message->body);
        }
        
        // set from
        $from = (isset($_account->from) && ! empty($_account->from)) 
            ? $_account->from 
            : substr($_account->email, 0, strpos($_account->email, '@'));
        
        $mail->setFrom($_account->email, $from);

        // set in reply to
        if ($_message->flags && $_message->flags == Zend_Mail_Storage::FLAG_ANSWERED && $_originalMessage !== NULL) {
            $mail->addHeader('In-Reply-To', $_originalMessage->messageuid);
        }
        
        // add recipients
        if (isset($_message->to)) {
            foreach ($_message->to as $to) {
                $mail->addTo($to);
                $_nonPrivateRecipients[] = $to;
            }
        }
        if (isset($_message->cc)) {
            foreach ($_message->cc as $cc) {
                $mail->addCc($cc);
                $_nonPrivateRecipients[] = $cc;
            }
        }
        if (isset($_message->bcc)) {
            foreach ($_message->bcc as $bcc) {
                $mail->addBcc($bcc);
            }
        }
        
        // set subject
        $mail->setSubject($_message->subject);
        
        // add attachments
        $this->_addAttachments($mail, $_message, $_originalMessage);
        
        // add user agent
        $mail->addHeader('User-Agent', 'Tine 2.0 Email Client (version ' . TINE20_CODENAME . ' - ' . TINE20_PACKAGESTRING . ')');
        
        // set organization
        if (isset($_account->organization) && ! empty($_account->organization)) {
            $mail->addHeader('Organization', $_account->organization);
        }
        
        // add other headers
        if (! empty($_message->headers) && is_array($_message->headers)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding custom headers: ' . print_r($_message->headers, TRUE));
            foreach ($_message->headers as $key => $value) {
                $mail->addHeader($key, $value);
            }
        }
        
        return $mail;
    }
    
    /**
     * add attachments to mail
     *
     * @param Tinebase_Mail $_mail
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Message $_originalMessage
     * @throws Felamimail_Exception if max attachment size exceeded or no originalMessage available for forward
     * 
     * @todo use getMessagePart() for attachments too?
     */
    protected function _addAttachments(Tinebase_Mail $_mail, Felamimail_Model_Message $_message, $_originalMessage = NULL)
    {
        if (isset($_message->attachments)) {
            $size = 0;
            foreach ($_message->attachments as $attachment) {
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding attachment: ' . print_r($attachment, TRUE));
                
                if ($attachment['type'] == Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    
                    if ($_originalMessage === NULL) {
                        throw new Felamimail_Exception('No original message available for forward!');
                    }
                    
                    $part = $this->getMessagePart($_originalMessage);
                    $part->decodeContent();
                    
                    if (! array_key_exists('size', $attachment) || empty($attachment['size']) ) {
                        $attachment['size'] = $_originalMessage->size;
                    }
                    $attachment['name'] .= '.eml';
                    
                } else {
                    if (! array_key_exists('path', $attachment)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not find attachment.');
                        continue;
                    }
                    
                    // get contents from uploaded files
                    $part = new Zend_Mime_Part(file_get_contents($attachment['path']));
                    $part->encoding = Zend_Mime::ENCODING_BASE64;
                }
                
                $part->disposition = Zend_Mime::ENCODING_BASE64; // is needed for attachment filenames
                $part->filename = $attachment['name'];
                $part->type = $attachment['type'] . '; name="' . $attachment['name'] . '"';
                
                $_mail->addAttachment($part);
            }
        }
    }
    
    /**
     * get message part
     *
     * @param string|Felamimail_Model_Message $_id
     * @param string $_partId (the part id, can look like this: 1.3.2 -> returns the second part of third part of first part...)
     * @return Zend_Mime_Part
     */
    public function getMessagePart($_id, $_partId = null)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }
        
        $partStructure  = $message->getPartStructure($_partId, FALSE);
        
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
        if (empty($part->filename) && array_key_exists('parameters', $partStructure) && array_key_exists('name', $partStructure['parameters'])) {
            $part->filename = $partStructure['parameters']['name'];
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' part structure: ' . print_r($partStructure, TRUE));
        
        return $part;
    }
    
    /**
     * get message body
     * 
     * @param string|Felamimail_Model_Message $_messageId
     * @param string $_partId
     * @param string $_contentType
     * @param Felamimail_Model_Account $_account
     * @param boolean $_readOnly
     * @return string
     */
    public function getMessageBody($_messageId, $_partId, $_contentType, $_account = NULL, $_readOnly = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get Message body of content type ' . $_contentType);
        
        if ($_messageId instanceof Felamimail_Model_Message) {
            $message = $_messageId;
        } else {
            $message = $this->get($_messageId);
        }
        
        $cache = Tinebase_Core::getCache();
        $cacheId = 'getMessageBody_' . $message->getId() . str_replace('.', '', $_partId) . substr($_contentType, -4) . (($_account !== NULL) ? 'acc' : '');
        
        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }
        
        $structure = $message->getPartStructure($_partId);
        $bodyParts = $message->getBodyParts($structure, $_contentType);
        
        if (empty($bodyParts)) {
            return '';
        }
        
        $messageBody = '';
        
        foreach ($bodyParts as $partId => $partStructure) {
            $bodyPart = $this->getMessagePart($message, $partId);
            
            $this->_appendCharsetFilter($bodyPart, $partStructure);
            
            try {
                $body = $bodyPart->getDecodedContent();
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                return '';
            }
            
            if ($partStructure['contentType'] != Zend_Mime::TYPE_TEXT) {
                $body = $this->_purifyBodyContent($body);
            }
            
            if (! ($_account !== NULL && $_account->display_format === Felamimail_Model_Account::DISPLAY_CONTENT_TYPE && $bodyPart->type == Zend_Mime::TYPE_TEXT)) {
                $body = Felamimail_Message::convertContentType($partStructure['contentType'], $_contentType, $body);
                if ($bodyPart->type == Zend_Mime::TYPE_TEXT && $_contentType == Zend_Mime::TYPE_HTML) {
                    $body = Felamimail_Message::replaceUriAndSpaces($body);
                    $body = Felamimail_Message::replaceEmails($body);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Do not convert ' . $bodyPart->type . ' part to ' . $_contentType);
            }
            
            $messageBody .= $body;
        }
        
        $cache->save($messageBody, $cacheId, array('getMessageBody'));
        
        return $messageBody;
    }
    
    /**
     * convert charset
     *
     * @param  Zend_Mime_Part  $_part
     * @param  array           $_structure
     * @param  string          $_contentType
     * 
     * @todo   try to add a fallback if iconv decoding fails (@see http://www.tine20.org/bugtracker/view.php?id=2892)
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
        
        $filter = "convert.iconv.$charset/utf-8//IGNORE";
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Appending decode filter: ' . $filter);
        $_part->appendDecodeFilter($filter);
    }
    
    /**
     * use html purifier to remove 'bad' tags/attributes from html body
     *
     * @param string $_content
     * @return string
     */
    protected function _purifyBodyContent($_content)
    {
        if (!defined('HTMLPURIFIER_PREFIX')) {
            define('HTMLPURIFIER_PREFIX', realpath(dirname(__FILE__) . '/../../library/HTMLPurifier'));
        }
        
        $config = Tinebase_Core::getConfig();
        $path = ($config->caching && $config->caching->active && $config->caching->path) 
            ? $config->caching->path : Tinebase_Core::getTempDir();

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Purifying html body. (cache path: ' . $path .')');
        
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', 'purify message body contents'); 
        $config->set('HTML.DefinitionRev', 1);
        $config->set('Cache.SerializerPath', $path);
        
        // remove images
        $config->set('HTML.ForbiddenElements', array('img'));
        $config->set('CSS.ForbiddenProperties', array('background-image'));
        
        // add target="_blank" to anchors
        $def = $config->getHTMLDefinition(true);
        $a = $def->addBlankElement('a');
        $a->attr_transform_post[] = new Felamimail_HTMLPurifier_AttrTransform_AValidator();
        
        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($_content);
        
        return $content;
    }
    
    /**
     * get message headers
     * 
     * @param string|Felamimail_Model_Message $_messageId
     * @param boolean $_readOnly
     * @return array
     */
    public function getMessageHeaders($_messageId, $_partId = null, $_readOnly = false)
    {
        if (! $_messageId instanceof Felamimail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = 'getMessageHeaders' . $message->getId() . str_replace('.', '', $_partId);
        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }
        
        $imapBackend = $this->_getBackendAndSelectFolder($message->folder_id);
        
        if ($imapBackend === null) {
            throw new Felamimail_Exception('failed to get imap backend');
        }
        
        $section = ($_partId === null) ?  'HEADER' : $_partId . '.HEADER';
        
        try {
            $rawHeaders = $imapBackend->getRawContent($message->messageuid, $section, $_readOnly);
        } catch (Felamimail_Exception_IMAPMessageNotFound $feimnf) {
            $this->_backend->delete($message->getId());
            throw $feimnf;
        }
        Zend_Mime_Decode::splitMessage($rawHeaders, $headers, $null);
        
        $cache->save($headers, $cacheId, array('getMessageHeaders'));
        
        return $headers;
    }
    
    /**
     * get imap backend and folder (and select folder)
     *
     * @param string                    $_folderId
     * @param Felamimail_Backend_Folder &$_folder
     * @param boolean                   $_select
     * @param Felamimail_Backend_ImapProxy   $_imapBackend
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @return Felamimail_Backend_ImapProxy
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
                $backendFolderValues = $imapBackend->selectFolder(Felamimail_Model_Folder::encodeFolderName($_folder->globalname));
            }
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection
            throw new Felamimail_Exception_IMAPServiceUnavailable();
        }
        
        return $imapBackend;
    }
    
    /**
     * get attachments of message
     *
     * @param  array  $_structure
     * @return array
     */
    public function getAttachments($_messageId, $_partId = null)
    {
        if (! $_messageId instanceof Felamimail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }
        
        $structure = $message->getPartStructure($_partId);

        $attachments = array();
        
        if (!array_key_exists('parts', $structure)) {
            return $attachments;
        }
        
        foreach ($structure['parts'] as $part) {
            if ($part['type'] == 'multipart') {
                $attachments = $attachments + $this->getAttachments($message, $part['partId']);
            } else {
                if ($part['type'] == 'text' && 
                    (!is_array($part['disposition']) || ($part['disposition']['type'] == Zend_Mime::DISPOSITION_INLINE && !array_key_exists("parameters", $part['disposition'])))
                ) {
                    continue;
                }
                
                if (is_array($part['disposition']) && array_key_exists('parameters', $part['disposition']) && array_key_exists('filename', $part['disposition']['parameters'])) {
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
        
        return $attachments;
    }
    
    /**
     * delete messages from cache by folder
     * 
     * @param $_folder
     */
    public function deleteByFolder(Felamimail_Model_Folder $_folder)
    {
        $this->_backend->deleteByFolderId($_folder);
    }
}
