<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for Felamimail, does event handling
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * main controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Felamimail_Model_Message';

    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
     */
    private function __construct() {
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
     * @return Felamimail_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()
            ->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch (get_class($_eventObject)) {
            case Tinebase_Event_User_ChangeCredentialCache::class:
                /** @var Tinebase_Event_User_ChangeCredentialCache $_eventObject */
                Felamimail_Controller_Account::getInstance()
                    ->updateCredentialsOfAllUserAccounts($_eventObject->oldCredentialCache);
                break;
            case Tinebase_Event_User_CreatedAccount::class:
                /** @var Tinebase_Event_User_CreatedAccount $_eventObject */
                if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}
                        ->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
                    Felamimail_Controller_Account::getInstance()->addSystemAccount($_eventObject->account);
                }
                break;
        }
    }

    public function handleAccountLogin(Tinebase_Model_FullUser $_account)
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
            Felamimail_Controller_Account::getInstance()->addSystemAccount($_account);
        }
    }
}
