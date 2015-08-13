<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        use other/shared namespaces
 * @todo        extend Tinebase_Controller_Record Abstract and add modlog fields and acl
 */

/**
 * folder controller for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Controller
 */
class Expressomail_Controller_Folder extends Tinebase_Controller_Abstract implements Tinebase_Controller_SearchInterface
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Expressomail';

    /**
     * last search count (2 dim array: userId => accountId => count)
     *
     * @var array
     */
    protected $_lastSearchCount = array();

    /**
     * folder delimiter/separator
     *
     * @var string
     */
    protected $_delimiter = '/';

    /**
     * folder backend
     *
     * @var Expressomail_Backend_Folder
     */
    protected $_backend = NULL;

    /**
     * cache controller
     *
     * @var Expressomail_Controller_Cache_Folder
     */
    protected $_cacheController = NULL;

    /**
     * holds the instance of the singleton
     *
     * @var Expressomail_Controller_Folder
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_backend = new Expressomail_Backend_Folder();
        //$this->_cacheController = Expressomail_Controller_Cache_Folder::getInstance();
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
     * get folder status/values from imap server and update folder cache record in database
     *
     * @param Expressomail_Model_Folder $_folder
     * @param Expressomail_Backend_Imap|boolean $_imap
     * @return Expressomail_Model_Folder
     *
     * @todo It should'nt access Imap Backend directly. Method should be at Expressomail_Backend_Folder
     */
    public function getIMAPFolderCounter(Expressomail_Model_Folder $_folder)
    {
        $folder = ($_folder instanceof Expressomail_Model_Folder) ? $_folder : Expressomail_Controller_Folder::getInstance()->get($_folder);

        $imap = Expressomail_Backend_ImapFactory::factory($folder->account_id);

        // get folder values / status from imap server
        $counter = $imap->examineFolder(Expressomail_Model_Folder::encodeFolderName($folder->globalname));

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .  ' ' . print_r($counter, TRUE));

        // check validity
        $folder->cache_uidvalidity = $folder->imap_uidvalidity;
        $folder->imap_uidvalidity  = $counter['uidvalidity'];
        $folder->imap_totalcount   = $counter['exists'];
        $folder->imap_status       = Expressomail_Model_Folder::IMAP_STATUS_OK;
        $folder->imap_timestamp    = Tinebase_DateTime::now();

        return $folder;
    }

    /**
     * the singleton pattern
     *
     * @return Expressomail_Controller_Folder
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressomail_Controller_Folder();
        }

        return self::$_instance;
    }

    /************************************* public functions *************************************/

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        $filterValues = $this->_extractFilter($_filter);

        if (empty($filterValues['account_id'])) {
            throw new Expressomail_Exception('No account id set in search filter. Check default account preference!');
        }

        // get folders from db
        $filter = new Expressomail_Model_FolderFilter(array(
            array('field' => 'parent',      'operator' => 'equals', 'value' => $filterValues['globalname']),
            array('field' => 'account_id',  'operator' => 'equals', 'value' => $filterValues['account_id'])
        ));
        $result = $this->_backend->search($filter);
        if (count($result) == 0) {
            // try to get folders from imap server
            $result = $this->updateFolderCache($filterValues['account_id'], $filterValues['globalname']);
        }

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' folder search result count: ' . count($result) );

        $this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['account_id']] = count($result);

        // Folders already sorted by Imap Backend
        //$result = $this->_sortFolders($result, $filterValues['globalname']);

        return $result;
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $filterValues = $this->_extractFilter($_filter);

        return $this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['account_id']];
    }

     /**
     * get folder cache counts from database
     *
     * @param Expressomail_Model_Folder $_folder
     * @param Expressomail_Backend_Imap|boolean $_imap
     * @return array  counters of totcal count and unread count
     */
    public function getCacheFolderCounter(Expressomail_Model_Folder $_folder)
    {
        $result = $this->_backend->getFolderCounter($_folder);

        return $result;
    }

    /**
     * get single folder
     *
     * @param string $_id
     * @return Expressomail_Model_Folder
     */
    public function get($_id, $_useCache = TRUE)
    {
        return $this->_backend->get($_id, $_useCache);
    }

    /**
     * get multiple folders
     *
     * @param string|array $_ids
     * @return Tinebase_RecordSet
     */
    public function getMultiple($_ids)
    {
        return $this->_backend->getMultiple($_ids);
    }

    /**
     * get saved folder record by backend and globalname
     *
     * @param  mixed   $_accountId
     * @param  string  $_globalName
     * @return Expressomail_Model_Folder
     */
    public function getByBackendAndGlobalName($_accountId, $_globalName, $_useCache = TRUE)
    {
        $accountId = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId->getId() : $_accountId;
        $result = $this->_backend->get($this->_backend->encodeFolderUid(Expressomail_Model_Folder::encodeFolderName($_globalName), $accountId), $_useCache);

        return $result;
    }

    /**
     * create folder
     *
     * @param string|Expressomail_Model_Account $_accountId
     * @param string $_folderName to create
     * @param string $_parentFolder
     * @return Expressomail_Model_Folder
     * @throws Expressomail_Exception_IMAPServiceUnavailable
     */
    public function create($_accountId, $_folderName, $_parentFolder = '')
    {
        $account = ($_accountId instanceof Expressomail_Controller_Account) ? $_accountId : Expressomail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $account->delimiter;

        $foldername = $this->_prepareFolderName($_folderName);
        $globalname = (empty($_parentFolder)) ? $foldername : $_parentFolder . $this->_delimiter . $foldername;
        
        if($this->isFolderNameSystemFolder($foldername)){
            $translation = Tinebase_Translation::getTranslation('Expressomail');
            throw new Expressomail_Exception_IMAPFolderDuplicated($translation->_('cannot create folder') . ' ' . $foldername . ', ' .$translation->_('it already exists.'));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to create new folder: ' . $globalname . ' (parent: ' . $_parentFolder . ')');

        $imap = Expressomail_Backend_ImapFactory::factory($account);

        try {
            $imap->createFolder(
                Expressomail_Model_Folder::encodeFolderName($foldername),
                (empty($_parentFolder)) ? NULL : Expressomail_Model_Folder::encodeFolderName($_parentFolder),
                $this->_delimiter
            );

            // create new folder
            $folder = new Expressomail_Model_Folder(array(
                'localname'     => $foldername,
                'globalname'    => $globalname,
                'account_id'    => $account->getId(),
                'parent'        => $_parentFolder
            ));

            $folder = $this->_backend->create($folder);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Create new folder: ' . $globalname);

        } catch (Zend_Mail_Storage_Exception $zmse) {
            // perhaps the folder already exists
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Could not create new folder: ' . $globalname . ' (' . $zmse->getMessage() . ')');
            
            if (!$imap->checkACL(Expressomail_Model_Folder::encodeFolderName($_parentFolder), Expressomail_Backend_Imap::ACLWRITE))
            {
                throw new Expressomail_Exception_IMAPFolderDuplicated('You dont have permission to create folder here.');
            }
            
            throw new Expressomail_Exception_IMAPFolderDuplicated($zmse->getMessage());
        }

        // update parent (has_children)
        $this->_updateHasChildren($_accountId, $_parentFolder, 1);

        return $folder;
    }

    /**
     * prepare foldername given by user (remove some bad chars)
     *
     * @param string $_folderName
     * @return string
     */
    protected function _prepareFolderName($_folderName)
    {
        $result = stripslashes($_folderName);
        $result = str_replace(array('/', $this->_delimiter), '', $result);
        return $result;
    }
    
    /**
     * create new folders or get existing folders from db and return record set
     *
     * @param array $_folders
     * @param Expressomail_Model_Account $_account
     * @param string $_parentFolder
     * @return Tinebase_Record_RecordSet of Expressomail_Model_Folder
     * 
     * @todo    move delete sync to extra function
     */
    protected function _getOrCreateFolders(array $_folders, $_account, $_parentFolder)
    {
        $parentFolder = ($_parentFolder !== NULL) ? $_parentFolder : '';
        $result = new Tinebase_Record_RecordSet('Expressomail_Model_Folder');
        $systemFolders = Expressomail_Controller_Folder::getInstance()->getSystemFolders($_account);
        
        // get configured account standard folders here
        if (strtolower($_account->sent_folder) != $systemFolders[2]) {
            $systemFolders[2] = strtolower($_account->sent_folder);
        }
        if (isset($systemFolders[5]) && strtolower($_account->trash_folder) != $systemFolders[5]) {
            $systemFolders[5] = strtolower($_account->trash_folder);
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($systemFolders, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_folders, TRUE));
        
        // do some mapping and save folder in db (if it doesn't exist
        foreach ($_folders as $folderData) {
            try {
                $folderData['localName'] = Expressomail_Model_Folder::decodeFolderName($folderData['localName']);
                $folderData['globalName'] = Expressomail_Model_Folder::decodeFolderName($folderData['globalName']);
                
                $folder = Expressomail_Controller_Folder::getInstance()->getByBackendAndGlobalName($_account->getId(), $folderData['globalName']);
                
                $folder->is_selectable = $folderData['isSelectable'];
                $folder->imap_status   = Expressomail_Model_Folder::IMAP_STATUS_OK;
                $folder->has_children  = ($folderData['hasChildren'] == '1');
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Update cached folder ' . $folderData['globalName']);
                
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create new folder
                if (empty($folderData['localName'])) {
                    // skip
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Do not add folder ' . $folderData['globalName'] 
                        . '. Localname is empty.');
                    continue;
                    
                } else {
                    $delimiter = (strlen($folderData['delimiter']) === 1) ? $folderData['delimiter'] : '';
                    
                    $folder = new Expressomail_Model_Folder(array(
                        'localname'         => $folderData['localName'],
                        'globalname'        => $folderData['globalName'],
                        'is_selectable'     => $isSelectable,
                        'has_children'      => ($folderData['hasChildren'] == '1'),
                        'account_id'        => $_account->getId(),
                        'imap_timestamp'    => Tinebase_DateTime::now(),
                        'imap_status'       => Expressomail_Model_Folder::IMAP_STATUS_OK,
                        'user_id'           => Tinebase_Core::getUser()->getId(),
                        'parent'            => $parentFolder,
                        'system_folder'     => in_array(strtolower($folderData['localName']), $systemFolders),
                        'delimiter'         => $delimiter,
                    ));
                    
                    // update delimiter setting of account
                    if ($folder->delimiter && $folder->delimiter !== $_account->delimiter && $folder->localname === 'INBOX') {
                        $_account->delimiter = $folder->delimiter;
                        $_account = Expressomail_Controller_Account::getInstance()->update($_account);
                    }
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Adding new folder ' . $folderData['globalName'] . ' to cache.');
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . print_r($folder->toArray(), true));
                    
                    $folder = $this->_backend->create($folder);
                }
            }
            
            $result->addRecord($folder);
        }
        
        return $result;
    }

    /**
     * update single folder
     *
     * @param Expressomail_Model_Folder $_folder
     * @return Expressomail_Model_Folder
     */
    public function update(Expressomail_Model_Folder $_folder)
    {
        return $this->_backend->update($_folder);
    }
    
    /***************************** public funcs *******************************/
    
    /**
     * get (sub) folder and create folders in db backend cache
     *
     * @param  mixed   $_accountId
     * @param  string  $_folderName global name
     * @param  boolean $_recursive 
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     */
    public function updateFolderCache($_accountId, $_folderName = '', $_recursive = FALSE)
    {
        $account = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId : Expressomail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $account->delimiter;
        
        try {
            $folders = $this->_backend->getFolders($account, $_folderName);
            $result = $this->_getOrCreateFolders($folders, $account, $_folderName);
            
//            $hasChildren = (empty($folders) || count($folders) > 0 && count($result) == 0) ? 0 : 1;
//            $this->_updateHasChildren($_accountId, $_folderName, $hasChildren);
//            
//            if ($_recursive) {
//                $this->_updateRecursive($account, $result);
//            }
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' IMAP Protocol Exception: ' . $zmpe->getMessage());
            $result = new Tinebase_Record_RecordSet('Expressomail_Model_Folder');
        }
        
        return $result;
    }
    

    /**
     * update single folders counter
     *
     * @param  mixed  $_folderId
     * @param  array  $_counters
     * @return Expressomail_Model_Folder
     */
    public function updateFolderCounter($_folderId, array $_counters)
    {
    	if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  " starting update of folder counter with id {$_folderId}");
        return $this->_backend->updateFolderCounter($_folderId, $_counters);
    }

    /**
     * remove folder
     *
     * @param string $_accountId
     * @param string $_folderName globalName (complete path) of folder to delete
     * @param boolean $_recursive
     * @return void
     */
    public function delete($_accountId, $_folderName, $_recursive = FALSE)
    {
        try {
            $folder = $this->getByBackendAndGlobalName($_accountId, $_folderName);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Trying to delete non-existant folder ' . $_folderName);
            $folder = NULL;
        }

        // check if folder has subfolders and throw exception if that is the case / OR: delete subfolders if recursive param is TRUE
        // @todo this should not be a Tinebase_Exception_AccessDenied -> we have to create a new exception and call the fmail exception handler when deleting/adding/renaming folders
        $subfolders = $this->getSubfolders($_accountId, $_folderName);
        if (count($subfolders) > 0 && $folder) {
            if ($_recursive) {
                $this->deleteSubfolders($folder, $subfolders);
            } else {
                throw new Tinebase_Exception_AccessDenied('Could not delete folder ' . $_folderName . ' because it has subfolders.');
            }
        }

        if ($folder) {
            $cache = Tinebase_Core::getCache();
            $cacheKey = 'Expressomail_Model_Folder_'.$folder->id;
            $cache->remove($cacheKey);
        }

        $this->_deleteFolderOnIMAP($_accountId, $_folderName);
    }

    /**
     * rename folder on imap server
     *
     * @param string|Expressomail_Model_Account $_account
     * @param string $_folderName
     * @throws Expressomail_Exception_IMAPFolderNotFound
     * @throws Expressomail_Exception_IMAP
     */
    protected function _deleteFolderOnIMAP($_accountId, $_folderName)
    {
        try {
            $imap = Expressomail_Backend_ImapFactory::factory($_accountId);
            $imap->removeFolder(Expressomail_Model_Folder::encodeFolderName($_folderName));
        } catch (Zend_Mail_Storage_Exception $zmse) {
            try {
                $imap->selectFolder(Expressomail_Model_Folder::encodeFolderName($_folderName));
            } catch (Zend_Mail_Storage_Exception $zmse2) {
                throw new Expressomail_Exception_IMAPFolderNotFound('Folder not found (error: ' . $zmse2->getMessage() . ').');
            }

            throw new Expressomail_Exception_IMAP('Could not delete folder ' . $_folderName . '. IMAP Error: ' . $zmse->getMessage());
        }
    }

    /**
     * rename folder
     *
     * @param string $_accountId
     * @param string $_newLocalName
     * @param string $_oldGlobalName
     * @return Expressomail_Model_Folder
     */
    public function rename($_accountId, $_newLocalName, $_oldGlobalName)
    {
        $account = Expressomail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $account->delimiter;

        $newLocalName = $this->_prepareFolderName($_newLocalName);
        $newGlobalName = $this->_buildNewGlobalName($newLocalName, $_oldGlobalName);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Renaming ... ' . $_oldGlobalName . ' -> ' . $newGlobalName);

        $this->_renameFolderOnIMAP($account, $newGlobalName, $_oldGlobalName);
        $folder = $this->getByBackendAndGlobalName($_accountId, $newGlobalName, FALSE);
        $this->_updateSubfoldersAfterRename($account, $newGlobalName, $_oldGlobalName);
        $folder->recent = $_oldGlobalName;
        return $folder;
    }

        /**
     * Get Acls for a folder
     *
     * @param string $_accountId
     * @param string $_globalName
     * @return
     */
    public function getAcls($_accountId, $_globalName)
    {
        $_account = Expressomail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $_account->delimiter;

        //$foldername = $this->_prepareFolderName($_globalName);
        $foldername = $_globalName;

       // if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Renaming ... ' . $_oldGlobalName . ' -> ' . $newGlobalName);

        $imap = Expressomail_Backend_ImapFactory::factory($_account);
        $return = $imap->getFolderAcls(Expressomail_Model_Folder::encodeFolderName($foldername));

        return $return;
    }

    /**
    * get folder ids of all inboxes for accounts of current user
    *
    * @return array
    */
    public function getAllInboxes()
    {
        return $this->_backend->getAllInboxes();
    }

    /**
     * Get Acls for a folder
     *
     * @param string $_accountId
     * @return
     */
    public function getUsersWithSendAsAcl($_accountId)
    {
        $_account = Expressomail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $_account->delimiter;

        $filter = new Expressomail_Model_FolderFilter(array(
            array('field' => 'parent', 'operator' => 'equals',  'value' => 'user'),
            array('field' => 'account_id', 'operator' => 'equals',  'value' => $_accountId),
        ));

        $folders =  $this->_backend->search($filter);
        $imap = Expressomail_Backend_ImapFactory::factory($_account->id);
        $return = $imap->getUsersWithSendAsAcl($folders->toArray());
        return $return;
    }

    /** Set Acls for a folder
     *
     * @param string $_accountId
     * @param string $_globalName
     * @param Array  $_acls
     * @return
     */
    public function setAcls($_accountId, $_globalName, $_acls)
    {
        $_account = Expressomail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $_account->delimiter;

        //$foldername = $this->_prepareFolderName($_globalName);
        $foldername = $_globalName;

       // if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Renaming ... ' . $_oldGlobalName . ' -> ' . $newGlobalName);

        $imap = Expressomail_Backend_ImapFactory::factory($_account);
        $return = $imap->setFolderAcls(Expressomail_Model_Folder::encodeFolderName($foldername),$_acls);

        return $return;
    }

    /**
     * remove old localname and build new globalname
     *
     * @param string $_newLocalName
     * @param string $_oldGlobalName
     * @return string
     *
     * @todo generalize this
     */
    protected function _buildNewGlobalName($_newLocalName, $_oldGlobalName)
    {
        $globalNameParts = explode($this->_delimiter, $_oldGlobalName);
        array_pop($globalNameParts);
        if (! empty($_newLocalName)) {
            array_push($globalNameParts, $_newLocalName);
        }
        $newGlobalName = implode($this->_delimiter, $globalNameParts);

        return $newGlobalName;
    }

    /**
     * rename folder on imap server
     *
     * @param Expressomail_Model_Account $_account
     * @param string $_newGlobalName
     * @param string $_oldGlobalName
     * @throws Expressomail_Exception_IMAPFolderNotFound
     */
    protected function _renameFolderOnIMAP(Expressomail_Model_Account $_account, $_newGlobalName, $_oldGlobalName)
    {
        $imap = Expressomail_Backend_ImapFactory::factory($_account);

        try {
            $imap->renameFolder(Expressomail_Model_Folder::encodeFolderName($_oldGlobalName), Expressomail_Model_Folder::encodeFolderName($_newGlobalName));
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Folder could have been renamed / deleted by another client.');

            throw new Expressomail_Exception_IMAPFolderNotFound('Folder not found (error: ' . $zmse->getMessage() . ').');
        }
    }

    /**
     * loop subfolders (recursive) and replace new localname in globalname path
     *
     * @param Expressomail_Model_Account $_account
     * @param string $_newGlobalName
     * @param string $_oldGlobalName
     */
    protected function _updateSubfoldersAfterRename(Expressomail_Model_Account $_account, $_newGlobalName, $_oldGlobalName)
    {
        $subfolders = $this->getSubfolders($_account, $_oldGlobalName);
        foreach ($subfolders as $subfolder) {
            if ($_newGlobalName != $subfolder->globalname) {
                $newSubfolderGlobalname = str_replace($_oldGlobalName, $_newGlobalName, $subfolder->globalname);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Renaming ... ' . $subfolder->globalname . ' -> ' . $newSubfolderGlobalname);

                $subfolder->globalname = $newSubfolderGlobalname;
                $this->update($subfolder);
            }
        }
    }

    /**
     * delete all messages in one folder -> be careful, they are completly removed and not moved to trash
     * -> delete subfolders if param set
     *
     * @param string $_folderId
     * @param boolean $_deleteSubfolders
     * @return Expressomail_Model_Folder
     * @throws Expressomail_Exception_IMAPServiceUnavailable
     */
    public function emptyFolder($_folderId, $_deleteSubfolders = FALSE)
    {
        $folder = $this->_backend->get($_folderId);
        $account = Expressomail_Controller_Account::getInstance()->get($folder->account_id);

        if ($folder) {
            $cache = Tinebase_Core::getCache();
            $cacheKey = 'Expressomail_Model_Folder_'.$folder->id;
            $cache->remove($cacheKey);
        }

        $imap = Expressomail_Backend_ImapFactory::factory($account);
        try {
            // try to delete messages in imap folder
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete all messages in folder ' . $folder->globalname);
            $imap->emptyFolder(Expressomail_Model_Folder::encodeFolderName($folder->globalname));

        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            throw new Expressomail_Exception_IMAPServiceUnavailable($zmpe->getMessage());
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Folder could be empty (' . $zmse->getMessage() . ')');
        }

        if ($_deleteSubfolders) {
            $this->deleteSubfolders($folder);
        }

        return $folder;
    }

    /**
     * delete subfolders recursivly
     *
     * @param Expressomail_Model_Folder $_folder
     * @param Tinebase_Record_RecordSet $_subfolders if we know the subfolders already
     */
    public function deleteSubfolders(Expressomail_Model_Folder $_folder, $_subfolders = NULL)
    {
        $account = Expressomail_Controller_Account::getInstance()->get($_folder->account_id);
        $subfolders = ($_subfolders === NULL) ? $this->getSubfolders($account, $_folder->globalname) : $_subfolders;

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete ' . count($subfolders) . ' subfolders of ' . $_folder->globalname);
        foreach ($subfolders as $subfolder) {
            $this->delete($account, $subfolder->globalname, TRUE);
        }
    }

    /**
     * get system folders
     *
     * @param string $_accountId
     * @return array
     *
     * @todo    update these from account settings
     */
    public function getSystemFolders($_accountId)
    {
        return Expressomail_Backend_FolderComparator::$SYSTEM_FOLDERS;
    }
    
    
    /**
     * check if folder name is system folder or its translation (case insensitive)
     *
     * @param string $folderName
     * @return bool
     */
    public function isFolderNameSystemFolder($folderName){
        
        
        $systemFolders = $this->getSystemFolders(null);
        if(in_array(strtolower($folderName), $systemFolders)){
            return TRUE;
        }
        
        $translation = Tinebase_Translation::getTranslation('Expressomail');
        if(strtolower($translation->_('INBOX')) === strtolower($folderName)){
            return TRUE;
        }
        foreach ($systemFolders as $systemFolder) {
            
            if( strtolower($folderName) == strtolower( $translation->_( ucfirst(strtolower($systemFolder)) ) ) ){
                return TRUE;
            }
        }
        return FALSE;
    }

    /************************************* protected functions *************************************/

    /**
     * extract values from folder filter
     *
     * @param Expressomail_Model_FolderFilter $_filter
     * @return array (assoc) with filter values
     *
     * @todo add AND/OR conditions for multiple filters of the same field?
     */
    protected function _extractFilter(Expressomail_Model_FolderFilter $_filter)
    {
        $result = array(
            'account_id' => Tinebase_Core::getPreference('Expressomail')->{Expressomail_Preference::DEFAULTACCOUNT},
            'globalname' => ''
        );

        $filters = $_filter->getFilterObjects();

        foreach($filters as $filter) {
            switch($filter->getField()) {
                case 'account_id':
                    $result['account_id'] = $filter->getValue();
                    break;
                case 'globalname':
                    $result['globalname'] = $filter->getValue();
                    break;
            }
        }

        return $result;
    }

//    /**
//     * sort folder record set
//     * - begin with INBOX + other standard/system folders, add other folders
//     *
//     * @param Tinebase_Record_RecordSet $_folders
//     * @param string $_parentFolder
//     * @return Tinebase_Record_RecordSet
//     */
//    protected function _sortFolders(Tinebase_Record_RecordSet $_folders, $_parentFolder)
//    {
//        $sortedFolders = new Tinebase_Record_RecordSet('Expressomail_Model_Folder');
//
//        $_folders->sort('localname', 'ASC', 'natcasesort');
//        $_folders->addIndices(array('globalname'));
//
//        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Sorting subfolders of "' . $_parentFolder . '".');
//
//        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_folders->globalname, TRUE));
//
//        foreach ($this->_systemFolders as $systemFolderName) {
//            $folders = $_folders->filter('globalname', '@^' . $systemFolderName . '$@i', TRUE);
//            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $systemFolderName . ' => ' . print_r($folders->toArray(), TRUE));
//            if (count($folders) > 0) {
//                $sortedFolders->addRecord($folders->getFirstRecord());
//            }
//        }
//
//        foreach ($_folders as $folder) {
//            if (! in_array(strtolower($folder->globalname), $this->_systemFolders)) {
//                $sortedFolders->addRecord($folder);
//            }
//        }
//
//        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($sortedFolders->globalname, TRUE));
//
//        return $sortedFolders;
//    }

    /**
     * update folder value has_children
     *
     * @param string $_globalname
     * @param integer $_value
     * @return null|Expressomail_Model_Folder
     */
    protected function _updateHasChildren($_accountId, $_globalname, $_value = NULL)
    {
        if (empty($_globalname)) {
            return NULL;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Checking has_children for folder ' . $_globalname);

        $account = Expressomail_Controller_Account::getInstance()->get($_accountId);

        if (! $account->has_children_support) {
            return NULL;
        }

        try {
            $folder = $this->getByBackendAndGlobalName($_accountId, $_globalname);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' .
                $tenf->getMessage());
            return NULL;
        }
        if ($_value === NULL) {
            // check if folder has children by searching in db
            $subfolders = $this->getSubfolders($account, $_globalname);
            $value = (count($subfolders) > 0) ? 1 : 0;
        } else {
            $value = $_value;
        }

        if ($folder->has_children != $value) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set new has_children value for folder ' . $_globalname . ': ' . $value);
            $folder->has_children = $value;
            $folder = $this->update($folder);
        }

        return $folder;
    }

    /**
     * get subfolders
     *
     * @param string|Expressomail_Model_Account $_account
     * @param string $_globalname
     * @return Tinebase_Record_RecordSet
     */
    public function getSubfolders($_account, $_globalname)
    {
        $account = ($_account instanceof Expressomail_Model_Account) ? $_account : Expressomail_Controller_Account::getInstance()->get($_account);
        $globalname = (empty($_globalname)) ? '' : $_globalname . $account->delimiter;

        $filter = new Expressomail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'startswith',  'value' => $globalname),
            array('field' => 'account_id', 'operator' => 'equals',      'value' => $account->getId()),
        ));

        return $this->_backend->search($filter);
    }

     /**
     * get folder IMAP status (MESSAGES RECENT UNSEEN)
     * 
     * @param string $_accountId
     * @param string $_globalname
     * @return string imap_status | false
     *
     */
    public static function getFolderImapStatus($_accountId, $_globalname)
    {
        $imap = Expressomail_Backend_ImapFactory::factory($_accountId);
        try {
            return $imap->getFolderStatus($_globalname);            
        } catch(Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  ' Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * get folder status and return all folders where something needs to be done
     *
     * @param Expressomail_Model_FolderFilter  $_filter
     * @return Tinebase_Record_RecordSet
     *
     * @todo find a way to detect diferences in folders content
     */
    public function getFolderStatus(Expressomail_Model_FolderFilter $_filter)
    {

        // add user account ids to filter and use the folder backend to search as the folder controller has some special handling in its search function
        $_filter->createFilter(array('field' => 'account_id', 'operator' => 'in', 'value' => Expressomail_Controller_Account::getInstance()->search()->getArrayOfIds()));
        $folders = $this->_backend->search($_filter, FALSE);

        $result = new Tinebase_Record_RecordSet('Expressomail_Model_Folder');
        foreach ($folders as $folder) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .  ' Checking folder ' . $folder->globalname);

            $folder = $this->getIMAPFolderCounter($folder);

            // we don't have cache, checking only recent messages
            if ($folder->cache_recentcount > 0) {
                $result->addRecord($folder);
            }
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .  " Found " . count($result) . ' folders that need an update.');

        return $result;
    }

    /**
     * get all folders for ActiveSync
     *
     * @param string|Expressomail_Model_Account $_account
     * @return array
     */
    public function getFoldersAS($_account)
    {
    	$account = ($_account instanceof Expressomail_Model_Account) ? $_account : Expressomail_Controller_Account::getInstance()->get($_account);

    	return $this->_backend->getAllFoldersAS($account);
    }
}
