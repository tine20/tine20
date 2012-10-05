<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * HTTP Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Http implements Tinebase_Server_Interface
{
    /**
     * the request method
     * 
     * @var string
     */
    protected $_method = NULL;
    
    /**
     * handler for HTTP api requests
     * @todo session expire handling
     * 
     * @return HTTP
     */
    public function handle()
    {
        $server = new Tinebase_Http_Server();
        $server->setClass('Tinebase_Frontend_Http', 'Tinebase');
        
        try {
            try {
                Tinebase_Core::initFramework();
            } catch (Zend_Session_Exception $zse) {
                // expire session cookie on client
                Zend_Session::expireSessionCookie();
            }
            
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ .' Is HTTP request. method: ' . $this->getRequestMethod());
            
            // register addidional HTTP apis only available for authorised users
            if (Zend_Session::isStarted() && Zend_Auth::getInstance()->hasIdentity()) {
                if (empty($_REQUEST['method'])) {
                    $_REQUEST['method'] = 'Tinebase.mainScreen';
                }
                
                $applicationParts = explode('.', $_REQUEST['method']);
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
                if (Zend_Session::sessionExists()) {
                    // expire session cookie on client
                    Zend_Session::expireSessionCookie();
                }
            }
            
            $this->_method = $_REQUEST['method'];
            
            $server->handle($_REQUEST);
            
        } catch (Zend_Json_Server_Exception $zjse) {
            // invalid method requested or not authenticated
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ .' Attempt to request a privileged Http-API method without valid session from "' . $_SERVER['REMOTE_ADDR']);
            
            header('HTTP/1.0 403 Forbidden');

            exit;
            
        } catch (Exception $exception) {
            if (! is_object(Tinebase_Core::getLogger())) {
                // no logger -> exception happened very early
                error_log($exception);
                header('HTTP/1.0 503 Service Unavailable');
                die('Service Unavailable');
            }
            
            Tinebase_Core::getLogger()->info($exception);
            
            try {
                // check if setup is required
                $setupController = Setup_Controller::getInstance();
                if ($setupController->setupRequired()) {
                    $this->_method = 'Tinebase.setupRequired';
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(
                        __METHOD__ . '::' . __LINE__ . ' (' . __LINE__ .') Http-Api exception: ' . print_r($exception, true));
                    
                    $this->_method = 'Tinebase.exception';
                }
                
                $server->handle(array('method' => $this->_method));
                
            } catch (Exception $e) {
                error_log($exception);
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
        if ($this->_method && isset($_REQUEST['method'])) {
            $this->_method = $_REQUEST['method'];
        }
        
        return $this->_method;
    }
}
