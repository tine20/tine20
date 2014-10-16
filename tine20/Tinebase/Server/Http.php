<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * HTTP Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Http extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    /**
     * the request method
     * 
     * @var string
     */
    protected $_method = NULL;
    
    /**
     * 
     * @var boolean
     */
    protected $_supportsSessions = true;
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        $this->_request = $request instanceof \Zend\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);
        $this->_body    = $body !== null ? $body : fopen('php://input', 'r');
        
        $server = new Tinebase_Http_Server();
        $server->setClass('Tinebase_Frontend_Http', 'Tinebase');
        $server->setClass('Filemanager_Frontend_Download', 'Download');
        
        try {
            if (Tinebase_Session::sessionExists()) {
                try {
                    Tinebase_Core::startCoreSession();
                } catch (Zend_Session_Exception $zse) {
                    // expire session cookie for client
                    Tinebase_Session::expireSessionCookie();
                }
            }
            
            Tinebase_Core::initFramework();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' Is HTTP request. method: ' . $this->getRequestMethod());
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' REQUEST: ' . print_r($_REQUEST, TRUE));
            
            // register additional HTTP apis only available for authorised users
            if (Tinebase_Session::isStarted() && Zend_Auth::getInstance()->hasIdentity()) {
                if (empty($_REQUEST['method'])) {
                    $_REQUEST['method'] = 'Tinebase.mainScreen';
                }
                
                $applicationParts = explode('.', $this->getRequestMethod());
                $applicationName = ucfirst($applicationParts[0]);
                
                if(Tinebase_Core::getUser() && Tinebase_Core::getUser()->hasRight($applicationName, Tinebase_Acl_Rights_Abstract::RUN)) {
                    try {
                        $server->setClass($applicationName.'_Frontend_Http', $applicationName);
                    } catch (Exception $e) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ ." Failed to add HTTP API for application '$applicationName' Exception: \n". $e);
                    }
                }
                
            } else {
                if (empty($_REQUEST['method'])) {
                    $_REQUEST['method'] = 'Tinebase.login';
                }
                
                // sessionId got send by client, but we don't use sessions for non authenticated users
                if (Tinebase_Session::sessionExists()) {
                    // expire session cookie on client
                    Tinebase_Session::expireSessionCookie();
                }
            }
            
            $this->_method = $this->getRequestMethod();
            
            $server->handle($_REQUEST);
            
        } catch (Zend_Json_Server_Exception $zjse) {
            // invalid method requested or not authenticated, etc.
            Tinebase_Exception::log($zjse);
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ .' Attempt to request a privileged Http-API method without valid session from "' . $_SERVER['REMOTE_ADDR']);
            
            header('HTTP/1.0 403 Forbidden');

            exit;
            
        } catch (Exception $exception) {
            Tinebase_Exception::log($exception);
            
            try {
                $setupController = Setup_Controller::getInstance();
                if ($setupController->setupRequired()) {
                    $this->_method = 'Tinebase.setupRequired';
                } else {
                    $this->_method = 'Tinebase.exception';
                }
                
                $server->handle(array('method' => $this->_method));
                
            } catch (Exception $e) {
                header('HTTP/1.0 503 Service Unavailable');
                die('Service Unavailable');
            }
        }
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        if (isset($_REQUEST['method'])) {
            $this->_method = $_REQUEST['method'];
        }
        
        return $this->_method;
    }
}
