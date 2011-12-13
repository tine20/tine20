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
class Felamimail_Controller_Message_Flags extends Felamimail_Controller_Message
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
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message_Flags
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
     * @return Felamimail_Controller_Message_Flags
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Message_Flags();
        }
        
        return self::$_instance;
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
        return $this->_addOrClearFlags($_messages, $_flags, 'add');
    }
    
    /**
     * clear message flag(s)
     *
     * @param mixed                     $_messages
     * @param array                     $_flags
     * @return Tinebase_Record_RecordSet with affected folders
     */
    public function clearFlags($_messages, $_flags)
    {
        return $this->_addOrClearFlags($_messages, $_flags, 'clear');
    }
    
    /**
     * add or clear message flag(s)
     *
     * @param mixed                     $_messages
     * @param array                     $_flags
     * @param string                    $_mode add/clear
     * @return Tinebase_Record_RecordSet with affected folders
     */
    protected function _addOrClearFlags($_messages, $_flags, $_mode = 'add')
    {
        $flags = (array) $_flags;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_mode. ' flags: ' . print_r($_flags, TRUE));
        
        // only get the first 100 messages if we got a filtergroup
        $pagination = ($_messages instanceof Tinebase_Model_Filter_FilterGroup) ? new Tinebase_Model_Pagination(array('sort' => 'folder_id', 'start' => 0, 'limit' => 100)) : NULL;
        $messagesToUpdate = $this->_convertToRecordSet($_messages, TRUE, $pagination);
        
        $lastFolderId       = null;
        $imapBackend        = null;
        $folderCounterById  = array();
        
        while (count($messagesToUpdate) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Retrieved ' . count($messagesToUpdate) . ' messages from cache.');
            
            // update flags on imap server
            foreach ($messagesToUpdate as $message) {
                // write flags on imap (if folder changes)
                if ($imapBackend !== null && ($lastFolderId != $message->folder_id)) {
                    $this->_updateFlagsOnImap($imapMessageUids, $flags, $imapBackend, $_mode);
                    $imapMessageUids = array();
                }
                
                // init new folder
                if ($lastFolderId != $message->folder_id) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Getting new IMAP backend for folder ' . $message->folder_id);
                    $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id);
                    $lastFolderId             = $message->folder_id;
                    
                    if ($_mode === 'add') {
                        $folderCounterById[$lastFolderId] = array(
                            'decrementMessagesCounter' => 0, 
                            'decrementUnreadCounter'   => 0
                        );
                    } else if ($_mode === 'clear') {
                        $folderCounterById[$lastFolderId] = array(
                            'incrementUnreadCounter' => 0
                        );                        
                    }
                }
                
                $imapMessageUids[] = $message->messageuid;
            }
            
            // write remaining flags
            if ($imapBackend !== null && count($imapMessageUids) > 0) {
                $this->_updateFlagsOnImap($imapMessageUids, $flags, $imapBackend, $_mode);
            }
    
            if ($_mode === 'add') {
                $folderCounterById = $this->_addFlagsOnCache($messagesToUpdate, $flags, $folderCounterById);
            } else if ($_mode === 'clear') {
                $folderCounterById = $this->_clearFlagsOnCache($messagesToUpdate, $flags, $folderCounterById);
            }
            
            // get next 100 messages if we had a filter
            if ($_messages instanceof Tinebase_Model_Filter_FilterGroup) {
                $pagination->start += 100;
                $messagesToUpdate = $this->_convertToRecordSet($_messages, TRUE, $pagination);
            } else {
                $messagesToUpdate = array();
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_mode . 'ed flags');
        
        $affectedFolders = $this->_updateFolderCounts($folderCounterById);
        return $affectedFolders;
    }
    
    /**
     * add/clear flags on imap server
     * 
     * @param array $_imapMessageUids
     * @param array $_flags
     * @param Felamimail_Backend_ImapProxy $_imapBackend
     * @throws Felamimail_Exception_IMAP
     */
    protected function _updateFlagsOnImap($_imapMessageUids, $_flags, $_imapBackend, $_mode)
    {
        $flagsToChange = array_intersect($_flags, array_keys(self::$_allowedFlags));
        if (empty($flagsToChange)) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . $_mode .'ing flags on IMAP server for ' . print_r($_imapMessageUids, TRUE) . ' messages:' . print_r($flagsToChange, TRUE));
        
        try {
            if ($_mode === 'add') {
                $_imapBackend->addFlags($_imapMessageUids, $flagsToChange);
            } else if ($_mode === 'clear') {
                $_imapBackend->clearFlags($_imapMessageUids, $flagsToChange);
            }
        } catch (Zend_Mail_Storage_Exception $zmse) {
            throw new Felamimail_Exception_IMAP($zmse->getMessage());
        }
    }
    
    /**
     * returns supported flags
     * 
     * @param boolean translated
     * @return array
     * 
     * @todo add gettext for flags
     */
    public function getSupportedFlags($_translated = TRUE)
    {
        if ($_translated) {
            $result = array();
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            
            foreach (self::$_allowedFlags as $flag) {
                $result[] = array('id'        => $flag,      'name'      => $translate->_(substr($flag, 1)));
            }
            
            return $result;
        } else {
            return self::$_allowedFlags;
        }
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
     * clears flags in local database
     * 
     * @param Tinebase_Record_RecordSet $_messagesToFlag
     * @param array $_flags
     * @param array $_folderCounts
     * @return array folder counts
     */
    protected function _clearFlagsOnCache(Tinebase_Record_RecordSet $_messagesToUnflag, $_flags, $_folderCounts)
    {
        $folderCounts = $_folderCounts;
        
        // set flags in local database
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        // store flags in local cache
        foreach($_messagesToUnflag as $message) {
            if (in_array(Zend_Mail_Storage::FLAG_SEEN, $_flags) && in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                // count messages with seen flag for the first time
                $folderCounts[$message->folder_id]['incrementUnreadCounter']++;
            }
            
            $this->_backend->clearFlag($message, $_flags);
        }
        
        // mark message as changed in the cache backend
        $this->_backend->updateMultiple(
            $_messagesToUnflag->getArrayOfIds(), 
            array(
                'timestamp' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            )
        );
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        return $folderCounts;
    }
    
    /**
     * set seen flag of message
     * 
     * @param Felamimail_Model_Message $_message
     */
    public function setSeenFlag(Felamimail_Model_Message $_message)
    {
        if (! in_array(Zend_Mail_Storage::FLAG_SEEN, $_message->flags)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Add \Seen flag to msg uid ' . $_message->messageuid);
            
            $this->addFlags($_message, Zend_Mail_Storage::FLAG_SEEN);
            $_message->flags[] = Zend_Mail_Storage::FLAG_SEEN;
        }        
    }
}
