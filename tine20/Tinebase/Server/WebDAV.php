<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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

    /**
     * @var Tinebase_Auth_NtlmV2
     */
    protected $_ntlmV2 = null;

    public function __construct()
    {
        $this->_supportsSessions = true;
        parent::__construct();
    }

    /**
     * @return Tinebase_Auth_NtlmV2
     */
    public function getNtlmV2()
    {
        if (null === $this->_ntlmV2) {
            $this->_ntlmV2 = new Tinebase_Auth_NtlmV2();
        }
        return $this->_ntlmV2;
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
                if ($this->_request instanceof Tinebase_Http_Request) {
                    $this->_body = $this->_request->getContentStream();
                } else if ($this->_request instanceof \Zend\Http\Request) {
                    $this->_body = fopen('php://temp', 'r+');
                    fwrite($this->_body, $request->getContent());
                    rewind($this->_body);
                }
            }

            /*
             * JN: dirty hack for native Windows 7 & 10 webdav client (after early 2017):
             * client sends empty request instead empty xml-skeleton -> inject it here
             *(improvement: do not rely on user agent because other clients use windows stuff, too)
             */
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'PROPFIND') {
                $content = stream_get_contents($this->_body);
                rewind($this->_body);


                if ($content == '') {
                    $broken_user_agent_body = '<?xml version="1.0" encoding="utf-8" ?><D:propfind xmlns:D="DAV:"><D:prop>';
                    $broken_user_agent_body .= '<D:creationdate/><D:displayname/><D:getcontentlength/>';
                    $broken_user_agent_body .= '<D:getcontenttype/><D:getetag/><D:getlastmodified/><D:resourcetype/>';
                    $broken_user_agent_body .= '</D:prop></D:propfind>';
                    fwrite($this->_body, $broken_user_agent_body);
                    rewind($this->_body);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " broken userAgent detected: " .
                            (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown user agent') . " --> inserted xml body");
                    }
                }
            }

            $hasIdentity = false;

            if (isset($_SERVER['HTTP_USER_AGENT']) &&
                    (strpos($_SERVER['HTTP_USER_AGENT'], 'Microsoft-WebDAV-MiniRedir') === 0 ||
                        strpos($_SERVER['HTTP_USER_AGENT'], 'Microsoft Office') === 0)) {
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
                        $this->getNtlmV2()->sendHeaderForAuthPase();
                        return;
                    }
                }

                if (!$hasIdentity && Tinebase_Auth_NtlmV2::isEnabled()) {
                    $ntlmAuthStatus = $this->getNtlmV2()->authorize($this->_request);

                    if (Tinebase_Auth_NtlmV2::AUTH_SUCCESS === $ntlmAuthStatus) {
                        try {
                            Tinebase_Controller::getInstance()->loginUser($this->_ntlmV2->getUser(), $this->_request,
                                self::REQUEST_TYPE);
                        } catch (Tinebase_Exception_MaintenanceMode $temm) {
                            header('HTTP/1.1 503 Service Unavailable');
                            return;
                        }
                        $hasIdentity = true;
                    } else {
                        $this->_ntlmV2->sendHeaderForAuthPase($ntlmAuthStatus);
                        return;
                    }
                }
            }

            if (!$hasIdentity) {
                try {
                    list($loginName, $password) = $this->_getAuthData($this->_request);
                    Tinebase_Core::startCoreSession();
                    Tinebase_Core::initFramework();
                } catch (Zend_Session_Exception $zse) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                            . ' Maintenance mode / other session problem: ' . $zse->getMessage());
                    }
                    header('HTTP/1.1 503 Service Unavailable');
                    return;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    header('WWW-Authenticate: Basic realm="WebDAV for Tine 2.0"');
                    header('HTTP/1.1 401 Unauthorized');

                    return;
                }
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' is CalDav, CardDAV or WebDAV request.');
            }


            if (!$hasIdentity && $this->_request->getMethod() !== 'GET' && null !== ($denyList = Tinebase_Config
                    ::getInstance()->get(Tinebase_Config::DENY_WEBDAV_CLIENT_LIST)) && is_array($denyList)) {
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
            self::$_server = new \Sabre\DAV\Server(new Filemanager_Frontend_WebDAV('',
                [Filemanager_Frontend_WebDAV::FM_REAL_WEBDAV_ROOT => new Tinebase_WebDav_Root()]));
            \Sabre\DAV\Server::$exposeVersion = false;
            self::$_server->httpResponse = new Tinebase_WebDav_HTTP_LogResponse();

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                self::$_server->debugExceptions = true;
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " headers: " . print_r(self::$_server->httpRequest->getHeaders(),
                        true));
                $contentType = self::$_server->httpRequest->getHeader('Content-Type');
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " requestContentType: " . $contentType . ' requestMethod: ' . $this->_request->getMethod());

                if ($this->_request->getMethod() !== 'PUT' && (stripos($contentType, 'text') === 0 || stripos($contentType, '/xml') !== false)) {
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

            if (Tinebase_Core::isFilesystemAvailable()) {
                self::$_server->addPlugin(
                    new \Sabre\DAV\Locks\Plugin(
                        new \Sabre\DAV\Locks\Backend\File('tine20://Tinebase/folders/shared/webdav.lock'))
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
                $userA = null;
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    list($userA,) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
                }
                if (Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD !== $userA) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                        __LINE__ . ' SyncTokenSupport enabled');
                    self::$_server->addPlugin(new Tinebase_WebDav_Plugin_SyncToken());
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport disabled');
                }
            }
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin());
            self::$_server->httpResponse->startBodyLog(Tinebase_Core::isLogLevel(Zend_Log::DEBUG) &&
                $this->_request->getMethod() !== 'GET');

            self::$_server->exec();

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " >>> *DAV response:\n" .
                    implode("\n", headers_list()) . "\n\n" .
                    self::$_server->httpResponse->stopBodyLog());
            }

            Tinebase_Controller::getInstance()->logout();
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            }
            @header('HTTP/1.1 403 Forbidden');
        } catch (Throwable $e) {
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
        return self::$_server ? self::$_server->httpResponse : new Tinebase_WebDav_HTTP_LogResponse();
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
