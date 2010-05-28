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
     * @return resource
     */
    public function handle()
    {
        $this->_saveInSent = (bool)$_GET['SaveInSent'] == 'T';
        
        $this->_incomingMessage = new Zend_Mail_Message(
            array(
                'file' => fopen("php://input", 'r')
            )
        );

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " saveInSent: " . $this->_saveInSent);
        
    }    
    
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        $currentUser = Tinebase_Core::getUser();
        
        if(empty($currentUser->accountEmailAddress)) {
            throw new ActiveSync_Exception('no email address set for current user');
        }
        
        $message = Felamimail_Message::createMessageFromZendMailMessage($this->_incomingMessage);
        
        $accounts = Felamimail_Controller_Account::getInstance()->search(null, null, null, true);
        
        if(count($accounts) == 0) {
            throw new ActiveSync_Exception('no email account found');
        }
        
        $message->from = $accounts[0];
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " content_type: " . $message->content_type);
        
        Felamimail_Controller_Message::getInstance()->sendMessage($message);
    }
    
    /**
     * keeped for reference
     * 
    
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
    */ 
}