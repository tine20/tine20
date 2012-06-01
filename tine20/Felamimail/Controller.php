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
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Tinebase_Event_User_ChangeCredentialCache':
                Felamimail_Controller_Account::getInstance()->updateCredentialsOfAllUserAccounts($_eventObject->oldCredentialCache);
                break;
        }
    }
}
