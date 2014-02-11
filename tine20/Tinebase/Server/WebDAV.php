<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    /**
     * the request
     *
     * @var Zend_Controller_Request_Http
     */
    protected $_request = NULL;
    
    protected $_body;
    
   /**
    * @var \Sabre\DAV\Server
    */
    protected static $_server;
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(Zend_Controller_Request_Http $request = null, $body = null)
    {
        $this->_request = $request instanceof Zend_Controller_Request_Http ? $request : new Zend_Controller_Request_Http();
        $this->_body    = $body !== null ? $body : fopen('php://input', 'r');
        
        Tinebase_Core::initFramework();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' is CalDav, CardDAV or WebDAV request.');
        
        // when used with (f)cgi no PHP_AUTH* variables are available without defining a special rewrite rule
        $loginName = $this->_request->getServer('PHP_AUTH_USER');
        $password  = $this->_request->getServer('PHP_AUTH_PW');
        
        if (empty($loginName)) {
            $basicAuthData = $this->_getBasicAuthData();
            if ($basicAuthData) {
                list($loginName, $password) = explode(":", $basicAuthData, 2);
            }
        }
        
        if (empty($loginName)) {
            header('WWW-Authenticate: Basic realm="WebDav for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        if (Tinebase_Controller::getInstance()->login($loginName, $password, $_SERVER['REMOTE_ADDR'], 'TineWebDav') !== true) {
            header('WWW-Authenticate: Basic realm="CardDav for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' requestUri:' . $this->_request->getRequestUri());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            // NOTE inputstream can not be rewinded
            $debugStream = fopen('php://temp','r+');
            stream_copy_to_stream($this->_body, $debugStream);
            rewind($debugStream);
            $this->_body = $debugStream;
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " <<< *DAV request\n" . stream_get_contents($this->_body));
            rewind($this->_body);
        }
        
        self::$_server = new \Sabre\DAV\Server(new Tinebase_WebDav_Root());
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
        
        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $aclPlugin->defaultUsernamePath    = Tinebase_WebDav_PrincipalBackend::PREFIX_USERS;
        $aclPlugin->principalCollectionSet = array (Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS);
        self::$_server->addPlugin($aclPlugin);
        
        self::$_server->addPlugin(new \Sabre\CardDAV\Plugin());
        self::$_server->addPlugin(new \Sabre\CalDAV\Plugin());
        self::$_server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
        self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginAutoSchedule());
        self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginDefaultAlarms());
        self::$_server->addPlugin(new Tinebase_WebDav_Plugin_Inverse());
        self::$_server->addPlugin(new Tinebase_WebDav_Plugin_OwnCloud());
        self::$_server->addPlugin(new Tinebase_WebDav_Plugin_PrincipalSearch());
        #self::$_server->addPlugin(new DAV\Sync\Plugin());
        self::$_server->addPlugin(new \Sabre\DAV\Browser\Plugin());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            ob_start();
        }
        
        self::$_server->exec();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " >>> *DAV response:\n" . ob_get_contents());
            ob_end_flush();
        }
        
        Tinebase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
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
    * returns request method
    *
    * @return string
    */
    public function getRequestMethod()
    {
        return self::getRequest()->getMethod();
    }
}
