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
 
class ActiveSync_Command_SmartForward extends ActiveSync_Command_SendMail
{
    /**
     * save copy in sent folder
     *
     * @var boolean
     */
    protected $_saveInSent;
    
    protected $_itemId;
    
    protected $_collectionId;
        
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @todo we need to stored the initial data for folders and lifetime as the phone is sending them only when they change
     * @return resource
     */
    public function handle()
    {
        parent::handle();
        
        $this->_collectionId    = $_GET['CollectionId'];
        $this->_itemId          = $_GET['ItemId'];
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " collectionId: " . $this->_collectionId . " itemId: " . $this->_itemId
        );
    }    
    
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        $rfc822 = Felamimail_Controller_Message::getInstance()->getMessagePart($this->_itemId);
        $rfc822->type = Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822;
        $rfc822->filename = 'forwarded email.eml';
        $rfc822->encoding = Zend_Mime::ENCODING_7BIT;
        
        $mail = Tinebase_Mail::createFromZMM($this->_incomingMessage);
        
        $mail->addAttachment($rfc822);
        
        Felamimail_Controller_Message::getInstance()->sendZendMail($this->_account, $mail, $this->_saveInSent);        
    }    
}