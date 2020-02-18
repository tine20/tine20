<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * NtlmV2 authentication backend
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_NtlmV2
{
    const AUTH_SUCCESS = 1;
    const AUTH_FAILURE = -1;
    const AUTH_FAULTY_NTLM = -2;
    const AUTH_NOT_NTLM = -3;
    const AUTH_PHASE_NOT_STARTED = -4;
    const AUTH_PHASE_ONE = -5;

    protected static $_isEnabled = null;

    protected $_serverNounce;
    protected $_targetName = 'testwebsite';
    protected $_computer = 'mycomputer';
    protected $_dnsDomain = 'testdomain.local';
    protected $_dnsComputer = 'mycomputer.local';
    protected $_msg;
    protected $_response = null;
    protected $_error = [];
    protected $_lastAuthStatus = null;

    /**
     * @var null|Tinebase_Model_FullUser
     */
    protected $_user = null;

    public function __construct()
    {
        /** @var Zend_Session_Namespace $session */
        $session = Tinebase_Core::get(Tinebase_Core::SESSION);

        if (!$session) {
            // ok we have a session problem, auth wont work... but we better not die here... let's just loop a bit
            $this->_serverNounce = static::ntlm_get_random_bytes(8);
        } else {
            if (!isset($session->ntlmv2ServerNounce)) {
                $session->ntlmv2ServerNounce = static::ntlm_get_random_bytes(8);
            }
            $this->_serverNounce = $session->ntlmv2ServerNounce;
        }
    }

    /**
     * @return string
     */
    public function getServerNounce()
    {
        return $this->_serverNounce;
    }

    /**
     * @return null|Tinebase_Model_FullUser
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * @return string
     */
    public function getErrors()
    {
        return join(PHP_EOL, $this->_error);
    }

    /**
     * @return null|string
     */
    public function getLastResponse()
    {
        return $this->_response;
    }

    /**
     * @return null|int
     */
    public function getLastAuthStatus()
    {
        return $this->_lastAuthStatus;
    }

    /**
     * @param int $phase
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function sendHeaderForAuthPase($phase = self::AUTH_PHASE_NOT_STARTED)
    {
        if (self::AUTH_SUCCESS === $phase) {
            throw new Tinebase_Exception_InvalidArgument('can\'t send headers for authentication success');
        }

        header('HTTP/1.1 401 Unauthorized');
        if (self::AUTH_PHASE_ONE === $phase) {
            header('WWW-Authenticate: NTLM ' . trim(base64_encode($this->_response)));
        } else {
            header('WWW-Authenticate: NTLM');
        }
    }

    /**
     * @return int
     */
    public function authorize(\Zend\Http\PhpEnvironment\Request $request)
    {
        $this->_lastAuthStatus = $this->_authorize($request);
        return $this->_lastAuthStatus;
    }

    /**
     * @return int
     */
    protected function _authorize(\Zend\Http\PhpEnvironment\Request $request)
    {
        if (empty($auth = $request->getHeaders('Authorization')) &&
                empty($auth = $request->getServer('HTTP_AUTHORIZATION'))) {
            return self::AUTH_PHASE_NOT_STARTED;
        }

        $auth = $auth->getFieldValue();
        if (substr($auth, 0, 5) !== 'NTLM ') {
            return self::AUTH_NOT_NTLM;
        }

        $this->_msg = base64_decode(substr($auth, 5));
        if (substr($this->_msg, 0, 8) !== "NTLMSSP\x00") {
            return self::AUTH_FAULTY_NTLM;
        }

        if ($this->_msg[8] == "\x01") {
            if (null === ($this->_response = $this->_ntlm_get_challenge_msg())) {
                return self::AUTH_FAULTY_NTLM;
            }
            return self::AUTH_PHASE_ONE;

        } elseif ($this->_msg[8] == "\x03") {
            if (null === ($result = $this->_ntlm_parse_response_msg())) {
                return self::AUTH_FAULTY_NTLM;
            }
            return $result ? self::AUTH_SUCCESS : self::AUTH_FAILURE;

        } else {
            $this->_error[] = 'msg number not 1 or 3';
            return self::AUTH_FAULTY_NTLM;
        }
    }

    /**
     * @param string $str
     * @return string
     */
    protected static function _ntlm_utf8_to_utf16le($str)
    {
        return iconv('UTF-8', 'UTF-16LE', $str);
    }

    /**
     * @param string $s
     * @return string
     */
    protected static function _ntlm_md4($s)
    {
        if (function_exists('mhash'))
            return mhash(MHASH_MD4, $s);
        return pack('H*', hash('md4', $s));
    }

    /**
     * @param string $type
     * @param string $utf16
     * @return string
     */
    protected function _ntlm_av_pair($type, $utf16)
    {
        return pack('v', $type) . pack('v', strlen($utf16)) . $utf16;
    }

    /**
     * @param string $msg
     * @param int $start
     * @param bool $decode_utf16
     * @return null|string
     */
    protected function _ntlm_field_value($msg, $start, $decode_utf16 = true)
    {
        if (strlen($msg) < $start + 4) {
            $this->_error[] = 'msg len shorter than start + 4 ' . ($start + 4);
            return null;
        }
        $len = (ord($msg[$start+1]) * 256) + ord($msg[$start]);
        $off = (ord($msg[$start+5]) * 256) + ord($msg[$start+4]);
        if (strlen($msg) < $off + $len) {
            $this->_error[] = 'msg len shorter than start + offset ' . ($off + $len);
            return null;
        }
        $result = substr($msg, $off, $len);
        if ($decode_utf16) {
            if (false === ($result = @iconv('UTF-16LE', 'UTF-8', $result))) {
                $this->_error[] = 'could not decode UTF-16LE';
                return null;
            }
        }
        return $result;
    }

    /**
     * @param string $key
     * @param string $msg
     * @return string
     */
    public static function ntlm_hmac_md5($key, $msg)
    {
        $blocksize = 64;
        if (strlen($key) > $blocksize)
            $key = pack('H*', md5($key));

        $key = str_pad($key, $blocksize, "\0");
        $ipadk = $key ^ str_repeat("\x36", $blocksize);
        $opadk = $key ^ str_repeat("\x5c", $blocksize);
        return pack('H*', md5($opadk . pack('H*', md5($ipadk . $msg))));
    }

    /**
     * @param int $length
     * @return string
     */
    public static function ntlm_get_random_bytes($length)
    {
        if (function_exists('random_bytes')) {
            try {
                return random_bytes($length);
            } catch (Exception $e) {}
        }
        if (function_exists('openssl_random_pseudo_bytes') &&
            false !== ($result = openssl_random_pseudo_bytes($length))) {
            return $result;
        }
        $result = '';
        if (function_exists('mt_rand')) {
            for ($i = 0; $i < $length; $i++) {
                $result .= chr(mt_rand(0, 255));
            }
        } else {
            for ($i = 0; $i < $length; $i++) {
                $result .= chr(rand(0, 255));
            }
        }
        return $result;
    }

    /**
     * @return null|string
     */
    protected function _ntlm_get_challenge_msg()
    {
        if (null === ($domain = $this->_ntlm_field_value($this->_msg, 16))) {
            $this->_error[] = 'couldn\'t get domain';
            return null;
        }
        $tdata = $this->_ntlm_av_pair(2, static::_ntlm_utf8_to_utf16le($domain)) . $this->_ntlm_av_pair(1,
                static::_ntlm_utf8_to_utf16le($this->_computer)) . $this->_ntlm_av_pair(4,
                static::_ntlm_utf8_to_utf16le($this->_dnsDomain)). $this->_ntlm_av_pair(3,
                static::_ntlm_utf8_to_utf16le($this->_dnsComputer)) . "\0\0\0\0\0\0\0\0";
        $tname = static::_ntlm_utf8_to_utf16le($this->_targetName);
        $msg2 = "NTLMSSP\x00\x02\x00\x00\x00"
            . pack('vvV', strlen($tname), strlen($tname), 48) // target name len/alloc/offset
            . "\x01\x02\x81\x00" // flags
            . $this->_serverNounce
            . "\x00\x00\x00\x00\x00\x00\x00\x00" // context
            . pack('vvV', strlen($tdata), strlen($tdata), 48 + strlen($tname)) // target info len/alloc/offset
            . $tname . $tdata;
        return $msg2;
    }

    /**
     * @param string $user
     * @param string $domain
     * @param string $clientblobhash
     * @param string $clientblob
     * @return bool
     */
    protected function _ntlm_verify_hash($user, $domain, $clientblobhash, $clientblob)
    {
        $md4hash = $this->_get_ntlm_user_hash($user);
        if (false === $md4hash) {
            return false;
        }
        $ntlmv2hash = static::ntlm_hmac_md5($md4hash, static::_ntlm_utf8_to_utf16le(strtoupper($user) . $domain));
        $blobhash = static::ntlm_hmac_md5($ntlmv2hash, $this->_serverNounce . $clientblob);

        return ($blobhash === $clientblobhash);
    }

    /**
     * @return bool|null
     */
    protected function _ntlm_parse_response_msg() {
        if (null === ($user = $this->_ntlm_field_value($this->_msg, 36))) {
            $this->_error[] = 'couldn\'t get user';
            return null;
        }
        if (null === ($domain = $this->_ntlm_field_value($this->_msg, 28))) {
            $this->_error[] = 'couldn\'t get domain';
            return null;
        }
        if (null === ($ntlmresponse = $this->_ntlm_field_value($this->_msg, 20, false))) {
            $this->_error[] = 'couldn\'t get ntlmresponse';
            return null;
        }

        /*if (strlen($ntlmresponse) !== 264) {
            $this->_error[] = 'ntlmresponse part should be 264 byte long ' . strlen($ntlmresponse); == 264
            return null;
        }*/

        $clientblob = substr($ntlmresponse, 16);
        $clientblobhash = substr($ntlmresponse, 0, 16);
        if (substr($clientblob, 0, 8) != "\x01\x01\x00\x00\x00\x00\x00\x00") {
            $this->_error = 'NTLMv2 response required. Please force your client to use NTLMv2.';
            return null;
        }

        return $this->_ntlm_verify_hash($user, $domain, $clientblobhash, $clientblob);
    }

    /**
     * @param string $user
     * @return bool
     */
    protected function _get_ntlm_user_hash($user) {
        try {
            $this->_user = Tinebase_User::getInstance()->getFullUserByLoginName($user);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $hash = Tinebase_User::getInstance()->getNtlmV2Hash($this->_user->accountId);
        if (empty($hash)) {
            return false;
        }

        return $hash;
    }

    /**
     * @param string $pwd
     * @return string
     */
    public static function getPwdHash($pwd)
    {
        // enforce utf8
        $pwd = Tinebase_Helper::mbConvertTo($pwd);
        return static::_ntlm_md4(static::_ntlm_utf8_to_utf16le($pwd));
    }

    /**
     * @return boolean
     */
    public static function isEnabled()
    {
        if (null === static::$_isEnabled) {
            if (Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_SUPPORT_NTLMV2} &&
                    !empty(Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_NTLMV2_ENCRYPTION_KEY}) &&
                    get_class(Tinebase_User::getInstance()) === Tinebase_User_Sql::class) {
                static::$_isEnabled = true;
            } else {
                static::$_isEnabled = false;
            }
        }
        return static::$_isEnabled;
    }
}