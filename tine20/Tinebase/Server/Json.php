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
 * JSON Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Json
{
    /**
     * handler for JSON api requests
     * @todo session expre handling
     * 
     * @return JSON
     */
    public function handleJson()
    {
        try {
            $this->_initFramework();
            
            // 2008-09-12 temporary bug hunting for FF or ExtJS bug. 
            if ($_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] !== $_POST['requestType']) {
                Zend_Registry::get('logger')->debug('HEADER - POST API REQUEST MISMATCH! Header is:"' . $_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] .
                    '" whereas POST is "' . $_POST['requestType'] . '"' . ' HTTP_USER_AGENT: "' . $_SERVER['HTTP_USER_AGENT'] . '"');
            }
            
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' is json request. method: ' . $_REQUEST['method']);
            
            $anonymnousMethods = array(
                'Tinebase.getRegistryData',
                'Tinebase.getAllRegistryData',
                'Tinebase.login',
                'Tinebase.getAvailableTranslations',
                'Tinebase.getTranslations',
                'Tinebase.setLocale'
            );
            // check json key for all methods but some exceptoins
            if ( !(in_array($_POST['method'], $anonymnousMethods) || preg_match('/Tinebase_UserRegistration/', $_POST['method'])) 
                    && $_POST['jsonKey'] != Zend_Registry::get('jsonKey') ) { 
    
                if (! Zend_Registry::isRegistered('currentAccount')) {
                    Zend_Registry::get('logger')->INFO('Attempt to request a privileged Json-API method without autorisation from "' . $_SERVER['REMOTE_ADDR'] . '". (seesion timeout?)');
                    
                    throw new Exception('Not Autorised', 401);
                } else {
                    Zend_Registry::get('logger')->WARN('Fatal: got wrong json key! (' . $_POST['jsonKey'] . ') Possible CSRF attempt!' .
                        ' affected account: ' . print_r(Zend_Registry::get('currentAccount')->toArray(), true) .
                        ' request: ' . print_r($_REQUEST, true)
                    );
                    
                    throw new Exception('Not Autorised', 401);
                    //throw new Exception('Possible CSRF attempt detected!');
                }
            }
    
            $server = new Zend_Json_Server();
            
            // add json apis which require no auth
            $server->setClass('Tinebase_Frontend_Json', 'Tinebase');
            $server->setClass('Tinebase_Json_UserRegistration', 'Tinebase_UserRegistration');
            
            // register addidional Json apis only available for authorised users
            if (Zend_Auth::getInstance()->hasIdentity()) {
                $applicationParts = explode('.', $_REQUEST['method']);
                $applicationName = ucfirst($applicationParts[0]);
                
                switch($applicationName) {
                    case 'Tinebase_Container':
                        // addidional Tinebase json apis
                        $server->setClass('Tinebase_Json_Container', 'Tinebase_Container');                
                        break;
                        
                    default;
                        if(Zend_Registry::get('currentAccount')->hasRight($applicationName, Tinebase_Application_Rights_Abstract::RUN)) {
                            try {
                                $server->setClass($applicationName.'_Frontend_Json', $applicationName);
                            } catch (Exception $e) {
                                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " Failed to add JSON API for application '$applicationName' Exception: \n". $e);
                            }
                        }
                        break;
                }
            }
            
        } catch (Exception $exception) {
            
            // hanlde all kind of session exceptions as 'Not Autorised'
            if ($exception instanceof Zend_Session_Exception) {
                $exception = new Exception('Not Autorised', 401);
            }
            
            $server = new Zend_Json_Server();
            $server->fault($exception, $exception->getCode());
            exit;
        }
         
        $server->handle($_REQUEST);
    }
}
