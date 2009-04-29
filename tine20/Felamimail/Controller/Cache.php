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
    
    /**
     * update cache if required
     *
     * @param string $_folderId
     * 
     * @todo    check if mails have been deleted (compare counts)
     * @todo    parse received data to get Zend_Date
     * @todo    get message size
     * @todo    make encoding changeable via prefs or backend settings
     */
    public function update($_folderId)
    {
        $folder = $this->_folderBackend->get($_folderId);

        $backend = $this->_getBackend($folder->backend_id);
        $backendFolderValues = $backend->selectFolder($folder->globalname);
        
        //print_r($backendFolderValues);
        
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

            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                ' Trying to add ' . count($messages) . ' new messages to cache. Old uidnext: ' . $folder->uidnext .
                ' New uidnext: ' . $backendFolderValues['uidnext']
            );
            
            // get all (?) message headers from folder and save them in cache db
            foreach ($messages as $uid => $message) {
                
                try {
                    $cachedMessage = new Felamimail_Model_Message(array(
                        'messageuid'    => $uid,
                        'subject'       => @iconv('ISO_8859-1', $this->_encoding, $message->subject),
                        'from'          => @iconv('ISO_8859-1', $this->_encoding, $message->from),
                        'to'            => @iconv('ISO_8859-1', $this->_encoding, $message->to),
                        'sent'          => new Zend_Date($message->date),
                        'folder_id'     => $_folderId,
                        'timestamp'     => Zend_Date::now()
                        //'received'      => $message->received,
                        //'size'          => $message->size
                    ));
                    
                    $this->_messageCacheBackend->create($cachedMessage);
                } catch (Zend_Mail_Exception $zme) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 
                        ' Could not parse message ' . $uid . ' in folder ' . $folder->globalname .
                        '. Error: ' . $zme->getMessage()
                    );
                }
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No need to get new messages, cache is up to date.');
        }
        
        if ($folder->uidvalidity != $backendFolderValues['uidvalidity']) {
            throw new Tinebase_Exception_UnexpectedValue(
                'Got non matching uidvalidity value: ' . $backendFolderValues['uidvalidity'] .'. Expected: ' . $folder->uidvalidity);
        }
        
        // save nextuid/validity in folder
        if ($folder->uidnext != $backendFolderValues['uidnext']) {
            $folder->uidnext = $backendFolderValues['uidnext'];
            $this->_folderBackend->update($folder);
        }
        
        //-- check if mails have been deleted (compare counts)
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
}
