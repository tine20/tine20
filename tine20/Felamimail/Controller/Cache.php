<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add body of messages of last week (?) to cache?
 */

/**
 * cache controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Cache extends Tinebase_Controller_Abstract
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
     * number of initially cached messages
     *
     * @var int
     */
    protected $_initialNumber = 50;
    
    /**
     * folder backend
     *
     * @var Felamimail_Backend_Folder
     */
    protected $_folderBackend = NULL;

    /**
     * message backend
     *
     * @var Felamimail_Backend_Cache_Sql_Message
     */
    protected $_messageCacheBackend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Cache
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_folderBackend = new Felamimail_Backend_Folder();
        $this->_messageCacheBackend = new Felamimail_Backend_Cache_Sql_Message();
        
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
     * @return Felamimail_Controller_Cache
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Cache();
        }
        
        return self::$_instance;
    }
    
    /***************************** public funcs *******************************/
    
    /**
     * update cache if required
     *
     * @param string|Felamimail_Model_Folder $_folder
     * @param boolean $_recursive try it again if something goes wrong
     * @return Felamimail_Model_Folder
     * 
     * @todo    check if more than $_initialNumber new messages arrived even if cache 
     *          is already complete (-> do initial import again?)
     */
    public function updateMessages($_folder, $_recursive = TRUE)
    {
        /***************** get folder ***************************************/
        
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : $this->_folderBackend->get($_folder);
        
        if (! $folder->is_selectable) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Folder ' . $folder->globalname . ' is not selectable.');
            return $folder;
        }
        
        /***************** get backend **************************************/

        try {
            $backend = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection -> no update
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            return $folder;
        }
        
        /***************** get counts and folder data ***********************/
        
        try {
            $backendFolderValues = $backend->selectFolder($folder->globalname);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Folder ' . $folder->globalname . ' not found ... Error: ' . $zmse->getMessage());
            $folder->totalcount = 0;
            return $folder;
        }
        
        $messageCount = $backend->countMessages();
        
        // check if message count is strange
        if ($messageCount < $backendFolderValues['exists']) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                . ' Message count strange: countMessages() = ' . $messageCount 
                . ' / exists = ' . $backendFolderValues['exists']
            );
            
            $messageCount = $backendFolderValues['exists'];
        }

        // remove old \Recent flag from cached messages
        $this->_messageCacheBackend->clearFlag($folder->getId(), '\Recent', 'folder');
        
        /***************** check for messages to add ************************/
        
        $backendFolderValues = $this->_updateMessagesAdd($backend, $folder, $backendFolderValues, $messageCount);
                
        /***************** check uidvalidity ********************************/
        
        if (! $this->_updateMessagesCheckValidity($folder, $backendFolderValues) && $_recursive) {
            $folder = $this->clear($folder);
            return $this->updateMessages($folder, FALSE);
        }

        /***************** get folder message count *************************/
        
        $folderCount = $this->_messageCacheBackend->searchCountByFolderId($folder->getId());
        
        /***************** check for messages to delete *********************/
        
        $messageCount = $this->_updateMessagesDelete($backend, $folder, $backendFolderValues, $messageCount, $folderCount);
        
        /***************** compare message counts ***************************/
        
        $this->_updateMessagesCompare($backend, $folder, $messageCount, $folderCount);
        
        /***************** update folder ************************************/
        
        $folder = $this->_updateFolderValues($folder, $backendFolderValues, $messageCount, $folderCount);
        
        return $folder;
    }
    
    /**
     * finish initial import of folder messages
     *
     * @param   string $_folderId
     * @return  boolean
     */
    public function initialImport($_folderId)
    {
        $folder = $this->_folderBackend->get($_folderId);
        
        // check status first
        if ($folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
                if ($folder->timestamp->compare(Zend_Date::now()->subMinute(5)) == -1) {
                    // it seems that the old import process ended (timestamp is older than 5 mins) -> start a new one
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                        . ' Old initial import process ended without finishing: '
                        . $folder->timestamp->get() . ' / ' . Zend_Date::now()->subMinute(5)->get() 
                        . ' Starting new import for folder ' 
                        . $folder->globalname . ' ... '
                    );
                } else {
                    // do nothing
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Caching of folder ' . $folder->globalname . ' is still running.');
                    return TRUE;
                }
            } else {
                // do nothing
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Caching of folder ' . $folder->globalname . ' is already complete.');
                return TRUE;
            }
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Finishing Initial import for folder ' . $folder->globalname . ' ... ');
        }
        
        try {
            $backend = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection -> no update
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            return FALSE;
        }
        $backendFolderValues    = $backend->selectFolder($folder->globalname);

        // update folder and add timestamp to folder to check for deadlocks (status = updating & timestamp is older than 5 mins)
        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
        $folder->timestamp = Zend_Date::now();
        $folder = $this->_folderBackend->update($folder);
        
        // get the remaining messages from imap backend
        $messageCount = $backend->countMessages();
        if ($messageCount < $backendFolderValues['exists']) {
            $messageCount = $backendFolderValues['exists'];
        }
        $folderCount = $this->_messageCacheBackend->searchCountByFolderId($_folderId);
        
        $to = $messageCount - $folderCount;
        
        while (! isset($from) || $from > 1) {
            $from = ($to > 200) ? $to - 200 : 1;
            
            // get next 200 message headers
            
            $uids = $backend->getUid($from, $to);
            
            if (count($uids) > 0) {
                sort($uids, SORT_NUMERIC);
                $messages = $backend->getSummary(array_reverse($uids));
                
                // import        
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Initial import: trying to add ' . count($messages) . ' new messages to cache of folder ' . $folder->localname
                    . '. Beginning with message uid: ' . $uids[0] . ' (from: ' . $from .' to: ' . $to . ')'
                );
                
                // get message headers and save them in cache db
                $this->_addMessages($messages, $_folderId);
            }
            
            $to = $from - 1;
            //$from = ($to > 200) ? $to - 200 : 1;
        }
        
        // get number of unread messages
        $seenCount = $this->_messageCacheBackend->seenCountByFolderId($_folderId);
        $folder->unreadcount = $messageCount - $seenCount;
        
        // update folder
        $folder->totalcount = $messageCount;
        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
        $folder->cache_lowest_uid = 0;
        $folder = $this->_folderBackend->update($folder);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ... done with Initial import for folder ' . $folder->globalname);
        
        return TRUE;
    }
    
    /**
     * remove all cached messages for this folder
     *
     * @param string|Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Folder
     */
    public function clear($_folder)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : $this->_folderBackend->get($_folder);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing cache of ' . $folder->globalname);
        
        $this->_messageCacheBackend->deleteByFolderId($folder->getId());
        
        // set folder uidnext + uidvalidity to 0 and reset cache status
        $folder->uidnext = 0;
        $folder->totalcount = 0;
        $folder->unreadcount = 0;
        $folder->uidvalidity = 0;
        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_EMPTY;
        $folder->cache_lowest_uid = 0;
        $folder = $this->_folderBackend->update($folder);
        
        return $folder;
    }
    
    
    /**
     * get (sub) folder and create folders in db backend cache
     *
     * @param string $_folderName
     * @param string $_accountId [optional]
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     */
    public function updateFolders($_folderName = '', $_accountId = 'default')
    {
        $account = Felamimail_Controller_Account::getInstance()->get($_accountId);
        $imap = Felamimail_Backend_ImapFactory::factory($account);
        
        $this->_delimiter = $account->delimiter;
        
        // try to get subfolders of $_folderName
        if(empty($_folderName)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Get subfolders of root for backend ' . $_accountId);
            $folders = $imap->getFolders('', '%');
            
            // get imap server capabilities and save delimiter / personal namespace in account
            Felamimail_Controller_Account::getInstance()->updateCapabilities(
                $account, 
                $imap, 
                (! empty($folders) && isset($folders[0]['delimiter']) && ! empty($folders[0]['delimiter'])) ? $folders[0]['delimiter'] : NULL
            );
            
        } else {
            try {
                
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' trying to get subfolders of ' . $_folderName . $this->_delimiter);
                $folders = $imap->getFolders($_folderName . $this->_delimiter, '%');
                
            } catch (Zend_Mail_Storage_Exception $zmse) {
                
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getMessage() .' - Trying again ...');
                
                // try again without delimiter
                try {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' trying to get subfolders of ' . $_folderName);
                    $folders = $imap->getFolders($_folderName, '%');
                    
                } catch (Zend_Mail_Storage_Exception $zmse) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getMessage());
                    $folders = array();
                }
            }
            
            // remove folder if self
            if (in_array($_folderName, array_keys($folders))) {
                unset($folders[$_folderName]);
            }
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($account->toArray(), true));
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($folders, true));
        
        // get folder recordset and sort it
        $result = $this->_getOrCreateFolders($folders, $account, $_folderName);
        
        return $result;
    }
    
    /***************************** protected funcs *******************************/
    
    /**
     * add new messages to cache
     * 
     * @param Felamimail_Backend_Imap $_backend
     * @param Felamimail_Model_Folder $_folder
     * @param array $_backendFolderValues
     * @param integer $_messageCount
     * @return array
     */
    protected function _updateMessagesAdd($_backend, $_folder, $_backendFolderValues, $_messageCount)
    {
        $backendFolderValues = $_backendFolderValues;
        
        // init uidnext if empty
        if (! isset($backendFolderValues['uidnext'])) {
            $backendFolderValues['uidnext'] = $backendFolderValues['exists'];
            $getUidsFirst = TRUE;
        } else {
            $getUidsFirst = FALSE;
        }

        // check uidnext & get missing mails
        if ($_folder->uidnext < $backendFolderValues['uidnext']) {
            if (empty($_folder->uidnext)) {
                
                // initial 
                $messages = $this->_updateInitial($_backend, $_folder, $backendFolderValues, $_messageCount);
                
            } else {
                
                // update
                if ($getUidsFirst) {
                    // only get messages with $backendFolderValues['uidnext'] > uid > $_folder->uidnext
                    $uids = $_backend->getUid($_folder->uidnext, $backendFolderValues['uidnext']);
                    $messages = $_backend->getSummary($uids);
                } else {
                    $messages = $_backend->getSummary($_folder->uidnext, $backendFolderValues['uidnext']);
                }
            }

            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Trying to add ' . count($messages) . ' new messages to cache. ');
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Old uidnext: ' . $_folder->uidnext
                . ' New uidnext: ' . $backendFolderValues['uidnext']
                //. ' uids: ' . print_r($uids, true)
            );
            
            // get message headers and save them in cache db
            $this->_addMessages($messages, $_folder->getId());
            
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No need to get new messages, cache is up to date.');
        }
        
        return $backendFolderValues;
    }
    
    /**
     * check if mails have been deleted (compare counts)
     *
     * @param Felamimail_Model_Folder $_folder
     * @param array $_backendFolderValues
     * @return boolean
     */
    protected function _updateMessagesCheckValidity($_folder, $_backendFolderValues)
    {
        $result = TRUE;
        
        if ($_folder->uidvalidity != $_backendFolderValues['uidvalidity'] && $_backendFolderValues['uidvalidity'] != 1) {
            
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                ' Got non matching uidvalidity value: ' . $_backendFolderValues['uidvalidity'] .'. Expected: ' . $_folder->uidvalidity
            );
            
            $result = FALSE;
        }
        
        return $result;
    }
    
    /**
     * check if mails have been deleted (compare counts)
     *
     * @param Felamimail_Backend_Imap $_backend
     * @param Felamimail_Model_Folder $_folder
     * @param array $_backendFolderValues
     * @param integer $_messageCount
     * @param integer $_folderCount
     * @return integer new messageCount
     */
    protected function _updateMessagesDelete($_backend, $_folder, $_backendFolderValues, $_messageCount, $_folderCount)
    {
        if ($_backendFolderValues['exists'] < $_folderCount) {
            // some messages have been deleted
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Checking for deleted messages.' .
                ' cached msgs: ' . $_folderCount . ' server msgs: ' . $_backendFolderValues['exists']
            );
            
            // get cached msguids
            $cachedMsguids = $this->_messageCacheBackend->getMessageuidsByFolderId($_folder->getId());
            
            // get server msguids
            $uids = $_backend->getUid(1, $_messageCount);
            
            // array diff the msg uids to delete from cache
            $uidsToDelete = array_diff($cachedMsguids, $uids);
            $deleted = $this->_messageCacheBackend->deleteMessageuidsByFolderId($uidsToDelete, $_folder->getId());
            
            $result = $_messageCount - $deleted;
            
            if ($result < 0) {
                $result = 0;
            }
            
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' No need to remove old messages from cache / it is up to date.' .
                ' cached msgs: ' . $_folderCount . ' server msgs: ' . $_backendFolderValues['exists']
            );
            
            $result = $_messageCount;
        }
        
        return $result;
    }
    
    
    /**
     * compare folder count with message count
     *
     * @param Felamimail_Backend_Imap $_backend
     * @param Felamimail_Model_Folder $_folder
     * @param integer $_messageCount
     * @param integer $_folderCount
     * @return void
     * 
     * @todo only try to get the uncached mails instead of clearing the whole cache when count mismatched
     */
    protected function _updateMessagesCompare($_backend, $_folder, $_messageCount, $_folderCount)
    {
        if ($_folderCount < $_messageCount 
            && $_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE 
            && $_folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_DELETING
        ) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                ' foldercount is lower than (server)messagecount: ' . $_folderCount . ' < ' . $_messageCount
            );
            
            // @todo get remaining messages from server
            /*
            // update folder and cache messages again (try it only once)
            if ($_recursive) {
                $folder = $this->clear($folder);
                return $this->updateMessages($folder, FALSE);
            } else {
                return $folder;
            }
            */
        }
    }
    
    /**
     * add messages to cache
     *
     * @param array $_messages
     * @param string $_folderId
     * 
     * @todo get replyTo & inReplyTo
     */
    protected function _addMessages($_messages, $_folderId)
    {
        // set fields with try / catch blocks
        $exceptionFields = array('subject', 'to', 'cc', 'bcc', 'content_type', 'from', 'sent');
        
        // set time limit to infinity for this operation
        set_time_limit(0);
        
        foreach ($_messages as $uid => $value) {
            $message = $value['message'];
            $subject = '';
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($message, true));
            
            try {
                $cachedMessage = new Felamimail_Model_Message(array(
                    'messageuid'    => $uid,
                    'folder_id'     => $_folderId,
                    'timestamp'     => Zend_Date::now(),
                    'received'      => $this->_convertDate($value['received'], Felamimail_Model_Message::DATE_FORMAT_RECEIVED),
                    'size'          => $value['size'],
                    'flags'         => $message->getFlags(),
                ));
                
                // try to get optional fields
                foreach ($exceptionFields as $field) {
                    try {
                        switch ($field) {
                            case 'subject':
                                $cachedMessage->subject = Felamimail_Message::convertText($message->subject);
                                $subject = $cachedMessage->subject;
                                break;
                            case 'content_type':
                                $cachedMessage->content_type = $message->contentType;
                                break;
                            case 'from':
                                $cachedMessage->from = Felamimail_Message::convertText($message->from, TRUE, 256);
                                // unquote meta chars
                                $cachedMessage->from = preg_replace("/\\\\([\[\]\*\?\+\.\^\$\(\)]+)/", "$1", $cachedMessage->from);
                                break;
                            case 'sent':
                                $cachedMessage->sent = $this->_convertDate($message->date);
                                break;
                            default:
                                $cachedMessage->{$field} = $this->_convertAddresses($message->{$field});
                        }
                    } catch (Zend_Mail_Exception $zme) {
                        // no 'subject', 'to', 'cc', 'bcc', from, sent or content_type available
                        if (in_array($field, array('to', 'cc', 'bcc'))) {
                            $cachedMessage->{$field} = array();
                        } else if ($field == 'sent') {
                            $cachedMessage->{$field} = new Zend_Date(0);
                        } else {
                            $cachedMessage->{$field} = '';
                        }
                    }
                }
                
                $this->_messageCacheBackend->create($cachedMessage);
                
            } catch (Zend_Mail_Exception $zme) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                    ' Could not parse message ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zme->getMessage()
                );
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                    ' Failed to create cache entry for msg ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zdse->getMessage()
                );
            } catch (Zend_Date_Exception $zde) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                    ' Could not parse message ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zde->getMessage()
                );
            }
        }
    }
   
    /**
     * convert date from sent/received
     *
     * @param string $_dateString
     * @param string $_format default: 'Thu, 21 Dec 2000 16:01:07 +0200' (Zend_Date::RFC_2822)
     * @return Zend_Date
     */
    protected function _convertDate($_dateString, $_format = Zend_Date::RFC_2822)
    {
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
                    
                    //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($matches, true));
                }
            }
        }
        
        /*
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' format: ' . $_format);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' before: ' . $_dateString);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' after: ' . $dateString);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' zend date:' . $date->toString());
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' zend date:' . $date->getTimezone());
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' zend date:' . $date->get(Zend_Date::GMT_DIFF));
        */
        
        return $date;
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
    
    /**
     * do initial cache update
     * - only get the first 100 mails and mark cache as 'incomplete' if mor messages in folder
     *
     * @param Felamimail_Backend_Imap $_backend
     * @param Felamimail_Model_Folder $_folder
     * @param array $_backendFolderValues
     * @param integer $_messageCount
     * @return array
     */
    protected function _updateInitial($_backend, $_folder, $_backendFolderValues, $_messageCount)
    {
        //$uids = $backend->getUid(1, $backend->countMessages());
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Getting initial ' . $_messageCount .' messages.');
        
        if ($_messageCount > $this->_initialNumber) {
            $bottom = $_messageCount - $this->_initialNumber - 1;
            $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
        } else {
            $bottom = 1;
            $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
        }
        
        $uids = $_backend->getUid($bottom, $_messageCount);
        
        if ($_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $_folder->cache_lowest_uid = min($uids); 
        }
        
        $messages = $_backend->getSummary($uids);
        $_folder->uidvalidity = $_backendFolderValues['uidvalidity'];
        
        return $messages;
    }

    /**
     * update folder values and save it in db (cache)
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param array $_backendFolderValues
     * @param integer $_messageCount
     * @param integer $_folderCount
     * @return Felamimail_Model_Folder
     * 
     * @todo    make 'recent' persistent?
     */
    protected function _updateFolderValues(Felamimail_Model_Folder $_folder, array $_backendFolderValues, $_messageCount, $_folderCount)
    {
        // save nextuid/validity in folder
        if ($_folder->uidnext != $_backendFolderValues['uidnext']) {
            $_folder->uidnext = $_backendFolderValues['uidnext'];
            $_folder->timestamp = Zend_Date::now();
        }
        
        // get unread count
        $oldUnreadCount = $_folder->unreadcount;
        $seenCount = $this->_messageCacheBackend->seenCountByFolderId($_folder->getId());
        $_folder->unreadcount = $_messageCount - $seenCount;
        if ($_folder->unreadcount < 0 || $_folderCount < $_messageCount) {
            $_folder->unreadcount = 0;
        }
        
        $_folder->totalcount = $_messageCount;
        
        $result = $this->_folderBackend->update($_folder);
        
        // add recent after the update
        if (isset($_backendFolderValues['recent'])) {
            $result->recent = $_backendFolderValues['recent'];
        } else {
            $result->recent = ($oldUnreadCount < $_folder->unreadcount) ? 1 : 0;
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result->toArray(), TRUE));
        
        return $result;
    }
    
    /**
     * create new folders or get existing folders from db and return record set
     *
     * @param array $_folders
     * @param Felamimail_Model_Account $_account
     * @param string $_parentFolder
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     * 
     * @todo replace mb_convert_encoding with iconv or something like that
     */
    protected function _getOrCreateFolders(array $_folders, $_account, $_parentFolder)
    {
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Folder');
        $systemFolders = Felamimail_Controller_Folder::getInstance()->getSystemFolders($_account);
        
        // get configured account standard folders here
        if (strtolower($_account->sent_folder) != $systemFolders[2]) {
            $systemFolders[2] = strtolower($_account->sent_folder);
        }
        if (strtolower($_account->trash_folder) != $systemFolders[5]) {
            $systemFolders[5] = strtolower($_account->trash_folder);
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($systemFolders, TRUE));
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_folders, TRUE));
        
        // do some mapping and save folder in db (if it doesn't exist
        foreach ($_folders as $folderData) {
            try {
                // decode folder name
                if (extension_loaded('mbstring')) {
                    $folderData['localName'] = mb_convert_encoding($folderData['localName'], "utf-8", "UTF7-IMAP");
                }
                
                $folder = $this->_folderBackend->getByBackendAndGlobalName($_account->getId(), $folderData['globalName']);
                $folder->is_selectable = ($folderData['isSelectable'] == '1');
                $folder->has_children = ($folderData['hasChildren'] == '1');
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding cached folder ' . $folderData['globalName']);
                
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create new folder
                if (empty($folderData['localName'])) {
                    // skip
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Do not add folder ' . $folderData['globalName']);
                    continue;
                    
                } else {
                    $folder = new Felamimail_Model_Folder(array(
                        'localname'     => $folderData['localName'],
                        'globalname'    => $folderData['globalName'],
                        'is_selectable' => ($folderData['isSelectable'] == '1'),
                        'has_children'  => ($folderData['hasChildren'] == '1'),
                        'account_id'    => $_account->getId(),
                        'timestamp'     => Zend_Date::now(),
                        'user_id'       => $this->_currentAccount->getId(),
                        'parent'        => $_parentFolder,
                        'system_folder' => in_array(strtolower($folderData['localName']), $systemFolders),
                        'delimiter'     => $folderData['delimiter']
                    ));
                    
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding new folder ' . $folderData['globalName'] . ' to cache.');
                    $folder = $this->_folderBackend->create($folder);
                }
            }
            
            $result->addRecord($folder);
        }
        
        // remove folders that exist no longer on the imap server
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'parent',      'operator' => 'equals', 'value' => $_parentFolder),
            array('field' => 'account_id',  'operator' => 'equals', 'value' => $_account->getId()),
        ));
        $cachedFolderIds = $this->_folderBackend->search($filter, NULL, TRUE);
        if (count($cachedFolderIds) > count($result)) {
            // remove folders from cache
            $noLongerExistingIds = array_diff($cachedFolderIds, $result->getArrayOfIds());
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Removing ' . count($noLongerExistingIds) . ' no longer existing folder from cache.');
            $this->_folderBackend->delete($noLongerExistingIds);
        }
        
        return $result;
    }
}
