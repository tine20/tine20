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
     * folder backend
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
     * @todo write tests for cache handling
     * @todo check if more than $_initialNumber new messages arrived even if cache 
     *       is already complete (-> do initial import again?)
     */
    public function update($_folder, $_recursive = TRUE)
    {
        $result = 0;
        
        /***************** get folder & backend *****************************/
        
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : $this->_folderBackend->get($_folder);
        
        if (! $folder->is_selectable) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Folder ' . $folder->globalname . ' is not selectable.');
            return $folder;
        }
        
        $folderId = $folder->getId();
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($folder->toArray(), true));
        
        try {
            $backend = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection -> no update
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            return $folder;
        }
        try {
            $backendFolderValues = $backend->selectFolder($folder->globalname);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Folder ' . $folder->globalname . ' not found ... Error: ' . $zmse->getMessage());
            $folder->totalcount = 0;
            return $folder;
        }
        
        // init uidnext if empty
        if (! isset($backendFolderValues['uidnext'])) {
            $backendFolderValues['uidnext'] = $backendFolderValues['exists'];
            $getUidsFirst = TRUE;
        } else {
            $getUidsFirst = FALSE;
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

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Select Folder: ' . $backend->getCurrentFolder() 
            //. ' Values: ' . print_r($backendFolderValues, TRUE)
        );
        
        // remove old \Recent flag from cached messages
        $this->_messageCacheBackend->clearFlag($folderId, '\Recent', 'folder');
        
        /***************** check for messages to add ************************/
        
        // check uidnext & get missing mails
        if ($folder->uidnext < $backendFolderValues['uidnext']) {
            if (empty($folder->uidnext)) {
                
                /********* initial ******************************************/
                
                $messages = $this->_updateInitial($backend, $folder, $backendFolderValues, $messageCount);
                $result = $messageCount;
                
            } else {
                
                /********* update *******************************************/
                
                // only get messages with $backendFolderValues['uidnext'] > uid > $folder->uidnext
                if ($getUidsFirst) {
                    $uids = $backend->getUid($folder->uidnext, $backendFolderValues['uidnext']);
                    $messages = $backend->getSummary($uids);
                } else {
                    $messages = $backend->getSummary($folder->uidnext, $backendFolderValues['uidnext']);
                }
            }

            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Trying to add ' . count($messages) . ' new messages to cache. Old uidnext: ' . $folder->uidnext
                . ' New uidnext: ' . $backendFolderValues['uidnext']
                //. ' uids: ' . print_r($uids, true)
            );
            
            // get message headers and save them in cache db
            $this->_addMessages($messages, $folderId);
            
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No need to get new messages, cache is up to date.');
            
            // check if folder is updating at the moment to show correct message number
            if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
                $result = $messageCount;
            }
        }
                
        /***************** check uidvalidity and update folder *************/
        
        if ($folder->uidvalidity != $backendFolderValues['uidvalidity'] && $backendFolderValues['uidvalidity'] != 1) {
            
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                ' Got non matching uidvalidity value: ' . $backendFolderValues['uidvalidity'] .'. Expected: ' . $folder->uidvalidity
            );
            
            // update folder and cache messages again
            if ($_recursive) {
                $folder = $this->clear($folder);
                return $this->update($folder, FALSE);
            } /* else {
                return $folder;
            } */
        }

        $folderCount = $this->_messageCacheBackend->searchCountByFolderId($folderId);
        
        /***************** check for messages to delete *********************/
        
        $messageCount = $this->_updateDelete($backend, $folderId, $backendFolderValues, $messageCount, $folderCount);
        
        /***************** compare message counts ***************************/
        
        if ($folderCount < $messageCount && $folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                ' foldercount is lower than (server)messagecount: ' . $folderCount . ' < ' . $messageCount
            );
            
            // update folder and cache messages again (try it only once)
            if ($_recursive) {
                $folder = $this->clear($folder);
                return $this->update($folder, FALSE);
            } /* else {
                return $folder;
            } */
        }
        
        /***************** update folder ************************************/
        
        // save nextuid/validity in folder
        if ($folder->uidnext != $backendFolderValues['uidnext']) {
            $folder->uidnext = $backendFolderValues['uidnext'];
            $folder->timestamp = Zend_Date::now();
        }
        
        // get unread count
        $seenCount = $this->_messageCacheBackend->seenCountByFolderId($folderId);
        $folder->unreadcount = $messageCount - $seenCount;
        if ($folder->unreadcount < 0 || $folderCount < $messageCount) {
            $folder->unreadcount = 0;
        }
        $folder->totalcount = $messageCount;
        
        $folder = $this->_folderBackend->update($folder);        
        
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
            sort($uids, SORT_NUMERIC);
            $messages = $backend->getSummary(array_reverse($uids));
            
            // import        
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Initial import: trying to add ' . count($messages) . ' new messages to cache of folder ' . $folder->localname
                . '. Beginning with message uid: ' . $uids[0] . ' (from: ' . $from .' to: ' . $to . ')'
            );
            
            // get message headers and save them in cache db
            $this->_addMessages($messages, $_folderId);
            
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ... done with Initial import for folder ' . $folder->globalname);
        
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Clearing cache of ' . $folder->globalname);
        
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
    
    /***************************** protected funcs *******************************/
    
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
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' Could not parse message ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zme->getMessage()
                );
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' Failed to create cache entry for msg ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zdse->getMessage()
                );
            } catch (Zend_Date_Exception $zde) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
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
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting initial ' . $_messageCount .' messages.');
        
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
     * check if mails have been deleted (compare counts)
     *
     * @param Felamimail_Backend_Imap $_backend
     * @param string $_folderId
     * @param array $_backendFolderValues
     * @param integer $_messageCount
     * @param integer $_folderCount
     * @return integer new messageCount
     */
    protected function _updateDelete($_backend, $_folderId, $_backendFolderValues, $_messageCount, $_folderCount)
    {
        if ($_backendFolderValues['exists'] < $_folderCount) {
            // some messages have been deleted
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Checking for deleted messages.' .
                ' cached msgs: ' . $_folderCount . ' server msgs: ' . $_backendFolderValues['exists']
            );
            
            // get cached msguids
            $cachedMsguids = $this->_messageCacheBackend->getMessageuidsByFolderId($_folderId);
            
            // get server msguids
            $uids = $_backend->getUid(1, $_messageCount);
            
            // array diff the msg uids to delete from cache
            $uidsToDelete = array_diff($cachedMsguids, $uids);
            $deleted = $this->_messageCacheBackend->deleteMessageuidsByFolderId($uidsToDelete, $_folderId);
            
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
}
