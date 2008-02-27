<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Egwbase_Notification_Backend_Smtp
{
    protected $_fromAddress;
    
    protected $_fromName = 'Tine 2.0 notification service';
    
    public function __construct()
    {
        $this->_fromAddress = 'webmaster@tine20.org';
    }
    
    public function send($_recipient, $_subject, $_messagePlain, $_messageHtml = NULL)
    {
        $mail = new Zend_Mail('UTF-8');
        
        $mail->setSubject($_subject);
        
        $mail->setBodyText($_messagePlain);
        
        if($_messageHtml !== NULL) {
            $mail->setBodyHtml($_messageHtml);
        }
        
        $mail->addHeader('X-MailGenerator', 'Tine 2.0');
        
        $mail->setFrom($this->_fromAddress, $this->_fromName);

        if(!empty($_recipient->accountEmailAddress)) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' send notification email to ' . $_recipient->accountEmailAddress);

            $mail->addTo($_recipient->accountEmailAddress, $_recipient->accountDisplayName);
        
            $mail->send();
        }
    }
}