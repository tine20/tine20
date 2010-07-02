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
        try {
            $imap = Felamimail_Backend_ImapFactory::factory($account);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            return $this->_getOrCreateFolders(array(), $account, $_folderName);
        }
        
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
            $parentFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($_accountId, $_folderName);
            $hasChildren = (empty($folders)) ? 0 : 1;
            if ($hasChildren != $parentFolder->has_children) {
                $parentFolder->has_children = $hasChildren;
                $this->_backend->update($parentFolder);
            }
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($account->toArray(), true));
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($folders, true));
        
        // get folder recordset and sort it
        $result = $this->_getOrCreateFolders($folders, $account, $_folderName);
        
        return $result;
    }
    
    /**
     * delete folder(s) from cache
     * 
     * @param string|array $_id
     */
    public function delete($_id)
    {
        $this->_backend->delete($_id);
    }
    
    /**
     * get folder status/values from imap server and update folder cache record in database
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_Imap|boolean $_imap
     * @return Felamimail_Model_Folder
     */
    public function updateFolderStatus(Felamimail_Model_Folder $_folder, $_imap)
    {
        return Felamimail_Controller_Cache_Message::getInstance()->update($_folder, 1);
    }
    
    /**
     * get folder status/values from imap server and update folder cache record in database
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_Imap|boolean $_imap
     * @return Felamimail_Model_Folder
     */
    public function getIMAPFolderCounter(Felamimail_Model_Folder $_folder)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);
        
        // get folder values / status from imap server
        $counter = $imap->examineFolder($folder->globalname);
            
        // check validity
        $folder->cache_uidvalidity = $folder->imap_uidvalidity;
        $folder->imap_uidvalidity  = $counter['uidvalidity'];
        $folder->imap_totalcount   = $counter['exists'];
        $folder->imap_status       = Felamimail_Model_Folder::IMAP_STATUS_OK;
        $folder->imap_timestamp    = Zend_Date::now();
        
        if (! array_key_exists('uidnext', $counter)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Non-standard IMAP server. Trying to guess uidnext by getting all Uids. Maybe it does not work.');
            $folder->imap_uidnext = 0;
        } else {
            $folder->imap_uidnext = $counter['uidnext'];
        }
                    
        return $folder;
    }
    
    /**
     * get folder status/values from imap server and update folder cache record in database
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_Imap|boolean $_imap
     * @return array  counters of totcal count and unread count
     */
    public function getCacheFolderCounter(Felamimail_Model_Folder $_folder)
    {
        $result = $this->_backend->getFolderCounter($_folder);
        
        return $result;
    }
    
    /**
     * get folder status/values from imap server and update folder cache record in database
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_Imap|boolean $_imap
     * @return Felamimail_Model_Folder
     */
    public function updateFolderCounters(Felamimail_Model_Folder $_folder, $_imap)
    {
        return Felamimail_Controller_Cache_Message::getInstance()->update($folder, 1);
    }
    
    
    /***************************** protected funcs *******************************/
    
    /**
     * create new folders or get existing folders from db and return record set
     *
     * @param array $_folders
     * @param Felamimail_Model_Account $_account
     * @param string $_parentFolder
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     * 
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
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($systemFolders, TRUE));
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_folders, TRUE));
        
        // do some mapping and save folder in db (if it doesn't exist
        foreach ($_folders as $folderData) {
            try {
                // decode folder name
                if (extension_loaded('mbstring')) {
                    $folderData['localName'] = mb_convert_encoding($folderData['localName'], "utf-8", "UTF7-IMAP");
                } else if (extension_loaded('imap')) {
                    $folderData['localName'] = iconv('ISO-8859-1', 'utf-8', imap_utf7_decode($folderData['localName']));
                }
                
                $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($_account->getId(), $folderData['globalName']);
                $folder->is_selectable = ($folderData['isSelectable'] == '1');
                $folder->has_children = ($folderData['hasChildren'] == '1');
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding cached folder ' . $folderData['globalName']);
                
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
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Adding new folder ' . $folderData['globalName'] . ' to cache.');
                    $folder = $this->_backend->create($folder);
                }
            }
            
            $result->addRecord($folder);
        }
        
        // remove folders that exist no longer on the imap server
        // @todo move this to another place (async services? mark as deleted?)
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'parent',      'operator' => 'equals', 'value' => $_parentFolder),
            array('field' => 'account_id',  'operator' => 'equals', 'value' => $_account->getId()),
        ));
        $cachedFolderIds = $this->_backend->search($filter, NULL, TRUE);
        if (count($cachedFolderIds) > count($result)) {
            // remove folders from cache
            $noLongerExistingIds = array_diff($cachedFolderIds, $result->getArrayOfIds());
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Removing ' . count($noLongerExistingIds) . ' no longer existing folder from cache.');
            $this->delete($noLongerExistingIds);
        }
        
        return $result;
    }
    
    /**
     * check if folder cache is updating atm
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     * 
     * @todo we should check the time of the last update to dynamically decide if process could have died
     */
    public function updateAllowed(Felamimail_Model_Folder $_folder)
    {
        // if cache status is CACHE_STATUS_UPDATING and timestamp is less than 5 minutes ago, don't update
        if ($_folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING &&
            ($_folder->cache_timestamp instanceof Zend_Date && $_folder->cache_timestamp->compare(Zend_Date::now()->subMinute(5)) == 1)
        ) {
            return false;
        }
                        
        $result = $this->_backend->lockFolder($_folder);
        
        return $result;
    }
    
}
