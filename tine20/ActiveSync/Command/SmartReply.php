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
 
class ActiveSync_Command_SmartReply extends ActiveSync_Command_SendMail
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
        $this->_saveInSent      = (bool)$_GET['SaveInSent'] == 'T';
        $this->_collectionId    = $_GET['CollectionId'];
        $this->_itemId          = $_GET['ItemId'];
        
        $rawMessage = file_get_contents("php://input"); 

        $this->_incomingMessage = new Zend_Mail_Message(array('raw' => $rawMessage));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " saveInSent: " . $this->_saveInSent . " message: " . $rawMessage);
        
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
}