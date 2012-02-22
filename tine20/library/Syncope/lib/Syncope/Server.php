<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle incoming http ActiveSync requests
 * 
 * @package     Syncope
 */
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
        $this->_request = $request !== null ? $request : new Zend_Controller_Request_Http();
        $this->_body    = $body    !== null ? $body    : fopen('php://input', 'r');
        
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
                echo "It works!<br>Your userid is: {$this->_userId} and your IP address is: {$_SERVER['REMOTE_ADDR']}.";
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
        
        // get user device
        $device = $this->_getUserDevice(
            $this->_userId, 
            $requestParameters['deviceId'],
            $requestParameters['deviceType'],
            $userAgent,
            $requestParameters['protocolVersion']
        );
        
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
            
        } catch (Syncope_Exception_PolicyKeyMissing $sepkm) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " X-MS-POLICYKEY missing (" . $_command. ')');
            header("HTTP/1.1 400 header X-MS-POLICYKEY not found");
            return;
            
        } catch (Syncope_Exception_ProvisioningNeeded $sepn) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " provisioning needed");
            header("HTTP/1.1 449 Retry after sending a PROVISION command");
            return;
            
        } catch (Exception $e) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " unexpected exception occured: " . get_class($e));
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " exception message: " . $e->getMessage());
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " " . $e->getTraceAsString());
            
            header("HTTP/1.1 500 Internal server error");
            return;
        }
        
        if ($this->_request->getServer('CONTENT_TYPE') == 'application/vnd.ms-sync.wbxml' && $response instanceof DOMDocument) {
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
    
    /**
     * get existing device of owner or create new device for owner
     * 
     * @param unknown_type $ownerId
     * @param unknown_type $deviceId
     * @param unknown_type $deviceType
     * @param unknown_type $userAgent
     * @param unknown_type $protocolVersion
     * @return Syncope_Model_Device
     */
    protected function _getUserDevice($ownerId, $deviceId, $deviceType, $userAgent, $protocolVersion)
    {
        try {
            $device = $this->_deviceBackend->getUserDevice($ownerId, $deviceId);
        
            $device->useragent = $userAgent;
            $device->acsversion = $protocolVersion;
            $device = $this->_deviceBackend->update($device);
        
        } catch (Syncope_Exception_NotFound $senf) {
            $device = $this->_deviceBackend->create(new Syncope_Model_Device(array(
                'owner_id'   => $ownerId,
                'deviceid'   => $deviceId,
                'devicetype' => $deviceType,
                'useragent'  => $userAgent,
                'acsversion' => $protocolVersion
            )));
        }
        
        return $device;
    }
}