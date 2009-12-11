<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * notifications smtp backend class
 *
 * @package     Tinebase
 * @subpackage  Notification
 */
class Tinebase_Notification_Backend_Smtp implements Tinebase_Notification_Interface
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
        $smtpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP, 'Tinebase', array());
        $this->_fromAddress = (isset($smtpConfig['from']) && ! empty($smtpConfig['from'])) ? $smtpConfig['from'] : '';
        
        // try to sanitize sender address
        if (empty($this->_fromAddress) && isset($smtpConfig['domain']) && ! empty($smtpConfig['domain'])) {
            $this->_fromAddress = 'noreply@' . $smtpConfig['domain'];
        }
    }
    
    /**
     * send a notification as email
     *
     * @param Tinebase_Model_FullUser   $_updater
     * @param Addressbook_Model_Contact $_recipient
     * @param string                    $_subject the subject
     * @param string                    $_messagePlain the message as plain text
     * @param string                    $_messageHtml the message as html
     * @param string|array              $_attachements
     */
    public function send($_updater, Addressbook_Model_Contact $_recipient, $_subject, $_messagePlain, $_messageHtml = NULL, $_attachements = NULL)
    {
        // create mail object
        $mail = new Tinebase_Mail('UTF-8');
        $mail->setSubject($_subject);
        $mail->setBodyText($_messagePlain);
        
        if($_messageHtml !== NULL) {
            $mail->setBodyHtml($_messageHtml);
        }
        
        $mail->addHeader('X-MailGenerator', 'Tine 2.0');

        if (empty($this->_fromAddress)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No notification service address set. Could not send notification.');
            return;
        }
        
        if($_updater !== NULL && ! empty($_updater->accountEmailAddress)) {
            $mail->setFrom($_updater->accountEmailAddress, $_updater->accountDisplayName);
            $mail->setSender($this->_fromAddress, $this->_fromName);
        } else {
            $mail->setFrom($this->_fromAddress, $this->_fromName);
        }
        
        // attachements
        if (is_array($_attachements)) {
            $attachements = &$_attachements;
        } elseif (is_string($_attachements)) {
            $attachements = array(&$_attachements);
        } else {
            $attachements = array();
        }
        foreach ($attachements as $attachement) {
            if (isset($attachement['filename'])) {
                $mail->createAttachment(
                    $attachement['rawdata'], 
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    $attachement['filename']
                );
            } else {
                $mail->createAttachment($attachement);
            }
        }
        
        // send
        if(! empty($_recipient->email)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Send notification email to ' . $_recipient->email);
            $mail->addTo($_recipient->email, $_recipient->n_fileas);
            Tinebase_Smtp::getInstance()->sendMessage($mail);
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Not sending notification email to ' . $_recipient->n_fn . '. No email address available.');
        }
    }
}
