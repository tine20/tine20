<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Mail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_htmlRelatedAttachments = null;


     /**
     * Public constructor
     *
     * @param string $charset
     */
    public function __construct($charset = 'iso-8859-1')
    {
        $this->_charset = $charset;
        $this->_htmlRelatedAttachments = new Zend_Mime_Message();
    }

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

    /**
     * Adds an existing attachment related to the HTML part of the message
     *
     * @param  Zend_Mime_Part $attachment
     * @return Zend_Mail Provides fluent interface
     */
    public function addHtmlRelatedAttachment(Zend_Mime_Part $attachment)
    {
        if (!$this->_bodyHtml) {
            /**
             * @see Zend_Mail_Exception
             */
            require_once 'Zend/Mail/Exception.php';
            throw new Zend_Mail_Exception('No HTML Body defined');
        }
        $this->_htmlRelatedAttachments->addPart($attachment);
        return $this;
    }

        /**
     * Creates a Zend_Mime_Part attachment related to the HTML part of the message
     *
     * Attachment is automatically added to the mail object after creation. The
     * attachment object is returned to allow for further manipulation.
     *
     * @param  string         $body
     * @param  string         $cid The Content Id
     * @param  string         $mimeType
     * @param  string         $disposition
     * @param  string         $encoding
     * @param  string         $filename OPTIONAL A filename for the attachment
     * @return Zend_Mime_Part Newly created Zend_Mime_Part object (to allow
     * advanced settings)
     */
    public function createHtmlRelatedAttachment($body, $cid = null,
                                     $mimeType    = Zend_Mime::TYPE_OCTETSTREAM,
                                     $disposition = Zend_Mime::DISPOSITION_INLINE,
                                     $encoding    = Zend_Mime::ENCODING_BASE64,
                                     $filename    = null)
    {

        if (null === $cid) {
            $cid = $this->createCid($body);
        }
        $mp = new Zend_Mime_Part($body);
        $mp->id = $cid;
        $mp->encoding = $encoding;
        $mp->type = $mimeType;
        $mp->disposition = $disposition;
        $mp->filename = $filename;

        $this->addHtmlRelatedAttachment($mp);

        return $mp;
    }


    /**
     * Generates and returns a new cid
     *
     * @return
     */
    public function createCid($body)
    {
        static $unique = 0;
        return md5($body . ($unique++));
    }

    /**
     * Returns the list of all Zend_Mime_Parts related to the HTML part of the message
     *
     * @return array of Zend_Mime_Part
     */
    public function getHtmlRelatedAttachments()
    {
      return $this->_htmlRelatedAttachments;
    }



}
