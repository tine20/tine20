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
 * @todo        finish & use it
 * @todo        add Server Interface?
 */

/**
 * HTTP Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Http
{
    /**
     * handler for HTTP api requests
     * @todo session expre handling
     * 
     * @return HTTP
     */
    public function handle()
    {
        try {
            $this->_initFramework();
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' is http request. method: ' . (isset($_REQUEST['method']) ? $_REQUEST['method'] : 'EMPTY'));
            
            $server = new Tinebase_Http_Server();
            
            //NOTE: auth check for Tinebase HTTP api is done via Tinebase_Http::checkAuth  
            $server->setClass('Tinebase_Http', 'Tinebase');
    
            // register addidional HTTP apis only available for authorised users
            if (Zend_Auth::getInstance()->hasIdentity()) {
                if (empty($_REQUEST['method'])) {
                    $_REQUEST['method'] = 'Tinebase.mainScreen';
                }
                
                $applicationParts = explode('.', $_REQUEST['method']);
                $applicationName = ucfirst($applicationParts[0]);
                
                if(Zend_Registry::get('currentAccount')->hasRight($applicationName, Tinebase_Application_Rights_Abstract::RUN)) {
                    try {
                        $server->setClass($applicationName.'_Http', $applicationName);
                    } catch (Exception $e) {
                        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ ." Failed to add HTTP API for application '$applicationName' Exception: \n". $e);
                    }
                }
            }
            
            if (empty($_REQUEST['method'])) {
                $_REQUEST['method'] = 'Tinebase.login';
            }

            $server->handle($_REQUEST);
        } catch (Exception $exception) {
            Zend_Registry::get('logger')->INFO($exception);
            
            $server = new Tinebase_Http_Server();
            $server->setClass('Tinebase_Http', 'Tinebase');
            if ($exception instanceof Zend_Session_Exception) {
                Zend_Registry::get('logger')->INFO(__METHOD__ . '::' . __LINE__ .' Attempt to request a privileged Http-API method without valid session from "' . $_SERVER['REMOTE_ADDR']);
                $server->handle(array('method' => 'Tinebase.sessionException'));
            } else {
                Zend_Registry::get('logger')->DEBUG(__CLASS__ . '::' . __METHOD__ . ' (' . __LINE__ .') Http-Api exception: ' . print_r($exception, true));
                $server->handle(array('method' => 'Tinebase.exception'));
            }
        }
    }
}
