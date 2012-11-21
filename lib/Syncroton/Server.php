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
    const PARAMETER_ATTACHMENTNAME = 0;
    const PARAMETER_COLLECTIONID   = 1;
    const PARAMETER_ITEMID         = 3;
    const PARAMETER_OPTIONS        = 7;
    
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
     */
    protected function _handleOptions()
    {
        $command = new Syncroton_Command_Options();
    
        $this->_sendHeaders($command->getHeaders());
    }
    
    protected function _sendHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
    } 
    
    /**
     * handle post request
     */
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
        
        if ($requestParameters['contentType'] == 'application/vnd.ms-sync.wbxml' || $requestParameters['contentType'] == 'application/vnd.ms-sync') {
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
        
        header("MS-Server-ActiveSync: 14.00.0536.000");

        try {
            $command = new $className($requestBody, $device, $requestParameters);
        
            $command->handle();
        
            $response = $command->getResponse();
            
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
                $response->formatOutput = false;
            }
            
            if (isset($command) && $command instanceof Syncroton_Command_ICommand) {
                $this->_sendHeaders($command->getHeaders());
            }
            
            $outputStream = fopen("php://temp", 'r+');
            
            $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
            $encoder->encode($response);
            
            if ($requestParameters['acceptMultipart'] == true) {
                $parts = $command->getParts();
                
                // output multipartheader
                $bodyPartCount = 1 + count($parts);
                
                // number of parts (4 bytes)
                $header  = pack('i', $bodyPartCount);
                
                $partOffset = 4 + (($bodyPartCount * 2) * 4);
                
                // wbxml body start and length
                $streamStat = fstat($outputStream);
                $header .= pack('ii', $partOffset, $streamStat['size']);
                
                $partOffset += $streamStat['size'];
                
                // calculate start and length of parts
                foreach ($parts as $partId => $partStream) {
                    rewind($partStream);
                    $streamStat = fstat($partStream);
                    
                    // part start and length
                    $header .= pack('ii', $partOffset, $streamStat['size']);
                    $partOffset += $streamStat['size'];
                }
                
                echo $header;
            }
                        
            // output body
            rewind($outputStream);
            fpassthru($outputStream);
            
            // output multiparts
            if (isset($parts)) {
                foreach ($parts as $partStream) {
                    rewind($partStream);
                    fpassthru($partStream);
                }
            }
        }
    }    
    
    /**
     * return request params
     * 
     * @return array
     */
    protected function _getRequestParameters(Zend_Controller_Request_Http $request)
    {
        if (strpos($request->getRequestUri(), '&') === false) {
            $commands = array(
                0  => 'Sync',
                1  => 'SendMail',
                2  => 'SmartForward',
                3  => 'SmartReply',
                4  => 'GetAttachment',
                9  => 'FolderSync',
                10 => 'FolderCreate',
                11 => 'FolderDelete',
                12 => 'FolderUpdate',
                13 => 'MoveItems',
                14 => 'GetItemEstimate',
                15 => 'MeetingResponse',
                16 => 'Search',
                17 => 'Settings',
                18 => 'Ping',
                19 => 'ItemOperations',
                20 => 'Provision',
                21 => 'ResolveRecipients',
                22 => 'ValidateCert'
            );
            
            $requestParameters = substr($request->getRequestUri(), strpos($request->getRequestUri(), '?'));

            $stream = fopen("php://temp", 'r+');
            fwrite($stream, base64_decode($requestParameters));
            rewind($stream);

            // unpack the first 4 bytes
            $unpacked = unpack('CprotocolVersion/Ccommand/vlocale', fread($stream, 4));
            
            // 140 => 14.0
            $protocolVersion = substr($unpacked['protocolVersion'], 0, -1) . '.' . substr($unpacked['protocolVersion'], -1);
            $command = $commands[$unpacked['command']];
            $locale = $unpacked['locale'];
            
            // unpack deviceId
            $length = ord(fread($stream, 1));
            if ($length > 0) {
                $toUnpack = fread($stream, $length);
                
                $unpacked = unpack("H" . ($length * 2) . "string", $toUnpack);
                $deviceId = $unpacked['string'];
            }
            
            // unpack policyKey
            $length = ord(fread($stream, 1));
            if ($length > 0) {
                $unpacked  = unpack('Vstring', fread($stream, $length));
                $policyKey = $unpacked['string'];
            }
            
            // unpack device type
            $length = ord(fread($stream, 1));
            if ($length > 0) {
                $unpacked   = unpack('A' . $length . 'string', fread($stream, $length));
                $deviceType = $unpacked['string'];
            }
            
            while (! feof($stream)) {
                $tag    = ord(fread($stream, 1));
                $length = ord(fread($stream, 1));

                switch ($tag) {
                    case self::PARAMETER_ATTACHMENTNAME:
                        $unpacked = unpack('A' . $length . 'string', fread($stream, $length));
                        
                        $attachmentName = $unpacked['string'];
                        break;
                        
                    case self::PARAMETER_COLLECTIONID:
                        $unpacked = unpack('A' . $length . 'string', fread($stream, $length));
                        
                        $collectionId = $unpacked['string'];
                        break;
                        
                    case self::PARAMETER_ITEMID:
                        $unpacked = unpack('A' . $length . 'string', fread($stream, $length));
                        
                        $itemId = $unpacked['string'];
                        break;
                        
                    case self::PARAMETER_OPTIONS:
                        $options = ord(fread($stream, 1));
                        
                        $saveInSent      = !!($options & 0x01); 
                        $acceptMultiPart = !!($options & 0x02);
                        break;
                        
                    default:
                        if ($this->_logger instanceof Zend_Log)
                            $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " found unhandled command parameters");
                        
                }
            }
             
            $result = array(
                'protocolVersion' => $protocolVersion,
                'command'         => $command,
                'deviceId'        => $deviceId,
                'deviceType'      => isset($deviceType)      ? $deviceType      : null,
                'policyKey'       => isset($policyKey)       ? $policyKey       : null,
                'saveInSent'      => isset($saveInSent)      ? $saveInSent      : false,
                'collectionId'    => isset($collectionId)    ? $collectionId    : null,
                'itemId'          => isset($itemId)          ? $itemId          : null,
                'attachmentName'  => isset($attachmentName)  ? $attachmentName  : null,
                'acceptMultipart' => isset($acceptMultiPart) ? $acceptMultiPart : false
            );
        } else {
            $result = array(
                'protocolVersion' => $request->getServer('HTTP_MS_ASPROTOCOLVERSION'),
                'command'         => $request->getQuery('Cmd'),
                'deviceId'        => $request->getQuery('DeviceId'),
                'deviceType'      => $request->getQuery('DeviceType'),
                'policyKey'       => $request->getServer('HTTP_X_MS_POLICYKEY'),
                'saveInSent'      => $request->getQuery('SaveInSent') == 'T',
                'collectionId'    => $request->getQuery('CollectionId'),
                'itemId'          => $request->getQuery('ItemId'),
                'attachmentName'  => $request->getQuery('AttachmentName'),
                'acceptMultipart' => $request->getServer('HTTP_MS_ASACCEPTMULTIPART') == 'T'
            );
        }
        
        $result['userAgent']   = $request->getServer('HTTP_USER_AGENT', $result['deviceType']);
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
                'acsversion' => $requestParameters['protocolVersion'],
                'policyId'   => Syncroton_Registry::isRegistered(Syncroton_Registry::DEFAULT_POLICY) ? Syncroton_Registry::get(Syncroton_Registry::DEFAULT_POLICY) : null
            )));
        }
        
        return $device;
    }
}