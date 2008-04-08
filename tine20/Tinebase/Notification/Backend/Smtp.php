<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * notifications smtp backend class
 *
 * @package     Tinebase
 * @subpackage  Notification
 */
class Tinebase_Notification_Backend_Smtp
{
    /**
     * the from address
     *
     * @var string
     */
    protected $_fromAddress;
    
    /**
     * the sender name
     *
     * @var string
     */
    protected $_fromName = 'Tine 2.0 notification service';
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_fromAddress = 'webmaster@tine20.org';
    }
    
    /**
     * send a notification as email
     *
     * @param Tinebase_Account_Model_FullAccount $_updater
     * @param Addressbook_Model_Contact $_recipient
     * @param string $_subject the subject
     * @param string $_messagePlain the message as plain text
     * @param string $_messageHtml the message as html
     */
    public function send(Tinebase_Account_Model_FullAccount $_updater, Addressbook_Model_Contact $_recipient, $_subject, $_messagePlain, $_messageHtml = NULL)
    {
        $mail = new Tinebase_Mail('UTF-8');
        
        $mail->setSubject($_subject);
        
        $mail->setBodyText($_messagePlain);
        
        if($_messageHtml !== NULL) {
            $mail->setBodyHtml($_messageHtml);
        }
        
        $mail->addHeader('X-MailGenerator', 'Tine 2.0');
        
        if(!empty($_updater->accountEmailAddress)) {
            $mail->setFrom($_updater->accountEmailAddress, $_updater->accountDisplayName);
            $mail->setSender($this->_fromAddress, $this->_fromName);
        } else {
            $mail->setFrom($this->_fromAddress, $this->_fromName);
        }

        if(!empty($_recipient->accountEmailAddress)) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' send notification email to ' . $_recipient->email);

            $mail->addTo($_recipient->email, $_recipient->n_fileas);
        
            $mail->send();
        }
    }
}