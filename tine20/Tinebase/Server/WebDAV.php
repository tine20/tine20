<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        $this->_request = $request instanceof \Zend\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);
        $this->_body    = $body !== null ? $body : fopen('php://input', 'r');
        
        try {
            list($loginName, $password) = $this->_getAuthData($this->_request);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            header('WWW-Authenticate: Basic realm="WebDAV for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' is CalDav, CardDAV or WebDAV request.');
        
        Tinebase_Core::initFramework();
        
        if (Tinebase_Controller::getInstance()->login(
            $loginName,
            $password,
            $this->_request,
            self::REQUEST_TYPE
        ) !== true) {
            header('WWW-Authenticate: Basic realm="WebDAV for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' requestUri:' . $this->_request->getRequestUri());
        
        self::$_server = new \Sabre\DAV\Server(new Tinebase_WebDav_Root());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            $contentType = self::$_server->httpRequest->getHeader('Content-Type');
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " requestContentType: " . $contentType);
            
            if (preg_match('/^text/', $contentType)) {
                // NOTE inputstream can not be rewinded
                $debugStream = fopen('php://temp','r+');
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
        
        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $aclPlugin->defaultUsernamePath    = Tinebase_WebDav_PrincipalBackend::PREFIX_USERS;
        $aclPlugin->principalCollectionSet = array (Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS);
        
        $aclPlugin->principalSearchPropertySet = array(
            '{DAV:}displayname'                                                   => 'Display name',
            '{' . \Sabre\DAV\Server::NS_SABREDAV . '}email-address'               => 'Email address',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}email-address-set'  => 'Email addresses',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'         => 'First name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'          => 'Last name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV         . '}calendar-user-address-set' => 'Calendar user address set',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV         . '}calendar-user-type' => 'Calendar user type'
        );
        
        self::$_server->addPlugin($aclPlugin);
        
        self::$_server->addPlugin(new \Sabre\CardDAV\Plugin());
        self::$_server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPlugin); // this plugin must be loaded before CalDAV plugin
        self::$_server->addPlugin(new \Sabre\CalDAV\Plugin());
        self::$_server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
        self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginAutoSchedule());
        self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginDefaultAlarms());
        self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginManagedAttachments());
        self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginPrivateEvents());
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
        
        Tinebase_Controller::getInstance()->logout($this->_request->getServer('REMOTE_ADDR'));
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
