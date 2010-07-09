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
     * 
     * @var Felamimail_Model_Account
     */
    protected $_account;
        
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        $defaultAccountId = Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT};
        
        try {
            $this->_account = Felamimail_Controller_Account::getInstance()->get($defaultAccountId);
        } catch (Tinebase_Exception_NotFound $ten) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " no email account configured");
            throw new ActiveSync_Exception('no email account configured');
        }
        
        if(empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            throw new ActiveSync_Exception('no email address set for current user');
        }
        
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
        $mail = Tinebase_Mail::createFromZMM($this->_incomingMessage);
        
        Felamimail_Controller_Message::getInstance()->sendZendMail($this->_account, $mail, $this->_saveInSent);        
    }    
}