<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Json.php 5147 2008-10-28 17:03:33Z p.schuele@metaways.de $
 */

/**
 * JSON Server class with handle() function
 * 
 * @package     Setup
 * @subpackage  Server
 */
class Setup_Server_Json extends Tinebase_Server_Json
{
    /**
     * handler for JSON api requests
     * 
     * @return JSON
     */
    public function handle()
    {
        try {
            // init server and request first
            $request = new Zend_Json_Server_Request_Http();
            $server = new Zend_Json_Server();
            $server->setClass('Setup_Frontend_Json', 'Setup');
            $server->setClass('Tinebase_Frontend_Json', 'Tinebase');
            
            Setup_Core::initFramework();
            
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
                }
            }
            
            $server->handle($request);
            
        } catch (Exception $exception) {
            echo $this->_handleException($server, $request, $exception);
            exit;
        }
    }
}
