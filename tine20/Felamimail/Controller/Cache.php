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
class Felamimail_Controller_Cache extends Felamimail_Controller_Abstract
{
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
     * @todo    check if mails have been deleted (compare counts)
     * @todo    create new folder if no uidvalidity
     * @todo    write tests for cache handling
     * @todo    split this into smaller functions
     */
    public function update($_folderId)
    {
        /***************** get folder & backend *****************************/
        
        $folder                 = $this->_folderBackend->get($_folderId);
        $backend                = $this->_getImapBackend($folder->backend_id);
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
            // @todo create new folder and update cache again
            throw new Tinebase_Exception_UnexpectedValue(
                'Got non matching uidvalidity value: ' . $backendFolderValues['uidvalidity'] .'. Expected: ' . $folder->uidvalidity);
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
     * 
     * @todo add more parsing of header and other mail stats (size, received, ...)
     * @todo make encoding changeable via prefs or backend settings?
     */
    protected function _addMessages($_messages, $_folderId, $_encodingFrom = 'ISO_8859-1')
    {
        foreach ($_messages as $uid => $value) {
            $message = $value['message'];
            
            //var_dump($message);
            
            try {
                $cachedMessage = new Felamimail_Model_Message(array(
                    'messageuid'    => $uid,
                    'subject'       => @iconv($_encodingFrom, $this->_encoding, $message->subject),
                    'from'          => @iconv($_encodingFrom, $this->_encoding, $message->from),
                    'to'            => @iconv($_encodingFrom, $this->_encoding, $message->to),
                    'sent'          => new Zend_Date($message->date),
                    'folder_id'     => $_folderId,
                    'timestamp'     => Zend_Date::now(),
                    'received'      => new Zend_Date($value['received']),
                    'size'          => $value['size'],
                    'flags'         => Zend_Json::encode($message->getFlags())
                ));
                
                $this->_messageCacheBackend->create($cachedMessage);
                
            } catch (Zend_Mail_Exception $zme) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                    ' Could not parse message ' . $uid . ' | ' . $message->subject .
                    '. Error: ' . $zme->getMessage()
                );
            }
        }        
    }
}
