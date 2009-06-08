<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for felamimail, does event handling
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        implement events (update credentials if user pw changed, ...)
 */

/**
 * main controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller extends Tinebase_Controller_Abstract implements Tinebase_Events_Interface
{
    /**
     * holdes the instance of the singleton
     *
     * @var Felamimail_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
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
     * @param Tinebase_Events_Abstract $_eventObject the eventObject
     * 
     */
    public function handleEvents(Tinebase_Events_Abstract $_eventObject)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_ChangePassword':
                $this->updateCredentials($_eventObject);
                break;
        }
    }
        
    /**
     * updates imap/smtp credentials with new user pw
     *
     * @param Admin_Event_ChangePassword $_eventObject
     * 
     * @todo implement that
     */
    public function updateCredentials(Admin_Event_ChangePassword $_eventObject)
    {
    }
}
