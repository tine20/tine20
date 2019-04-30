<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
        // make sure pw is always replaced in Logger
        Tinebase_Core::getLogger()->addReplacement($password);

        if (Tinebase_Core::inMaintenanceModeAll()) {
            throw new Tinebase_Exception_MaintenanceMode();
        }

        $authResult = Tinebase_Auth::getInstance()->authenticate($loginName, $password);

        $accessLog = Tinebase_AccessLog::getInstance()->getAccessLogEntry($loginName, $authResult, $request,
            $clientIdString);

        $user = $this->_validateAuthResult($authResult, $accessLog);

        if (!($user instanceof Tinebase_Model_FullUser)) {
            return false;
        }

        $this->_loginUser($user, $accessLog, $password);

        if (Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_NTLMV2_HASH_UPDATE_ON_LOGIN}) {
            $userController = Tinebase_User::getInstance();
            if ($userController instanceof Tinebase_User_Sql) {
                $userController->updateNtlmV2Hash($user->getId(), $password);
            }
        }

        return true;
    }

    /**
     * @param Tinebase_Model_FullUser $user
     * @param \Zend\Http\Request $request
     * @param string|null $clientIdString
     * @throws Tinebase_Exception_MaintenanceMode
     */
    public function loginUser(Tinebase_Model_FullUser $user, \Zend\Http\PhpEnvironment\Request $request, $clientIdString = null)
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
        Tinebase_Core::set(Tinebase_Core::USER, $_user);
        
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
        // FIXME 0010508: Session_Validator_AccountStatus causes problems
        //Tinebase_Session::registerValidatorAccountStatus();

        Tinebase_Session::registerValidatorMaintenanceMode();
        
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::SESSIONUSERAGENTVALIDATION, TRUE)) {
            Tinebase_Session::registerValidatorHttpUserAgent();
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' User agent validation disabled.');
        }
        
        // we only need to activate ip session validation for non-encrypted connections
        $ipSessionValidationDefault = Tinebase_Core::isHttpsRequest() ? FALSE : TRUE;
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::SESSIONIPVALIDATION, $ipSessionValidationDefault)) {
            Tinebase_Session::registerValidatorIpAddress();
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Session ip validation disabled.');
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
        
        Tinebase_Session::getSessionNamespace()->currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * login failed
     * 
     * @param  string                    $loginName
     * @param  Tinebase_Model_AccessLog  $accessLog
     */
    protected function _loginFailed($authResult, Tinebase_Model_AccessLog $accessLog)
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
     * renders and send to browser one captcha image
     *
     * @return array
     */
    public function makeCaptcha()
    {
        return $this->_makeImage();
    }

    /**
     * renders and send to browser one captcha image
     *
     * @return array
     */
    protected function _makeImage()
    {
        $result = array();
        $width='170';
        $height='40';
        $characters= mt_rand(5,7);
        $possible = '123456789aAbBcCdDeEfFgGhHIijJKLmMnNpPqQrRstTuUvVwWxXyYZz';
        $code = '';
        $i = 0;
        while ($i < $characters) {
            $code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
            $i++;
        }
        $font = './fonts/Milonga-Regular.ttf';
        /* font size will be 70% of the image height */
        $font_size = $height * 0.67;
        try {
            $image = @imagecreate($width, $height);
            /* set the colours */
            $text_color = imagecolorallocate($image, 20, 40, 100);
            $noise_color = imagecolorallocate($image, 100, 120, 180);
            /* generate random dots in background */
            for( $i=0; $i<($width*$height)/3; $i++ ) {
                imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
            }
            /* generate random lines in background */
            for( $i=0; $i<($width*$height)/150; $i++ ) {
                imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
            }
            /* create textbox and add text */
            $textbox = imagettfbbox($font_size, 0, $font, $code);
            $x = ($width - $textbox[4])/2;
            $y = ($height - $textbox[5])/2;
            imagettftext($image, $font_size, 0, $x, $y, $text_color, $font , $code);
            ob_start();
            imagejpeg($image);
            $image_code = ob_get_contents ();
            ob_end_clean();
            imagedestroy($image);
            $result = array();
            $result['1'] = base64_encode($image_code);
            Tinebase_Session::getSessionNamespace()->captcha['code'] = $code;
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }
        return $result;
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
            Tinebase_User::getInstance()->setPassword($user, $_newPassword, true, false);
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
            Tinebase_Session::getSessionNamespace()->userAccountChanged = true;
            Tinebase_Session::getSessionNamespace()->originalAccountName = $currentAccountName;
            
        } else if (Tinebase_Session::getSessionNamespace()->userAccountChanged 
            && isset($allowedRoleChangesArray[Tinebase_Session::getSessionNamespace()->originalAccountName])
        ) {
            $user = Tinebase_User::getInstance()->getFullUserByLoginName(Tinebase_Session::getSessionNamespace()->originalAccountName);
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

        if (! $this->_validateSecondFactor($accessLog, $user)) {
            $authResult = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                $user->accountLoginName,
                array('Second factor authentication failed.')
            );
            $accessLog->result = Tinebase_Auth::FAILURE;
            $this->_loginFailed($authResult, $accessLog);
            return false;
        }

        return $user;
    }

    /**
     * @param Tinebase_Model_AccessLog $accessLog
     * @param Tinebase_Model_FullUser $user
     * @return bool
     */
    protected function _validateSecondFactor(Tinebase_Model_AccessLog $accessLog, Tinebase_Model_FullUser $user)
    {
        if (! Tinebase_AreaLock::getInstance()->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN)
            || $accessLog->clienttype !== 'JSON-RPC'
        ) {
            // no login lock or non json access
            return true;
        }

        $context = $this->getRequestContext();
        $password = $context['otp'];
        try {
            Tinebase_AreaLock::getInstance()->unlock(
                Tinebase_Model_AreaLockConfig::AREA_LOGIN,
                $password,
                $user->accountLoginName
            );
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            return false;
        }

        return true;
    }

    /**
     * returns true if user account has been changed
     * 
     * @return boolean
     */
    public function userAccountChanged()
    {
        try {
            $session = Tinebase_Session::getSessionNamespace();
        } catch (Zend_Session_Exception $zse) {
            $session = null;
        }
        
        return ($session instanceof Zend_Session_Namespace && isset($session->userAccountChanged)) 
                ? $session->userAccountChanged
                : false;
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
        foreach($applications as $application) {
            try {
                $app = Tinebase_Core::getApplicationInstance($application, '', true);
            } catch (Tinebase_Exception_NotFound $tenf) {
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

    public static function addFastRoutes(
        /** @noinspection PhpUnusedParameterInspection */
        \FastRoute\RouteCollector $r
    ) {

        $r->addGroup('', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/favicon[/{size}[/{ext}]]', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Controller::class, 'getFavicon', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });

        $r->addGroup('/Tinebase', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/_status[/{apiKey}]', (new Tinebase_Expressive_RouteHandler(
                Tinebase_Controller::class, 'getStatus', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });

        $r->addGroup('/autodiscover', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->post('/autodiscover.xml', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicApiMSAutodiscoverXml', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });

        $r->addGroup('/Autodiscover', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->post('/Autodiscover.xml', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicApiMSAutodiscoverXml', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });
    }

    /**
     * @return \Zend\Diactoros\Response
     * @throws Tinebase_Exception_AccessDenied
     */
    public function getStatus($apiKey = null)
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::STATUS_INFO) || ! Tinebase_Config::getInstance()->get(Tinebase_Config::STATUS_API_KEY)) {
            return new \Zend\Diactoros\Response\EmptyResponse();
        }
        
        if ($apiKey !== Tinebase_Config::getInstance()->get(Tinebase_Config::STATUS_API_KEY, false)) {
            throw new Tinebase_Exception_AccessDenied('Not authorized. Invalid API Key.');
        }

        // @todo fetch more status info

        $data = [
            'actionqueue' => Tinebase_ActionQueue::getStatus(),
        ];
        $response = new \Zend\Diactoros\Response\JsonResponse($data);
        return $response;
    }

    /**
     * @param int|string $size
     * @param string $ext
     * @return \Zend\Diactoros\Response
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Cache_Exception
     */
    public function getFavicon($size = 16, $ext = 'png')
    {
        if ($size == 'svg' || $ext == 'svg') {
            $config = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON_SVG);

            $response = new \Zend\Diactoros\Response();
            $response->getBody()->write(Tinebase_Helper::getFileOrUriContents($config));

            return $response
                ->withAddedHeader('Content-Type', 'image/svg+xml');
        }
        $mime = Tinebase_ImageHelper::getMime($ext);
        if (! in_array($mime, Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
            throw new Tinebase_Exception_UnexpectedValue('image format not supported');
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
                foreach($config as $s => $i) {
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

        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write($imageBlob);

        return $response
            ->withAddedHeader('Content-Type', $mime);
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

        /** @var \Zend\Diactoros\Request $request */
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $body = (string)$request->getBody();
        if (Tinebase_Core::isLogLevel(Tinebase_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' request body: ' . $body);
        $reqXml = simplexml_load_string($body);
        $view = new Zend_View();
        $view->setScriptPath(__DIR__ . '/views/autodiscover');
        $response = new \Zend\Diactoros\Response();
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
}
