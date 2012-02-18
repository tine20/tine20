<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
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
     * handler for ActiveSync requests
     * 
     * @return boolean
     */
    public function handle()
    {
        $request = new Zend_Controller_Request_Http();
        
        try {
            Tinebase_Core::initFramework();
        } catch (Zend_Session_Exception $exception) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' invalid session. Delete session cookie.');
            Zend_Session::expireSessionCookie();
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' is ActiveSync request.');
        
        // when used with (f)cgi no PHP_AUTH* variables are available without defining a special rewrite rule
        if(!isset($_SERVER['PHP_AUTH_USER'])) {
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
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(":", $basicAuthData);
            }
        }
        
        if(empty($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        if($this->_authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']) !== true) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        try {
            $activeSync = Tinebase_Application::getInstance()->getApplicationByName('ActiveSync');
        } catch (Tinebase_Exception_NotFound $e) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $_SERVER['PHP_AUTH_USER']);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not installed');
            return;
        }
        
        if($activeSync->status != 'enabled') {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $_SERVER['PHP_AUTH_USER']);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled');
            return;
        }
        
        if(Tinebase_Core::getUser()->hasRight($activeSync, Tinebase_Acl_Rights::RUN) !== true) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $_SERVER['PHP_AUTH_USER']);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled for account');
            return;
        }
        
        $this->_initializeRegistry();
                
        $syncFrontend = new Syncope_Server(Tinebase_Core::getUser()->accountId);
        
        $syncFrontend->handle();
        
        Tinebase_Controller::getInstance()->logout($request->getClientIp());
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
    
    protected function _initializeRegistry()
    {
        Syncope_Registry::setDatabase(Tinebase_Core::getDb());
        Syncope_Registry::setTransactionManager(Tinebase_TransactionManager::getInstance());
        
        Syncope_Registry::set('deviceBackend',       new Syncope_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncope_Registry::set('folderStateBackend',  new Syncope_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncope_Registry::set('syncStateBackend',    new Syncope_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncope_Registry::set('contentStateBackend', new Syncope_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncope_Registry::set('loggerBackend',       Tinebase_Core::getLogger());
        
        if(Tinebase_Core::getUser()->hasRight('Addressbook', Tinebase_Acl_Rights::RUN) === true) {
            Syncope_Registry::setContactsDataClass('ActiveSync_Controller_Contacts');
        }
        if(Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN) === true) {
            Syncope_Registry::setCalendarDataClass('ActiveSync_Controller_Calendar');
        }
        if(Tinebase_Core::getUser()->hasRight('Felamimail', Tinebase_Acl_Rights::RUN) === true) {
            Syncope_Registry::setEmailDataClass('ActiveSync_Controller_Email');
        }
        if(Tinebase_Core::getUser()->hasRight('Tasks', Tinebase_Acl_Rights::RUN) === true) {
            Syncope_Registry::setTasksDataClass('ActiveSync_Controller_Tasks');
        }
    }
}
