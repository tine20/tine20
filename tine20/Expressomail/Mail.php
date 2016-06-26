<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Mail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 * @author      Bruno Vieira Costa <bruno.vieira-costa@serpro.gov.br>
 */

/**
 * This class extends the Tinebase_Mail class
 *
 * @package     Expressomail
 * @subpackage  Mail
 */
class Expressomail_Mail extends Tinebase_Mail
{
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
        
        if (preg_match('/application\/(x\-){0,1}pkcs7-mime/i', $_zmm->getHeader('content-type')) > 0) {
            $mp = new Zend_Mime_Part($_zmm->getContent());
        } else {
            fputs($contentStream, $_zmm->getContent());
            rewind($contentStream);

            $mp = new Zend_Mime_Part($contentStream);
        }
        if ($_zmm->headerExists('content-transfer-encoding')) {
            $mp->encoding = $_zmm->getHeader('content-transfer-encoding');
            $mp->decodeContent();
        } else {
            $mp->encoding = Zend_Mime::ENCODING_7BIT;
        }
        
        // append old body when no multipart/mixed
        if ($_replyBody !== null && $_zmm->headerExists('content-transfer-encoding')) {
            $mp = self::_appendReplyBody($mp, $_replyBody);
            $mp->encoding = $_zmm->getHeader('content-transfer-encoding');
        }
        
        if ($_zmm->headerExists('content-type')) {
            $contentTypeHeader = Zend_Mime_Decode::splitHeaderField($_zmm->getHeader('content-type'));
            
            if($mp->type = strtolower($contentTypeHeader[0]) === 'application/pkcs7-mime'){
                $mp->type = $_zmm->getHeader('content-type');
            }else{
                $mp->type = $contentTypeHeader[0];
            }
            if (isset($contentTypeHeader['boundary'])) {
                $mp->boundary = $contentTypeHeader['boundary'];
            }
            
            if (isset($contentTypeHeader['charset'])) {
                $mp->charset = $contentTypeHeader['charset'];
            }
        } else {
            $mp->type = Zend_Mime::TYPE_TEXT;
        }
        
        $result = new Expressomail_Mail('utf-8');
        
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
                        $addresses = Expressomail_Message::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $result->addBcc($address['address'], $address['name']);
                        }
                        
                        break;
                        
                    case 'cc':
                        $addresses = Expressomail_Message::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $result->addCc($address['address'], $address['name']);
                        }
                                                
                        break;
                        
                    case 'date':
                        try {
                            $result->setDate($value);
                        } catch (Zend_Mail_Exception $zme) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Could not set date: " . $value);
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " " . $zme);
                            $result->setDate();
                        }
                        
                        break;
                        
                    case 'from':
                        $addresses = Expressomail_Message::parseAdresslist($value);
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
                        $addresses = Expressomail_Message::parseAdresslist($value);
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
}