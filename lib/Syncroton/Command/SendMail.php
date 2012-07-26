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
class Syncroton_Command_SendMail
{
    /**
     * save copy in sent folder
     *
     * @var boolean
     */
    protected $_saveInSent;
    
    /**
     * @var resource
     */
    protected $_inputStream;

    /**
     * informations about the currently device
     *
     * @var Syncroton_Model_Device
     */
    protected $_device;
    
    /**
     * timestamp to use for all sync requests
     *
     * @var DateTime
     */
    protected $_syncTimeStamp;
    
    /**
     * @var Zend_Log
     */
    protected $_logger;
    
    /**
     * the constructor
     *
     * @param  mixed                   $requestBody
     * @param  Syncroton_Model_Device  $device
     * @param  string                  $policyKey
     */
    public function __construct($requestBody, Syncroton_Model_IDevice $device, $policyKey)
    {
        if (!is_resource($requestBody)) {
            throw new Syncroton_Exception_UnexpectedValue('$requestBody must be stream');
        }
        
        $this->_inputStream = $requestBody;
        
        $this->_policyKey     = $policyKey;
        $this->_device        = $device;
        $this->_syncTimeStamp = new DateTime(null, new DateTimeZone('UTC'));
        
        if (Syncroton_Registry::isRegistered('loggerBackend')) {
            $this->_logger    = Syncroton_Registry::get('loggerBackend');
        }
    }
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        $this->_saveInSent = isset($_GET['SaveInSent']) && (bool)$_GET['SaveInSent'] == 'T';
        
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " saveInSent: " . (int)$this->_saveInSent);
        
        /**
         * uncomment next lines to log email body
         *
        if ($this->_logger instanceof Zend_Log) {
            $debugStream = fopen("php://temp", 'r+');
            stream_copy_to_stream($this->_inputStream, $debugStream);
            rewind($debugStream);
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " email to send:" . stream_get_contents($debugStream));

            // replace original stream wirh debug stream, as php://input can't be rewinded
            $this->_inputStream = $debugStream;
            rewind($this->_inputStream);
        }
        */
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
