<?php
/**
 * factory class for imap backends
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Factory.php 7539 2009-04-01 11:01:13Z p.schuele@metaways.de $
 * 
 * @todo        get matching config for given backendId
 * @todo        add something like 'useCustomCredentials' to use config imap settings instead of account settings from db
 * @todo        add support for multiple accounts per user
 * @todo        do we need a backend for each folder on the mailserver? 
 */

/**
 * An instance of the imap backendclass should be created using this class
 * 
 * @package     Felamimail
 */
class Felamimail_Backend_ImapFactory
{
    /**
     * object instance
     *
     * @var array of Felamimail_Backend_Imap
     */
    private static $_instance = NULL;
    
    /**
     * backend object instances
     */
    private static $_backends = array();
    
    /**
     * factory function to return a selected contacts backend class
     *
     * @param   string $_backendId
     * @return  Felamimail_Backend_Imap
     */
    static public function factory ($_backendId)
    {
        if (!isset(self::$_backends[$_backendId])) {
            // we need to instantiate a new imap backend
            $imapConfig = Tinebase_Core::getConfig()->imap;
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Connecting to server ' . $imapConfig->host . ':' . $imapConfig->port . ' with username ' . $imapConfig->user);
            
            self::$_backends[$_backendId] = new Felamimail_Backend_Imap($imapConfig->toArray());
        }
        
        $instance = self::$_backends[$_backendId];
        return $instance;
    }
}    
