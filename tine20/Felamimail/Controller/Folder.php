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
 * @todo        finish implementation
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
     * @param $_config imap config data
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
     * @todo implement
     */
    public function createFolder()
    {
        
    }
    
    /**
     * create folder
     *
     * @todo implement
     */
    public function deleteFolder()
    {
        
    }
    
    /**
     * create folder
     *
     * @todo implement
     */
    public function renameFolder()
    {
        
    }

    /**
     * get (sub) folder
     *
     * @param string $_backendId
     * @param string $_folderName
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Folder
     * 
     * @todo get delimiter from backend?
     */
    public function getSubFolders($_backendId = 'default', $_folderName = '', $_delimiter = '/')
    {
        $imapConnection = $this->_getBackend($_backendId);
        
        if(empty($_folderName)) {
            $folder = $imapConnection->getFolders('', '%');
        } else {
            $folder = $imapConnection->getFolders($_folderName . $_delimiter, '%');
        }
        
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Folder', $folder);
        
        return $result;
    }
}
