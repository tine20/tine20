<?php

class Syncope_Server
{
    protected $_body;
    
    /**
     * informations about the currently device
     *
     * @var Syncope_Backend_IDevice
     */
    protected $_deviceBackend;
    
    /**
     * @var Zend_Log
     */
    protected $_logger;
    
    /**
     * @var Zend_Controller_Request_Http
     */
    protected $_request;
    
    protected $_userId;
    
    public function __construct($userId, Zend_Controller_Request_Http $request = null, $body = null)
    {
        if (Syncope_Registry::isRegistered('loggerBackend')) {
            $this->_logger = Syncope_Registry::get('loggerBackend');
        }
        
        $this->_userId  = $userId;
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST METHOD: ');
        $this->_request = $request !== null ? $request : new Zend_Controller_Request_Http();
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST METHOD: ');
        $this->_body    = $body    !== null ? $body    : fopen('php://input', 'r');
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST METHOD: ');
        
        $this->_deviceBackend = Syncope_Registry::get('deviceBackend');
        
    }
        
    public function handle()
    {
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST METHOD: ' . $this->_request->getMethod());
        
        switch($this->_request->getMethod()) {
            case 'OPTIONS':
                $this->_handleOptions();
                break;
        
            case 'POST':
                $this->_handlePost();
                break;
        
            case 'GET':
                echo "It works!<br>Your username is: {$this->_username} and your IP address is: {$_SERVER['REMOTE_ADDR']}.";
                break;
        }       
    }
    
    /**
    * handle options request
    *
    */
    protected function _handleOptions()
    {
        $command = new Syncope_Command_Options();
    
        $command->getResponse();
    }
    
    protected function _handlePost()
    {
        if(count($_GET) == 1) {
            $arrayKeys = array_keys($_GET);
            $requestParameters = $this->_decodeRequestParameters($arrayKeys[0]);
        } else {
            $requestParameters = $this->_getRequestParameters();
        }
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST ' . print_r($requestParameters, true));
        
        $userAgent = $this->_request->getServer('HTTP_USER_AGENT', $requestParameters['deviceType']);
        $policyKey = $this->_request->getServer('HTTP_X_MS_POLICYKEY');
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " Agent: $userAgent  PolicyKey: $policyKey ASVersion: {$requestParameters['protocolVersion']} Command: {$requestParameters['command']}");
        
        $className = 'Syncope_Command_' . $requestParameters['command'];
        
        if(!class_exists($className)) {
            throw new Syncope_Exception_CommandNotFound('unsupported command ' . $requestParameters['command']);
        }
        
        $device = $this->_deviceBackend->getUserDevice($this->_userId, $requestParameters['deviceId'], $requestParameters['deviceType']);
        
        $device->useragent = $userAgent;
        $device->acsversion = $requestParameters['protocolVersion'];
        
        $this->_deviceBackend->update($device);
        
        if ($this->_request->getServer('CONTENT_TYPE') == 'application/vnd.ms-sync.wbxml') {
            // decode wbxml request
            try {
                $decoder = new Wbxml_Decoder($this->_body);
                $requestBody = $decoder->decode();
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " xml request: " . $requestBody->saveXML());
            } catch(Wbxml_Exception_UnexpectedEndOfFile $e) {
                $requestBody = NULL;
            }
        } else {
            $requestBody = $this->_body;
        }
        
        try {
            $command = new $className($requestBody, $device, $policyKey);
        
            $command->handle();

            if (PHP_SAPI !== 'cli') {
                header("MS-Server-ActiveSync: 8.3");
            }
        
            $response = $command->getResponse();
        } catch (Syncope_Exception_PolicyKeyMissing $asepkm) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " X-MS-POLICYKEY missing (" . $_command. ')');
            header("HTTP/1.1 400 header X-MS-POLICYKEY not found");
            return;
        } catch (Syncope_Exception_ProvisioningNeeded $asepn) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " provisioning needed");
            header("HTTP/1.1 449 Retry after sending a PROVISION command");
            return;
        }
        
        if ($this->_request->getServer('CONTENT_TYPE') == 'application/vnd.ms-sync.wbxml') {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " xml response: " . $response->saveXML());
        
            $outputStream = fopen("php://temp", 'r+');
        
            $encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
            $encoder->encode($response);
        
            header("Content-Type: application/vnd.ms-sync.wbxml");
        
            rewind($outputStream);
            fpassthru($outputStream);
        }
    }
    
    /**
     * return request params
     * 
     * @return array
     */
    protected function _getRequestParameters()
    {
        $result = array(
            'protocolVersion'  => $this->_request->getServer('HTTP_MS_ASPROTOCOLVERSION'),
            'command'          => $this->_request->getParam('Cmd'),
            'deviceId'         => $this->_request->getParam('DeviceId'),
            'deviceType'       => $this->_request->getParam('DeviceType')
        );
        
        return $result;
    }
}