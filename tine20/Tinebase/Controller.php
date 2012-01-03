<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Controller extends Tinebase_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Controller
     */
    private static $_instance = NULL;
    
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
        
    /**
     * the constructor
     *
     */
    private function __construct() 
    {    
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * the singleton pattern
     *
     * @return Tinebase_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Controller;
        }
        
        return self::$_instance;
    }
    
    /**
     * create new user session
     *
     * @param   string $_loginname
     * @param   string $_password
     * @param   string $_ipAddress
     * @param   string $_clientIdString
     * @return  bool
     */
    public function login($_loginname, $_password, $_ipAddress, $_clientIdString = NULL)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_loginname, $_password);
        
        $accessLog = new Tinebase_Model_AccessLog(array(
            'sessionid'     => session_id(),
            'ip'            => $_ipAddress,
            'li'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'result'        => $authResult->getCode(),
            'clienttype'    => $_clientIdString,   
        ), TRUE);

        $user = NULL;
        if ($accessLog->result == Tinebase_Auth::SUCCESS) {
            $user = $this->_getLoginUser($authResult->getIdentity(), $accessLog);
            if ($user !== NULL) {
                $this->_checkUserStatus($user, $accessLog);
            }
        }
        
        if ($accessLog->result === Tinebase_Auth::SUCCESS && $user !== NULL && $user->accountStatus === Tinebase_User::STATUS_ENABLED) {
            $this->_initUser($user, $accessLog, $_password);
            $result = true;
        } else {
            $this->_loginFailed($_loginname, $accessLog);
            $result = false;
        } 
        
        Tinebase_AccessLog::getInstance()->create($accessLog);
        
        return $result;
    }
    
    /**
     * get login user
     * 
     * @param string $_username
     * @param Tinebase_Model_AccessLog $_accessLog
     * @return Tinebase_Model_FullUser|NULL
     */
    protected function _getLoginUser($_username, Tinebase_Model_AccessLog $_accessLog)
    {
        $accountsController = Tinebase_User::getInstance();
        $user = NULL;
                
        try {
            // does the user exist in the user database?
            if ($accountsController instanceof Tinebase_User_Interface_SyncAble) {
                /**
                 * catch all exceptions during user data sync
                 * either it's the first sync and no user data get synchronized or
                 * we can work with the data synced during previous login
                 */ 
                try {
                    Tinebase_User::syncUser($_username);
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Failed to sync user data for: ' . $_username . ' reason: ' . $e->getMessage());
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                }
            }
            
            $user = $accountsController->getFullUserByLoginName($_username);
        } catch (Tinebase_Exception_NotFound $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Account ' . $_username . ' not found in account storage.');
            $_accessLog->result = Tinebase_Auth::FAILURE_IDENTITY_NOT_FOUND;
        } catch (Zend_Db_Adapter_Exception $zdae) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Some database connection failed: ' . $zdae->getMessage());
            $_accessLog->result = Tinebase_Auth::FAILURE_DATABASE_CONNECTION;
        }
        
        return $user;
    }
    
    /**
     * check user status
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_AccessLog $_accessLog
     */
    protected function _checkUserStatus(Tinebase_Model_FullUser $_user, Tinebase_Model_AccessLog $_accessLog)
    {
        // is the user enabled?
        if ($_accessLog->result == Tinebase_Auth::SUCCESS && $_user->accountStatus !== Tinebase_User::STATUS_ENABLED) {
            // is the account enabled?
            if ($_user->accountStatus == Tinebase_User::STATUS_DISABLED) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $_user->accountLoginName . ' is disabled');
                $_accessLog->result = Tinebase_Auth::FAILURE_DISABLED;
            }
                
            // is the account expired?
            else if ($_user->accountStatus == Tinebase_User::STATUS_EXPIRED) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $_user->accountLoginName . ' password is expired');
                $_accessLog->result = Tinebase_Auth::FAILURE_PASSWORD_EXPIRED;
            }
        
            // too many login failures?
            else if ($_user->accountStatus == Tinebase_User::STATUS_BLOCKED) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $_user->accountLoginName . ' is blocked');
                $_accessLog->result = Tinebase_Auth::FAILURE_BLOCKED;
            } 
        }
    }
    
    /**
     * init user session
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_AccessLog $_accessLog
     * @param string $_password
     */
    protected function _initUser(Tinebase_Model_FullUser $_user, Tinebase_Model_AccessLog $_accessLog, $_password)
    {
        if ($_accessLog->result === Tinebase_Auth::SUCCESS && $_user->accountStatus === Tinebase_User::STATUS_ENABLED) {
            $this->_initUserSession($_user);
            
            Tinebase_Core::set(Tinebase_Core::USER, $_user);
            
            $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($_user->accountLoginName, $_password);
            Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
            
            // need to set locale again and save it in preference because locale might not be set correctly during loginFromPost
            // use 'auto' setting because it is fetched from cookie or preference then
            Tinebase_Core::setupUserLocale('auto', TRUE);
            
            // need to set userTimeZone again
            $userTimezone = Tinebase_Core::getPreference()->getValue(Tinebase_Preference::TIMEZONE);
            Tinebase_Core::setupUserTimezone($userTimezone);
            
            $_user->setLoginTime($_accessLog->ip);
            
            $_accessLog->sessionid = session_id();
            $_accessLog->login_name = $_user->accountLoginName;
            $_accessLog->account_id = $_user->getId();
        }
    }
    
    /**
     * init session after successful login
     * 
     * @param Tinebase_Model_FullUser $_user
     */
    protected function _initUserSession(Tinebase_Model_FullUser $_user)
    {
        if (Tinebase_Config::getInstance()->getConfig(Tinebase_Config::SESSIONUSERAGENTVALIDATION, NULL, TRUE)->value) {
            Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' User agent validation disabled.');
        }
        
        if (Tinebase_Config::getInstance()->getConfig(Tinebase_Config::SESSIONIPVALIDATION, NULL, TRUE)->value) {
            Zend_Session::registerValidator(new Zend_Session_Validator_IpAddress());
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Session ip validation disabled.');
        }
        
        Zend_Session::regenerateId();
        
        /** 
         * fix php session header handling http://forge.tine20.org/mantisbt/view.php?id=4918 
         * -> search all Set-Cookie: headers and replace them with the last one!
         **/
        $cookieHeaders = array();
        foreach(headers_list() as $headerString) {
            if (strpos($headerString, 'Set-Cookie: TINE20SESSID=') === 0) {
                array_push($cookieHeaders, $headerString);
            }   
        }   
        header(array_pop($cookieHeaders), true);
        /** end of fix **/
        
        Tinebase_Core::getSession()->currentAccount = $_user;
    }
    
    /**
     * login failed
     * 
     * @param unknown_type $_loginname
     * @param Tinebase_Model_AccessLog $_accessLog
     */
    protected function _loginFailed($_loginname, Tinebase_Model_AccessLog $_accessLog)
    {
        Tinebase_User::getInstance()->setLastLoginFailure($_loginname);
        
        $_accessLog->login_name = $_loginname;
        $_accessLog->lo = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        Zend_Session::destroy();
        
        sleep(mt_rand(2,5));
    }
    
    /**
     * authenticate user but don't log in
     *
     * @param   string $_loginname
     * @param   string $_password
     * @param   string $_ipAddress
     * @param   string $_clientIdString
     * @return  bool
     */
    public function authenticate($_loginname, $_password, $_ipAddress, $_clientIdString = NULL)
    {
        $result = $this->login($_loginname, $_password, $_ipAddress, $_clientIdString);
        
        /**
         * we unset the Zend_Auth session variable. This way we keep the session,
         * but the user is not logged into Tine 2.0
         * we use this to validate passwords for OpenId for example
         */ 
        unset($_SESSION['Zend_Auth']);
        unset(Tinebase_Core::getSession()->currentAccount);
        
        return $result;
    }
    /**
     * change user password
     *
     * @param string $_oldPassword
     * @param string $_newPassword
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function changePassword($_oldPassword, $_newPassword)
    {
        //error_log(print_r(Tinebase_Core::getUser()->toArray(), true));
        
        // check config setting 
        if (!Tinebase_User::getBackendConfiguration('changepw', true)) {
            throw new Tinebase_Exception_AccessDenied('Password change not allowed.');                
        }
        
        $loginName = Tinebase_Core::getUser()->accountLoginName;
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " change password for $loginName");
        
        if (!Tinebase_Auth::getInstance()->isValidPassword($loginName, $_oldPassword)) {
            throw new Tinebase_Exception_InvalidArgument('Old password is wrong.');
        }
        
        Tinebase_User::getInstance()->setPassword(Tinebase_Core::getUser(), $_newPassword, true, false);
    }
    
    /**
     * destroy session
     *
     * @return void
     */
    public function logout($_ipAddress)
    {
        if (Tinebase_Core::isRegistered(Tinebase_Core::USER)) {
            $currentAccount = Tinebase_Core::getUser();
    
            if (is_object($currentAccount)) {
                Tinebase_AccessLog::getInstance()->setLogout(session_id(), $_ipAddress);                
            }
        }
        
        Zend_Session::destroy();
    }   
    
    /**
     * gets image info and data
     * 
     * @param   string $_application application which manages the image
     * @param   string $_identifier identifier of image/record
     * @param   string $_location optional additional identifier
     * @return  Tinebase_Model_Image
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function getImage($_application, $_identifier, $_location = '')
    {
        $appController = Tinebase_Core::getApplicationInstance($_application);
        if (!method_exists($appController, 'getImage')) {
            throw new Tinebase_Exception_NotFound("$_application has no getImage function.");
        }
        $image = $appController->getImage($_identifier, $_location);
        
        if (!$image instanceof Tinebase_Model_Image) {
            throw new Tinebase_Exception_UnexpectedValue("$_application returned invalid image.");
        }
        return $image;
    }
    
    /**
     * remove obsolete/outdated stuff from cache
     * notes: CLEANING_MODE_OLD -> removes obsolete cache entries (files for file cache)
     *        CLEANING_MODE_ALL -> removes complete cache structure (directories for file cache) + cache entries
     * 
     * @param string $_mode
     */
    public function cleanupCache($_mode = Zend_Cache::CLEANING_MODE_OLD)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Cleaning up the cache (mode: ' . $_mode . ')');
        
        Tinebase_Core::getCache()->clean($_mode);
    }
}
