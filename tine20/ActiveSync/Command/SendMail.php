<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Command_SendMail
{
    /**
     * the incoming mail
     *
     * @var Zend_Mail_Message
     */
    protected $_incomingMessage;
    
    /**
     * save copy in sent folder
     *
     * @var boolean
     */
    protected $_saveInSent;
        
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @todo we need to stored the initial data for folders and lifetime as the phone is sending them only when they change
     * @return resource
     */
    public function handle()
    {
        $this->_saveInSent = (bool)$_GET['SaveInSent'] == 'T';
        
        $rawMessage = file_get_contents("php://input"); 

        $this->_incomingMessage = new Zend_Mail_Message(array('raw' => $rawMessage));

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " saveInSent: " . $this->_saveInSent . " message: " . $rawMessage);
        
    }    
    
    /**
     * this function generates the response for the client
     */
    public function getResponse()
    {
        $currentUser = Tinebase_Core::getUser();
        
        if(empty($currentUser->accountEmailAddress)) {
            throw new Exception('no email address set for current user');
        }
        
        return;
        
        $mail = new Tinebase_Mail();
        
        $mail->setFrom($currentUser->accountEmailAddress, $currentUser->accountDisplayName);
        
        $this->_addHeaders($mail);
        
        if($this->_incomingMessage->isMultipart() === true) {
            foreach (new RecursiveIteratorIterator($this->_incomingMessage) as $part) {
                $this->_addPart($mail, $part);
            }    
        } else {
            $this->_addPart($mail, $this->_incomingMessage);
        }
        
        Tinebase_Smtp::getInstance()->sendMessage($mail);
    }
    
    protected function _addHeaders(Tinebase_Mail $_mail)
    {
        foreach($this->_incomingMessage->getHeaders() as $headerName => $headerValue) {
            switch($headerName) {
                case 'date':
                    $_mail->setDate($headerValue);
                    
                    break;
                    
                case 'content-transfer-encoding':
                case 'content-type':
                case 'from':
                case 'mime-version':
                    // do nothing
                    break;
                    
                case 'subject':
                    $_mail->setSubject($headerValue);
                    
                    break;
                    
                case 'to':
                    $tos = $this->_incomingMessage->getHeader('to');
                    $tos = Felamimail_Message::parseAdresslist($tos);
                    foreach($tos as $to) {
                        $_mail->addTo($to['address'], $to['name']);
                    }
                    
                    break;
                    
                case 'cc':
                    $ccs = $this->_incomingMessage->getHeader('cc');
                    $ccs = Felamimail_Message::parseAdresslist($ccs);
                    foreach($ccs as $cc) {
                        $_mail->addCc($cc['address'], $cc['name']);
                    }
                    
                    break;
                    
                case 'bcc':
                    $bccs = $this->_incomingMessage->getHeader('bcc');
                    $bccs = Felamimail_Message::parseAdresslist($bccs);
                    foreach($bccs as $bcc) {
                        $_mail->addBcc($bcc['address'], $bcc['name']);
                    }
                                
                    break;
                    
                    
                default:
                    $_mail->addHeader(ucwords($headerName), $headerValue);
                    
                    break;
            }
        }
        
    }
    
    function _addPart(Tinebase_Mail $_mail, Zend_Mail_Part $_part)
    {
        $contentType    = $_part->getHeaderField('content-type', 0);
        $charset        = $_part->getHeaderField('content-type', 'charset');
        $encoding       = $_part->getHeaderField('content-transfer-encoding');
        $content        = $_part->getContent();
        
        switch ($encoding) {
            case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                $content = quoted_printable_decode($content);
                break;
            case Zend_Mime::ENCODING_BASE64:
                $content = base64_decode($content);
                break;
        }
        
        $mimePart           = new Zend_Mime_Part($content);
        $mimePart->type     = $contentType;
        $mimePart->charset  = $charset;
        $mimePart->encoding = $encoding;
    
        switch ($mimePart->type) {
            case Zend_Mime::TYPE_HTML:
                $mimePart->disposition = Zend_Mime::DISPOSITION_INLINE;
                
                $_mail->setBodyHtml($mimePart);
                
                break;
            
            case Zend_Mime::TYPE_TEXT:
                $mimePart->disposition = Zend_Mime::DISPOSITION_INLINE;
                
                $_mail->setBodyText($mimePart);
                
                break;
            
            default:
                $disposition = $_part->getHeaderField('content-disposition', 0);
                $filename    = $_part->getHeaderField('content-disposition', 'filename');
                
                $mimePart->disposition = $disposition;
                $mimePart->filename    = $filename;
                
                $_mail->addAttachment($mimePart);
                
                break;
        }
    }    
}