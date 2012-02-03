<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync SmartReply command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_SmartReply extends Syncope_Command_SendMail
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " collectionId: " . $this->_collectionId . " itemId: " . $this->_itemId);
        
    }    
    
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        $replyBody = Felamimail_Controller_Message::getInstance()->getMessageBody($this->_itemId, null, 'text/plain');
        
        $mail = Tinebase_Mail::createFromZMM($this->_incomingMessage, $replyBody);
        
        Felamimail_Controller_Message_Send::getInstance()->sendZendMail($this->_account, $mail, $this->_saveInSent);        
    }    
}
