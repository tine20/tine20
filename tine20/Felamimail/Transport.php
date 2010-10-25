<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * mail transport for Felamimail
 * - extended Zend_Mail_Transport_Smtp, added getBody/getHeaders and use these for appendMessage / sendMessage
 *
 * @package     Felamimail
 */
class Felamimail_Transport extends Zend_Mail_Transport_Smtp 
{
    /**
     * get mail body as string
     *
     * @param Zend_Mail $_mail
     * @return string
     */
    public function getBody(Zend_Mail $_mail = NULL)
    {
        if (! isset($this->body)) {
            $mime = $_mail->getMime();
            $message = new Zend_Mime_Message();
            $message->setMime($mime);
            $this->body = $message->generateMessage($this->EOL);            
        }
        
        return $this->body;
    }

    /**
     * get mail headers as string
     *
     * @return string
     */
    public function getHeaders()
    {
        if (! isset($this->header)) {
            // we could add the creation of headers here
            throw new Felamimail_Exception('Header not found');
        }
        
        return $this->header;
    }
    
    /**
     * get raw message as string
     * 
     * @param Zend_Mail $mail
     * @return string
     */
    public function getRawMessage(Zend_Mail $mail = NULL)
    {
        if ($mail !== NULL) {
            // this part is from Zend_Mail_Transport_Abstract::send()
            $this->_isMultipart = false;
            $this->_mail        = $mail;
            $this->_parts       = $mail->getParts();
            $mime               = $mail->getMime();
    
            // Build body content
            $this->_buildBody();
    
            // Determine number of parts and boundary
            $count    = count($this->_parts);
            $boundary = null;
            if ($count < 1) {
                /**
                 * @see Zend_Mail_Transport_Exception
                 */
                require_once 'Zend/Mail/Transport/Exception.php';
                throw new Zend_Mail_Transport_Exception('Mail is empty');
            }
    
            if ($count > 1) {
                // Multipart message; create new MIME object and boundary
                $mime     = new Zend_Mime($this->_mail->getMimeBoundary());
                $boundary = $mime->boundary();
            } elseif ($this->_isMultipart) {
                // multipart/alternative -- grab boundary
                $boundary = $this->_parts[0]->boundary;
            }
    
            // Determine recipients, and prepare headers
            $this->recipients = implode(',', $mail->getRecipients());
            $this->_prepareHeaders($this->_getHeaders($boundary));
    
            // Create message body
            // This is done so that the same Zend_Mail object can be used in
            // multiple transports
            $message = new Zend_Mime_Message();
            $message->setParts($this->_parts);
            $message->setMime($mime);
            $this->body = $message->generateMessage($this->EOL);
        }
        
        $mailAsString = $this->getHeaders() . $this->EOL. $this->getBody();
        // convert \n to \r\n
        $mailAsString = preg_replace("/(?<!\\r)\\n(?!\\r)/", "\r\n", $mailAsString);
        
        return $mailAsString;
    }
}
