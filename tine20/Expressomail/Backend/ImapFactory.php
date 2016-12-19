<?php
/**
 * factory class for imap backends
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * An instance of the imap backend class should be created using this class
 * 
 * @package     Expressomail
 */
class Expressomail_Backend_ImapFactory
{
    /**
     * backend object instances
     */
    private static $_backends = array();
    
    /**
     * factory function to return a selected account/imap backend class
     *
     * @param   string|Expressomail_Model_Account $_accountId
     * @return  Expressomail_Backend_ImapProxy
     * @throws  Expressomail_Exception_IMAPInvalidCredentials
     */
    static public function factory($_accountId, $_readOnly = FALSE)
    {
        $accountId = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId->getId() : $_accountId;
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Getting IMAP backend for account id ' . $accountId);
        
        if (!isset(self::$_backends[$accountId])) {
            // get imap config from account
            $account = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId : Expressomail_Controller_Account::getInstance()->get($_accountId);
            $imapConfig = $account->getImapConfig();
            
            // we need to instantiate a new imap backend
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Connecting to server ' . $imapConfig['host'] . ':' . $imapConfig['port'] 
                . ' (' . ((array_key_exists('ssl', $imapConfig)) ? $imapConfig['ssl'] : 'none') . ')'
                . ' with username ' . $imapConfig['user']);
            
            try {
                self::$_backends[$accountId] = new Expressomail_Backend_ImapProxy($imapConfig,$_readOnly);
                if (Tinebase_Core::get(Tinebase_Core::SERVER_CLASS_NAME) !== 'ActiveSync_Server_Http') {
                    Expressomail_Controller_Account::getInstance()->updateCapabilities($account, self::$_backends[$accountId]);
                }    
                
            } catch (Expressomail_Exception_IMAPInvalidCredentials $feiic) {
                // add account and username to Expressomail_Exception_IMAPInvalidCredentials
                $feiic->setAccount($account)
                      ->setUsername($imapConfig['user']);
                throw $feiic;
            }
        }
        
        return self::$_backends[$accountId];
    }
}    
