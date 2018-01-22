<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * webdav Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_WebDAV extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    const REQUEST_TYPE = 'WebDAV';
    
   /**
    * @var \Sabre\DAV\Server
    */
    protected static $_server;

    public function __construct()
    {
        $this->_supportsSessions = true;
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     * @param \Zend\Http\Request $request
     * @param $body
     * @return void
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        try {
            $this->_request = $request instanceof \Zend\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);
            if ($body !== null) {
                $this->_body = $body;
            } else {
                if ($this->_request instanceof \Zend\Http\Request) {
                    $this->_body = fopen('php://temp', 'r+');
                    fwrite($this->_body, $request->getContent());
                    rewind($this->_body);
                    /*
                    * JN: dirty hack for native Windows 7 & 10 webdav client (after early 2017):
                                 * client sends empty request instead empty xml-sceleton -> inject it here
                                 */
                    $broken_user_agent_preg = '/^Microsoft-WebDAV-MiniRedir\/[6,10]/';
                    if (isset($_SERVER['HTTP_USER_AGENT']) && (preg_match($broken_user_agent_preg,
                                $_SERVER['HTTP_USER_AGENT']) === 1)) {
                        if ($request->getContent() == '') {
                            $broken_user_agent_body = '<?xml version="1.0" encoding="utf-8" ?><D:propfind xmlns:D="DAV:"><D:prop>';
                            $broken_user_agent_body .= '<D:creationdate/><D:displayname/><D:getcontentlength/><D:getcontenttype/><D:getetag/><D:getlastmodified/><D:resourcetype/>';
                            $broken_user_agent_body .= '</D:prop></D:propfind>';
                            fwrite($this->_body, $broken_user_agent_body);
                            rewind($this->_body);
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " broken userAgent detected: " .
                                    $_SERVER['HTTP_USER_AGENT'] . " --> inserted xml body");
                            }
                        }
                    }
                }
            }

            $hasIdentity = false;

            if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'],
                        'Microsoft-WebDAV-MiniRedir') === 0)) {
                try {
                    Tinebase_Core::startCoreSession();
                    Tinebase_Core::initFramework();

                    if (Tinebase_Session::isStarted() && Zend_Auth::getInstance()->hasIdentity()) {
                        $hasIdentity = true;
                    }
                } catch (Zend_Session_Exception $zse) {
                    // expire session cookie for client
                    Tinebase_Session::expireSessionCookie();

                    // session error, we just need to start over
                    // but we don't know where we failed, so better initFramework
                    Tinebase_Core::initFramework();
                    if (Tinebase_Auth_NtlmV2::isEnabled()) {
                        (new Tinebase_Auth_NtlmV2())->sendHeaderForAuthPase();
                        return;
                    }
                }

                if (!$hasIdentity && Tinebase_Auth_NtlmV2::isEnabled()) {
                    $ntlm = new Tinebase_Auth_NtlmV2();
                    $ntlmAuthStatus = $ntlm->authorize();

                    if (Tinebase_Auth_NtlmV2::AUTH_SUCCESS === $ntlmAuthStatus) {
                        try {
                            Tinebase_Controller::getInstance()->loginUser($ntlm->getUser(), $this->_request,
                                self::REQUEST_TYPE);
                        } catch (Tinebase_Exception_MaintenanceMode $temm) {
                            header('HTTP/1.1 503 Service Unavailable');
                            return;
                        }
                        $hasIdentity = true;
                    } else {
                        $ntlm->sendHeaderForAuthPase($ntlmAuthStatus);
                        return;
                    }
                }
            }

            if (!$hasIdentity) {
                try {
                    list($loginName, $password) = $this->_getAuthData($this->_request);
                    Tinebase_Core::startCoreSession();
                    Tinebase_Core::initFramework();
                } catch (Tinebase_Exception_NotFound $tenf) {
                    header('WWW-Authenticate: Basic realm="WebDAV for Tine 2.0"');
                    header('HTTP/1.1 401 Unauthorized');

                    return;
                }
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' is CalDav, CardDAV or WebDAV request.');
            }

            if (!$hasIdentity && null !== ($denyList = Tinebase_Config::getInstance()->get(
                    Tinebase_Config::DENY_WEBDAV_CLIENT_LIST)) && is_array($denyList)) {
                foreach ($denyList as $deny) {
                    if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match($deny, $_SERVER['HTTP_USER_AGENT'])) {
                        header('HTTP/1.1 420 Policy Not Fulfilled User Agent Not Accepted');
                        return;
                    }
                }
            }

            try {
                if (!$hasIdentity && Tinebase_Controller::getInstance()->login(
                        $loginName,
                        $password,
                        $this->_request,
                        self::REQUEST_TYPE
                    ) !== true) {
                    header('WWW-Authenticate: Basic realm="WebDAV for Tine 2.0"');
                    header('HTTP/1.1 401 Unauthorized');

                    return;
                }
            } catch (Tinebase_Exception_MaintenanceMode $temm) {
                header('HTTP/1.1 503 Service Unavailable');
                return;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Starting to handle WebDAV request ( requestUri:' . $this->_request->getRequestUri()
                    . ' PID: ' . getmypid() . ')'
                );
            }
            self::$_server = new \Sabre\DAV\Server(new Tinebase_WebDav_Root());
            \Sabre\DAV\Server::$exposeVersion = false;

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                self::$_server->debugExceptions = true;
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " headers: " . print_r(self::$_server->httpRequest->getHeaders(),
                        true));
                $contentType = self::$_server->httpRequest->getHeader('Content-Type');
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " requestContentType: " . $contentType . ' requestMethod: ' . $this->_request->getMethod());

                if (stripos($contentType, 'text') === 0 || stripos($contentType, '/xml') !== false) {
                    // NOTE inputstream can not be rewinded
                    $debugStream = fopen('php://temp', 'r+');
                    stream_copy_to_stream($this->_body, $debugStream);
                    rewind($debugStream);
                    $this->_body = $debugStream;

                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " <<< *DAV request\n" . stream_get_contents($this->_body));
                    rewind($this->_body);
                } else {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " <<< *DAV request\n -- BINARY DATA --");
                }
            }

            self::$_server->httpRequest->setBody($this->_body);

            // compute base uri
            self::$_server->setBaseUri($this->_request->getBaseUrl() . '/');

            $tempDir = Tinebase_Core::getTempDir();
            if (!empty($tempDir)) {
                self::$_server->addPlugin(
                    new \Sabre\DAV\Locks\Plugin(new \Sabre\DAV\Locks\Backend\File($tempDir . '/webdav.lock'))
                );
            }

            self::$_server->addPlugin(
                new \Sabre\DAV\Auth\Plugin(new Tinebase_WebDav_Auth(), null)
            );

            $aclPlugin = new Tinebase_WebDav_Plugin_ACL();
            $aclPlugin->defaultUsernamePath = Tinebase_WebDav_PrincipalBackend::PREFIX_USERS;
            $aclPlugin->principalCollectionSet = array(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS, Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS
            );
            $aclPlugin->principalSearchPropertySet = array(
                '{DAV:}displayname' => 'Display name',
                '{' . \Sabre\DAV\Server::NS_SABREDAV . '}email-address' => 'Email address',
                '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}email-address-set' => 'Email addresses',
                '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name' => 'First name',
                '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name' => 'Last name',
                '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-address-set' => 'Calendar user address set',
                '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type' => 'Calendar user type'
            );

            self::$_server->addPlugin($aclPlugin);

            self::$_server->addPlugin(new \Sabre\CardDAV\Plugin());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPlugin); // this plugin must be loaded before CalDAV plugin
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_FixMultiGet404Plugin()); // replacement for new \Sabre\CalDAV\Plugin());
            self::$_server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginAutoSchedule());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginDefaultAlarms());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginManagedAttachments());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginPrivateEvents());
            self::$_server->addPlugin(new Tinebase_WebDav_Plugin_Inverse());
            self::$_server->addPlugin(new Tinebase_WebDav_Plugin_OwnCloud());
            self::$_server->addPlugin(new Tinebase_WebDav_Plugin_PrincipalSearch());
            self::$_server->addPlugin(new Tinebase_WebDav_Plugin_ExpandedPropertiesReport());
            self::$_server->addPlugin(new \Sabre\DAV\Browser\Plugin());
            if (Tinebase_Config::getInstance()->get(Tinebase_Config::WEBDAV_SYNCTOKEN_ENABLED)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport enabled');
                }
                self::$_server->addPlugin(new Tinebase_WebDav_Plugin_SyncToken());
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport disabled');
                }
            }
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin());

            $contentType = self::$_server->httpRequest->getHeader('Content-Type');
            $logOutput = Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && (stripos($contentType,
                        'text') === 0 || stripos($contentType, '/xml') !== false);

            if ($logOutput) {
                ob_start();
            }

            self::$_server->exec();

            if ($logOutput) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " >>> *DAV response:\n" . ob_get_contents());
                ob_end_flush();
            } else {

                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " <<< *DAV response\n -- BINARY DATA --");
            }

            Tinebase_Controller::getInstance()->logout($this->_request->getServer('REMOTE_ADDR'));
        } catch (Exception $e) {
            Tinebase_Exception::log($e, false);
            @header('HTTP/1.1 500 Internal Server Error');
        }
    }

    /**
     * Set an odd parity bit for a given byte, in least-significant position.
     *
     * @link https://github.com/jclulow/node-smbhash/blob/edc48e2b/lib/common.js
     *   Implementation basis.
     * @param int $byte An 8-bit byte value.
     * @return int An 8-bit byte value.
     */
    private static function setParityBit($byte)
    {
        $parity = 1;
        for ($i = 1; $i < 8; $i++) {
            $parity = ($parity + (($byte >> $i) & 1)) %2;
        }
        $byte = $byte | ($parity & 1);
        return $byte;
    }

   /**
    * helper to return request
    *
    * @return Sabre\HTTP\Request
    */
    public static function getRequest()
    {
        return self::$_server ? self::$_server->httpRequest : new Sabre\HTTP\Request();
    }

    /**
     * helper to return response
     *
     * @return Sabre\HTTP\Response
     */
    public static function getResponse()
    {
        return self::$_server ? self::$_server->httpResponse : new Sabre\HTTP\Response();
    }

    /**
    * returns request method
    *
    * @return string
    */
    public function getRequestMethod()
    {
        return self::getRequest()->getMethod();
    }
}