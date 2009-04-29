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
     * @todo    add mechanism to update cache without the need to clear it first?
     *          -> only fetch messages with uid > nextuid?
     * @todo    parse received to get Zend_Date
     * @todo    get message size
     * @todo    make encoding changeable via prefs or backend settings
     * @todo    save nextuid/validity in folder
     */
    public function update($_folderId)
    {
        $folder = $this->_folderBackend->get($_folderId);
        
        //if ($this->_cacheController->updateRequired($folder)) {
            
        //}
        
        // clear cache
        $this->clear($_folderId);
        
        // get all (?) message headers from folder and save them in cache db
        $backend = $this->_getBackend($folder->backend_id);
        $backend->selectFolder($folder->globalname);
        $uids = $backend->getUid(1, $backend->countMessages());
        $messages = $backend->getSummary($uids);
        
        foreach ($messages as $uid => $message) {
            $cachedMessage = new Felamimail_Model_Message(array(
                'messageuid'    => $uid,
                'subject'       => $message->subject,
                'from'          => @iconv('ISO_8859-1', $this->_encoding, $message->from),
                'to'            => $message->to,
                'sent'          => new Zend_Date($message->date),
                'folder_id'     => $_folderId,
                'timestamp'     => Zend_Date::now()
                //'received'      => $message->received,
                //'size'          => $message->size
            ));
            
            $this->_messageCacheBackend->create($cachedMessage);
        }
        
        //-- save nextuid/validity in folder 
    }
    
    /**
     * check if cache update/rebuild is required for this folder
     *
     * @param string $_folderId
     * @return boolean
     * 
     * @todo    add nextuid/validity check
     */
    public function updateRequired($_folderId)
    {
        // try to get folder from db
        $folder = $this->_folderBackend->get($_folderId);
        
        //-- check backend and folder nextuid/validity flags
        
        $result = TRUE;
        return $result;
    }
    
    /**
     * remove all cached messages for this folder
     *
     * @param string $_folderId
     */
    public function clear($_folderId)
    {
        $this->_messageCacheBackend->deleteByFolderId($_folderId);
    }
}
