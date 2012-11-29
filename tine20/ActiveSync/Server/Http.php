<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * http server
 *
 * @package     ActiveSync
 * @subpackage  Server
 */
class ActiveSync_Server_Http implements Tinebase_Server_Interface
{
    /**
     * the request
     * 
     * @var Zend_Controller_Request_Http
     */
    protected $_request = NULL;
    
    /**
     * request body
     * 
     * @var resource
     */
    protected $_body;
    
    /**
     * handler for ActiveSync requests
     * 
     * @param Zend_Controller_Request_Http $request
     * @param resource $body used mostly for unittesting
     * @return boolean
     * 
     * @todo 0007504: research input stream problems / remove the hotfix afterwards
     */
    public function handle(Zend_Controller_Request_Http $request = null, $body = null)
    {
        $this->_request = $request instanceof Zend_Controller_Request_Http ? $request : new Zend_Controller_Request_Http();
        
        if ($body === null) {
            // FIXME: this is a hotfix for 0007454: no email reply or forward (iOS/android 4.1.1)
            // the wbxml decoder seems to run into problems when we just pass the input stream
            // when the stream is copied first, the problems disappear
            //$this->_body    = $body !== null ? $body : fopen('php://input', 'r');
            $tempStream = fopen("php://temp", 'r+');
            stream_copy_to_stream(fopen('php://input', 'r'), $tempStream);
            rewind($tempStream);
            // file_put_contents(tempnam('/var/tmp', 'wbxml'), $tempStream); // for debugging
            $this->_body = $tempStream;
        } else {
            $this->_body = $body;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' is ActiveSync request.');
        
        // make sure that no session is created
        unset($_COOKIE['TINE20SESSID']);
        
        Tinebase_Core::initFramework();
        
        // when used with (f)cgi no PHP_AUTH* variables are available without defining a special rewrite rule
        $loginName = $this->_request->getServer('PHP_AUTH_USER');
        $password  = $this->_request->getServer('PHP_AUTH_PW');
        
        if(empty($loginName)) {
            // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
            if (isset($_SERVER["REMOTE_USER"])) {
                $basicAuthData = base64_decode(substr($_SERVER["REMOTE_USER"], 6));
            } elseif (isset($_SERVER["REDIRECT_REMOTE_USER"])) {
                $basicAuthData = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6));
            } elseif (isset($_SERVER["Authorization"])) {
                $basicAuthData = base64_decode(substr($_SERVER["Authorization"], 6));
            } elseif (isset($_SERVER["HTTP_AUTHORIZATION"])) {
                $basicAuthData = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6));
            }

            if (isset($basicAuthData) && !empty($basicAuthData)) {
                list($loginName, $password) = explode(":", $basicAuthData);
            }
        }
        
        if(empty($loginName)) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        if($this->_authenticate($loginName, $password, $this->_request->getClientIp()) !== true) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        try {
            $activeSync = Tinebase_Application::getInstance()->getApplicationByName('ActiveSync');
        } catch (Tinebase_Exception_NotFound $e) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not installed');
            return;
        }
        
        if($activeSync->status != 'enabled') {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled');
            return;
        }
        
        if(Tinebase_Core::getUser()->hasRight($activeSync, Tinebase_Acl_Rights::RUN) !== true) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled for account');
            return;
        }
        
        $this->_initializeRegistry();
        
        $syncFrontend = new Syncroton_Server(Tinebase_Core::getUser()->accountId, $this->_request, $this->_body);
        
        $syncFrontend->handle();
        
        Tinebase_Controller::getInstance()->logout($this->_request->getClientIp(), false);
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        return ($this->_request) ? $this->_request->getMethod() : NULL;
    }
    
    /**
     * authenticate user
     *
     * @param string $_username
     * @param string $_password
     * @param string $_ipAddress
     * @return bool
     */
    protected function _authenticate($_username, $_password, $_ipAddress)
    {
        $pos = strrchr($_username, '\\');
        
        if($pos !== false) {
            $username = substr(strrchr($_username, '\\'), 1);
        } else {
            $username = $_username;
        }
        
        return Tinebase_Controller::getInstance()->login($username, $_password, $_ipAddress, 'TineActiveSync');
    }
    
    /**
     * init registry
     */
    protected function _initializeRegistry()
    {
        Syncroton_Registry::setDatabase(Tinebase_Core::getDb());
        Syncroton_Registry::setTransactionManager(Tinebase_TransactionManager::getInstance());
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set('loggerBackend',       Tinebase_Core::getLogger());
        
        if (Tinebase_Core::getUser()->hasRight('Addressbook', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setContactsDataClass('ActiveSync_Controller_Contacts');
            Syncroton_Registry::setGALDataClass('ActiveSync_Controller_Contacts');
        }
        if (Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setCalendarDataClass('ActiveSync_Controller_Calendar');
        }
        if (Tinebase_Core::getUser()->hasRight('Felamimail', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setEmailDataClass('ActiveSync_Controller_Email');
        }
        if (Tinebase_Core::getUser()->hasRight('Tasks', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setTasksDataClass('ActiveSync_Controller_Tasks');
        }
        
        Syncroton_Registry::set(Syncroton_Registry::DEFAULT_POLICY, ActiveSync_Config::getInstance()->get(ActiveSync_Config::DEFAULT_POLICY));
    }
}
