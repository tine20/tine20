<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sendmail command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_SendMail extends Syncroton_Command_Wbxml
{
    protected $_defaultNameSpace    = 'uri:ComposeMail';
    protected $_documentElement     = 'Sendmail';
    
    protected $_itemId;
    
    protected $_collectionId;
    
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        if ($this->_requestParameters['contentType'] == 'message/rfc822') {
            $this->_saveInSent    = isset($_GET['SaveInSent']) && $_GET['SaveInSent'] == 'T';
            $this->_collectionId  = isset($_GET['CollectionId']) ? $_GET['CollectionId'] : null;
            $this->_itemId        = isset($_GET['ItemId'])       ? $_GET['ItemId']       : null;
            $this->_inputStream   = $this->_requestBody;
            
        } else {
            $xml = simplexml_import_dom($this->_requestBody);
            
            $this->_saveInSent    = isset($xml->SaveinSentItems);
            $this->_collectionId  = isset($xml->FolderId) ? (string)$xml->FolderId : null;
            $this->_itemId        = isset($xml->ItemId)   ? (string)$xml->ItemId   : null;
            $this->_inputStream   = (string) $xml->Mime;
        }
        
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " saveInSent: " . (int)$this->_saveInSent);
    }
    
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_EMAIL, $this->_device, $this->_syncTimeStamp);
    
        $dataController->sendEmail($this->_inputStream, $this->_saveInSent);        
    }
}
