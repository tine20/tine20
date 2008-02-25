<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Egwbase_Notification
{
    protected $_smtpBackend;
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_smtpBackend = Egwbase_Notification_Factory::getBackend(Egwbase_Notification_Factory::SMTP);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Adressbook_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Notification
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Egwbase_Notification;
        }
        
        return self::$_instance;
    }
    
    public function send(Egwbase_Record_RecordSet $_recipients, $_subject, $_messagePlain, $_messageHtml = NULL)
    {
        foreach($_recipients as $recipient) {
            $this->_smtpBackend->send($recipient, $_subject, $_messagePlain, $_messageHtml);
        }
    }
}