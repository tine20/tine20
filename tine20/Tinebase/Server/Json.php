<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * JSON Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Json extends Tinebase_Server_Abstract
{
	const ERROR_NOT_AUTHORIZED       = -32001;
	const ERROR_INSUFFICIENT_RIGHTS  = -32003;
	const ERROR_MISSING_DATA         = -32004;
	const ERROR_CONCURRENCY_CONFILCT = -32009;
	
    protected $_errorMap = array(
        401 => self::ERROR_NOT_AUTHORIZED,
        403 => self::ERROR_INSUFFICIENT_RIGHTS,
        404 => self::ERROR_MISSING_DATA,
        409 => self::ERROR_CONCURRENCY_CONFILCT
    );
    
    /**
     * handler for JSON api requests
     * @todo session expire handling
     * 
     * @return JSON
     */
    public function handle()
    {
        try {
            $this->_initFramework();
            
            $server = new Zend_Json_Server();
            $server->setAutoHandleExceptions(false);
            //$server->setUseNamedParams(true);
            
            $request = new Zend_Json_Server_Request_Http();
            
            $method  = $request->getMethod();
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' is JSON request. method: ' . $method);
            
            $jsonKey = $_SERVER['HTTP_X_TINE20_JSONKEY'];
            $this->_checkJsonKey($method, $jsonKey);
            
            // add json apis which require no auth
            $server->setClass('Tinebase_Frontend_Json', 'Tinebase');
            $server->setClass('Tinebase_Frontend_Json_UserRegistration', 'Tinebase_UserRegistration');
            
            // register additional Json apis only available for authorised users
            if (Zend_Auth::getInstance()->hasIdentity()) {
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " user data: " . print_r(Tinebase_Core::getUser()->toArray(), true));
                
                if (array_key_exists('stateInfo', $_REQUEST) && ! empty($_REQUEST['stateInfo'])) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " About to save clients appended stateInfo ... ");
                    // save state info here (and return in with getAllRegistryData)
                    Tinebase_State::getInstance()->saveStateInfo($_REQUEST['stateInfo']);
                }
                
                $applicationParts = explode('.', $method);
                $applicationName = ucfirst($applicationParts[0]);
                
                switch($applicationName) {
                    // additional Tinebase json apis
                    case 'Tinebase_Container':
                        $server->setClass('Tinebase_Frontend_Json_Container', 'Tinebase_Container');                
                        break;
                    case 'Tinebase_PersistentFilter':
                        $server->setClass('Tinebase_Frontend_Json_PersistentFilter', 'Tinebase_PersistentFilter');                
                        break;
                        
                    default;
                        if(Tinebase_Core::getUser()->hasRight($applicationName, Tinebase_Acl_Rights_Abstract::RUN)) {
                            try {
                                $server->setClass($applicationName.'_Frontend_Json', $applicationName);
                            } catch (Exception $e) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Failed to add JSON API for application '$applicationName' Exception: \n". $e);
                            }
                        }
                        break;
                }
            }
            
            $server->handle($request);
            
        } catch (Exception $exception) {
            
            // handle all kind of session exceptions as 'Not Authorised'
            if ($exception instanceof Zend_Session_Exception) {
                $exception = new Tinebase_Exception_AccessDenied('Not Authorised', 401);
            }
            
            if (! $server) {
            	// exception from initFramework
            	error_log($exception);
            	$server = new Zend_Json_Server();
            	$request = new Zend_Json_Server_Request_Http();
            }
            
            $code = $exception->getCode();
            if (array_key_exists($code, $this->_errorMap)) {
            	$code = $this->_errorMap[$code];
            }
            $server->fault($exception->getMessage(), $code, $exception->getTraceAsString());
            
            $response = $server->getResponse();
	        if (null !== ($id = $request->getId())) {
	            $response->setId($id);
	        }
	        if (null !== ($version = $request->getVersion())) {
	            $response->setVersion($version);
	        }
        
            echo $response;
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $exception);
            exit;
        }
    }
    
    protected function _checkJsonKey($method, $jsonKey)
    {
        $anonymnousMethods = array(
            'Tinebase.getRegistryData',
            'Tinebase.getAllRegistryData',
            'Tinebase.login',
            'Tinebase.getAvailableTranslations',
            'Tinebase.getTranslations',
            'Tinebase.setLocale'
        );
        // check json key for all methods but some exceptions
        if ( !(in_array($method, $anonymnousMethods) || preg_match('/Tinebase_UserRegistration/', $method))  
                && $jsonKey != Tinebase_Core::get('jsonKey')) {
        
            if (! Tinebase_Core::isRegistered(Tinebase_Core::USER)) {
                Tinebase_Core::getLogger()->INFO('Attempt to request a privileged Json-API method without autorisation from "' . $_SERVER['REMOTE_ADDR'] . '". (seesion timeout?)');
                
                throw new Tinebase_Exception_AccessDenied('Not Authorised', 401);
            } else {
                Tinebase_Core::getLogger()->WARN('Fatal: got wrong json key! (' . $jsonKey . ') Possible CSRF attempt!' .
                    ' affected account: ' . print_r(Tinebase_Core::getUser()->toArray(), true) .
                    ' request: ' . print_r($_REQUEST, true)
                );
                
                throw new Tinebase_Exception_AccessDenied('Not Authorised', 401);
            }
        }
    }
}
