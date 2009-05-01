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
 * @todo        add cleanup routine for deleted (by other clients)/outofdate  folders?
 */

/**
 * folder controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Folder extends Felamimail_Controller_Abstract implements Tinebase_Controller_SearchInterface
{
    /**
     * last search count (2 dim array: userId => backendId => count)
     *
     * @var array
     */
    protected $_lastSearchCount = array();
    
    /**
     * Enter description here...
     * 
     * @staticvar string
     * @todo get delimiter from backend?
     * 
     */
    const DELIMITER = '/';
    
    /**
     * folder backend
     *
     * @var Felamimail_Backend_Folder
     */
    protected $_folderBackend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Folder
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_folderBackend = new Felamimail_Backend_Folder();
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
     * @return Felamimail_Controller_Folder
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Folder();
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
        
        try {
            // try to get folders from imap backend
            $result = $this->getSubFolders($filterValues['globalname'], $filterValues['backend_id']);    
            
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            
            // get folders from db
            $filter = new Felamimail_Model_FolderFilter(array(
                array('field' => 'parent', 'operator' => 'equals', 'value' => $filterValues['globalname'])
            ));
            $result = $this->_folderBackend->search($filter);
        }
        
        $this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['backend_id']] = count($result);
        
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
        
        return $this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['backend_id']];
    }
    
    /**
     * create folder
     *
     * @param string $_folderName to create
     * @param string $_parentFolder
     * @param string $_backendId [optional]
     * @return Felamimail_Model_Folder
     * 
     * @todo get delimiter from backend?
     */
    public function create($_folderName, $_parentFolder = '', $_backendId = 'default')
    {
        $imap = $this->_getImapBackend($_backendId);
        $imap->createFolder($_folderName, $_parentFolder);
        
        // create new folder
        $folder = new Felamimail_Model_Folder(array(
            'localname'     => $_folderName,
            'globalname'    => $_parentFolder . self::DELIMITER . $_folderName,
            'backend_id'    => $_backendId
        ));           
        
        $folder = $this->_folderBackend->create($folder);
        return $folder;
    }
    
    /**
     * remove folder
     *
     * @param string $_folderName globalName (complete path) of folder to delete
     * @param string $_backendId
     */
    public function delete($_folderName, $_backendId = 'default')
    {
        $imap = $this->_getImapBackend($_backendId);
        $imap->removeFolder($_folderName);
        
        try {
            $folder = $this->_folderBackend->getByBackendAndGlobalName($_backendId, $_folderName);
            $this->_folderBackend->delete($folder->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Trying to delete non-existant folder.');
        }
    }
    
    /**
     * rename folder
     *
     * @param string $_oldFolderName globalName (complete path) of folder to rename
     * @param string $_newFolderName new globalName of folder
     * @param string $_backendId [optional]
     * @return Felamimail_Model_Folder
     */
    public function rename($_oldFolderName, $_newFolderName, $_backendId = 'default')
    {
        $imap = $this->_getImapBackend($_backendId);
        $imap->renameFolder($_oldFolderName, $_newFolderName);
        
        // rename folder in db
        try {
            $folder = $this->_folderBackend->getByBackendAndGlobalName($_backendId, $_oldFolderName);
            $folder->globalname = $_newFolderName;
            $globalNameParts = explode(self::DELIMITER, $_newFolderName);
            $folder->localname = array_pop($globalNameParts);
            $folder = $this->_folderBackend->update($folder);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Trying to rename non-existant folder.');
            throw $tenf;
        }
        
        return $folder;
    }

    /**
     * get (sub) folder and create folders in db backend
     *
     * @param string $_folderName
     * @param string $_backendId [optional]
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     * 
     * @todo delete all subfolders from db first?
     * @todo get delimiter from backend?
     */
    public function getSubFolders($_folderName = '', $_backendId = 'default')
    {
        $imap = $this->_getImapBackend($_backendId);
        
        if(empty($_folderName)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' get subfolders of root for backend ' . $_backendId);
            $folders = $imap->getFolders('', '%');
        } else {
            try {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' trying to get subfolders of ' . $_folderName . self::DELIMITER);
                $folders = $imap->getFolders($_folderName . self::DELIMITER, '%');
            } catch (Zend_Mail_Storage_Exception $zmse) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getMessage());
                $folders = array();
            }
        }
        
        // do some mapping and save folder in db
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Folder');
        
        foreach ($folders as $folderData) {
            try {
                $folder = $this->_folderBackend->getByBackendAndGlobalName($_backendId, $folderData['globalName']);
                $folder->is_selectable = ($folderData['isSelectable'] == '1');
                $folder->has_children = ($folderData['hasChildren'] == '1');
                
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create new folder
                $folder = new Felamimail_Model_Folder(array(
                    'localname'     => $folderData['localName'],
                    'globalname'    => $folderData['globalName'],
                    'is_selectable' => ($folderData['isSelectable'] == '1'),
                    'has_children'  => ($folderData['hasChildren'] == '1'),
                    'backend_id'    => $_backendId,
                    'timestamp'     => Zend_Date::now(),
                    'user_id'       => $this->_currentAccount->getId(),
                    'parent'        => $_folderName
                ));
                
                $folder = $this->_folderBackend->create($folder);
            }
            
            $result->addRecord($folder);
        }
        
        return $result;
    }
    
    /************************************* protected functions *************************************/
    
    /**
     * extract values from folder filter
     *
     * @param Felamimail_Model_FolderFilter $_filter
     * @return array (assoc) with filter values
     * 
     * @todo add AND/OR conditions for multiple filters of the same field?
     */
    protected function _extractFilter(Felamimail_Model_FolderFilter $_filter)
    {
        $result = array('backend_id' => 'default', 'globalname' => '');
        
        $filters = $_filter->getFilterObjects();
        foreach($filters as $filter) {
            switch($filter->getField()) {
                case 'backend_id':
                    $result['backend_id'] = $filter->getValue();
                    break;
                case 'globalname':
                    $result['globalname'] = $filter->getValue();
                    break;
            }
        }
        
        return $result;
    }
}
