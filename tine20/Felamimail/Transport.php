<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * 
     * @todo add creation of headers from Zend_Mail?
     */
    public function getHeaders()
    {
        if (! isset($this->header)) {
            // we could add the creation of headers here
            throw new Felamimail_Exception('Header not found');
        }
        
        return $this->header;
    }
}
