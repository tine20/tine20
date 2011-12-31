<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * message flags controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message_Move extends Felamimail_Controller_Message
{
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message_Move
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
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
     * @return Felamimail_Controller_Message_Move
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Message_Move();
        }
        
        return self::$_instance;
    }
    
    /**
     * move messages to folder
     *
     * @param  mixed  $_messages
     * @param  mixed  $_targetFolder can be one of: folder_id, Felamimail_Model_Folder or Felamimail_Model_Folder::FOLDER_TRASH (constant)
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     */
    public function moveMessages($_messages, $_targetFolder)
    {
        if ($_targetFolder !== Felamimail_Model_Folder::FOLDER_TRASH) {
            $targetFolder = ($_targetFolder instanceof Felamimail_Model_Folder) ? $_targetFolder : Felamimail_Controller_Folder::getInstance()->get($_targetFolder);
        } else {
            $targetFolder = $_targetFolder;
        }
        
        if ($_messages instanceof Tinebase_Model_Filter_FilterGroup) {
            $iterator = new Tinebase_Record_Iterator(array(
                'iteratable' => $this,
            	'controller' => $this, 
            	'filter'     => $_messages,
            	'function'   => 'processMoveIteration',
            ));
            $iterateResult = $iterator->iterate($targetFolder);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Moved ' . $iterateResult['totalcount'] . ' message(s).');
            
            // @todo return all results?
            $result = array_pop($iterateResult['results']);
        } else {
            $messages = $this->_convertToRecordSet($_messages, TRUE);
            $result = $this->processMoveIteration($messages, $targetFolder);
        }
        
        return $result;
    }
    
    /**
     * move messages
     * 
     * @param Tinebase_Record_RecordSet $_messages
     * @param  mixed  $_targetFolder can be one of: Felamimail_Model_Folder or Felamimail_Model_Folder::FOLDER_TRASH (constant)
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     */
    public function processMoveIteration($_messages, $_targetFolder)
    {
        $_messages->addIndices(array('folder_id'));
        
        $movedMessages = FALSE;
        foreach (array_unique($_messages->folder_id) as $folderId) {
            $movedMessages = ($this->_moveMessagesByFolder($_messages, $folderId, $_targetFolder) || $movedMessages);
        }
        
        if (! $movedMessages) {
            // no messages have been moved -> return empty record set
            $result = new Tinebase_Record_RecordSet('Felamimail_Model_Folder');
        } else {
            // delete messages in local cache
            $number = $this->_backend->delete($_messages->getArrayOfIds());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleted ' . $number .' messages from cache');
        
            $result = $this->_updateCountsAfterMove($_messages);
        }
        
        return $result;
    }
        
    /**
     * move messages from one folder to another
     * 
     * @param Tinebase_Record_RecordSet $_messages
     * @param string $_folderId
     * @param Felamimail_Model_Folder|string $_targetFolder
     * @return boolean did we move messages?
     */
    protected function _moveMessagesByFolder(Tinebase_Record_RecordSet $_messages, $_folderId, $_targetFolder)
    {
        $messagesInFolder = $_messages->filter('folder_id', $_folderId);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Moving messages: ' . print_r($messagesInFolder->getArrayOfIds(), TRUE));
        
        $result = TRUE;
        if ($_targetFolder === Felamimail_Model_Folder::FOLDER_TRASH) {
            $result = $this->_moveMessagesToTrash($messagesInFolder, $_folderId);
        } else if ($_folderId === $_targetFolder->getId()) {
            // no need to move
            $result = FALSE;
        } else if ($messagesInFolder->getFirstRecord()->account_id == $_targetFolder->account_id) {
            $this->_moveMessagesInFolderOnSameAccount($messagesInFolder, $_targetFolder);
        } else {
            $this->_moveMessagesToAnotherAccount($messagesInFolder, $_targetFolder);
        }
        
        if (! $result) {
            $_messages->removeRecords($messagesInFolder);
        }
        
        return $result;
    }
    
    /**
     * move messages to trash
     * 
     * @param Tinebase_Record_RecordSet $_messagesInFolder
     * @param string $_folderId
     * @return boolean did we move messages?
     */
    protected function _moveMessagesToTrash(Tinebase_Record_RecordSet $_messagesInFolder, $_folderId)
    {
        // messages should be moved to trash -> need to determine the trash folder for the account of the folder that contains the messages
        $targetFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder(
            $_messagesInFolder->getFirstRecord()->account_id,
            Felamimail_Model_Folder::FOLDER_TRASH
        );
        if ($_folderId === $targetFolder->id) {
            return FALSE;
        }
        
        try {
            $this->_moveMessagesInFolderOnSameAccount($_messagesInFolder, $targetFolder);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' No trash folder found - skipping messages in this folder.');
            return FALSE;
        }
        
        return TRUE;
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
}
