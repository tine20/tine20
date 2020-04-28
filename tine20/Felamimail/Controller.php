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
            ->debug(__METHOD__ . '::' . __LINE__ . ' Handle event of type ' . get_class($_eventObject));
        
        switch (get_class($_eventObject)) {
            case Tinebase_Event_User_ChangeCredentialCache::class:
                /** @var Tinebase_Event_User_ChangeCredentialCache $_eventObject */
                Felamimail_Controller_Account::getInstance()
                    ->updateCredentialsOfAllUserAccounts($_eventObject->oldCredentialCache);
                break;
            case Admin_Event_AddAccount::class:
                /** @var Tinebase_Event_User_CreatedAccount $_eventObject */
                if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}
                        ->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
                    Felamimail_Controller_Account::getInstance()->addSystemAccount($_eventObject->account,
                        $_eventObject->pwd);
                }
                break;
            case Admin_Event_UpdateAccount::class:
                /** @var Admin_Event_UpdateAccount $_eventObject */
                if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}
                    ->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
                    Felamimail_Controller_Account::getInstance()->updateSystemAccount(
                        $_eventObject->account, $_eventObject->oldAccount);
                }
                break;
            case Admin_Event_BeforeDeleteAccount::class:
                /** @var Admin_Event_BeforeDeleteAccount $_eventObject */
                if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}
                    ->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
                    try {
                        $systemAccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($_eventObject->account);
                        if ($systemAccount) {
                            Admin_Controller_EmailAccount::getInstance()->delete($systemAccount->getId());
                        }
                    } catch (Tinebase_Exception_AccessDenied $tead) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()
                            ->warn(__METHOD__ . '::' . __LINE__ . ' Could not delete system account: ' . $tead->getMessage());
                    }
                }
                break;
        }
    }

    public function handleAccountLogin(Tinebase_Model_FullUser $_account, $pwd)
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
            // this is sort of a wired flag to make addSystemAccount do its actual work
            $_account->imapUser = new Tinebase_Model_EmailUser(null, true);
            Felamimail_Controller_Account::getInstance()->addSystemAccount($_account, $pwd);
        }
    }

    public function truncateEmailCache()
    {
        $db = Tinebase_Core::getDb();

        // disable fk checks
        $db->query("SET FOREIGN_KEY_CHECKS=0");

        $cacheTables = array(
            'felamimail_cache_message',
            'felamimail_cache_msg_flag',
            'felamimail_cache_message_to',
            'felamimail_cache_message_cc',
            'felamimail_cache_message_bcc'
        );

        // truncate tables
        foreach ($cacheTables as $table) {
            $db->query("TRUNCATE TABLE " . $db->table_prefix . $table);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()
                ->info(__METHOD__ . '::' . __LINE__ . ' Truncated ' . $table . ' table');
        }

        $db->query("SET FOREIGN_KEY_CHECKS=1");
    }
}
