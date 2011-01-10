<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
     * @throws  Tinebase_Exception_NotFound
     * 
     * @todo split this function in smaller parts!
     */
    public function login($_loginname, $_password, $_ipAddress, $_clientIdString = NULL)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_loginname, $_password);
        
        $accountsController = Tinebase_User::getInstance();
        $groupsController   = Tinebase_Group::getInstance();
        
        $accessLog = new Tinebase_Model_AccessLog(array(
            'sessionid'     => session_id(),
            'ip'            => $_ipAddress,
            'li'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'result'        => $authResult->getCode(),
            'clienttype'    => $_clientIdString,   
        ), TRUE);

        // does the user exist in the user database?
        if ($accessLog->result == Tinebase_Auth::SUCCESS) {
            $accountName = $authResult->getIdentity();
            
            try {
                if ($accountsController instanceof Tinebase_User_Interface_SyncAble) {
                    Tinebase_User::syncUser($accountName);
                }
                $user = $accountsController->getFullUserByLoginName($accountName);
            } catch (Tinebase_Exception_NotFound $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . 'Account ' . $accountName . ' not found in account storage.');
                $accessLog->result = Tinebase_Auth::FAILURE_IDENTITY_NOT_FOUND;
            }
        }
        
        // is the user enabled?
        if ($accessLog->result == Tinebase_Auth::SUCCESS && $user->accountStatus !== Tinebase_User::STATUS_ENABLED) {
            // is the account enabled?
            if($user->accountStatus == Tinebase_User::STATUS_DISABLED) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $accountName . ' is disabled');
                $accessLog->result = Tinebase_Auth::FAILURE_DISABLED;
            }
                
            // is the account expired?
            elseif($user->accountStatus == Tinebase_User::STATUS_EXPIRED) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $accountName . ' password is expired');
                $accessLog->result = Tinebase_Auth::FAILURE_PASSWORD_EXPIRED;
            }
        
            // to many login failures?
            elseif($user->accountStatus == Tinebase_User::STATUS_BLOCKED) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $accountName . ' is blocked');
                $accessLog->result = Tinebase_Auth::FAILURE_BLOCKED;
            } 
        }
        
        if ($accessLog->result === Tinebase_Auth::SUCCESS && $user->accountStatus === Tinebase_User::STATUS_ENABLED) {
            $this->_initSessionAfterLogin($user);
            
            Tinebase_Core::set(Tinebase_Core::USER, $user);
            
            $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($accountName, $_password);
            Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
            
            // need to set locale again if user preference is available because locale might not be set correctly during loginFromPost
            $userPrefLocaleString = Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE};
            if ($userPrefLocaleString !== 'auto') {
                Tinebase_Core::setupUserLocale($userPrefLocaleString);
            }
            
            $user->setLoginTime($_ipAddress);
            
            $accessLog->sessionid = session_id();
            $accessLog->login_name = $accountName;
            $accessLog->account_id = $user->getId();
            
            $result = true;
            
        } else {
            $accountsController->setLastLoginFailure($_loginname);
            
            $accessLog->login_name = $_loginname;
            $accessLog->lo = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
            
            Zend_Session::destroy();
            
            sleep(mt_rand(2,5));
            
            $result = false;
        }
        
        Tinebase_AccessLog::getInstance()->create($accessLog);
        
        return $result;
    }
    
    /**
     * init session after successful login
     * 
     * @param Tinebase_Model_FullUser $_user
     */
    protected function _initSessionAfterLogin(Tinebase_Model_FullUser $_user)
    {
        if (Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::SESSIONUSERAGENTVALIDATION, NULL, TRUE)->value) {
            Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' User agent validation disabled.');
        }
        
        if (Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::SESSIONIPVALIDATION, NULL, TRUE)->value) {
            Zend_Session::registerValidator(new Zend_Session_Validator_IpAddress());
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Session ip validation disabled.');
        }
        
        Zend_Session::regenerateId();
        
        Tinebase_Core::getSession()->currentAccount = $user;
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
     */
    public function cleanupCache()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Cleaning up the cache.');
        
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_OLD);
    }
}
