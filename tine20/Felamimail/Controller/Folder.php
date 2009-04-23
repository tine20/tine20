<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * folder controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Folder extends Felamimail_Controller_Abstract
{
    /**
     * holdes the instance of the singleton
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
    
    /**
     * create folder
     *
     * @param string $_folderName to create
     * @param string $_parentFolder
     * @param string $_backendId [optional]
     */
    public function createFolder($_folderName, $_parentFolder = '', $_backendId = 'default')
    {
        $imap = $this->_getBackend($_backendId);
        
        $imap->createFolder($_folderName, $_parentFolder);
    }
    
    /**
     * remove folder
     *
     * @param string $_folderName globalName (complete path) of folder to delete
     * @param string $_backendId
     * 
     * @todo add test
     */
    public function removeFolder($_folderName, $_backendId = 'default')
    {
        $imap = $this->_getBackend($_backendId);
        
        $imap->removeFolder($_folderName);
    }
    
    /**
     * rename folder
     *
     * @param string $_oldFolderName globalName (complete path) of folder to rename
     * @param string $_newFolderName new globalName of folder
     * @param string $_backendId [optional]
     */
    public function renameFolder($_oldFolderName, $_newFolderName, $_backendId = 'default')
    {
        $imap = $this->_getBackend($_backendId);
        
        $imap->renameFolder($_oldFolderName, $_newFolderName);
    }

    /**
     * get (sub) folder
     *
     * @param string $_folderName
     * @param string $_backendId [optional]
     * @param string $_delimiter [optional]
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     * 
     * @todo get delimiter from backend?
     */
    public function getSubFolders($_folderName = '', $_backendId = 'default', $_delimiter = '/')
    {
        $imap = $this->_getBackend($_backendId);
        
        if(empty($_folderName)) {
            $folder = $imap->getFolders('', '%');
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' trying to get subfolders of ' . $_folderName . $_delimiter);
            $folder = $imap->getFolders($_folderName . $_delimiter, '%');
        }
        
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Folder', $folder);
        
        return $result;
    }
}
