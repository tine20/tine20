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
    protected $_documentElement     = 'SendMail';

    protected $_saveInSent;
    protected $_source;
    protected $_replaceMime = false;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        if ($this->_requestParameters['contentType'] == 'message/rfc822') {
            $this->_mime          = $this->_requestBody;
            $this->_saveInSent    = $this->_requestParameters['saveInSent'];
            $this->_replaceMime   = false;
            
            $this->_source = array(
                'collectionId' => $this->_requestParameters['collectionId'],
                'itemId'       => $this->_requestParameters['itemId'],
                'instanceId'   => null
            );
            
        } else {
            $xml = simplexml_import_dom($this->_requestBody);
            
            $this->_mime          = (string) $xml->Mime;
            $this->_saveInSent    = isset($xml->SaveInSentItems);
            $this->_replaceMime   = isset($xml->ReplaceMime);
            
            if (isset ($xml->Source)) {
                if ($xml->Source->LongId) {
                    $this->_source = (string)$xml->Source->LongId;
                } else {
                    $this->_source = array(
                        'collectionId' => (string)$xml->Source->FolderId,
                        'itemId'       => (string)$xml->Source->ItemId,
                        'instanceId'   => isset($xml->Source->InstanceId) ? (string)$xml->Source->InstanceId : null
                    );
                }
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

        try {
            $dataController->sendEmail($this->_mime, $this->_saveInSent);
        } catch (Syncroton_Exception_Status $ses) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " Sending email failed: " . $ses->getMessage());

            $response = new Syncroton_Model_SendMail(array(
                'status' => $ses->getCode(),
            ));

            $response->appendXML($this->_outputDom->documentElement, $this->_device);

            return $this->_outputDom;
        }
    }
}
