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
        $mimeType = $account->display_format == 'html' ? Zend_Mime::TYPE_HTML : Zend_Mime::TYPE_TEXT;
        
        $headers     = $this->getMessageHeaders($message, $_partId, true);
        $body        = $this->getMessageBody($message, $_partId, $mimeType, true);
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
                'size'        => $structure['size'],
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
        $folderIds    = array();
        
        // set flags on imap server
        foreach ($messagesToFlag as $message) {
            if($imapBackend !== null && ($lastFolderId != $message->folder_id || count($imapMessageUids) >= 50)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set flags on imap server.');
                $imapBackend->addFlags($imapMessageUids, array_intersect($flags, array_keys(self::$_allowedFlags)));
                $imapMessageUids = array();
            }
            
            if ($lastFolderId != $message->folder_id) {
                $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id);
                $lastFolderId             = $message->folder_id;
                $folderIds[$lastFolderId] = array(
                    'decrementMessagesCounter' => 0, 
                    'decrementUnreadCounter'   => 0
                );
            }
            
            $imapMessageUids[] = $message->messageuid;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set flags on imap server.');
        
        if($imapBackend !== null && count($imapMessageUids) > 0) {
            $imapBackend->addFlags($imapMessageUids, array_intersect($flags, array_keys(self::$_allowedFlags)));
        }    

        // set flags in local database
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        foreach($messagesToFlag as $message) {
            foreach ($flags as $flag) {
                if ($flag == Zend_Mail_Storage::FLAG_DELETED) {
                    if (is_array($message->flags) && !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                        $folderIds[$message->folder_id]['decrementUnreadCounter']++;
                    }
                    $folderIds[$message->folder_id]['decrementMessagesCounter']++;
                    
                    $this->_backend->delete($message->getId());
                    $messagesToFlag->removeRecord($message);
                } elseif (!is_array($message->flags) || !in_array($flag, $message->flags)) {
                    $this->_backend->addFlag($message, $flag);
                    if ($flag == Zend_Mail_Storage::FLAG_SEEN) {
                        // count messages with seen flag for the first time
                        $folderIds[$message->folder_id]['decrementUnreadCounter']++;
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

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' set flags on cache');
        
        $affectedFolders = $this->_updateFolderCounts($folderIds);
        
        return $affectedFolders;
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
        $folderIds    = array();
        
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
                $folderIds[$lastFolderId] = array(
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
                $folderIds[$message->folder_id]['incrementUnreadCounter']++;
            }
            
            $this->_backend->clearFlag($message, $flags);
        }
        
        // mark message as changed in the cache backend
        $this->_backend->updateMultiple(
            $messagesToUnflag->getArrayOfIds(), 
            array(
                'timestamp' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            )
        );
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cleared flags on cache');
        
        $affectedFolders = $this->_updateFolderCounts($folderIds);
        return $affectedFolders;
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
        $messages = $this->_convertToRecordSet($_messages, TRUE);
                
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' deleted messages on cache');
        
        // @todo return list of affected folders
        $affectedFolders = $this->_updateFolderCounts($folderIds);
        
        return Felamimail_Controller_Folder::getInstance()->get($targetFolder);
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
     * append a new message to given folder
     *
     * @param  string|Felamimail_Model_Folder  $_folder   id of target folder
     * @param  string  $_message  full message content
     * @param  array   $_flags    flags for new message
     * @param  string  $_date     date for new message
     */
    public function appendMessage($_folder, $_message, $_flags = null, $_date = null)
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

        // increase execution time (sending message with attachments can take a long time)
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // get account
        $account = Felamimail_Controller_Account::getInstance()->get($_message->from);
        
        // get original message
        $originalMessage = ($_message->original_id) ? $this->get($_message->original_id) : NULL;

        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($_message->toArray(), TRUE));
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($account->toArray(), TRUE));
        
        $mail = $this->_createMailForSending($_message, $account, $nonPrivateRecipients, $originalMessage);
        
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
                $this->_addEmailNote($nonPrivateRecipients, $_message->subject, $mail->getBodyText(TRUE));
            }
        
            // append mail to sent folder nonPrivateRecipients
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
        if ($imap->getFolderStatus($folderName) === false){
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Found no Sent Folder, trying to add it.');
            $Felamimail_Controller_Folder = Felamimail_Controller_Folder::getInstance()->create($_account->id, $folderName);
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
    public function sendZendMail($_accountId, Zend_Mail $_message, $_saveInSent = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Sending message with subject ' . $_message->getSubject() 
        );

        // increase execution time (sending message with attachments can take a long time)
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        // get account
        $account = ($_accountId instanceof Felamimail_Model_Account) ? $_accountId : Felamimail_Controller_Account::getInstance()->get($_accountId);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($_message->toArray(), TRUE));
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($account->toArray(), TRUE));
        
        $mail = $_message;
        
        // set transport + send mail
        $smtpConfig = $account->getSmtpConfig();
        if (! empty($smtpConfig)) {
            $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
            
            // send message via smtp
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' About to send message via SMTP ...');
            Tinebase_Smtp::getInstance()->sendMessage($mail, $transport);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' successful.');
            
            // append mail to sent folder
            if ($_saveInSent == true) {
                $this->_saveInSent($transport, $account);
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send message, no smtp config found.');
        }
        
        return $_message;
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
            $mailBodyText = Felamimail_Message::removeHtml($_message->body);
            $mail->setBodyText($mailBodyText);
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
        $maxSize = (self::MAX_ATTACHMENT_SIZE == 0) ? convertToBytes(ini_get('upload_max_filesize')) : self::MAX_ATTACHMENT_SIZE;
        
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
     * get message part
     *
     * @param string $_id
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
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' part structure: ' . print_r($partStructure, TRUE));
        
        return $part;
    }
    
    /**
     * get message body
     * 
     * @param string|Felamimail_Model_Message $_messageId
     * @param string $_contentType
     * @param boolean $_readOnly
     * @return string
     */
    public function getMessageBody($_messageId, $_partId, $_contentType, $_readOnly = false)
    {
        if ($_messageId instanceof Felamimail_Model_Message) {
            $message = $_messageId;
        } else {
            $message = $this->get($_messageId);
        }
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = 'getMessageBody_' . $message->getId() . str_replace('.', '', $_partId) . substr($_contentType, -4);
        
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
            
            $body = $bodyPart->getDecodedContent();
            
            if($partStructure['contentType'] != Zend_Mime::TYPE_TEXT) {
                $body = $this->_purifyBodyContent($body);
            }
            
            $body = Felamimail_Message::convertContentType($partStructure['contentType'], $_contentType, $body);
            
            if ($bodyPart->type == Zend_Mime::TYPE_TEXT && $_contentType == Zend_Mime::TYPE_HTML) {
                $body = Felamimail_Message::replaceUriAndSpaces($body);
                $body = Felamimail_Message::replaceEmails($body);
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
        
        $rawHeaders = $imapBackend->getRawContent($message->messageuid, $section, $_readOnly);
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
