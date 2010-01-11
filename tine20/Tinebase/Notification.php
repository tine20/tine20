<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * primary class to handle notifications
 *
 * @package     Tinebase
 * @subpackage  Notifications
 */
class Tinebase_Notification
{
    protected $_smtpBackend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_smtpBackend = Tinebase_Notification_Factory::getBackend(Tinebase_Notification_Factory::SMTP);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holds the instance of the singleton
     *
     * @var Adressbook_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Notification
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Notification();
        }
        
        return self::$_instance;
    }
    
    /**
     * send notifications to a list a receipients
     *
     * @param Tinebase_Model_FullUser   $_updater
     * @param array                     $_recipients array of int|Addressbook_Model_Contact
     * @param string                    $_subject
     * @param string                    $_messagePlain
     * @param string                    $_messageHtml
     * @param string|array              $_attachements
     */
    public function send(Tinebase_Model_FullUser $_updater, $_recipients, $_subject, $_messagePlain, $_messageHtml = NULL, $_attachements = NULL)
    {
        $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        foreach($_recipients as $recipient) {
            try {
                if(!$recipient instanceof Addressbook_Model_Contact) {
                    $recipient = $contactsBackend->get($recipient);
                }
                $this->_smtpBackend->send($_updater, $recipient, $_subject, $_messagePlain, $_messageHtml, $_attachements);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Failed to send notification message. Error: " . $e->getMessage());
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $e");
            }
        }
    }
}