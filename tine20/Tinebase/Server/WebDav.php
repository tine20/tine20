<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * webdav Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_WebDav extends Tinebase_Server_Abstract
{
    /**
     * 
     * @var Sabre_DAV_Server
     */
    protected $_server;
    
    /**
     * the constructor
     * 
     */
    public function __construct()
    {
        $rootDirectory = new Sabre_DAV_SimpleDirectory('root');
        $rootDirectory->addChild(
            new Tinebase_WebDav_Root('dav')
        );
        
        $tree = new Sabre_DAV_ObjectTree($rootDirectory);
        
        $server = new Sabre_DAV_Server($tree);
        
        $server->setBaseUri('/');
        
        #$lockBackend = new Sabre_DAV_Locks_Backend_FS('/var/www/phpfcgi/cache');
        #$lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
        #$server->addPlugin($lockPlugin);
        
        $server->addPlugin(
            new Sabre_DAV_Browser_Plugin()
        );
        
        $server->addPlugin(
            new Sabre_CalDAV_Plugin()
        );
        
        $this->_server = $server;
    }
    
    public function handle()
    {
        try {
            $this->_initFramework();
        } catch (Zend_Session_Exception $exception) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' invalid session. Delete session cookie.');
            Zend_Session::expireSessionCookie();
            
            header('WWW-Authenticate: Basic realm="WebDav for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            
            return;
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' is WebDav request.');
        
        if(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['REMOTE_USER'])) {
            header('WWW-Authenticate: Basic realm="WebDav for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            
            return;
        }
        
        // when used with (f)cgi no PHP_AUTH variables are available without defining a special rewrite rule
        if(!isset($_SERVER['PHP_AUTH_USER'])) {
            // $_SERVER["REMOTE_USER"] == "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
            $basicAuthData = base64_decode(substr($_SERVER["REMOTE_USER"],6));
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(":", $basicAuthData);
        }
        
        if(Tinebase_Controller::getInstance()->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']) !== true) {
            header('WWW-Authenticate: Basic realm="WebDav for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;                            
        }
        
        $this->_server->exec();
    }
}