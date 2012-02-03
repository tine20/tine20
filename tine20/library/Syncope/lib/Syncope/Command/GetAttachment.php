<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     Syncope
 * @subpackage  Command
 */
 
class Syncope_Command_GetAttachment
{
    /**
     *
     * @var string
     */
    protected $_messageId;
    
    /**
     *
     * @var string
     */
    protected $_partId;
    
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
        
        list($this->_messageId, $this->_partId) = explode('-', $_GET['AttachmentName'], 2);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " messageId: " . $this->_messageId . ' partId: ' . $this->_partId);
    }    
    
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        $frontend = new Felamimail_Frontend_Http();
        
        $frontend->downloadAttachment($this->_messageId, $this->_partId);
    }    
}
