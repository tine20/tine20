<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

use \Psr\Http\Message\RequestInterface;

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Controller extends Tinebase_Controller_Event
{
    const SYNC_CLASS_CONTACTS = 'Contacts';
    const SYNC_CLASS_EVENTS = 'Events';
    const SYNC_CLASS_TASKS = 'Tasks';
    const SYNC_CLASS_EMAIL = 'Email';

    const SYNC_API_ACTIVESYNC = 'ActiveSync';
    const SYNC_API_DAV = 'DAV';

    public const PAM_VALIDATE_REQUEST_TYPE = 'PAMvalidate';

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
    
    protected $_writeAccessLog;

    protected $_forceUnlockLoginArea = false;

    public function forceUnlockLoginArea(bool $bool = true)
    {
        $this->_forceUnlockLoginArea = $bool;
    }

    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_writeAccessLog = Tinebase_Application::getInstance()->isInstalled('Tinebase')
            && (Tinebase_Core::get('serverclassname') !== 'ActiveSync_Server_Http' 
                || (Tinebase_Application::getInstance()->isInstalled('ActiveSync')
                        && !(ActiveSync_Config::getInstance()->get(ActiveSync_Config::DISABLE_ACCESS_LOG))));
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
     * @param   string                           $loginName
     * @param   string                           $password
     * @param   \Zend\Http\PhpEnvironment\Request $request
     * @param   string                           $clientIdString
     *
     * @return  bool
     * @throws  Tinebase_Exception_MaintenanceMode
     */
    public function login($loginName, $password, \Zend\Http\PhpEnvironment\Request $request, $clientIdString = NULL)
    {
        // enforce utf8
        $password = Tinebase_Helper::mbConvertTo($password);

        // make sure pw is always replaced in Logger
        Tinebase_Core::getLogger()->addReplacement($password);

        if (Tinebase_Core::inMaintenanceModeAll()) {
            throw new Tinebase_Exception_MaintenanceMode();
        }

        // sanitize loginname - we might not support invalid/out of range characters
        $loginName = Tinebase_Core::filterInputForDatabase($loginName);

        // rolechange user: username*authuser?
        $authUserParts = preg_split('/\*+(?=[^*]+$)/', $loginName);
        if (isset($authUserParts[1]) && Tinebase_User::getInstance()->getUserByLoginName($authUserParts[1])) {
            $loginName = $authUserParts[1];
            $roleChangeUserName = $authUserParts[0];
        }

        $authResult = Tinebase_Auth::getInstance()->authenticate($loginName, $password);

        $accessLog = Tinebase_AccessLog::getInstance()->getAccessLogEntry($loginName, $authResult, $request,
            $clientIdString);

        $user = $this->_validateAuthResult($authResult, $accessLog);

        if (!($user instanceof Tinebase_Model_FullUser)) {
            return false;
        }

        $this->_loginUser($user, $accessLog, $password);

        $this->_checkPasswordPolicyAtLogin($password, $user);

        if (Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_NTLMV2_HASH_UPDATE_ON_LOGIN}) {
            $userController = Tinebase_User::getInstance();
            if ($userController instanceof Tinebase_User_Sql) {
                $userController->updateNtlmV2Hash($user->getId(), $password);
            }
        }

        if (Tinebase_Application::getInstance()->isInstalled('Felamimail', true)) {
            Felamimail_Controller::getInstance()->handleAccountLogin($user, $password);
        }

        if (isset($roleChangeUserName)) {
            Tinebase_Controller::getInstance()->changeUserAccount($roleChangeUserName);
        }

        return true;
    }

    protected function _checkPasswordPolicyAtLogin($password, $user)
    {
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PASSWORD_POLICY)
            ->{Tinebase_Config::CHECK_AT_LOGIN}) {
            try {
                Tinebase_User_PasswordPolicy::checkPasswordPolicy($password, $user);
            } catch (Tinebase_Exception_PasswordPolicyViolation $e) {
                $session = Tinebase_Core::get(Tinebase_Core::SESSION);
                if ($session) {
                    $session->mustChangePassword = $e->getMessage();
                }
            }
        }
    }

    protected function _throwMFAException(Tinebase_Model_AreaLockConfig $config, Tinebase_Record_RecordSet $feCfg)
    {
        $e = new Tinebase_Exception_AreaLocked('mfa required');
        $e->setArea($config->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME});
        $e->setMFAUserConfigs($feCfg);
        throw $e;
    }

    /**
     * create new user session (via openID connect)
     *
     * @param   string                           $oidcResponse
     * @param   \Zend\Http\PhpEnvironment\Request $request
     *
     * @return  bool
     * @throws  Tinebase_Exception_MaintenanceMode
     */
    public function loginOIDC($oidcResponse, \Zend\Http\PhpEnvironment\Request $request)
    {
        if (Tinebase_Core::inMaintenanceModeAll()) {
            throw new Tinebase_Exception_MaintenanceMode();
        }

        $ssoConfig = Tinebase_Config::getInstance()->{Tinebase_Config::SSO};
        if (! $ssoConfig->{Tinebase_Config::SSO_ACTIVE}) {
            throw new Tinebase_Exception('sso client config inactive');
        }

        $adapterName = $ssoConfig->{Tinebase_Config::SSO_ADAPTER};
        /** @var Tinebase_Auth_OpenIdConnect $authAdapter */
        $authAdapter = Tinebase_Auth_Factory::factory($adapterName);
        $authAdapter->setOICDResponse($oidcResponse);
        $authResult = $authAdapter->authenticate();
        $adapterUser = $authAdapter->getLoginUser();
        if (! $adapterUser) {
            return false;
        }
        $loginName = $adapterUser->accountLoginName;

        $accessLog = Tinebase_AccessLog::getInstance()->getAccessLogEntry($loginName, $authResult, $request,
            $adapterName);

        $user = $this->_validateAuthResult($authResult, $accessLog);

        if (!($user instanceof Tinebase_Model_FullUser)) {
            return false;
        }

        // TODO make credential cache work without PW
        $this->_loginUser($user, $accessLog, Tinebase_Record_Abstract::generateUID(20));

        return true;
    }

    /**
     * @param Tinebase_Model_FullUser $user
     * @param \Laminas\Http\PhpEnvironment\Request $request
     * @param string|null $clientIdString
     * @throws Tinebase_Exception_MaintenanceMode
     */
    public function loginUser(Tinebase_Model_FullUser $user, \Laminas\Http\PhpEnvironment\Request $request, $clientIdString = null)
    {
        $loginName = $user->accountLoginName;
        $authResult = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $loginName);
        $accessLog = Tinebase_AccessLog::getInstance()->getAccessLogEntry($loginName, $authResult, $request,
            $clientIdString);
        $this->_loginUser($user, $accessLog);
    }

    /**
     * @param Tinebase_Model_FullUser $user
     * @param Tinebase_Model_AccessLog $accessLog
     * @param string|null $password
     * @throws Tinebase_Exception_MaintenanceMode
     */
    protected function _loginUser(Tinebase_Model_FullUser $user, Tinebase_Model_AccessLog $accessLog, $password = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . " Login with username {$accessLog->login_name} from {$accessLog->ip} succeeded.");

        if (Tinebase_Core::inMaintenanceMode()) {
            if (Tinebase_Core::inMaintenanceModeAll() ||
                    ! $user->hasRight('Tinebase', Tinebase_Acl_Rights::MAINTENANCE)) {
                throw new Tinebase_Exception_MaintenanceMode();
            }
        }

        Tinebase_AccessLog::getInstance()->setSessionId($accessLog);

        $this->initUser($user);

        if (null !== $password) {
            $this->_updateCredentialCache($user->accountLoginName, $password);
        }

        $this->_updateAccessLog($user, $accessLog);
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
                    // only syncContactData if non-sync client!
                    $syncOptions = $this->_isSyncClient($_accessLog)
                        ? array()
                        : array(
                            'syncContactData' => true,
                            'syncContactPhoto' => true
                        );

                    Tinebase_User::syncUser($_username, $syncOptions);
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Failed to sync user data for: ' . $_username . ' reason: ' . $e->getMessage());
                    Tinebase_Exception::log($e);
                }
            }
            
            $user = $accountsController->getFullUserByLoginName($_username);
            
            $_accessLog->account_id = $user->getId();
            $_accessLog->login_name = $user->accountLoginName;
            
        } catch (Tinebase_Exception_NotFound $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Account ' . $_username . ' not found in account storage.');
            $_accessLog->result = Tinebase_Auth::FAILURE_IDENTITY_NOT_FOUND;
        } catch (Zend_Db_Adapter_Exception $zdae) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Some database connection failed: ' . $zdae->getMessage());
            $_accessLog->result = Tinebase_Auth::FAILURE_DATABASE_CONNECTION;
        }
        
        return $user;
    }

    protected function _isSyncClient($accessLog)
    {
        return in_array($accessLog->clienttype, array(
            Tinebase_Server_WebDAV::REQUEST_TYPE,
            ActiveSync_Server_Http::REQUEST_TYPE
        ));
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
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                    . __LINE__ . ' Account: '. $_user->accountLoginName . ' is disabled');
                $_accessLog->result = Tinebase_Auth::FAILURE_DISABLED;
            }
            
            // is the account expired?
            else if ($_user->accountStatus == Tinebase_User::STATUS_EXPIRED) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' Account: '. $_user->accountLoginName . ' password is expired');
                $_accessLog->result = Tinebase_Auth::FAILURE_PASSWORD_EXPIRED;
            }
            
            // too many login failures?
            else if ($_user->accountStatus == Tinebase_User::STATUS_BLOCKED) {

                // first check if the current user agent should be blocked
                if (! Tinebase_AccessLog::getInstance()->isUserAgentBlocked($_user, $_accessLog)) {
                    return;
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' Account: '. $_user->accountLoginName . ' is blocked');
                $_accessLog->result = Tinebase_Auth::FAILURE_BLOCKED;
            }

            // Tinebase run permission
            else if (! $_user->hasRight('Tinebase', Tinebase_Acl_Rights_Abstract::RUN)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' Account: '. $_user->accountLoginName . ' has not permissions for Tinebase');
                $_accessLog->result = Tinebase_Auth::FAILURE_DISABLED;
            }
        }
    }
    
    /**
     * initialize user (session, locale, tz)
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param boolean $fixCookieHeader
     */
    public function initUser(Tinebase_Model_FullUser $_user, $fixCookieHeader = true)
    {
        Tinebase_Core::setUser($_user);

        if (Tinebase_Session_Abstract::getSessionEnabled()) {
            $this->_initUserSession($fixCookieHeader);
        }
        
        // need to set locale again and because locale might not be set correctly during loginFromPost
        // use 'auto' setting because it is fetched from cookie or preference then
        Tinebase_Core::setupUserLocale('auto');
        
        // need to set userTimeZone again
        $userTimezone = Tinebase_Core::getPreference()->getValue(Tinebase_Preference::TIMEZONE);
        Tinebase_Core::setupUserTimezone($userTimezone);
    }
    
    /**
     * init session after successful login
     * 
     * @param Tinebase_Model_FullUser $user
     * @param boolean $fixCookieHeader
     */
    protected function _initUserSession($fixCookieHeader = true)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Init user session');

        // FIXME 0010508: Session_Validator_AccountStatus causes problems
        //Tinebase_Session::registerValidatorAccountStatus();

        Tinebase_Session::registerValidatorMaintenanceMode();
        
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::SESSIONUSERAGENTVALIDATION, TRUE)) {
            Tinebase_Session::registerValidatorHttpUserAgent();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' User agent validation disabled.');
        }
        
        // we only need to activate ip session validation for non-encrypted connections
        $ipSessionValidationDefault = Tinebase_Core::isHttpsRequest() ? FALSE : TRUE;
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::SESSIONIPVALIDATION, $ipSessionValidationDefault)) {
            Tinebase_Session::registerValidatorIpAddress();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Session ip validation disabled.');
        }
        
        if ($fixCookieHeader && Zend_Session::getOptions('use_cookies')) {
            /** 
             * fix php session header handling http://forge.tine20.org/mantisbt/view.php?id=4918 
             * -> search all Set-Cookie: headers and replace them with the last one!
             **/
            $cookieHeaders = array();
            foreach (headers_list() as $headerString) {
                if (strpos($headerString, 'Set-Cookie: TINE20SESSID=') === 0) {
                    array_push($cookieHeaders, $headerString);
                }
            }
            header(array_pop($cookieHeaders), true);
            /** end of fix **/
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Save account object in session');

        Tinebase_Session::getSessionNamespace()->currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * login failed
     * 
     * @param  Zend_Auth_Result          $authResult
     * @param  Tinebase_Model_AccessLog  $accessLog
     */
    protected function _loginFailed(Zend_Auth_Result $authResult, Tinebase_Model_AccessLog $accessLog)
    {
        // @todo update sql schema to allow empty sessionid column
        $accessLog->sessionid = Tinebase_Record_Abstract::generateUID();
        $accessLog->lo = $accessLog->li;
        $user = null;

        if (Tinebase_Auth::FAILURE_CREDENTIAL_INVALID == $accessLog->result) {
            $user = Tinebase_User::getInstance()->setLastLoginFailure($accessLog->login_name);
        }

        $loglevel = Zend_Log::INFO;
        if (null !== $user) {
            $accessLog->account_id = $user->getId();
            $warnLoginFailures = Tinebase_Config::getInstance()->get(Tinebase_Config::WARN_LOGIN_FAILURES, 4);
            if ($user->loginFailures >= $warnLoginFailures) {
                $loglevel = Zend_Log::WARN;
            }
        }

        if (Tinebase_Core::isLogLevel($loglevel)) Tinebase_Core::getLogger()->log(
            __METHOD__ . '::' . __LINE__
                . " Login with username {$accessLog->login_name} from {$accessLog->ip} failed ({$accessLog->result})!"
                . ($user ? ' Auth failure count: ' . $user->loginFailures : ''),
            $loglevel);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Auth result messages: ' . print_r($authResult->getMessages(), TRUE));

        Tinebase_AccessLog::getInstance()->create($accessLog);
    }

    /**
     * authenticate user but don't log in
     *
     * @param   string $loginName
     * @param   string $password
     * @param   string $clientIdString
     * @return  bool
     */
    public function authenticate($loginName, $password, $clientIdString = NULL)
    {
        $result = $this->login($loginName, $password, Tinebase_Core::get(Tinebase_Core::REQUEST), $clientIdString);
        
        /**
         * we unset the Zend_Auth session variable. This way we keep the session,
         * but the user is not logged into Tine 2.0
         * we use this to validate passwords for OpenId for example
         */
        $coreSession = Tinebase_Session::getSessionNamespace();
        unset($coreSession->Zend_Auth);
        unset($coreSession->currentAccount);
        
        return $result;
    }
    
    /**
     * change user password
     *
     * @param string $_oldPassword
     * @param string $_newPassword
     * @param string $_pwType
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function changePassword($_oldPassword, $_newPassword, $_pwType = 'password')
    {
        if ($_pwType === 'password' && ! Tinebase_Config::getInstance()->get(Tinebase_Config::PASSWORD_CHANGE, TRUE)) {
            throw new Tinebase_Exception_AccessDenied('Password change not allowed.');
        }

        $user = Tinebase_Core::getUser();
        $loginName = $user->accountLoginName;
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " change $_pwType for $loginName");

        if ($_pwType === 'password') {
            if (!Tinebase_Auth::getInstance()->isValidPassword($loginName, $_oldPassword)) {
                throw new Tinebase_Exception_InvalidArgument('Old password is wrong.');
            }
            if ($_oldPassword == $_newPassword) {
                // @Todo translation didn work
                throw new Tinebase_Exception_SystemGeneric('The new password must be different from the old one.'); // _('The new password must be different from the old one.')
            }
            Tinebase_User::getInstance()->setPassword($user, $_newPassword, true, false);
            Tinebase_Core::get(Tinebase_Core::SESSION)->mustChangePassword = null;
            Tinebase_Core::get(Tinebase_Core::SESSION)->currentAccount->password_must_change = false;
        } else {
            $pinAuth = Tinebase_Auth_Factory::factory(Tinebase_Auth::PIN);
            $pinAuth->setIdentity($loginName)->setCredential($_oldPassword);
            $authResult = $pinAuth->authenticate();
            if (! $authResult->isValid()) {
                throw new Tinebase_Exception_SystemGeneric('Old pin is wrong.'); // _('Old pin is wrong.')
            }
            Tinebase_User::getInstance()->setPin($user, $_newPassword);
        }
    }
    
    /**
     * switch to another user's account
     *
     * @param string $loginName
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    public function changeUserAccount($loginName)
    {
        $allowedRoleChanges = Tinebase_Config::getInstance()->get(Tinebase_Config::ROLE_CHANGE_ALLOWED);
        
        if (!$allowedRoleChanges) {
            throw new Tinebase_Exception_AccessDenied('It is not allowed to switch to this account');
        }
        
        $currentAccountName = Tinebase_Core::getUser()->accountLoginName;
        
        $allowedRoleChangesArray = $allowedRoleChanges->toArray();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ROLE_CHANGE_ALLOWED: ' . print_r($allowedRoleChangesArray, true));
        
        $user = null;
        
        if (isset($allowedRoleChangesArray[$currentAccountName])
            && in_array($loginName, $allowedRoleChangesArray[$currentAccountName])
        ) {
            $user = Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
            // CalDAV / ActiveSync have no session
            Tinebase_Core::set('userAccountChanged', true);
            Tinebase_Core::set('originalAccountName', $currentAccountName);
            if (Tinebase_Session::isStarted()) {
                Tinebase_Session::getSessionNamespace()->userAccountChanged = true;
                Tinebase_Session::getSessionNamespace()->originalAccountName = $currentAccountName;
            }
        } else if (Tinebase_Session::getSessionNamespace()->userAccountChanged 
            && isset($allowedRoleChangesArray[Tinebase_Session::getSessionNamespace()->originalAccountName])
        ) {
            $user = Tinebase_User::getInstance()->getFullUserByLoginName(Tinebase_Session::getSessionNamespace()->originalAccountName);
            Tinebase_Core::set('userAccountChanged', false);
            Tinebase_Core::set('originalAccountName', null);
            Tinebase_Session::getSessionNamespace()->userAccountChanged = false;
            Tinebase_Session::getSessionNamespace()->originalAccountName = null;
        }
        
        if ($user) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Switching to user account ' . $user->accountLoginName);
            
            $this->initUser($user, /* $fixCookieHeader = */ false);
            return true;
        }

        return false;
    }
    
    /**
     * logout user
     *
     * @return void
     */
    public function logout()
    {
        if ($this->_writeAccessLog) {
            if (Tinebase_Core::isRegistered(Tinebase_Core::USER) && is_object(Tinebase_Core::getUser())) {
                Tinebase_AccessLog::getInstance()->setLogout();
            }
        }
    }
    
    /**
     * gets image info and data
     * 
     * @param   string $application application which manages the image
     * @param   string $identifier identifier of image/record
     * @param   string $location optional additional identifier
     * @return  Tinebase_Model_Image
     * @throws  Tinebase_Exception_NotFound
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function getImage($application, $identifier, $location = '')
    {
        if ($location === 'vfs') {
            $node = Tinebase_FileSystem::getInstance()->get($identifier);
            $path = Tinebase_Model_Tree_Node_Path::STREAMWRAPPERPREFIX . Tinebase_FileSystem::getInstance()->getPathOfNode($node, /* $getPathAsString */ true);
            $image = Tinebase_ImageHelper::getImageInfoFromBlob(file_get_contents($path));

        } else if ($application == 'Tinebase' && $location == 'tempFile') {
            $tempFile = Tinebase_TempFile::getInstance()->getTempFile($identifier);
            $image = Tinebase_ImageHelper::getImageInfoFromBlob(file_get_contents($tempFile->path));

        } else {
            $appController = Tinebase_Core::getApplicationInstance($application);
            if (!method_exists($appController, 'getImage')) {
                throw new Tinebase_Exception_NotFound("$application has no getImage function.");
            }
            $image = $appController->getImage($identifier, $location);
        }

        if (! $image instanceof Tinebase_Model_Image) {
            if (is_array($image)) {
                $image = new Tinebase_Model_Image($image + array(
                    'application' => $application,
                    'id' => $identifier,
                    'location' => $location
                ));
            } else {
                throw new Tinebase_Exception_UnexpectedValue('broken image');
            }
        }


        return $image;
    }
    
    /**
     * remove obsolete/outdated stuff from cache
     * notes: CLEANING_MODE_OLD -> removes obsolete cache entries (files for file cache)
     *        CLEANING_MODE_ALL -> removes complete cache structure (directories for file cache) + cache entries
     * 
     * @param string $_mode
     * @return bool
     */
    public function cleanupCache($_mode = Zend_Cache::CLEANING_MODE_OLD)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Cleaning up the cache (mode: ' . $_mode . ')');
        
        Tinebase_Core::getCache()->clean($_mode);

        $cacheDir = rtrim(Tinebase_Core::getCacheDir(), '/') . '/tine20Twig/';
        if (is_dir($cacheDir)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' cleaning twig cache in ' . $cacheDir . ' ...');

            $time = time() - (7 * 24 * 3600); // older than one week
            $startTime = time();
            /** @var DirectoryIterator $di */
            foreach (new DirectoryIterator($cacheDir) as $di) {
                if (strpos($di->getFilename(), '.') === false && $di->isDir()) {
                    /** @var DirectoryIterator $fileIterator */
                    foreach (new DirectoryIterator($cacheDir . $di->getFilename()) as $fileIterator) {
                        if ($fileIterator->isFile() && $fileIterator->getCTime() < $time) {
                            unlink($fileIterator->getPathname());
                        }
                    }
                    if (time() - $startTime > 3600) break; // we do this for one hour max
                }
            }
        }

        return true;
    }
    
    /**
     * cleanup old sessions files => needed only for filesystems based sessions
     *
     * @return bool
     */
    public function cleanupSessions()
    {
        $config = Tinebase_Core::getConfig();
        $backendType = Tinebase_Session_Abstract::getConfiguredSessionBackendType();
        
        if (strpos($backendType, 'File') === 0) {
            $maxLifeTime = ($config->session && $config->session->lifetime) ? $config->session->lifetime : 86400;
            $path = Tinebase_Session_Abstract::getSessionDir();
            
            $unlinked = 0;
            try {
                $dir = new DirectoryIterator($path);
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . " Could not cleanup sessions");
                Tinebase_Exception::log($e);
                return false;
            }
            
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot() && !$fileinfo->isLink() && $fileinfo->isFile()) {
                    if ($fileinfo->getMTime() < Tinebase_DateTime::now()->getTimestamp() - $maxLifeTime) {
                        unlink($fileinfo->getPathname());
                        $unlinked++;
                    }
                }
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . " Deleted $unlinked expired session files");
            
            Tinebase_Config::getInstance()->set(Tinebase_Config::LAST_SESSIONS_CLEANUP_RUN, Tinebase_DateTime::now()->toString());
        }

        return true;
    }
    
    /**
     * spy function for unittesting of queue workers
     * 
     * this function writes the number of executions of itself in the given 
     * file and optionally sleeps a given time
     * 
     * @param string  $filename
     * @param int     $sleep
     * @param int     $fail
     */
    public function testSpy($filename=NULL, $sleep=0, $fail=NULL)
    {
        $filename = $filename ? $filename : ('/tmp/'.__METHOD__);
        $counter = file_exists($filename) ? (int) file_get_contents($filename) : 0;
        
        file_put_contents($filename, ++$counter);
        
        if ($sleep) {
            sleep($sleep);
        }
        
        if ($fail && (int) $counter <= $fail) {
            throw new Exception('spy failed on request');
        }
        
        return;
    }
    
    /**
     * handle events for Tinebase
     * 
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case 'Admin_Event_DeleteGroup':
                foreach ($_eventObject->groupIds as $groupId) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Removing role memberships of group ' .$groupId );
                    
                    $roleIds = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($groupId, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP);
                    foreach ($roleIds as $roleId) {
                        Tinebase_Acl_Roles::getInstance()->removeRoleMember($roleId, array(
                            'id'   => $groupId,
                            'type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                        ));
                    }
                }
                break;
        }
    }
    
    /**
     * update access log entry if needed
     * 
     * @param Tinebase_Model_FullUser $user
     * @param Tinebase_Model_AccessLog $accessLog
     */
    protected function _updateAccessLog(Tinebase_Model_FullUser $user, Tinebase_Model_AccessLog $accessLog)
    {
        if (! $accessLog->getId()) {
            $user->setLoginTime($accessLog->ip);
            if ($this->_writeAccessLog) {
                $accessLog->setId(Tinebase_Record_Abstract::generateUID());
                $accessLog = Tinebase_AccessLog::getInstance()->create($accessLog);
            }
        }
        
        Tinebase_Core::set(Tinebase_Core::USERACCESSLOG, $accessLog);
    }
    
    /**
     * update credential cache
     * 
     * @param string $loginName
     * @param string $password
     */
    protected function _updateCredentialCache($loginName, $password)
    {
        $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($loginName, $password);
        Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
    }
    
    /**
     * validate is authentication was successful, user object is available and user is not expired
     * 
     * @param Zend_Auth_Result $authResult
     * @param Tinebase_Model_AccessLog $accessLog
     * @return boolean|Tinebase_Model_FullUser
     */
    protected function _validateAuthResult(Zend_Auth_Result $authResult, Tinebase_Model_AccessLog $accessLog)
    {
        // authentication failed
        if ($accessLog->result !== Tinebase_Auth::SUCCESS) {
            $this->_loginFailed($authResult, $accessLog);
            
            return false;
        }
        
        // try to retrieve user from accounts backend
        $user = $this->_getLoginUser($authResult->getIdentity(), $accessLog);
        
        if ($accessLog->result !== Tinebase_Auth::SUCCESS || !$user) {

            if ($user) {
                $accessLog->account_id = $user->getId();
            }
            $this->_loginFailed($authResult, $accessLog);
            
            return false;
        }
        
        // check if user is expired or blocked
        $this->_checkUserStatus($user, $accessLog);

        if ($accessLog->result !== Tinebase_Auth::SUCCESS) {
            $this->_loginFailed($authResult, $accessLog);
            return false;
        }

        $this->_validateSecondFactor($accessLog, $user);

        return $user;
    }

    /**
     * @param Tinebase_Model_AccessLog $accessLog
     * @param Tinebase_Model_FullUser $user
     * @return bool
     */
    public function _validateSecondFactor(Tinebase_Model_AccessLog $accessLog, Tinebase_Model_FullUser $user): void
    {
        $required = false;
        $areaLock = Tinebase_AreaLock::getInstance();
        $userConfigIntersection = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
        if ($areaLock->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN)) {
            /** @var Tinebase_Model_AreaLockConfig $areaConfig */
            foreach ($areaLock->getAreaConfigs(Tinebase_Model_AreaLockConfig::AREA_LOGIN) as $areaConfig) {
                if (Tinebase_Model_AreaLockConfig::POLICY_REQUIRED ===
                        $areaConfig->{Tinebase_Model_AreaLockConfig::FLD_POLICY}) {
                    $required = true;
                }
                $userConfigIntersection->mergeById($areaConfig->getUserMFAIntersection($user));
            }

            // user has no 2FA config -> currently its sort of optional -> no check
            if (!$required && $this->_forceUnlockLoginArea && count($userConfigIntersection->mfa_configs) === 0) {
                Tinebase_Core::get(Tinebase_Core::SESSION)->encourage_mfa = true;
                $areaLock->forceUnlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN);
                return;
            }
        }

        if (($accessLog->clienttype !== Tinebase_Frontend_Json::REQUEST_TYPE && $accessLog->clienttype !== self::PAM_VALIDATE_REQUEST_TYPE) ||
                ! $areaLock->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN) ||
                ! $areaLock->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN)
        ) {
            // no login lock or non json access
            return;
        }

        $areaConfig = $areaLock->getLastAuthFailedAreaConfig();

        $context = $this->getRequestContext();
        $mfaId = $context['MFAId'];
        $password = $context['MFAPassword'];

        // check if FE send mfa or if we only have one 2FA configured anyway
        if ((!empty($mfaId) && $userConfigIntersection->getById($mfaId)) || (1 === $userConfigIntersection->count() &&
                ($mfaId = $userConfigIntersection->getFirstRecord()->getId()))) {
            $userCfg = $userConfigIntersection->getById($mfaId);
            // FE send provider and password -> validate it
            if (!empty($password)) {
                foreach ($areaLock->getAreaConfigs(Tinebase_Model_AreaLockConfig::AREA_LOGIN)->filter(function($rec) use($userCfg) {
                            return in_array($userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID}, $rec->{Tinebase_Model_AreaLockConfig::FLD_MFAS});
                        }) as $areaCfg) {
                    if (!$areaCfg->getBackend()->hasValidAuth()) {
                        $areaLock->unlock(
                            $areaCfg->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME},
                            $mfaId,
                            $password,
                            $user
                        );
                        break;
                    }
                }
                return;
            } else {
                if (!Tinebase_Auth_MFA::getInstance($userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID})
                        ->sendOut($userCfg)) {
                    throw new Tinebase_Exception('mfa send out failed');
                } else {
                    // success, FE to render input field
                    $this->_throwMFAException($areaConfig, new Tinebase_Record_RecordSet(
                        Tinebase_Model_MFA_UserConfig::class, [$userConfigIntersection->getById($mfaId)]));
                }
            }
        } else {
            // FE to render selection which 2FA to use
            $this->_throwMFAException($areaConfig, $areaConfig->getUserMFAIntersection($user));
        }

        // must never reach this
        assert(false, 'should return true or throw, line must not be reached');
    }

    /**
     * returns true if user account has been changed
     * 
     * @return boolean
     */
    public function userAccountChanged()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ .' check if userAccountChanged');

        try {
            $session = Tinebase_Session::getSessionNamespace();
            return ($session instanceof Zend_Session_Namespace && isset($session->userAccountChanged))
                ? $session->userAccountChanged
                : false;
        } catch (Zend_Session_Exception $zse) {
            return !! Tinebase_Core::get('userAccountChanged');
        }
    }

    /**
     * rebuild paths
     *
     * @return bool
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function rebuildPaths()
    {
        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' search paths are not enabled');
            return false;
        }

        $applications = Tinebase_Application::getInstance()->getApplications();
        foreach ($applications as $application) {
            try {
                $app = Tinebase_Core::getApplicationInstance($application, '', true);
            } catch (Tinebase_Exception_NotFound $tenf) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                continue;
            } catch (Tinebase_Exception_AccessDenied $tead) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
                continue;
            }

            if (! $app instanceof Tinebase_Controller_Abstract) {
                continue;
            }

            $pathModels = $app->getModelsUsingPaths();
            if (!is_array($pathModels)) {
                $pathModels = array();
            }
            foreach($pathModels as $pathModel) {
                $controller = Tinebase_Core::getApplicationInstance($pathModel, '', true);

                $_filter = $pathModel . 'Filter';
                $_filter = new $_filter();

                $iterator = new Tinebase_Record_Iterator(array(
                    'iteratable' => $this,
                    'controller' => $controller,
                    'filter' => $_filter,
                    'options' => array('getRelations' => true),
                    'function' => 'rebuildPathsIteration',
                ));
                $result = $iterator->iterate();

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    if (false === $result) {
                        $result['totalcount'] = 0;
                    }
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Build paths for ' . $result['totalcount'] . ' records of ' . $pathModel);
                }
            }
        }

        Tinebase_TransactionManager::getInstance()->resetTransactions();
        /** @phpstan-ignore-next-line */
        Tinebase_Record_Path::getInstance()->getBackend()->executeDelayed();
        Tinebase_Path_Backend_Sql::optimizePathsTable();

        return true;
    }

    /**
     * rebuild paths for multiple records in an iteration
     * @see Tinebase_Record_Iterator / self::rebuildPaths()
     *
     * @param Tinebase_Record_RecordSet $records
     */
    public function rebuildPathsIteration(Tinebase_Record_RecordSet $records)
    {
        /** @var Tinebase_Record_Interface $record */
        foreach ($records as $record) {
            try {
                Tinebase_Record_Path::getInstance()->rebuildPaths($record);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' record path building failed: '
                    . $e->getMessage() . PHP_EOL
                    . $e->getTraceAsString() . PHP_EOL
                    . $record->toArray());
            }
        }
    }

    /**
     * @return bool
     */
    public function cleanAclTables()
    {
        $treeNodeAcl = new Tinebase_Backend_Sql_Grants(array(
            'modelName' => Tinebase_Model_Grants::class,
            'tableName' => 'tree_node_acl',
            'recordTable' => 'tree_nodes'
        ));
        $treeNodeAcl->cleanGrants();

        $persistentFilterAcl = new Tinebase_Backend_Sql_Grants(array(
            'modelName' => Tinebase_Model_PersistentFilterGrant::class,
            'tableName' => 'filter_acl',
            'recordTable' => 'filter'
        ));
        $persistentFilterAcl->cleanGrants();

        $containerAcl = new Tinebase_Backend_Sql_Grants(array(
            'modelName' => Tinebase_Model_Grants::class,
            'tableName' => 'container_acl',
            'recordTable' => 'container',
            'recordColumn' => 'container_id'
        ));
        $containerAcl->cleanGrants();

        return true;
    }

    /**
     * @param \FastRoute\RouteCollector $r
     * @return void|null
     */
    public static function addFastRoutes(
        /** @noinspection PhpUnusedParameterInspection */
        \FastRoute\RouteCollector $r
    ) {
        $r->addGroup('', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/favicon[/{size}[/{ext}]]', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Controller::class, 'getFavicon', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());

            $routeCollector->get('/logo[/{type}[/{size}]]', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Controller::class, 'getLogo', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());

            $routeCollector->get('/health', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Controller::class, 'healthCheck', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());

            $routeCollector->post('/authPAM/validate', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicPostAuthPAMvalidate', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());

            $routeCollector->get('/metrics[/{apiKey}]', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Controller::class, 'getStatusMetrics', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());
        });

        $r->addGroup('/Tinebase', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/_status[/{apiKey}]', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Controller::class, 'getStatus', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());

            $routeCollector->addRoute(['GET', 'POST'], '/export/{definitionId}', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Export_Abstract::class, 'expressiveApi'))->toArray());
        });

        $r->addGroup('/autodiscover', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->post('/autodiscover.xml', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicApiMSAutodiscoverXml', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());
        });

        $r->addGroup('/Autodiscover', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->post('/Autodiscover.xml', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicApiMSAutodiscoverXml', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());
        });
        $r->addGroup('/.well-known', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/webfinger', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Webfinger::class, 'handlePublicGet', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::IGNORE_MAINTENANCE_MODE => true,
            ]))->toArray());
        });

        $r->addGroup('/ocs/v2.php/cloud', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/user', (new Tinebase_Expressive_RouteHandler(
                Tinebase_OwncloudAPI::class, 'getUser', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
            ]))->toArray());

            $routeCollector->get('/capabilities', (new Tinebase_Expressive_RouteHandler(
                Tinebase_OwncloudAPI::class, 'getCapabilities', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
            ]))->toArray());
        });
    }

    public function publicPostAuthPAMvalidate(): \Psr\Http\Message\ResponseInterface
    {
        try {
            /** @var \Psr\Http\Message\ServerRequestInterface $request */
            $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);

            if (!($body = json_decode($request->getBody()->getContents(), true)) || !isset($body['user']) ||
                    !isset($body['pass'])) {
                return $this->_publicPostAuthPAMvalidateReturnError('bad request, json body needs to have user and pass');
            }

            try {
                $user = Tinebase_User::getInstance()->getFullUserByLoginName($body['user']);
            } catch (Tinebase_Exception_NotFound $tenf) {
                return $this->_publicPostAuthPAMvalidateReturnStatus(false);
            }
            if (isset($body['required-group'])) {
                try {
                    $group = Tinebase_Group::getInstance()->getGroupByName($body['required-group']);
                } catch (Tinebase_Exception_Record_NotDefined $tenf) {
                    return $this->_publicPostAuthPAMvalidateReturnError('required group does not exist');
                }
                if (!in_array($group->getId(), Tinebase_Group::getInstance()->getGroupMemberships($user))) {
                    return $this->_publicPostAuthPAMvalidateReturnStatus(false);
                }
            }

            $areaLock = Tinebase_AreaLock::getInstance();
            $userConfigIntersection = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
            /** @var Tinebase_Model_AreaLockConfig $areaConfig */
            foreach ($areaLock->getAreaConfigs(Tinebase_Model_AreaLockConfig::AREA_LOGIN) as $areaConfig) {
                $userConfigIntersection->mergeById($areaConfig->getUserMFAIntersection($user));
            }

            if (0 === $userConfigIntersection->count()) {
                return $this->_publicPostAuthPAMvalidateReturnStatus(false);
            }

            /** @var Tinebase_Model_MFA_UserConfig $uConf */
            foreach ($userConfigIntersection as $uConf) {
                if (null === ($mfaLength = Tinebase_Auth_MFA::getInstance(
                        $uConf->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID})->getAdapter()->getClientPasswordLength())) {
                    if (null === ($mfaLength = $uConf->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->getClientPasswordLength())) {
                        continue;
                    }
                }
                if (strlen($body['pass']) <= $mfaLength) {
                    continue;
                }
                if (Tinebase_Auth::getInstance()->authenticate($body['user'], substr($body['pass'], 0, 0 - $mfaLength))
                        ->getCode() !== Tinebase_Auth::SUCCESS) {
                    continue;
                }

                $this->setRequestContext([
                    'MFAPassword' => substr($body['pass'], 0 - $mfaLength),
                    'MFAId' => $uConf->getId(),
                ]);

                try {
                    if ($this->login($body['user'], substr($body['pass'], 0, 0 - $mfaLength), Tinebase_Core::getRequest(), self::PAM_VALIDATE_REQUEST_TYPE)) {
                        return $this->_publicPostAuthPAMvalidateReturnStatus(true);
                    }
                } catch (Tinebase_Exception_AreaUnlockFailed $teauf) {
                    $this->_publicPostAuthPAMvalidateReturnStatus(false);
                }
            }

            return $this->_publicPostAuthPAMvalidateReturnStatus(false);
        } catch (Tinebase_Exception_MaintenanceMode $temm) {
            return $this->_publicPostAuthPAMvalidateReturnError('maintenance mode is on');
        } catch (Exception $e) {
            return $this->_publicPostAuthPAMvalidateReturnError('internal server error');
        }
    }

    protected function _publicPostAuthPAMvalidateReturnStatus(bool $value): \Laminas\Diactoros\Response
    {
        return (new \Laminas\Diactoros\Response('php://memory', 200))
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(
                (new \Laminas\Diactoros\StreamFactory())->createStream(
                    json_encode(['login-success' => $value])
                )
            );
    }

    protected function _publicPostAuthPAMvalidateReturnError(string $msg): \Laminas\Diactoros\Response
    {
        return (new \Laminas\Diactoros\Response('php://memory', 200))
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(
                (new \Laminas\Diactoros\StreamFactory())->createStream(
                    json_encode(['error' => ['message' => $msg]])
                )
            );
    }

    /**
     * @return \Laminas\Diactoros\Response
     * @throws Tinebase_Exception_AccessDenied
     *
     * @todo replace with "healthCheck"?
     */
    public function getStatus($apiKey = null)
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::STATUS_INFO) || ! Tinebase_Config::getInstance()->get(Tinebase_Config::STATUS_API_KEY)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No API key configured');

            return new \Laminas\Diactoros\Response\EmptyResponse();
        }
        
        if ($apiKey !== Tinebase_Config::getInstance()->get(Tinebase_Config::STATUS_API_KEY, false)) {
            throw new Tinebase_Exception_AccessDenied('Not authorized. Invalid API Key.');
        }

        // @todo fetch more status info

        $data = [
            'actionqueue' => Tinebase_ActionQueue::getStatus(),
        ];
        $response = new \Laminas\Diactoros\Response\JsonResponse($data);
        return $response;
    }

    /**
     * @return \Laminas\Diactoros\Response
     * @throws Tinebase_Exception_AccessDenied
     */
    public function getStatusMetrics($apiKey = null)
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::METRICS_API_KEY)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No API key configured');
            return new \Laminas\Diactoros\Response\EmptyResponse();
        }

        if ($apiKey !== Tinebase_Config::getInstance()->get(Tinebase_Config::METRICS_API_KEY, false)) {
            throw new Tinebase_Exception_AccessDenied('Not authorized. Invalid metrics API Key.');
        }
        
        $data = [];
        foreach (Tinebase_Application::getInstance()->getApplications() as $application) {
            $appControllerName = $application->name . '_Controller';
            if (class_exists($appControllerName)) {
                $appController = call_user_func($appControllerName . '::getInstance');
                if (method_exists($appController, 'metrics')) {
                    $metricsData = $appController->metrics();
                    $data = array_merge($data, $metricsData);
                }
            }
        }

        $response = new \Laminas\Diactoros\Response\JsonResponse($data);
        return $response;
    }

    /**
     * get application metrics
     *
     * @return array
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function metrics(): array
    {
        $data = [
            'activeUsers' => Tinebase_User::getInstance()->getActiveUserCount(),
            'quotas' => Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA}->toArray(),
        ];

        $fileSystem = Tinebase_FileSystem::getInstance();
        $rootPath = $fileSystem->getApplicationBasePath('Tinebase');
        $fileSystemStorage = $fileSystem->getEffectiveAndLocalQuota($fileSystem->stat($rootPath));
        $data = array_merge($data, ['fileStorage' => $fileSystemStorage['effectiveUsage']]);

        if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            try {
                $imapBackend = Tinebase_EmailUser::getInstance();
            } catch (Tinebase_Exception_Backend $teb) {
                $imapBackend = null;
            }

            if ($imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
                $imapUsageQuota = $imapBackend->getTotalUsageQuota();
                $emailStorage = $imapUsageQuota['mailQuota'];
                $data = array_merge($data, ['emailStorage' => $emailStorage]);

                // there are tine instances without felamimail that still have system mailaccounts
                // we need to get the number of mail accounts from the users
                $usersWithSystemAccount = 0;
                foreach (Tinebase_User::getInstance()->getUsers() as $user) {
                    $systemEmailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user);
                    if ($imapBackend->userExists($systemEmailUser)) {
                        $usersWithSystemAccount++;
                    }
                }
                $data = array_merge($data, ['usersWithSystemAccount' => $usersWithSystemAccount]);
            }
        }
        
        return $data;
    }

    /**
     * return tine20 health (json encoded)
     * - status can be one of [pass, fail, warn]
     * - returns http error code 500 on fail
     * - checks: config, db, temp dir, files dir
     * - client ip address needs to be in Tinebase_Config::ALLOWEDHEALTHCHECKIPS
     *
     * @return \Laminas\Diactoros\Response
     *
     * @todo add cache check (see \Tinebase_Frontend_Cli::monitoringCheckCache + add $this->checkCache())
     * @todo use api key instead of client whitelist?
     */
    public function healthCheck()
    {
        $clientIp = Tinebase_Core::getRequest()->getRemoteAddress();
        if (! in_array($clientIp, Tinebase_Config::getInstance()->{Tinebase_Config::ALLOWEDHEALTHCHECKIPS})) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' client ip not allowed: '
                    . $clientIp);
            return new \Laminas\Diactoros\Response('php://memory', 404);
        }

        $status = 'pass';
        $problems = [];
        if (! Tinebase_Controller::getInstance()->checkConfig()) {
            $status = 'fail';
            $problems[] = 'config';
        }

        try {
            Tinebase_Core::getDb();
        } catch (Zend_Db_Adapter_Exception $zdae) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR))
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' '
                    . $zdae->getMessage());
            $status = 'fail';
            $problems[] = 'database';
        }

        $dirsToCheck = [
            'temp dir' => Tinebase_Core::getTempDir(),
            'files dir' => Tinebase_Core::getConfig()->filesdir
        ];
        foreach ($dirsToCheck as $key => $dir) {
            if (empty($dir)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR))
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $key . ' not configured');
                $status = 'fail';
                $problems[] = $key;
            } else {
                $filename = $dir . DIRECTORY_SEPARATOR . __METHOD__ . Tinebase_Record_Abstract::generateUID(10);
                try {
                    file_put_contents($filename, 'abc');
                    unlink($filename);
                } catch (Throwable $t) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR))
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $t->getMessage());
                    $status = 'fail';
                    $problems[] = $key;
                }
            }
        }

        $code = $status === 'fail' ? 500 : 200;
        $data = [
            'status' => $status,
            'problems' => $problems,
        ];
        return new \Laminas\Diactoros\Response\JsonResponse($data, $code);
    }

    /**
     * @return bool
     */
    public function checkConfig()
    {
        $configfile = Setup_Core::getConfigFilePath();
        if ($configfile) {
            $configfile = escapeshellcmd($configfile);
            if (preg_match('/^win/i', PHP_OS)) {
                exec("php -l $configfile 2> NUL", $error, $code);
            } else {
                exec("php -l $configfile 2> /dev/null", $error, $code);
            }
            if ($code == 0) {
                return true;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT))
                    Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Config file syntax error');
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT))
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Config file missing');
        }

        return false;
    }

    /**
     * @param int|string $size
     * @param string $ext
     * @return \Laminas\Diactoros\Response
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Cache_Exception
     *
     * @todo fix $size param - it should not be allowed to set it to png/svg
     */
    public function getFavicon($size = 16, string $ext = 'png')
    {
        if ($size == 'svg' || $ext == 'svg') {
            $config = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON_SVG);

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(Tinebase_Helper::getFileOrUriContents($config));

            return $response
                ->withAddedHeader('Content-Type', 'image/svg+xml');
        } else if ($size === 'png') {
            $size = 16;
            $ext = 'png';
        }
        $mime = Tinebase_ImageHelper::getMime($ext);
        if (! in_array($mime, Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Image format not supported: ' . $mime . ' ... using png');
            $mime = Tinebase_ImageHelper::getMime('png');
        }

        if (! is_numeric($size)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Size should be numeric ... setting it to 16');
            $size = 16;
        }

        $cacheId = sha1(self::class . 'getFavicon' . $size . $mime);
        $imageBlob = Tinebase_Core::getCache()->load($cacheId);

        if (! $imageBlob) {
            $config = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON);

            if (!is_array($config)) {
                $config = [16 => $config];
            }

            // find nearest icon
            if (array_key_exists($size, $config)) {
                $icon = $config[$size];
            } else {
                foreach ($config as $s => $i) {
                    if (! is_numeric($s)) continue;
                    $diffs[$s] = abs($size - $s);
                }
                $nearest = array_search(min($diffs), $diffs);

                $icon = $config[$nearest];
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Using favicon ' . $icon);

            $blob = Tinebase_Helper::getFileOrUriContents($icon);
            $image = Tinebase_Model_Image::getImageFromBlob($blob);
            Tinebase_ImageHelper::resize($image, $size, $size, Tinebase_ImageHelper::RATIOMODE_PRESERVNOFILL);
            $imageBlob = $image->getBlob($mime);
            Tinebase_Core::getCache()->save($imageBlob, $cacheId);
        }

        $response = new \Laminas\Diactoros\Response();
        $response->getBody()->write($imageBlob);

        return $response
            ->withAddedHeader('Content-Type', $mime);
    }

    /**
     * @param string $type
     * @param string $size
     * @return \Laminas\Diactoros\Response|\Psr\Http\Message\MessageInterface
     * @throws FileNotFoundException
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Cache_Exception
     */
    public function getLogo($type = 'b', $size = '135x50')
    {
        $mime = 'image/png';

        if (! in_array($type, ['b', 'i'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Unknown type: ' . $type
                . ' Using default "b"');
            $type = 'b';
        }

        // TODO why do we have this param if only one size is supported? :)
        if (! in_array($size, ['135x50'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Unknown size: ' . $type
                    . ' Using default "135x50"');
            $size = '135x50';
        }

        $cacheId = sha1(self::class . 'getLogo' . $type . $size . $mime);
        $imageBlob = Tinebase_Core::getCache()->load($cacheId);
        
        if (!$imageBlob) {
            preg_match('/^(\d+)x(\d+)$/', $size, $matches);

            $path = $type === 'i' ?
                Tinebase_Core::getInstallLogo() :
                Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_LOGO);
            
            $blob = Tinebase_Helper::getFileOrUriContents($path);
            $image = Tinebase_Model_Image::getImageFromBlob($blob);
            Tinebase_ImageHelper::resize($image, $matches[1], $matches[2], Tinebase_ImageHelper::RATIOMODE_PRESERVNOFILL);
            $imageBlob = $image->getBlob($mime);
            Tinebase_Core::getCache()->save($imageBlob, $cacheId);
        }

        $response = new \Laminas\Diactoros\Response();
        $response->getBody()->write($imageBlob);
        
        return $response
            ->withAddedHeader('Content-Type', $mime);
    }
    
    /**
     * @return bool
     */
    public function actionQueueConsistencyCheck()
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::ACTIONQUEUE}->{Tinebase_Config::ACTIONQUEUE_ACTIVE} &&
            Tinebase_ActionQueue::getInstance()->hasAsyncBackend()) {

            if (null === ($queueState = json_decode(Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                    Tinebase_Application::STATE_ACTION_QUEUE_STATE), true))) {
                $queueState = [
                    'lastFullCheck' => 0,
                    'lastSizeOver10k' => false,
                    'actionQueueMissingQueueKeys' => [],
                    'actionQueueMissingDaemonKeys' => [],
                    'lastLRSizeOver10k' => false,
                    'actionQueueLRMissingQueueKeys' => [],
                    'actionQueueLRMissingDaemonKeys' => [],
                ];
            }

            $time = [
                'actionQueue' => ['dataWarn' => 15, 'dataErr' => 60],
                'actionQueueLR' => ['dataWarn' => 60, 'dataErr' => 5 * 60],
            ];

            foreach ([
                        'actionQueue' => Tinebase_ActionQueue::getInstance(),
                        'actionQueueLR' => Tinebase_ActionQueue::getInstance(Tinebase_ActionQueue::QUEUE_LONG_RUN)
                     ] as $qName => $actionQueue) {

                $missingQueueKeys = [];
                $missingDaemonKeys = [];
                $warn = null;
                $err = null;
                // go through queue and daemon struct and check timestamps in data
                // remember stuff we did not find
                foreach ($actionQueue->getQueueKeys() as $key) {
                    if (empty($data = $actionQueue->getData($key))) {
                        if (isset($queueState[$qName . 'MissingQueueKeys'][$key])) {
                            if (null === $warn) {
                                $warn = $qName . ' contains keys which are not present in data';
                            }
                        } else {
                            $missingQueueKeys[$key] = true;
                        }
                    } else {
                        if (($timediff = time() - $data['time']) > $time[$qName]['dataWarn'] * 60) {
                            if (null === $warn) {
                                $warn = $qName . ' data contains data older than ' . $time[$qName]['dataWarn'] . ' minutes';
                            }
                        }
                        if ($timediff > $time[$qName]['dataErr'] * 60) {
                            $err = $qName . ' data contains data older than ' . $time[$qName]['dataErr'] . ' minutes';
                        }
                    }
                }
                $queueState[$qName . 'MissingQueueKeys'] = $missingQueueKeys;

                foreach ($actionQueue->getDaemonStructKeys() as $key) {
                    if (empty($data = $actionQueue->getData($key))) {
                        if (isset($queueState[$qName . 'MissingDaemonKeys'][$key])) {
                            if (null === $warn) {
                                $warn = $qName . ' daemon contains keys which are not present in data';
                            }
                        } else {
                            $missingDaemonKeys[$key] = true;
                        }
                    } else {
                        if (($timediff = time() - $data['time']) > $time[$qName]['dataWarn'] * 60) {
                            if (null === $warn) {
                                $warn = $qName . ' data contains data older than ' . $time[$qName]['dataWarn'] . ' minutes';
                            }
                        }
                        if ($timediff > $time[$qName]['dataErr'] * 60) {
                            $err = $qName . ' data contains data older than ' . $time[$qName]['dataErr'] . ' minutes';
                        }
                    }
                }
                $queueState[$qName . 'MissingDaemonKeys'] = $missingDaemonKeys;

                // go through data keys and check timestmaps
                while (false !== ($data = $actionQueue->iterateAllData())) {
                    foreach ($data as $jobId) {
                        if (!empty($job = $actionQueue->getData($jobId))) {
                            if (($timediff = time() - $job['time']) > $time[$qName]['dataWarn'] * 60) {
                                if (null === $warn) {
                                    $warn = $qName . ' data contains data older than ' . $time[$qName]['dataWarn'] . ' minutes';
                                }
                            }
                            if ($timediff > $time[$qName]['dataErr'] * 60) {
                                $err = $qName . ' data contains data older than ' . $time[$qName]['dataErr'] . ' minutes';
                            }
                        }
                    }
                }

                if (null !== $err) {
                    $e = new Tinebase_Exception($err);
                    Tinebase_Exception::log($e);
                } elseif (null !== $warn) {
                    ($e = new Tinebase_Exception($warn))->setLogLevelMethod('warn');
                    Tinebase_Exception::log($e);
                }
            }

            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                Tinebase_Application::STATE_ACTION_QUEUE_STATE, json_encode($queueState));
        }
        return true;
    }

    /**
     * @return bool
     */
    public function actionQueueActiveMonitoring()
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::ACTIONQUEUE}->{Tinebase_Config::ACTIONQUEUE_ACTIVE} &&
                Tinebase_ActionQueue::getInstance()->hasAsyncBackend()) {
            Tinebase_ActionQueue::getInstance()->executeAction([
                'action'    => 'Tinebase.measureActionQueue',
                'params'    => [microtime(true)]
            ]);
            Tinebase_ActionQueue::getInstance(Tinebase_ActionQueue::QUEUE_LONG_RUN)->executeAction([
                'action'    => 'Tinebase.measureActionQueueLongRun',
                'params'    => [microtime(true)]
            ]);
        }
        return true;
    }

    /**
     * @param float $start
     */
    public function measureActionQueue($start)
    {
        $end = microtime(true);
        $duration = $end - $start;
        $now = time();
        $lastUpdate = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE);
        if ($now - intval($lastUpdate) > 58) {
            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION, sprintf('%.3f', $duration));
            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE, $now);
        }
    }

    /**
     * @param float $start
     */
    public function measureActionQueueLongRun($start)
    {
        $end = microtime(true);
        $duration = $end - $start;
        $now = time();
        $lastUpdate = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
            Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_DURATION_UPDATE);
        if ($now - intval($lastUpdate) > 58) {
            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_DURATION, sprintf('%.3f', $duration));
            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_DURATION_UPDATE, $now);
        }
    }

    public function forceResync($contentClasses = [], $userIds = [], $apis = [])
    {
        $allowedContentClasses = [
            self::SYNC_CLASS_CONTACTS,
            self::SYNC_CLASS_EMAIL,
            self::SYNC_CLASS_EVENTS,
            self::SYNC_CLASS_TASKS,
        ];
        $allowedApis = [
            self::SYNC_API_ACTIVESYNC,
            self::SYNC_API_DAV,
        ];

        if (empty($apis)) {
            $apis = $allowedApis;
        } else {
            $apis = array_intersect($allowedApis, $apis);
        }

        if (empty($contentClasses)) {
            $contentClasses = $allowedContentClasses;
        } else {
            $contentClasses = array_intersect($allowedContentClasses, $contentClasses);
        }

        // resolve account login names to user ids
        if (!empty($userIds)) {
            $newUserIds = [];
            foreach ($userIds as $userId) {
                try {
                    $user = Tinebase_User::getInstance()->getFullUserById($userId);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountLoginName', $userId);
                }
                $newUserIds[] = $user->getId();
            }
            $userIds = $newUserIds;
        }

        foreach ($apis as $api) {
            $this->{'_forceResync' . $api}($contentClasses, $userIds);
        }
    }

    protected function _forceResyncActiveSync($contentClasses, $userIds)
    {
        $allowedContentClasses = [
            self::SYNC_CLASS_CONTACTS  => Syncroton_Data_Factory::CLASS_CONTACTS,
            self::SYNC_CLASS_EVENTS    => Syncroton_Data_Factory::CLASS_CALENDAR,
            self::SYNC_CLASS_TASKS     => Syncroton_Data_Factory::CLASS_TASKS,
            self::SYNC_CLASS_EMAIL     => Syncroton_Data_Factory::CLASS_EMAIL,
        ];
        if (empty($contentClasses)) {
            $classes = $allowedContentClasses;
        } else {
            $classes = [];
            foreach ($contentClasses as $cc) {
                if (isset($allowedContentClasses[$cc])) {
                    $classes[] = $cc;
                }
            }
            if (empty($classes)) {
                return;
            }
        }
        if (empty($userIds)) {
            $userIds = Tinebase_User::getInstance()->getUsers();
        }
        $activeSync = ActiveSync_Controller::getInstance();
        foreach ($userIds as $userId) {
            foreach ($classes as $cc) {
                $activeSync->resetSyncForUser($userId, $cc);
            }
        }
    }

    protected function _forceResyncDAV($contentClasses, $userIds)
    {
        foreach ($contentClasses as $cc) {
            switch ($cc) {
                case self::SYNC_CLASS_CONTACTS:
                    $filter = [
                        ['field' => 'application_id', 'operator' => 'equals', 'value' =>
                            Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId()],
                        ['field' => 'model', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::class]
                    ];
                    break;
                case self::SYNC_CLASS_EVENTS:
                    $filter = [
                        ['field' => 'application_id', 'operator' => 'equals', 'value' =>
                            Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()],
                        ['field' => 'model', 'operator' => 'equals', 'value' => Calendar_Model_Event::class]
                    ];
                    break;
                case self::SYNC_CLASS_TASKS:
                    $filter = [
                        ['field' => 'application_id', 'operator' => 'equals', 'value' =>
                            Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId()],
                        ['field' => 'model', 'operator' => 'equals', 'value' => Tasks_Model_Task::class]
                    ];
                    break;
                default:
                    continue 2;
            }
            if (!empty($userIds)) {
                $filter[] = ['field' => 'owner_id', 'operator' => 'in', 'value' => $userIds];
            }

            Tinebase_Container::getInstance()->forceSyncTokenResync(new Tinebase_Model_ContainerFilter($filter));
        }
    }

    public function publicApiMSAutodiscoverXml()
    {
        $tinebaseConfig = Tinebase_Config::getInstance();
        if (!$tinebaseConfig->featureEnabled(Tinebase_Config::FEATURE_AUTODISCOVER)) {
            throw new Tinebase_Exception_AccessDenied('this feature is not activated');
        }

        /** @var \Laminas\Diactoros\Request $request */
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $body = Tinebase_Helper::mbConvertTo((string)$request->getBody());
        if (Tinebase_Core::isLogLevel(Tinebase_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' request body: ' . $body);
        $reqXml = simplexml_load_string($body);
        $view = new Zend_View();
        $view->setScriptPath(__DIR__ . '/views/autodiscover');
        $response = new \Laminas\Diactoros\Response();
        $response = $response->withHeader('Content-Type', 'text/xml');

        if (!$reqXml || empty($reqXml->Request) || empty($reqXml->Request->AcceptableResponseSchema)) {
            $response->getBody()->write($view->render('error.php'));

        } elseif (strpos($reqXml->Request->AcceptableResponseSchema, 'mobilesync') ||
                strpos($reqXml->Request->AcceptableResponseSchema, 'outlook')) {

            $view->schema = $reqXml->Request->AcceptableResponseSchema;
            if (!empty($reqXml->Request->EMailAddress)) {
                $view->email = $reqXml->Request->EMailAddress;
            }
            $view->url = Tinebase_Core::getUrl() . '/Microsoft-Server-ActiveSync';
            $view->serverName = $tinebaseConfig->{Tinebase_Config::BRANDING_TITLE};
            $view->account = '';

            if (strpos($reqXml->Request->AcceptableResponseSchema, 'outlook') && $tinebaseConfig
                    ->featureEnabled(Tinebase_Config::FEATURE_AUTODISCOVER_MAILCONFIG)) {
                $protocols = [];
                $imapConfig = $tinebaseConfig->{Tinebase_Config::IMAP};
                // TODO: make host configurable independently, as 'localhost' wont help external clients
                if ($imapConfig && $imapConfig->host) {
                    $protocols['IMAP']['Server'] = $imapConfig->host;
                    $protocols['IMAP']['Port'] = $imapConfig->port;
                    $protocols['IMAP']['SSL'] = $imapConfig->ssl ? 'on' : 'off';
                    $protocols['IMAP']['SPA'] = 'off';
                    $protocols['IMAP']['AuthRequired'] = 'on';
                }
                $smtpConfig = $tinebaseConfig->{Tinebase_Config::SMTP};
                if ($smtpConfig && $smtpConfig->host) {
                    $protocols['SMTP']['Server'] = $smtpConfig->host;
                    $protocols['SMTP']['Port'] = $smtpConfig->port;
                    $protocols['SMTP']['SSL'] = $smtpConfig->ssl ? 'on' : 'off';
                    $protocols['SMTP']['SPA'] = 'off';
                    $protocols['SMTP']['AuthRequired'] = 'on';
                }
                if (!empty($protocols)) {
                    $subView = new Zend_View();
                    $subView->setScriptPath(__DIR__ . '/views/autodiscover');
                    $subView->protocols = $protocols;
                    $view->account = $subView->render('outlook.php');
                }
            }

            $response->getBody()->write($view->render('mobilesync.php'));

        } else {
            $response->getBody()->write($view->render('error.php'));
        }

        if (Tinebase_Core::isLogLevel(Tinebase_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' response body: ' .
                (string)$response->getBody());

        return $response;
    }

    /**
     * enable Maintenance Mode
     */
    public function goIntoMaintenanceMode()
    {
        parent::goIntoMaintenanceMode();
        Tinebase_Session::deleteSessions();
    }

    /**
     * get core data for this application
     *
     * @return Tinebase_Record_RecordSet
     */
    public function getCoreDataForApplication()
    {
        $result = parent::getCoreDataForApplication();

        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        if (Tinebase_Config::getInstance()->featureEnabled(
            Tinebase_Config::FEATURE_COMMUNITY_IDENT_NR)
        ) {
            $result->addRecord(new CoreData_Model_CoreData(array(
                'id' => 'cs_community_identification_number',
                'application_id' => $application,
                'model' => 'Tinebase_Model_MunicipalityKey',
                'label' => 'Municipality Key' // _('Municipality Key')
            )));
        }

        $result->addRecord(new CoreData_Model_CoreData(array(
            'id' => Tinebase_Model_CostCenter::class,
            'application_id' => $application,
            'model' => Tinebase_Model_CostCenter::class,
//            'label' => 'Cost Center' // _('Cost Center')
        )));

        $result->addRecord(new CoreData_Model_CoreData(array(
            'id' => Tinebase_Model_CostUnit::class,
            'application_id' => $application,
            'model' => Tinebase_Model_CostUnit::class,
//            'label' => 'Cost Center' // _('Cost Center')
        )));

        $result->addRecord(new CoreData_Model_CoreData(array(
            'id' => Tinebase_Model_BankHolidayCalendar::class,
            'application_id' => $application,
            'model' => Tinebase_Model_BankHolidayCalendar::class,
//            'label' => 'Bank Holiday Calendar' // _('Bank Holiday Calendar')
        )));

        return $result;
    }
}
