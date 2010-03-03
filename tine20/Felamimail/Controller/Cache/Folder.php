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
 * @todo        add cleanup routine for deleted (by other clients)/outofdate folders?
 */

/**
 * cache controller for Felamimail folders
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Cache_Folder extends Tinebase_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * folder backend
     *
     * @var Felamimail_Backend_Folder
     */
    protected $_backend = NULL;

    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Cache_Folder
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_backend = new Felamimail_Backend_Folder();
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
     * @return Felamimail_Controller_Cache_Folder
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Cache_Folder();
        }
        
        return self::$_instance;
    }
    
    /***************************** public funcs *******************************/
    
    /**
     * get (sub) folder and create folders in db backend cache
     *
     * @param string $_accountId
     * @param string $_folderName global name
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     */
    public function update($_accountId, $_folderName = '')
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
            
            // update has children
            $parentFolder = $this->_backend->getByBackendAndGlobalName($_accountId, $_folderName);
            $hasChildren = (empty($folders)) ? 0 : 1;
            if ($hasChildren != $parentFolder->has_children) {
                $parentFolder->has_children = $hasChildren;
                $this->_backend->update($parentFolder);
            }
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($account->toArray(), true));
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($folders, true));
        
        // get folder recordset and sort it
        $result = $this->_getOrCreateFolders($folders, $account, $_folderName);
        
        return $result;
    }
    
    /**
     * get status of all folders/selected of account
     * - use $messageCacheBackend->seenCountByFolderId if offline/no connection to imap
     *
     * @param string $_accountId
     * @param Tinebase_Record_RecordSet|array $_folders [optional] of Felamimail_Model_Folder or folder ids
     * @param string $_folderId [optional]
     * @return Tinebase_Record_RecordSet with updated folder status
     * @throws Felamimail_Exception
     */
    public function updateStatus($_accountId, $_folders = NULL, $_folderId = NULL)
    {
        if ($_folders === NULL && ($_folderId === NULL || empty($_folderId))) {
            // get all folders of account
            $filter = new Felamimail_Model_FolderFilter(array(
                array('field' => 'account_id',  'operator' => 'equals', 'value' => $_accountId)
            ));
            $folders = $this->_backend->search($filter);
        } else {
            if ($_folderId !== NULL && ! empty($_folderId)) {
                // get single folder
                $folders = new Tinebase_Record_RecordSet(
                    'Felamimail_Model_Folder', 
                    array($this->_backend->get($_folderId))
                );
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating folder ' . $folders->getFirstRecord()->globalname);
                
            } else if ($_folders !== NULL) {
                if ($_folders instanceof Tinebase_Record_RecordSet) {
                    // recordset was given
                    $folders = $_folders;
                } else if (is_array($_folders)) {
                    // array of ids
                    $filter = new Felamimail_Model_FolderFilter(array(
                        array('field' => 'account_id',  'operator' => 'equals', 'value' => $_accountId),
                        array('field' => 'id',          'operator' => 'in',     'value' => $_folders),
                    ));
                    $folders = $this->_backend->search($filter);
                }
            } else {
                throw new Felamimail_Exception("Wrong params: " . $_folderId);
            }
        }
        
        // try imap connection
        try {
            $imap = Felamimail_Backend_ImapFactory::factory($_accountId);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No connection to imap server: ' . $zmpe->getMessage());
            $imap = FALSE;
        }
        
        // return status of all folders
        foreach ($folders as $folder) {
            $folder = $this->_updateFolderStatus($folder, $imap);
        }
        
        return $folders;
    }
    
    /***************************** protected funcs *******************************/
    
    /**
     * get folder status/values from imap server and update folder cache record in database
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_Imap|boolean $_imap
     * @return Felamimail_Model_Folder
     * 
     * @todo do we need two imap calls here?
     * @todo delete folder from cache if it no longer exists
     */
    protected function _updateFolderStatus(Felamimail_Model_Folder $_folder, $_imap)
    {
        if ($_imap && $_imap instanceof Felamimail_Backend_Imap) {
            
            // get folder values / status from imap server
            // 
            try {
                $imapFolderValues = $_imap->selectFolder($_folder->globalname);
            } catch (Zend_Mail_Storage_Exception $zmse) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Folder ' . $_folder->globalname . ' not found ... Error: ' . $zmse->getMessage());
                // delete folder from cache if it no longer exists
                return;
            }
            $imapFolderStatus = $_imap->getFolderStatus($_folder->globalname);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Getting status and values for folder ' . $_folder->globalname);
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cache folder status: ' . print_r($_folder->toArray(), TRUE));
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' imap folder status: ' . print_r($imapFolderValues, TRUE));
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($imapFolderStatus, TRUE));
            
            // check validity
            if ($_folder->imap_uidvalidity != 0 && $_folder->imap_uidvalidity != $imapFolderValues['uidvalidity']) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Message cache of folder ' . $_folder->globalname . ' is invalid');
                $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INVALID;
            
            } else {
                $_folder->imap_unreadcount  = (isset($imapFolderStatus['unseen'])) ? $imapFolderStatus['unseen'] : 0;
                $_folder->imap_recentcount  = $imapFolderStatus['recent'];
                $_folder->imap_totalcount   = $imapFolderStatus['messages'];
                $_folder->imap_status       = Felamimail_Model_Folder::IMAP_STATUS_OK;
                $_folder->imap_uidvalidity  = $imapFolderValues['uidvalidity'];
                $_folder->imap_uidnext      = $imapFolderValues['uidnext'];
                
                // @todo do we need that here ?
                // @todo add cache recent count ?
                $messageCacheBackend = new Felamimail_Backend_Cache_Sql_Message();
                $_folder->cache_totalcount = $messageCacheBackend->searchCountByFolderId($_folder->getId());
                $_folder->cache_unreadcount = $_folder->cache_totalcount - $messageCacheBackend->seenCountByFolderId($_folder->getId());
                
                // update cache status if we need to do something
                if ($_folder->imap_totalcount != $_folder->cache_totalcount || $_folder->imap_uidnext != $_folder->cache_uidnext) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Cache of folder ' . $_folder->globalname . ' is incomplete.');
                    $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
                }
            }
            
        } else {
            $_folder->imap_status        = Felamimail_Model_Folder::IMAP_STATUS_DISCONNECT;
        }
        
        // update folder in cache
        $_folder->imap_timestamp = Zend_Date::now();
        return $this->_backend->update($_folder);
    }
    
    /**
     * create new folders or get existing folders from db and return record set
     *
     * @param array $_folders
     * @param Felamimail_Model_Account $_account
     * @param string $_parentFolder
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     * 
     * @todo    replace mb_convert_encoding with iconv or something like that
     * @todo    move delete sync to extra function
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
                
                $folder = $this->_backend->getByBackendAndGlobalName($_account->getId(), $folderData['globalName']);
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
                        'localname'         => $folderData['localName'],
                        'globalname'        => $folderData['globalName'],
                        'is_selectable'     => ($folderData['isSelectable'] == '1'),
                        'has_children'      => ($folderData['hasChildren'] == '1'),
                        'account_id'        => $_account->getId(),
                        'imap_timestamp'    => Zend_Date::now(),
                        'user_id'           => $this->_currentAccount->getId(),
                        'parent'            => $_parentFolder,
                        'system_folder'     => in_array(strtolower($folderData['localName']), $systemFolders),
                        'delimiter'         => $folderData['delimiter']
                    ));
                    
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding new folder ' . $folderData['globalName'] . ' to cache.');
                    $folder = $this->_backend->create($folder);
                }
            }
            
            $result->addRecord($folder);
        }
        
        // remove folders that exist no longer on the imap server
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'parent',      'operator' => 'equals', 'value' => $_parentFolder),
            array('field' => 'account_id',  'operator' => 'equals', 'value' => $_account->getId()),
        ));
        $cachedFolderIds = $this->_backend->search($filter, NULL, TRUE);
        if (count($cachedFolderIds) > count($result)) {
            // remove folders from cache
            $noLongerExistingIds = array_diff($cachedFolderIds, $result->getArrayOfIds());
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Removing ' . count($noLongerExistingIds) . ' no longer existing folder from cache.');
            $this->_backend->delete($noLongerExistingIds);
        }
        
        return $result;
    }
}
