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
 * HTTP Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Http extends Tinebase_Server_Abstract
{
    /**
     * handler for HTTP api requests
     * @todo session expire handling
     * 
     * @return HTTP
     */
    public function handle()
    {
        try {
            $this->_initFramework();
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ .' is HTTP request. method: ' . (isset($_REQUEST['method']) ? $_REQUEST['method'] : 'EMPTY'));
            
            $server = new Tinebase_Http_Server();
            
            //NOTE: auth check for Tinebase HTTP api is done via Tinebase_Http::checkAuth  
            $server->setClass('Tinebase_Frontend_Http', 'Tinebase');
    
            // register addidional HTTP apis only available for authorised users
            if (Zend_Auth::getInstance()->hasIdentity()) {
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
            }
            
            if (empty($_REQUEST['method'])) {
                $_REQUEST['method'] = 'Tinebase.login';
            }

            $server->handle($_REQUEST);
        } catch (Exception $exception) {
            Tinebase_Core::getLogger()->INFO($exception);
            
            $server = new Tinebase_Http_Server();
            $server->setClass('Tinebase_Frontend_Http', 'Tinebase');
            if ($exception instanceof Zend_Session_Exception) {
                Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ .' Attempt to request a privileged Http-API method without valid session from "' . $_SERVER['REMOTE_ADDR']);
                $server->handle(array('method' => 'Tinebase.sessionException'));
            } else {
                // check if setup is required
                $setupController = Setup_Controller::getInstance(); 
                if ($setupController->setupRequired()) {
                    $server->handle(array('method' => 'Tinebase.setupRequired'));
                } else {                
                    Tinebase_Core::getLogger()->DEBUG(__CLASS__ . '::' . __METHOD__ . ' (' . __LINE__ .') Http-Api exception: ' . print_r($exception, true));
                    $server->handle(array('method' => 'Tinebase.exception'));
                }
            }
        }
    }
}
