<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * http server
 *
 * @package     ActiveSync
 * @subpackage  Server
 */
class ActiveSync_Server_Http extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    const REQUEST_TYPE = 'ActiveSync';
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Laminas\Http\Request $request = null, $body = null)
    {
        try {
            $this->_request = $request instanceof \Laminas\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);
            $this->_body = $this->_getBody($body);

            try {
                $authData = $this->_getAuthData($this->_request);
                if (count($authData) === 2) {
                    list($loginName, $password) = $authData;
                    // Autodiscover comes always by mail not by username, if feature is activated enable auth, too.
                    if ( true === Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_AUTODISCOVER) ) {
                        Tinebase_Config::getInstance()->set(Tinebase_Config::AUTHENTICATION_BY_EMAIL, true);
                    }
                } else {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' auth data: '
                        . print_r($authData, true));
                    throw new Tinebase_Exception_NotFound('loginname or password not set');
                }

            } catch (Tinebase_Exception_NotFound $tenf) {
                header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
                header('HTTP/1.1 401 Unauthorized');

                return;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' is ActiveSync request.');
            }

            Tinebase_Core::initFramework();

            try {
                $authResult = $this->_authenticate(
                    $loginName,
                    $password,
                    $this->_request
                );

            } catch (Tinebase_Exception_MaintenanceMode $temm) {
                Tinebase_Server_Abstract::setHttpHeader(503);
                return;
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                $authResult = false;
            }

            if ($authResult !== true) {
                $this->_unauthorized();
                return;
            }

            if (!$this->_checkDenyList()) {
                return;
            }

            if (!$this->_checkUserPermissions($loginName)) {
                return;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Starting to handle ActiveSync request ('
                    . 'PID: ' . getmypid() . ')');
            }

            $this->_initializeRegistry();

            $request = new Zend_Controller_Request_Http();
            $request->setRequestUri($this->_request->getRequestUri());

            $syncFrontend = new Syncroton_Server(Tinebase_Core::getUser()->accountId, $request, $this->_body);

            $syncFrontend->handle();

            Tinebase_Controller::getInstance()->logout();
        } catch (Throwable $e) {
            $this->_handleException($e);
        }
    }

    protected function _unauthorized()
    {
        header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
        header('HTTP/1.1 401 Unauthorized');
    }

    /**
     * @param Throwable $e
     * @throws Throwable
     */
    protected function _handleException(Throwable $e)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' ActiveSync Request failed: ' . $e->getMessage());

        if ($e instanceof Tinebase_Exception_NotFound) {
            Tinebase_Server_Abstract::setHttpHeader(404);
        } else {
            Tinebase_Exception::log($e);
            throw $e;
        }
    }

    protected function _checkDenyList()
    {
        $denyList = ActiveSync_Config::getInstance()->get(ActiveSync_Config::USER_AGENT_DENY_LIST);
        // NOTE: Outlook app destroys your privacy. it mirrors all content on ms servers. if you like to use it, use exchange/m365 as well!
        $denyList[] = '/Outlook-iOS-Android.*/';
        foreach ($denyList as $deny) {
            if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match($deny, $_SERVER['HTTP_USER_AGENT'])) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' User agent blocked: ' . $_SERVER['HTTP_USER_AGENT']);
                header('HTTP/1.1 420 Policy Not Fulfilled User Agent Not Accepted');
                return false;
            }
        }

        return true;
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
     * get body
     * 
     * @param resource $body used mostly for unittesting
     * @return resource
     * 
     * @todo 0007504: research input stream problems / remove the hotfix afterwards
     */
    protected function _getBody($body)
    {
        if ($body === null) {
            // FIXME: this is a hotfix for 0007454: no email reply or forward (iOS/android 4.1.1)
            // the wbxml decoder seems to run into problems when we just pass the input stream
            // when the stream is copied first, the problems disappear
            //$this->_body    = $body !== null ? $body : fopen('php://input', 'r');
            $tempStream = fopen("php://temp", 'r+');
            stream_copy_to_stream(fopen('php://input', 'r'), $tempStream);
            rewind($tempStream);
            // file_put_contents(tempnam('/var/tmp', 'wbxml'), $tempStream); // for debugging
            return $tempStream;
        } else {
            return $body;
        }
    }
    
    /**
     * authenticate user
     *
     * @param string $_username
     * @param string $_password
     * @param string $_ipAddress
     * @return bool
     */
    protected function _authenticate($_username, $_password, \Laminas\Http\Request $request)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ActiveSync Authentication untransformed username: ' . $_username);

        $pos = strrchr($_username, '\\');
        
        if($pos !== false) {
            $username = substr(strrchr($_username, '\\'), 1);
        } else {
            $username = $_username;
        }
        
        return Tinebase_Controller::getInstance()->login(
            $username,
            $_password,
            $request,
            self::REQUEST_TYPE
        );
    }
    
    /**
     * check user permissions
     * 
     * @param string $loginName
     * @return boolean
     */
    protected function _checkUserPermissions($loginName)
    {
        try {
            $activeSync = Tinebase_Application::getInstance()->getApplicationByName('ActiveSync');
        } catch (Tinebase_Exception_NotFound $e) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not installed');
            return false;
        }
        
        if ($activeSync->status != 'enabled') {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled');
            return false;
        }
        
        if (Tinebase_Core::getUser()->hasRight($activeSync, Tinebase_Acl_Rights::RUN) !== true) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled for account');
            return false;
        }
        
        return true;
    }
    
    /**
     * init registry
     */
    protected function _initializeRegistry()
    {
        ActiveSync_Controller::initSyncrotonRegistry();
        
        $applications = is_object(Tinebase_Core::getUser())
            ? Tinebase_Core::getUser()->getApplications()
            : new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        
        if ($applications->find('name', 'Addressbook')) {
            Syncroton_Registry::setContactsDataClass('Addressbook_Frontend_ActiveSync');
            Syncroton_Registry::setGALDataClass('Addressbook_Frontend_ActiveSync');
        }
        
        if ($applications->find('name', 'Calendar')) {
            Syncroton_Registry::setCalendarDataClass('Calendar_Frontend_ActiveSync');
        }
        
        if ($applications->find('name', 'Felamimail')) {
            Syncroton_Registry::setEmailDataClass('Felamimail_Frontend_ActiveSync');
        }
        
        if ($applications->find('name', 'Tasks')) {
            Syncroton_Registry::setTasksDataClass('Tasks_Frontend_ActiveSync');
        }
        
        Syncroton_Registry::set(Syncroton_Registry::DEFAULT_POLICY, ActiveSync_Config::getInstance()
            ->get(ActiveSync_Config::DEFAULT_POLICY));

        Syncroton_Registry::set(Syncroton_Registry::SLEEP_CALLBACK, function() {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' closing db connection');
            }
            Tinebase_Core::getDb()->closeConnection();
            Tinebase_User_Plugin_SqlAbstract::disconnectDbConnections();
        });
    }
}
