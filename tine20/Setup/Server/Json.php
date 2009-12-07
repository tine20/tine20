<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Json.php 5147 2008-10-28 17:03:33Z p.schuele@metaways.de $
 * 
 * @todo        make this extend Tinebase_Server_Json to avoid code duplication (_handleException)
 */

/**
 * JSON Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Setup_Server_Json extends Setup_Server_Abstract
{
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
            
            $request = new Zend_Json_Server_Request_Http();
            
            $method  = $request->getMethod();
            $jsonKey = (isset($_SERVER['HTTP_X_TINE20_JSONKEY'])) ? $_SERVER['HTTP_X_TINE20_JSONKEY'] : '';
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' is JSON request. method: ' . $method);
            
            $anonymnousMethods = array(
                'Setup.getAllRegistryData',
                'Setup.login',
                'Tinebase.getAvailableTranslations',
                'Tinebase.getTranslations',
                'Tinebase.setLocale',
            );
            
            if (! Setup_Core::configFileExists()) {
            	$anonymnousMethods = array_merge($anonymnousMethods, array(
	                'Setup.envCheck',
	            ));
            }
            
            $server = new Zend_Json_Server();
            $server->setClass('Setup_Frontend_Json', 'Setup');
            $server->setClass('Tinebase_Frontend_Json', 'Tinebase');
            
            // check json key for all methods but some exceptoins
            if (! in_array($method, $anonymnousMethods) && Setup_Core::configFileExists()
                     && ( empty($jsonKey) || $jsonKey != Setup_Core::get('jsonKey')
                            || !Setup_Core::isRegistered(Setup_Core::USER)
                        )
               ) {
                if (! Setup_Core::isRegistered(Setup_Core::USER)) {
                    Setup_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . ' Attempt to request a privileged Json-API method without authorisation from "' . $_SERVER['REMOTE_ADDR'] . '". (session timeout?)');
                    
                    throw new Tinebase_Exception_AccessDenied('Not Authorised', 401);
                } else {
                    Setup_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . ' Fatal: got wrong json key! (' . $jsonKey . ') Possible CSRF attempt!' .
                        ' affected account: ' . print_r(Setup_Core::getUser(), true) .
                        ' request: ' . print_r($_REQUEST, true)
                    );
                    
                    throw new Tinebase_Exception_AccessDenied('Not Authorised', 401);
                    //throw new Exception('Possible CSRF attempt detected!');
                }
            }
            
        } catch (Exception $exception) {
            echo $this->_handleException($server, $request, $exception);
            exit;
        }
         
        $server->handle($request);
    }
    
    /**
     * handle exceptions
     * 
     * @param Zend_Json_Server $server
     * @param Zend_Json_Server_Request_Http $request
     * @param Exception $exception
     * @return string json data
     * 
     * @todo remove that / replace it with Tinebase_Server_Json::_handleException
     */
    protected function _handleException($server, $request, $exception)
    {
        $exceptionData = method_exists($exception, 'toArray')? $exception->toArray() : array();
        $exceptionData['message'] = $exception->getMessage();
        $exceptionData['code']    = $exception->getCode();
        if (Tinebase_Core::getConfig()->suppressExceptionTraces !== TRUE) {
            $exceptionData['trace']   = $exception->getTrace();
        }
        
        $server->fault($exceptionData['message'], $exceptionData['code'], $exceptionData);
        
        $response = $server->getResponse();
        if (null !== ($id = $request->getId())) {
            $response->setId($id);
        }
        if (null !== ($version = $request->getVersion())) {
            $response->setVersion($version);
        }
    
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $exception);
        
        return $response;
    }
}
