<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * message controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message
{
    /**
     * the current imap connection
     * 
     * @var Zend_Mail_Storage_Imap
     */
    protected $_imap = NULL;
        
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();

        $this->_imap = new Felamimail_Backend_Imap(Tinebase_Core::getConfig()->imap->toArray());
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * holdes the instance of the singleton
     *
     * @var Felamimail_Controller_Message
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Message
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Message();
        }
        
        return self::$_instance;
    }
    
    /**
     * send one message through smtp
     *
     * @todo use userspecific settings
     */
    public function sendMessage(Zend_Mail $_mail)
    {
        $config = array(
            'ssl' => 'tls',
            'port' => 25
        );
        $transport = new Zend_Mail_Transport_Smtp('localhost', $config);
        
        Tinebase_Smtp::getInstance()->sendMessage($_mail, $transport);
        
        $this->_imap->appendMessage($_mail, 'Sent');
    }
    
    /**
     * fetch message from folder
     *
     * @param string $_globalName the complete folder name
     * @param string $_messageId the message id
     * @return Zend_Mail_Message
     */
    public function getMessage($_globalName, $_messageId)
    {        
        $this->_imap->selectFolder($_globalName);
        
        $message = $this->_imap->getMessage($_messageId);
        
        return $message;
    }
}
