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

    protected $_saveInSent;
    protected $_itemId;
    protected $_collectionId;
    protected $_mime;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        if ($this->_requestParameters['contentType'] == 'message/rfc822') {
            $this->_mime          = $this->_requestBody;
            $this->_saveInSent    = $this->_requestParameters['SaveInSent'] == 'T';
            
            $this->_collectionId  = $this->_requestParameters['CollectionId'];
            $this->_itemId        = $this->_requestParameters['ItemId'];
            
        } else {
            $xml = simplexml_import_dom($this->_requestBody);
            
            $this->_mime          = (string) $xml->Mime;
            $this->_saveInSent    = isset($xml->SaveInSentItems);
            
            if (isset ($xml->Source)) {
                $this->_collectionId  = (string)$xml->Source->FolderId;
                $this->_itemId        = (string)$xml->Source->ItemId;
            }
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
    
        $dataController->sendEmail($this->_mime, $this->_saveInSent);        
    }
}
