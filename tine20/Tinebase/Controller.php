<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Controller extends Tinebase_Controller_Event
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
    
    protected $_writeAccessLog;
    /**
     * the constructor
     *
     */
    private function __construct() 
    {
        $this->_writeAccessLog = Tinebase_Core::get('serverclassname') !== 'ActiveSync_Server_Http' ||
            !(ActiveSync_Config::getInstance()->get(ActiveSync_Config::DISABLE_ACCESS_LOG));
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
     * @param   Zend_Controller_Request_Abstract $request
     * @param   string                           $clientIdString
     * @param   string                           $securitycode   the security code(captcha)
     * @return  bool
     */
    public function login($loginName, $password, Zend_Controller_Request_Abstract $request, $clientIdString = NULL, $securitycode = NULL)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($loginName, $password);
        
        $accessLog = $this->_getModelAccessLog($loginName, $authResult, $request, $clientIdString);
        
        $user = $this->_validateAuthResult($authResult, $accessLog);
        
        if (!($user instanceof Tinebase_Model_FullUser)) {
            return false;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " Login with username {$accessLog->login_name} from {$accessLog->ip} succeeded.");
        
        $this->_setSessionId($user, $accessLog, $clientIdString);
        
        $this->_initUserAfterLogin($user, $accessLog, $password);
        
        $this->_updateAccessLog($user, $accessLog);
        
        return true;
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
                    Tinebase_User::syncUser($_username,array('syncContactData' => TRUE));
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
     * init user after login (credential cache, access log, session, ...)
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Tinebase_Model_AccessLog $_accessLog
     * @param string $password
     */
    protected function _initUserAfterLogin(Tinebase_Model_FullUser $_user, Tinebase_Model_AccessLog $_accessLog, $_password)
    {
        $this->initUser($_user);
        
        $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($_user->accountLoginName, $_password);
        Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
    }
    
    /**
     * initialize user (session, locale, tz)
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param boolean $fixCookieHeader
     */
    public function initUser(Tinebase_Model_FullUser $_user, $fixCookieHeader = true)
    {
        $this->_initUserSession($_user, $fixCookieHeader);
        
        Tinebase_Core::set(Tinebase_Core::USER, $_user);
        
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
    protected function _initUserSession(Tinebase_Model_FullUser $user, $fixCookieHeader = true)
    {
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::SESSIONUSERAGENTVALIDATION, TRUE)) {
            Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' User agent validation disabled.');
        }
        
        // we only need to activate ip session validation for non-encrypted connections
        $ipSessionValidationDefault = Tinebase_Core::isHttpsRequest() ? FALSE : TRUE;
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::SESSIONIPVALIDATION, $ipSessionValidationDefault)) {
            Zend_Session::registerValidator(new Zend_Session_Validator_IpAddress());
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Session ip validation disabled.');
        }
        
        if (Zend_Session::isStarted()) {
            Zend_Session::regenerateId();
            Tinebase_Core::set(Tinebase_Core::SESSIONID, session_id());
            
            if ($fixCookieHeader) {
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
            
            Tinebase_Core::getSession()->currentAccount = $user;
        }
    }
    
    /**
     * login failed
     * 
     * @param  string                    $loginName
     * @param  Tinebase_Model_AccessLog  $accessLog
     */
    protected function _loginFailed($authResult, Tinebase_Model_AccessLog $accessLog)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
            __METHOD__ . '::' . __LINE__ . " Login with username {$accessLog->login_name} from {$accessLog->ip} failed ({$accessLog->result})!");
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Failure messages: ' . print_r($authResult->getMessages(), TRUE));
        
        // @todo update sql schema to allow empty sessionid column
        $accessLog->sessionid = Tinebase_Record_Abstract::generateUID();
        $accessLog->lo = $accessLog->li;
        
        Tinebase_User::getInstance()->setLastLoginFailure($accessLog->login_name);
        Tinebase_AccessLog::getInstance()->create($accessLog);
        
        sleep(mt_rand(2,5));
    }
    
     /**
     * renders and send to browser one captcha image
     *
     * @return void
     */
    public function makeCaptcha()
    {
        return $this->_makeImage();
    }

    /**
     * renders and send to browser one captcha image
     *
     * @return void
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
            $background_color = imagecolorallocate($image, 255, 255, 255);
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
            $_SESSION['captcha']['code'] = $code;
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
     * @param   array  $remoteInfo
     * @param   string $clientIdString
     * @return  bool
     */
    public function authenticate($loginName, $password, $remoteInfo, $clientIdString = NULL)
    {
        $result = $this->login($loginName, $password, $remoteInfo, $clientIdString);
        
        /**
         * we unset the Zend_Auth session variable. This way we keep the session,
         * but the user is not logged into Tine 2.0
         * we use this to validate passwords for OpenId for example
         */
        unset(Tinebase_Core::getSession()->Zend_Auth);
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
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::PASSWORD_CHANGE, TRUE)) {
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
     * switch to another user's account
     *
     * @param string $loginName
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    public function changeUserAccount($loginName)
    {
        $allowedRoleChanges = Tinebase_Config::getInstance()->get(Tinebase_Config::ROLE_CHANGE_ALLOWED);
        $currentAccountName = Tinebase_Core::getUser()->accountLoginName;
        if ($allowedRoleChanges) {
            $allowedRoleChangesArray = $allowedRoleChanges->toArray();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' ROLE_CHANGE_ALLOWED: ' . print_r($allowedRoleChangesArray, true));
            
            $user = null;
            
            if (isset($allowedRoleChangesArray[$currentAccountName])
                && in_array($loginName, $allowedRoleChangesArray[$currentAccountName])
            ) {
                $user = Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
                Tinebase_Core::getSession()->userAccountChanged = true;
                Tinebase_Core::getSession()->originalAccountName = $currentAccountName;
                
            } else if (Tinebase_Core::getSession()->userAccountChanged 
                && isset($allowedRoleChangesArray[Tinebase_Core::getSession()->originalAccountName])
            ) {
                $user = Tinebase_User::getInstance()->getFullUserByLoginName(Tinebase_Core::getSession()->originalAccountName);
                Tinebase_Core::getSession()->userAccountChanged = false;
                Tinebase_Core::getSession()->originalAccountName = null;
            }
            
            if ($user) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' Switching to user account ' . $user->accountLoginName);
                
                $this->initUser($user, /* $fixCookieHeader = */ false);
                return true;
            }
        } 
        
        throw new Tinebase_Exception_AccessDenied('It is not allowed to switch to this account');
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
                Tinebase_AccessLog::getInstance()->setLogout(Tinebase_Core::get(Tinebase_Core::SESSIONID));
            }
        }
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
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Cleaning up the cache (mode: ' . $_mode . ')');
        
        Tinebase_Core::getCache()->clean($_mode);
    }
    
    /**
     * cleanup old sessions files => needed only for filesystems based sessions
     */
    public function cleanupSessions()
    {
        $config = Tinebase_Core::getConfig();
        
        $backendType = ($config->session && $config->session->backend) ? ucfirst($config->session->backend) : 'File';
        
        if (strtolower($backendType) == 'file') {
            $maxLifeTime = ($config->session && $config->session->lifetime) ? $config->session->lifetime : 86400;
            $path = ini_get('session.save_path');
            
            $unlinked = 0;
            try {
                $dir = new DirectoryIterator($path);
            } catch (UnexpectedValueException $uve) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . " Could not cleanup sessions: " . $e->getMessage());
                return;
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
     * return accessLog instance 
     * 
     * @param string $loginName
     * @param Zend_Auth_Result $authResult
     * @param Zend_Controller_Request_Abstract $request
     * @param string $clientIdString
     * @return Tinebase_Model_AccessLog
     */
    protected function _getModelAccessLog($loginName, Zend_Auth_Result $authResult, Zend_Controller_Request_Abstract $request, $clientIdString)
    {
        $accessLog = new Tinebase_Model_AccessLog(array(
            'ip'         => $request->getClientIp(),
            'li'         => Tinebase_DateTime::now(),
            'result'     => $authResult->getCode(),
            'clienttype' => $clientIdString,
            'login_name' => $loginName ? $loginName : $authResult->getIdentity(),
            'user_agent' => substr($request->getHeader('USER_AGENT'), 0, 255)
        ), TRUE);
        
        return $accessLog;
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
     * set session for current request
     * 
     * @param Tinebase_Model_FullUser $user
     * @param Tinebase_Model_AccessLog $accessLog
     */
    protected function _setSessionId(Tinebase_Model_FullUser $user, Tinebase_Model_AccessLog $accessLog)
    {
        if (in_array($accessLog->clienttype, array(Tinebase_Server_WebDAV::REQUEST_TYPE, ActiveSync_Server_Http::REQUEST_TYPE))) {
            try {
                $accessLog = Tinebase_AccessLog::getInstance()->getPreviousAccessLog($accessLog);
                
                Tinebase_Core::set(Tinebase_Core::SESSIONID, $accessLog->sessionid);
            } catch (Tinebase_Exception_NotFound $tenf) {
                Tinebase_Core::set(Tinebase_Core::SESSIONID, Tinebase_Record_Abstract::generateUID());
            }
            
        } else {
            Tinebase_Core::set(Tinebase_Core::SESSIONID, Zend_Session::isStarted() ? session_id() : Tinebase_Record_Abstract::generateUID());
        }
        
        $accessLog->sessionid = Tinebase_Core::get(Tinebase_Core::SESSIONID);
    }
    
    /**
     * update access log entry if needed
     * 
     * @param Tinebase_Model_FullUser $user
     * @param Tinebase_Model_AccessLog $accessLog
     */
    protected function _updateAccessLog(Tinebase_Model_FullUser $user, Tinebase_Model_AccessLog $accessLog)
    {
        if (!$accessLog->getId()) {
            $user->setLoginTime($accessLog->ip);
            if ($this->_writeAccessLog) {
                $accessLog = Tinebase_AccessLog::getInstance()->create($accessLog);
            }
        }
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
            $this->_loginFailed($authResult, $accessLog);
            
            return false;
        }
        
        // check if user is expired or blocked
        $this->_checkUserStatus($user, $accessLog);
        
        if ($accessLog->result !== Tinebase_Auth::SUCCESS) {
            $this->_loginFailed($authResult, $accessLog);
            
            return false;
        }
        
        return $user;
    }
    
    /**
     * returns true if user account has been changed
     * 
     * @return boolean
     */
    public function userAccountChanged()
    {
        return (is_object(Tinebase_Core::getSession()) && isset(Tinebase_Core::getSession()->userAccountChanged)) 
                ? Tinebase_Core::getSession()->userAccountChanged
                : false;
    }
}
