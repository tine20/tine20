<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct(array()))->toArray();
        $this->_fromAddress = (isset($smtpConfig['from']) && ! empty($smtpConfig['from'])) ? $smtpConfig['from'] : '';
        
        // try to sanitize sender address
        if (empty($this->_fromAddress) && isset($smtpConfig['primarydomain']) && ! empty($smtpConfig['primarydomain'])) {
            $this->_fromAddress = 'noreply@' . $smtpConfig['primarydomain'];
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
     * @param string|array              $_attachments
     */
    public function send($_updater, Addressbook_Model_Contact $_recipient, $_subject, $_messagePlain, $_messageHtml = NULL, $_attachments = NULL)
    {
        // create mail object
        $mail = new Tinebase_Mail('UTF-8');
        // this seems to break some subjects, removing it for the moment 
        // -> see 0004070: sometimes we can't decode message subjects (calendar notifications?)
        //$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
        $mail->setSubject($_subject);
        $mail->setBodyText($_messagePlain);
        
        if($_messageHtml !== NULL) {
            $mail->setBodyHtml($_messageHtml);
        }
        
        // add header to identify mails sent by notification service / don't reply to this mail, dear autoresponder ... :)
        $mail->addHeader('X-Tine20-Type', 'Notification');
        $mail->addHeader('Precedence', 'bulk');
        
        if (empty($this->_fromAddress)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No notification service address set. Could not send notification.');
            return;
        }
        
        if($_updater !== NULL && ! empty($_updater->accountEmailAddress)) {
            $mail->setFrom($_updater->accountEmailAddress, $_updater->accountFullName);
            $mail->setSender($this->_fromAddress, $this->_fromName);
        } else {
            $mail->setFrom($this->_fromAddress, $this->_fromName);
        }
        
        // attachments
        if (is_array($_attachments)) {
            $attachments = &$_attachments;
        } elseif (is_string($_attachments)) {
            $attachments = array(&$_attachments);
        } else {
            $attachments = array();
        }
        foreach ($attachments as $attachment) {
            if ($attachment instanceof Zend_Mime_Part) {
                $mail->addAttachment($attachment);
            } else if (isset($attachment['filename'])) {
                $mail->createAttachment(
                    $attachment['rawdata'], 
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    $attachment['filename']
                );
            } else {
                $mail->createAttachment($attachment);
            }
        }
        
        // send
        if(! empty($_recipient->email)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Send notification email to ' . $_recipient->email);
            $mail->addTo($_recipient->email, $_recipient->n_fn);
            Tinebase_Smtp::getInstance()->sendMessage($mail);
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Not sending notification email to ' . $_recipient->n_fn . '. No email address available.');
        }
    }
}
