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
 * @see Zend_Auth_Http_Abstract
 */
require_once 'Zend/Auth/Http/Abstract.php';


/**
 * NTLM Authentication Protocol and Security Support Provider
 * @see http://davenport.sourceforge.net/ntlm.html 
 */
class Zend_Auth_Http_Ntlm extends Zend_Auth_Http_Abstract
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
     * @var string current client message
     */
    protected $_ntlmMessage;
    
    public function __construct()
    {
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
        if (substr($authMessage, 0, 8) != "NTLMSSP\x00") {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('Unexpected client response');
        }
        
        $this->_ntlmMessage = $authMessage;
        if ($this->getMessageNumber() === 3) {
            // try to authenticate
        }
        
        return $this->_challengeClient();
    }
    
    protected function _getAuthHeader()
    {
        $header = 'NTLM';
        
        if ($this->getMessageNumber() === 1) {
            $message2 = $this->_getChallengeMessage();
            $header .= ' ' . trim(base64_encode($message2));
        }
        
        return $header;
    }
    
    /**
     * generates challenge message
     * 
     * @return string
     */
    protected function _getChallengeMessage()
    {
        $flags = $this->getMessageFlags();
        
        error_log(dechex($flags));
        
        /*
        $domain = $this->_getMessageValue(16);
        $ws     = $this->_getMessageValue(24);
        $tdata  = $this->avPair(2, $this->toUTF16le($this->_domain)).
                  $this->avPair(1, $this->toUTF16le($this->_computer)).
                  $this->avPair(4, $this->toUTF16le($this->_dnsdomain)).
                  $this->avPair(3, $this->toUTF16le($this->_dnscomputer)).
                  "\0\0\0\0\0\0\0\0";
        $tname  = $this->toUTF16le($this->_targetname);
    
        return "NTLMSSP\x00\x02\x00\x00\x00".
            pack('vvV', strlen($tname), strlen($tname), 48). // target name len/alloc/offset
            "\x01\x02\x81\x00". // flags
            $this->_getChallenge(). // challenge
            "\x00\x00\x00\x00\x00\x00\x00\x00". // context
            pack('vvV', strlen($tdata), strlen($tdata), 48 + strlen($tname)). // target info len/alloc/offset
            $tname.$tdata;
        */
    }
    
    // @todo generate random challenge and store it in session
    protected function _getChallenge()
    {
        return '12345678';
    }
    
    protected function getMessageFlags()
    {
        $offset = $this->getMessageNumber() == 1 ? 12 : 60;
        $leFlags = substr($this->_ntlmMessage, $offset, 4);
        
        $flags = 0x00000000;
        extract(unpack('Vflags', $leFlags), EXTR_OVERWRITE);
        
        return $flags;
    }
    
    /**
     * gets number of current message
     * 
     * @return int 
     */
    protected function getMessageNumber()
    {
        // for some reason bindec does not work
        return hexdec(bin2hex($this->_ntlmMessage[8]));
    }
    
    /**
     * get contents from response message
     * 
     * @param  int  $start
     * @param  bool $decode_utf16
     * @return string
     */
    protected function _getMessageValue($start, $decode_utf16 = true) {
        if (! $this->_ntlmMessage) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('client response message not set');
        }
        
        $len = (ord($this->_ntlmMessage[$start+1]) * 256) + ord($this->_ntlmMessage[$start]);
        $off = (ord($this->_ntlmMessage[$start+5]) * 256) + ord($this->_ntlmMessage[$start+4]);
        $result = substr($this->_ntlmMessage, $off, $len);
        
        if ($decode_utf16) {
            iconv('UTF-16LE', 'UTF-8', $result);
        }
        
        return $result;
    }
    
    /**
     * 
     * @param  int    $type
     * @param  string $utf16
     * 
     * @return string
     */
    function avPair($type, $utf16) {
        return pack('v', $type).pack('v', strlen($utf16)).$utf16;
    }
    
    /**
     * converts UTF-8 to UTF-16LE
     * 
     * @param  string $str
     * @return string
     */
    public static function toUTF16le($str) {
        return iconv('UTF-8', 'UTF-16LE', $str);
    }
}