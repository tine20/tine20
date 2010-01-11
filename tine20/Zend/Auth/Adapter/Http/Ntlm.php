<?php
/**
 * Zend Framework
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter_Http
 * @version    $Id$
 */


/**
 * @see Zend_Auth_Adapter_Http_Abstract
 */
require_once 'Zend/Auth/Adapter/Http/Abstract.php';


/**
 * NTLM Authentication Protocol and Security Support Provider
 * 
 * @see http://davenport.sourceforge.net/ntlm.html
 * @see http://ubiqx.org/cifs/SMB.html
 * @see http://technet.microsoft.com/de-de/magazine/2006.08.securitywatch(en-us).aspx
 * 
 * IMPORTANT NOTE: quoting section 2.8.5.7 from  
 *  http://ubiqx.org/cifs/SMB.html: "The use of NTLMv2 is
 *  not negotiated between the client and server. There is 
 *  nothing in the protocol to determine which challenge/response 
 *  algorithms should be used."
 */
class Zend_Auth_Adapter_Http_Ntlm extends Zend_Auth_Adapter_Http_Abstract
{
    /**
     * Indicates that Unicode strings are supported for use in security buffer data.
     */
    const FLAG_NEGOTIATE_UNICODE = 0x00000001;
    /**
     * Indicates that OEM strings are supported for use in security buffer data.
     */
    const FLAG_NEGOTIATE_OEM = 0x00000002;
    /**
     * Requests that the server's authentication realm be included in the Type 2 message.
     */
    const FLAG_REQUEST_TARGET = 0x00000004;
    /**
     * Specifies that authenticated communication between the client and server should 
     * carry a digital signature (message integrity).
     */
    const FLAG_NEGOTIATE_SIGN = 0x00000010;
    /**
     * Specifies that authenticated communication between the client and server should 
     * be encrypted (message confidentiality).
     */
    const FLAG_NEGOTIATE_SEAL = 0x00000020;
    /**
     * Indicates that datagram authentication is being used.
     */
    const FLAG_NEGOTIATE_DATAGRAM_STYLE = 0x00000040;
    /**
     * Indicates that the Lan Manager Session Key should be used for signing and sealing 
     * authenticated communications.
     */
    const FLAG_NEGOTIATE_LAN_MANAGER_KEY = 0x00000080;
    /**
     * Indicates that NTLM authentication is being used.
     */
    const FLAG_NEGOTIATE_NTLM = 0x00000200;
    /**
     * Sent by the client in the Type 3 message to indicate that an anonymous context has 
     * been established. This also affects the response fields
     */
    const FLAG_NEGOTIATE_ANONYMOUS = 0x00000800;
    /**
     * Sent by the client in the Type 1 message to indicate that the name of the domain in 
     * which the client workstation has membership is included in the message. This is used 
     * by the server to determine whether the client is eligible for local authentication.
     */
    const FLAG_NEGOTIATE_DOMAIN_SUPPLIED = 0x00001000;
    /**
     * Sent by the client in the Type 1 message to indicate that the client workstation's 
     * name is included in the message. This is used by the server to determine whether the 
     * client is eligible for local authentication.
     */
    const FLAG_NEGOTIATE_WORKSTATION_SUPPLIED = 0x00002000;
    /**
     * Sent by the server to indicate that the server and client are on the same machine. 
     * Implies that the client may use the established local credentials for authentication 
     * instead of calculating a response to the challenge.
     */
    const FLAG_NEGOTIATE_LOCAL_CALL = 0x00004000;
    /**
     * Indicates that authenticated communication between the client and server should be 
     * signed with a "dummy" signature.
     */
    const FLAG_NEGOTIATE_ALWAYS_SIGN = 0x00008000;
    /**
     * Sent by the server in the Type 2 message to indicate that the target authentication 
     * realm is a domain.
     */
    const FLAG_TARGET_TYPE_DOMAIN = 0x00010000;
    /**
     * Sent by the server in the Type 2 message to indicate that the target authentication 
     * realm is a server.
     */
    const FLAG_TARGET_TYPE_SERVER = 0x00020000;
    /**
     * Sent by the server in the Type 2 message to indicate that the target authentication 
     * realm is a share. Presumably, this is for share-level authentication. Usage is unclear.
     */
    const FLAG_TARGET_TYPE_SHARE = 0x00040000;
    /**
     * Indicates that the NTLM2 signing and sealing scheme should be used for protecting 
     * authenticated communications. Note that this refers to a particular session security 
     * scheme, and is not related to the use of NTLMv2 authentication. This flag can,however, 
     * have an effect on the response calculations
     */
    const FLAG_NEGOTIATE_NTLM2_KEY = 0x00080000;
    /**
     * Sent by the server in the Type 2 message to indicate that it is including a Target 
     * Information block in the message. The Target Information block is used in the 
     * calculation of the NTLMv2 response.
     */
    const FLAG_NEGOTIATE_TARGET_INFO = 0x00800000;
    /**
     * Indicates that 128-bit encryption is supported.
     */
    const FLAG_NEGOTIATE_128 = 0x20000000;
    /**
     * Indicates that the client will provide an encrypted master key in the "Session Key" 
     * field of the Type 3 message.
     */
    const FLAG_NEGOTIATE_KEY_EXCHANGE = 0x40000000;
    /**
     * Indicates that 56-bit encryption is supported.
     */
    const FLAG_NEGOTIATE_56 = 0x80000000;
    
    /**
     * names for buffers
     */
    const BUFFER_DOMAIN         = 'domain';
    const BUFFER_WORKSTATION    = 'workstation';
    const BUFFER_TARGETNAME     = 'targetname';
    const BUFFER_TARGETINFO     = 'targetinfo';
    const BUFFER_LMRESPONSE     = 'lmresponse';
    const BUFFER_NTLMRESPONSE   = 'ntlmresponse';
    const BUFFER_USERNAME       = 'username';
    const BUFFER_SESSIONKEY     = 'sessionkey';
    
    /**
     * auth targets server name
     */
    const TARGETINFO_SERVER = 'servername';
    /**
     * auth targets domain name
     */
    const TARGETINFO_DOMAIN = 'domain';
    /**
     * auth targets fully-qualified DNS host name (i.e., server.domain.com)
     */
    const TARGETINFO_FQSERVER = 'fqserver';
    /**
     * auth DNS domain name (i.e., domain.com)
     */
    const TARGETINFO_DNSDOMAIN = 'dnsdomain';
    
    /**
     * map where to find start of security buffers
     * 
     * @var array $messageNumber => array $buffer => location in hex string
     */
    protected $_securityBufferMap = array(
        1 => array(
            self::BUFFER_DOMAIN       => 32,
            self::BUFFER_WORKSTATION  => 48
        ),
        2 => array(
            self::BUFFER_TARGETNAME   => 24,
            self::BUFFER_TARGETINFO   => 80
        ),
        3 => array(
            self::BUFFER_LMRESPONSE   =>  24,
            self::BUFFER_NTLMRESPONSE =>  40,
            self::BUFFER_TARGETNAME   =>  56,
            self::BUFFER_USERNAME     =>  72,
            self::BUFFER_WORKSTATION  =>  88,
            self::BUFFER_SESSIONKEY   => 104,
        )
    );
    
    /**
     * indicators in targetdata
     *  
     * @var array
     */
    protected $_targetInfoBufferTypMap = array(
        self::TARGETINFO_DOMAIN     => 2,
        self::TARGETINFO_SERVER     => 1,
        self::TARGETINFO_DNSDOMAIN  => 4,
        self::TARGETINFO_FQSERVER   => 3,
    );
    
    /**
     * @var Zend_Log
     */
    protected $_log;
    
    /**
     * @var Zend_Auth_Adapter_Http_Ntlm_Resolver_Interface
     */
    protected $_resolver;
    
    /**
     * @var string current client message
     */
    protected $_ntlmMessage;
    
    /**
     * @var int server flags
     */
    protected $_serverFlags;
    
    /**
     * @var array infos about the client
     */
    protected $_clientInfo;
    
    /**
     * @var array auth target info
     */
    protected $_targetInfo = array();
    
    /**
     * the constructor
     * 
     * @param  array $config
     * @return void
     */
    public function __construct(array $config = array())
    {
        if (array_key_exists('log', $config) && $config['log'] instanceof Zend_Log) {
            $this->_log = $config['log'];
        } else {
            $this->_log = new Zend_Log(new Zend_Log_Writer_Null());
        }
        
        if (array_key_exists('resolver', $config) /*&& $config['resoslver'] instanceof Zend_Auth_Adapter_Http_Resolver_Interface*/) {
            $this->setResolver($config['resolver']);
        }
        
        if (array_key_exists('targetInfo', $config)) {
            $this->_targetInfo = $config['targetInfo'];
        }
        
        if (! array_key_exists('serverFlags', $config)) {
            $config['serverFlags'] = dechex(
                (0x00000000 | self::FLAG_NEGOTIATE_UNICODE | self::FLAG_NEGOTIATE_NTLM)
            );
        }
        
        $this->setServerFlags($config['serverFlags']);
        
        
        // to be removed
        $this->_request = new Zend_Controller_Request_Http();
        $this->_response = new Zend_Controller_Response_Http();
    }
    
    /**
     * Authenticate
     *
     * @throws Zend_Auth_Adapter_Exception
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $authHeader = $this->getRequest()->getHeader('Authorization');
        
        if (! $authHeader) {
            return $this->_challengeClient();
        }
        
        if (substr($authHeader, 0, 5) !== 'NTLM ') {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('Unexpected authentication scheme');
        }
        
        $authMessage = base64_decode(substr($authHeader, 5));
        if (substr($authMessage, 0, 7) != "NTLMSSP") {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('Unexpected client response');
        }
        
        $this->_ntlmMessage = bin2hex($authMessage);
        $this->_log->INFO("client send ntlm message #{$this->_getMessageNumber()}");
        $this->_log->DEBUG("ntlmMessage #{$this->_getMessageNumber()}: {$this->_ntlmMessage}");
        
        if ($this->_getMessageNumber() === 3) {
            return $this->_authenticateClient();
        }
        
        return $this->_challengeClient();
    }
    
    /**
     * return message flags
     * 
     * @return int
     */
    public function getClientFlags()
    {
        $offset = $this->_getMessageNumber() == 1 ? 24 : 120;
        $leFlags = substr($this->_ntlmMessage, $offset, 8);
        
        $flags = $this->leHex2hex($leFlags);
        
        return hexdec($flags);
    }
    
    /**
     * returns client info
     * @return array
     */
    public function getClientInfo()
    {
        if (! empty($this->_clientInfo)) {
            return $this->_clientInfo;
        } else {
            $clientFlags = $this->getClientFlags();
            $this->_clientInfo = array();
        }
        
        
        // message 1 info
        if ($this->_getMessageNumber() == 1) {
            if ($clientFlags & self::FLAG_NEGOTIATE_DOMAIN_SUPPLIED) {
                $this->_clientInfo[self::BUFFER_DOMAIN] = $this->_getBufferData(self::BUFFER_DOMAIN, FALSE);
            }
            
            if ($clientFlags & self::FLAG_NEGOTIATE_WORKSTATION_SUPPLIED) {
                $this->_clientInfo[self::BUFFER_WORKSTATION] = $this->_getBufferData(self::BUFFER_WORKSTATION, FALSE);
            }
        }
        
        // message 3 info
        if ($this->_getMessageNumber() == 3) {
            $this->_clientInfo = array(
                self::BUFFER_USERNAME => $this->_getBufferData(self::BUFFER_USERNAME),
                self::BUFFER_WORKSTATION => $this->_getBufferData(self::BUFFER_WORKSTATION),
                self::BUFFER_TARGETNAME => $this->_getBufferData(self::BUFFER_TARGETNAME),
            );
        }
        
        
        return $this->_clientInfo;
    }
    
    /**
     * returns server flags (hex representation)
     * 
     * $param  $asLittleEndian
     * @return string 
     */
    public function getServerFlags($asLittleEndian = TRUE)
    {
        return $asLittleEndian ? 
            bin2hex(pack('V', $this->_serverFlags)) :
            dechex($this->_serverFlags);
    }
    
    /**
     * sets server falgs from hex representation
     * 
     * @param string $flags in hex representation
     * @return int
     */
    public function setServerFlags($flags)
    {
        $this->_serverFlags = hexdec($flags);
        
        $this->_serverFlags |= self::FLAG_NEGOTIATE_TARGET_INFO;
        
        if (! ($this->_serverFlags & (self::FLAG_TARGET_TYPE_SERVER | self::FLAG_TARGET_TYPE_SHARE))) {
            $this->_serverFlags |= self::FLAG_TARGET_TYPE_DOMAIN;
        }
        
        return $this->_serverFlags;
    }
    
    public function setTargetInfo($target)
    {
        $this->_targetInfo = $target;
    }
    
    /**
     * authenticates ntlm client (response to message 3)
     * 
     * @return Zend_Auth_Result
     */
    protected function _authenticateClient()
    {
        $resolver = $this->getResolver();
        if (! $resolver) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('A resolver object must be set before doing NTLM authentication');
        }
        
        $ntlmResponse = $this->_getBufferData(self::BUFFER_NTLMRESPONSE, FALSE);
        
        $clientBlob     = substr($ntlmResponse, 16);
        $clientBlobHash = substr($ntlmResponse, 0, 16);
        
        $userName = $this->_getBufferData(self::BUFFER_USERNAME);
        $authTarget = $this->_getBufferData(self::BUFFER_TARGETNAME);
        
        $md4hash = $resolver->resolve($userName);
        if (!$md4hash) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('Could not resolve shared secret');
        }
        
        $NTLMv2hash = hash_hmac('md5', $this->toUTF16LE(strtoupper($userName) . $authTarget), $md4hash, TRUE);
        $blobHash = hash_hmac('md5', pack('H*', $this->_getChallenge()) . $clientBlob, $NTLMv2hash, TRUE);
        
        $identity = new Zend_Auth_Adapter_Http_Ntlm_Identity(array(
            'ntlmData' => $this->_clientInfo
        ));
        
        if ($clientBlobHash == $blobHash) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity);
        } else {
            return $this->_challengeClient();
        }
    }
        
    /**
     * (non-PHPdoc)
     * @see tine20/Zend/Auth/Http/Zend_Auth_Adapter_Http_Abstract#_challengeClient()
     */
    protected function _challengeClient()
    {
        $result = parent::_challengeClient();
        
        // include identity from message 1
        return new Zend_Auth_Result(
            $result->getCode(),
            new Zend_Auth_Adapter_Http_Ntlm_Identity(array(
                'flags' => $this->getClientFlags(),
                'ntlmData' => $this->getClientInfo()
            )),
            $result->getMessages()
        ); 
    }
    
    protected function _getAuthHeader()
    {
        $header = 'NTLM';
        
        if ($this->_getMessageNumber() === 1) {
            $message2 = $this->_getChallengeMessage();
            $header .= ' ' . trim(base64_encode(pack('H*', $message2)));
        }
        
        return $header;
    }
    
    /**
     * generates challenge (message 2)
     * 
     * @return string hex
     */
    protected function _getChallengeMessage()
    {
        $clientFlags = $this->getClientFlags();
        
        $useNTLM2SessionSecurity = $clientFlags & self::FLAG_NEGOTIATE_NTLM2_KEY;
        $this->_log->INFO("client " . ($useNTLM2SessionSecurity ? 'supports' : " dosn't") . ' NTLM2 Session Security');
        
        // force NTLM2 as this implies NTLMv2 or NTLM2 session response
        //$this->_serverFlags |= self::FLAG_NEGOTIATE_NTLM2_KEY;
        
        // todo: decide by serverFlags
        $targetInfoBuffer = $this->_getTargetInfoBuffer($this->_targetInfo);
        
        // todo: decide by serverFlags
        $targetNameBuffer = bin2hex($this->toUTF16LE($this->_targetInfo[self::TARGETINFO_DOMAIN]));
        
        // base offset to first buffer
        $offset = 48;
        
        $message2 = 
            '4e544c4d53535000'.                             // NTLMSSP Signature
            '02000000'.                                     // Type 2 Indicator
            bin2hex(pack('vvV',                             // Target Name Security Buffer
                strlen($targetNameBuffer)/2,                //   - Length
                strlen($targetNameBuffer)/2,                //   - Allocated Space
                $offset                                     //   - Offset
            )).
            $this->getServerFlags().                        // Flags
            $this->_getChallenge().                         // Challenge
            '0000000000000000'.                             // Context
            bin2hex(pack('vvV',                             // Target Information Security Buffer
                strlen($targetInfoBuffer)/2,                //   - Length
                strlen($targetInfoBuffer)/2,                //   - Allocated Space
                $offset += strlen($targetNameBuffer)/2      //   - Offset
            )).
            $targetNameBuffer.
            $targetInfoBuffer;
            
        $this->_log->INFO('server generated ntlm message #2');
        $this->_log->DEBUG("ntlmMessage #2: $message2");
        
        return $message2;
    }
    
    // @todo generate random challenge and store it in session
    protected function _getChallenge()
    {
        return '0123456789abcdef';
    }
    
    /**
     * returns decoded buffer data
     * 
     * @param  string $name
     * @param  bool   $isUTF16LE
     * @return string
     */
    protected function _getBufferData($name, $isUTF16LE = TRUE)
    {
        $sboffset = $this->_securityBufferMap[$this->_getMessageNumber()][$name];
        
        $sbhex = substr($this->_ntlmMessage, $sboffset, 16);
        extract(unpack('vlength/vspace/Voffset', pack('H*', $sbhex)));
        
        $hexData = substr($this->_ntlmMessage, $offset*2, $length*2);
        
        $data = pack('H*', $hexData);
        if ($isUTF16LE) {
            $data = iconv('UTF-16LE', 'UTF-8', $data);
        }
        return $data;
    }
    
    /**
     * returns hex representation of given target info
     * 
     * @param  array $targetInfo
     * @return string
     */
    protected function _getTargetInfoBuffer(array $targetInfo)
    {
        $buffer = '';
        foreach ($this->_targetInfoBufferTypMap as $type => $typeIdentifier) {
            $data = array_key_exists($type, $targetInfo) ? $targetInfo[$type] : '';
            $buffer .= $this->_getTargetInfoSubBuffer($type, $data);
        }
        
        // terminate string (hex)
        $buffer .= '00000000';
        
        return $buffer;
    }
    
    /**
     * return hex representation of given target info subblock
     * 
     * @param  string   $type
     * @param  string   $data       utf8 encoded data
     * @return string
     */
    protected function _getTargetInfoSubBuffer($type, $data)
    {
        $utf16le = $this->toUTF16LE($data);
        return bin2hex(pack('vv', $this->_targetInfoBufferTypMap[$type], strlen($utf16le)).$utf16le);
    }
    
    /**
     * gets number of current message
     * 
     * @return int 
     */
    protected function _getMessageNumber()
    {
        return (int) $this->_ntlmMessage[17];
    }
    
    /**
     * converts little-endian hex string to normal hex string
     * 
     * @param  string $leHex
     * @return string
     */
    public static function leHex2hex($leHex)
    {
        $l = $leHex;
        return $l[6].$l[7].$l[4].$l[5].$l[2].$l[3].$l[0].$l[1];
    }
    
    /**
     * converts utf8 string to utf16+little-endian
     * 
     * @param  string $utf8
     * @return string
     */
    public static function toUTF16LE($utf8) {
        return iconv('UTF-8', 'UTF-16LE', $utf8);
    }
}