<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Mail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * This class extends the Zend_Mail class 
 *
 * @package     Tinebase
 * @subpackage  Mail
 */
class Tinebase_Mail extends Zend_Mail
{
    /**
     * Sender: address
     * @var string
     */
    protected $_sender = null;
    
    /**
     * create Tinebase_Mail from Zend_Mail_Message
     * 
     * @param  Zend_Mail_Message  $_zmm
     * @param  string             $_replyBody
     * @return Tinebase_Mail
     */
    public static function createFromZMM(Zend_Mail_Message $_zmm, $_replyBody = null)
    {
        $contentStream = fopen("php://temp", 'r+');
        fputs($contentStream, $_zmm->getContent());
        rewind($contentStream);
        
        $mp = new Zend_Mime_Part($contentStream);
        
        if ($_zmm->headerExists('content-transfer-encoding')) {
            $mp->encoding = $_zmm->getHeader('content-transfer-encoding');
            $mp->decodeContent();
        } else {
            $mp->encoding = Zend_Mime::ENCODING_7BIT;
        }
        
        // append old body when no multipart/mixed
        if ($_replyBody !== null && $_zmm->headerExists('content-transfer-encoding')) {
            $contentStream = fopen("php://temp", 'r+');
            
            stream_copy_to_stream($mp->getRawStream(), $contentStream);
            
            fputs($contentStream, $_replyBody);
            
            rewind($contentStream);
            
            // create decoded stream
            $mp = new Zend_Mime_Part($contentStream);
            $mp->encoding = $_zmm->getHeader('content-transfer-encoding');
        }
        
        if ($_zmm->headerExists('content-type')) {
            $contentTypeHeader = Zend_Mime_Decode::splitHeaderField($_zmm->getHeader('content-type'));
            
            $mp->type = $contentTypeHeader[0];
            
            if (isset($contentTypeHeader['boundary'])) {
                $mp->boundary = $contentTypeHeader['boundary'];
            }
            
            if (isset($contentTypeHeader['charset'])) {
                $mp->charset = $contentTypeHeader['charset'];
            }
        } else {
            $mp->type = Zend_Mime::TYPE_TEXT;
        }        
        
        $result = new Tinebase_Mail('utf-8');
        
        $result->setBodyText($mp);
        
        foreach ($_zmm->getHeaders() as $header => $values) {
            foreach ((array)$values as $value) {
                switch ($header) {
                    case 'content-transfer-encoding':
                    // these are implicitly set by Zend_Mail_Transport_Abstract::_getHeaders()
                    case 'content-type':
                    case 'mime-version':
                        // do nothing
                        
                        break;
                        
                    case 'bcc':
                        $addresses = Felamimail_Message::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $result->addBcc($address['address'], $address['name']);
                        }
                        
                        break;
                        
                    case 'cc':
                        $addresses = Felamimail_Message::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $result->addCc($address['address'], $address['name']);
                        }
                                                
                        break;
                        
                    case 'date':
                        $result->setDate($value);
                        
                        break;
                        
                    case 'from':
                        $addresses = Felamimail_Message::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $result->setFrom($address['address'], $address['name']);
                        }
                        
                        break;
                        
                    case 'message-id':
                        $result->setMessageId($value);
                        
                        break;
                        
                    case 'return-path':
                        $result->setReturnPath($value);
                        
                        break;
                        
                    case 'subject':
                        $result->setSubject($value);
                        
                        break;
                        
                    case 'to':
                        $addresses = Felamimail_Message::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $result->addTo($address['address'], $address['name']);
                        }
                        
                        break;
                        
                    default:
                        $result->addHeader($header, $value);
                        
                        break;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sets the HTML body for the message
     *
     * @param  string|Zend_Mime_Part    $html
     * @param  string    $charset
     *  @param  string    $encoding
     * @return Zend_Mail Provides fluent interface
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($html instanceof Zend_Mime_Part) {
            $mp = $html;
        } else {
            if ($charset === null) {
                $charset = $this->_charset;
            }
        
            $mp = new Zend_Mime_Part($html);
            $mp->encoding = $encoding;
            $mp->type = Zend_Mime::TYPE_HTML;
            $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
            $mp->charset = $charset;
        }
        
        $this->_bodyHtml = $mp;
    
        return $this;
    }
    
    /**
     * Sets the text body for the message.
     *
     * @param  string|Zend_Mime_Part $txt
     * @param  string $charset
     * @param  string $encoding
     * @return Zend_Mail Provides fluent interface
    */
    public function setBodyText($txt, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($txt instanceof Zend_Mime_Part) {
            $mp = $txt;
        } else {
            if ($charset === null) {
                $charset = $this->_charset;
            }
    
            $mp = new Zend_Mime_Part($txt);
            $mp->encoding = $encoding;
            $mp->type = Zend_Mime::TYPE_TEXT;
            $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
            $mp->charset = $charset;
        }
        
        $this->_bodyText = $mp;

        return $this;
    }
    
    /**
     * Sets Sender-header and sender of the message
     *
     * @param  string    $email
     * @param  string    $name
     * @return Zend_Mail Provides fluent interface
     * @throws Zend_Mail_Exception if called subsequent times
     */
    public function setSender($email, $name = '')
    {
        if ($this->_sender === null) {
            $email = strtr($email,"\r\n\t",'???');
            $this->_from = $email;
            $this->_storeHeader('Sender', $this->_encodeHeader('"'.$name.'"').' <'.$email.'>', true);
        } else {
            throw new Zend_Mail_Exception('Sender Header set twice');
        }
        return $this;
    }
    
    /**
     * Formats e-mail address
     * 
     * NOTE: we always add quotes to the name as this caused problems when name is encoded
     * @see Zend_Mail::_formatAddress
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    protected function _formatAddress($email, $name)
    {
        if ($name === '' || $name === null || $name === $email) {
            return $email;
        } else {
            $encodedName = $this->_encodeHeader($name);
            $format = '"%s" <%s>';
            return sprintf($format, $encodedName, $email);
        }
    }
}
