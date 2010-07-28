<?php
/**
 * factory class for sieve backends
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * An instance of the sieve backend class should be created using this class
 * 
 * @package     Felamimail
 */
class Felamimail_Backend_SieveFactory
{
    /**
     * backend object instances
     */
    private static $_backends = array();
    
    /**
     * factory function to return a selected account/imap backend class
     *
     * @param   string|Felamimail_Model_Account $_accountId
     * @return  Felamimail_Backend_Sieve
     */
    static public function factory($_accountId)
    {
        $accountId = ($_accountId instanceof Felamimail_Model_Account) ? $_accountId->getId() : $_accountId;
        
        if (! isset(self::$_backends[$accountId])) {
            $account = ($_accountId instanceof Felamimail_Model_Account) ? $_accountId : Felamimail_Controller_Account::getInstance()->get($accountId);
                    
            // get imap config from account to connect with sieve server
            $sieveConfig = $account->getSieveConfig();
            
            // we need to instantiate a new sieve backend
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Connecting to server ' . $sieveConfig['host'] . ':' . $sieveConfig['port'] 
                . ' (secure: ' . ((array_key_exists('ssl', $sieveConfig) && $sieveConfig['ssl'] !== FALSE) ? $sieveConfig['ssl'] : 'none') 
                . ') with user ' . $sieveConfig['username']);
            
            self::$_backends[$accountId] = new Felamimail_Backend_Sieve($sieveConfig);
        }
        
        return self::$_backends[$accountId];
    }
}
