<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle incoming http ActiveSync requests
 * 
 * @package     Syncroton
 */
class Syncroton_Server
{
    protected $_body;
    
    /**
     * informations about the currently device
     *
     * @var Syncroton_Backend_IDevice
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
        if (Syncroton_Registry::isRegistered('loggerBackend')) {
            $this->_logger = Syncroton_Registry::get('loggerBackend');
        }
        
        $this->_userId  = $userId;
        $this->_request = $request instanceof Zend_Controller_Request_Http ? $request : new Zend_Controller_Request_Http();
        $this->_body    = $body !== null ? $body : fopen('php://input', 'r');
        
        $this->_deviceBackend = Syncroton_Registry::getDeviceBackend();
        
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
        $command = new Syncroton_Command_Options();
    
        $command->getResponse();
    }
    
    protected function _handlePost()
    {
        $requestParameters = $this->_getRequestParameters($this->_request);
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST ' . print_r($requestParameters, true));
        
        $className = 'Syncroton_Command_' . $requestParameters['command'];
        
        if(!class_exists($className)) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " command not supported: " . $requestParameters['command']);
            
            header("HTTP/1.1 501 not implemented");
            
            return;
        }
        
        // get user device
        $device = $this->_getUserDevice($this->_userId, $requestParameters);
        
        if ($requestParameters['contentType'] == 'application/vnd.ms-sync.wbxml') {
            // decode wbxml request
            try {
                $decoder = new Syncroton_Wbxml_Decoder($this->_body);
                $requestBody = $decoder->decode();
                if ($this->_logger instanceof Zend_Log) {
                    $requestBody->formatOutput = true;
                    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " xml request:\n" . $requestBody->saveXML());
                }
            } catch(Syncroton_Wbxml_Exception_UnexpectedEndOfFile $e) {
                $requestBody = NULL;
            }
        } else {
            $requestBody = $this->_body;
        }
        
        if (PHP_SAPI !== 'cli') {
            header("MS-Server-ActiveSync: 14.00.0536.000");
        }

        try {
            $command = new $className($requestBody, $device, $requestParameters);
        
            $command->handle();
        
            $response = $command->getResponse();
            
        } catch (Syncroton_Exception_PolicyKeyMissing $sepkm) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " X-MS-POLICYKEY missing (" . $_command. ')');
            
            header("HTTP/1.1 400 header X-MS-POLICYKEY not found");
            
        } catch (Syncroton_Exception_ProvisioningNeeded $sepn) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " provisioning needed");
            
            if (version_compare($device->acsversion, '14.0', '>=')) {
                $response = $sepn->domDocument;
            } else {
                // pre 14.0 method
                header("HTTP/1.1 449 Retry after sending a PROVISION command");
                   
                return;
            }
            
            
            
        } catch (Exception $e) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " unexpected exception occured: " . get_class($e));
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " exception message: " . $e->getMessage());
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " " . $e->getTraceAsString());
            
            header("HTTP/1.1 500 Internal server error");
            
            return;
        }
        
        if ($response instanceof DOMDocument) {
            if ($this->_logger instanceof Zend_Log) {
                $response->formatOutput = true;
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " xml response:\n" . $response->saveXML());
            }
        
            $outputStream = fopen("php://temp", 'r+');
        
            $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
            $encoder->encode($response);

            // avoid sending headers while running on cli (phpunit)
            if (PHP_SAPI !== 'cli') {
                header("Content-Type: application/vnd.ms-sync.wbxml");
            }
        
            rewind($outputStream);
            fpassthru($outputStream);
        }
    }
    
    /**
     * return request params
     * 
     * @return array
     */
    protected function _getRequestParameters(Zend_Controller_Request_Http $request)
    {
        if(count($_GET) == 1) {
            $arrayKeys = array_keys($_GET);
            
            $base64Decoded = base64_decode($arrayKeys[0]);
            
            $stream = fopen("php://temp", 'r+');
            fwrite($stream, base64_decode($arrayKeys[0]));
            rewind($stream);

            #fpassthru($stream);rewind($stream);
            
            $protocolVersion = ord(fread($stream, 1));
            switch (ord(fread($stream, 1))) {
                case 0:
                    $command = 'Sync';
                    break;
                case 1:
                    $command = 'SendMail';
                    break;
                case 2:
                    $command = 'SmartForward';
                    break;
                case 3:
                    $command = 'SmartReply';
                    break;
                case 4:
                    $command = 'GetAttachment';
                    break;
                case 9:
                    $command = 'FolderSync';
                    break;
                case 10:
                    $command = 'FolderCreate';
                    break;
                case 11:
                    $command = 'FolderDelete';
                    break;
                case 12:
                    $command = 'FolderUpdate';
                    break;
                case 13:
                    $command = 'MoveItems';
                    break;
                case 14:
                    $command = 'GetItemEstimate';
                    break;
                case 15:
                    $command = 'MeetingResponse';
                    break;
                case 16:
                    $command = 'Search';
                    break;
                case 17:
                    $command = 'Settings';
                    break;
                case 18:
                    $command = 'Ping';
                    break;
                case 19:
                    $command = 'ItemOperations';
                    break;
                case 20:
                    $command = 'Provision';
                    break;
                case 21:
                    $command = 'ResolveRecipients';
                    break;
                case 22:
                    $command = 'ValidateCert';
                    break;
            }
            
            $locale = fread($stream, 2);
            
            $deviceIdLength = ord(fread($stream, 1));
            if ($deviceIdLength > 0) {
                $deviceId = fread($stream, $deviceIdLength);
            } 
            
            $policyKeyLength = ord(fread($stream, 1));
            if ($policyKeyLength > 0) {
                $policyKey = fread($stream, 4);
            }
            
            $deviceTypeLength = ord(fread($stream, 1));
            $deviceType = fread($stream, $deviceTypeLength);
            
            // @todo parse command parameters 
            $result = array(
                'protocolVersion' => $protocolVersion,
                'command'         => $command,
                'deviceId'        => $deviceId,
                'deviceType'      => $deviceType,
                'saveInSent'      => null,
                'collectionId'    => null,
                'itemId'          => null,
                'attachmentName'  => null
            );
        } else {
            $result = array(
                'protocolVersion' => $request->getServer('HTTP_MS_ASPROTOCOLVERSION'),
                'command'         => $request->getQuery('Cmd'),
                'deviceId'        => $request->getQuery('DeviceId'),
                'deviceType'      => $request->getQuery('DeviceType'),
                'saveInSent'      => $request->getQuery('SaveInSent'),
                'collectionId'    => $request->getQuery('CollectionId'),
                'itemId'          => $request->getQuery('ItemId'),
                'attachmentName'  => $request->getQuery('AttachmentName'),
            );
        }
        
        $result['userAgent']   = $request->getServer('HTTP_USER_AGENT', $result['deviceType']);
        $result['policyKey']   = $request->getServer('HTTP_X_MS_POLICYKEY');
        $result['contentType'] = $request->getServer('CONTENT_TYPE');
        
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
     * @return Syncroton_Model_Device
     */
    protected function _getUserDevice($ownerId, $requestParameters)
    {
        try {
            $device = $this->_deviceBackend->getUserDevice($ownerId, $requestParameters['deviceId']);
        
            $device->useragent  = $requestParameters['userAgent'];
            $device->acsversion = $requestParameters['protocolVersion'];
            
            $device = $this->_deviceBackend->update($device);
        
        } catch (Syncroton_Exception_NotFound $senf) {
            $device = $this->_deviceBackend->create(new Syncroton_Model_Device(array(
                'owner_id'   => $ownerId,
                'deviceid'   => $requestParameters['deviceId'],
                'devicetype' => $requestParameters['deviceType'],
                'useragent'  => $requestParameters['userAgent'],
                'acsversion' => $requestParameters['protocolVersion']
            )));
        }
        
        return $device;
    }
}