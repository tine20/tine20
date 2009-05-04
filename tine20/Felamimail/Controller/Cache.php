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
 * @todo        add acl
 * @todo        think about using the sort dir + paging for caching as well, cache only the first page initially, and so on
 * @todo        add param to include subfolders?
 */

/**
 * cache controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Cache extends Tinebase_Controller_Abstract // Felamimail_Controller_Abstract
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
     * @param string $_folderId
     * 
     * @todo    write tests for cache handling
     * @todo    split this into smaller functions?
     */
    public function update($_folderId)
    {
        /***************** get folder & backend *****************************/
        
        $folder                 = $this->_folderBackend->get($_folderId);
        
        try {
            $backend                = Felamimail_Backend_ImapFactory::factory($folder->backend_id);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // no imap connection -> no update
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            return FALSE;
        }
        $backendFolderValues    = $backend->selectFolder($folder->globalname);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($backendFolderValues, TRUE));
        
        /***************** check for messages to add ************************/
        
        // check uidnext
        if ($folder->uidnext < $backendFolderValues['uidnext']) {
            // get missing mails
            if (empty($folder->uidnext)) {
                $uids = $backend->getUid(1, $backend->countMessages());
                $messages = $backend->getSummary($uids);
                $folder->uidvalidity = $backendFolderValues['uidvalidity'];
            } else {
                // only get messages with $backendFolderValues['uidnext'] > uid > $folder->uidnext
                $messages = $backend->getSummary($folder->uidnext, $backendFolderValues['uidnext']);
            }

            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Trying to add ' . count($messages) . ' new messages to cache. Old uidnext: ' . $folder->uidnext .
                ' New uidnext: ' . $backendFolderValues['uidnext']
            );
            
            // get message headers and save them in cache db
            $this->_addMessages($messages, $_folderId);
            
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No need to get new messages, cache is up to date.');
        }
        
        /***************** check uidvalidity and update folder *************/
        
        if ($folder->uidvalidity != $backendFolderValues['uidvalidity']) {
            
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                ' Got non matching uidvalidity value: ' . $backendFolderValues['uidvalidity'] .'. Expected: ' . $folder->uidvalidity .
                ' Clearing cache ...'
            );
            
            // update folder and cache again
            $this->clear($_folderId);
            $this->update($_folderId);
            return;
            
            //throw new Tinebase_Exception_UnexpectedValue(
            //    'Got non matching uidvalidity value: ' . $backendFolderValues['uidvalidity'] .'. Expected: ' . $folder->uidvalidity);
        }
        
        // save nextuid/validity in folder
        if ($folder->uidnext != $backendFolderValues['uidnext']) {
            $folder->uidnext = $backendFolderValues['uidnext'];
            $folder = $this->_folderBackend->update($folder);
        }
        
        /***************** check for messages to delete *********************/
        
        // check if mails have been deleted (compare counts)
        // @todo save count in folder table ?
        // @todo use only getMessageuidsByFolderId here?
        $folderCount = $this->_messageCacheBackend->searchCountByFolderId($_folderId);
        
        if ($backendFolderValues['exists'] < $folderCount) {
            // some messages have been deleted
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Checking for deleted messages.' .
                ' cached msgs: ' . $folderCount . ' server msgs: ' . $backendFolderValues['exists']
            );
            
            // get cached msguids
            $cachedMsguids = $this->_messageCacheBackend->getMessageuidsByFolderId($_folderId);
            
            // get server msguids
            $uids = $backend->getUid(1, $backend->countMessages());
            
            // array diff the msg uids to delete from cache
            $uidsToDelete = array_diff($cachedMsguids, $uids);
            $this->_messageCacheBackend->deleteMessageuidsByFolderId($uidsToDelete, $_folderId);
            
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' No need to remove old messages from cache / it is up to date.' .
                ' cached msgs: ' . $folderCount . ' server msgs: ' . $backendFolderValues['exists']
            );
        }
    }
    
    /**
     * remove all cached messages for this folder
     *
     * @param string $_folderId
     */
    public function clear($_folderId)
    {
        $this->_messageCacheBackend->deleteByFolderId($_folderId);
        
        // set folder uidnext + uidvalidity to 0
        $folder = $this->_folderBackend->get($_folderId);
        $folder->uidnext = 0;
        $folder->uidvalidity = 0;
        $this->_folderBackend->update($folder);
    }
    
    /***************************** protected funcs *******************************/
    
    /**
     * add messages to cache
     *
     * @param array $_messages
     * @param string $_folderId
     */
    protected function _addMessages($_messages, $_folderId)
    {
        // set fields with try / catch blocks
        $exceptionFields = array('subject', 'to', 'cc', 'bcc');
        
        // set time limit to infinity for this operation
        set_time_limit(0);
        
        foreach ($_messages as $uid => $value) {
            $message = $value['message'];
            $subject = '';
            
            try {
                $cachedMessage = new Felamimail_Model_Message(array(
                    'messageuid'    => $uid,
                    'from'          => Felamimail_Message::convertText($message->from),
                    'sent'          => $this->_convertDate($message->date),
                    'folder_id'     => $_folderId,
                    'timestamp'     => Zend_Date::now(),
                    'received'      => $this->_convertDate($value['received']),
                    'size'          => $value['size'],
                    'flags'         => $message->getFlags()
                ));
                
                // try to get 'subject', 'to', 'cc', 'bcc'
                foreach ($exceptionFields as $field) {
                    try {
                        if ($field === 'subject') {
                            $cachedMessage->subject = Felamimail_Message::convertText($message->subject);
                            $subject = $cachedMessage->subject;
                        } else {
                            $cachedMessage->{$field} = $this->_convertAddresses($message->{$field});
                        }
                    } catch (Zend_Mail_Exception $zme) {
                        // no 'subject', 'to', 'cc', 'bcc' available
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
            }
        }
    }
    
    /**
     * convert date from sent/received
     *
     * @param string $_dateString
     * @return Zend_Date
     */
    protected function _convertDate($_dateString)
    {
        // strip of timezone information for example: (CEST)
        $dateString = preg_replace('/( [+-]{1}\d{4}) \(.*\)$/', '${1}', $_dateString);
        
        // append dummy weekday if missing
        if(preg_match('/^(\d{1,2})\s(\w{3})\s(\d{4})\s(\d{2}):(\d{2}):{0,1}(\d{0,2})\s([+-]{1}\d{4})$/', $dateString)) {
            $dateString = 'xxx, ' . $dateString;
        }
        
        try {
            # Fri,  6 Mar 2009 20:00:36 +0100
            $date = new Zend_Date($dateString, Zend_Date::RFC_2822, 'en_US');
        } catch (Zend_Date_Exception $e) {
            # Fri,  6 Mar 2009 20:00:36 CET
            $date = new Zend_Date($dateString, 'EEE, d MMM YYYY hh:mm:ss zzz', 'en_US');
            #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " date header $headerValue => $dateString => $date => " . $date->get(Zend_Date::ISO_8601));
        }
        
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
}
