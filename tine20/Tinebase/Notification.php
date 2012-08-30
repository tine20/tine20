<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * primary class to handle notifications
 *
 * @package     Tinebase
 * @subpackage  Notification
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
     * send notifications to a list a recipients
     *
     * @param Tinebase_Model_FullUser   $_updater
     * @param array                     $_recipients array of int|Addressbook_Model_Contact
     * @param string                    $_subject
     * @param string                    $_messagePlain
     * @param string                    $_messageHtml
     * @param string|array              $_attachments
     * @throws Tinebase_Exception
     * 
     * @todo improve exception handling: collect all messages / exceptions / failed email addresses / ...
     */
    public function send(Tinebase_Model_FullUser $_updater, $_recipients, $_subject, $_messagePlain, $_messageHtml = NULL, $_attachments = NULL)
    {
        $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $exception = NULL;
        $sentContactIds = array();
        foreach($_recipients as $recipient) {
            try {
                if (!$recipient instanceof Addressbook_Model_Contact) {
                    $recipient = $contactsBackend->get($recipient);
                }
                if (! in_array($recipient->getId(), $sentContactIds)) {
                    $this->_smtpBackend->send($_updater, $recipient, $_subject, $_messagePlain, $_messageHtml, $_attachments);
                    $sentContactIds[] = $recipient->getId();
                }
            } catch (Exception $e) {
                $exception = $e;
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Failed to send notification message to '{$recipient->email}'");
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " $e");
            }
        }
        
        if ($exception !== NULL) {
            // throw exception in the end when all recipients have been processed
            throw $exception;
        }
    }
}
