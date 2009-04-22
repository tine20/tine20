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
 * @todo        implement this
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
     * @param unknown_type $_accountId
     * @param unknown_type $_folderName
     * 
     * @todo implement
     */
    public function getSubFolder($_accountId, $_folderName)
    {
        /*
        $imapConnection = $this->getImapConnection($_accountId);
        
        if(empty($folderName)) {
            $folder = $imapConnection->getFolders('', '%');
        } else {
            $folder = $imapConnection->getFolders($folderName.'/', '%');
        }
        */
    }
}
