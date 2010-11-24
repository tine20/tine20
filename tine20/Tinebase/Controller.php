<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Tinebase_Controller
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Controller
     */
    private static $_instance = NULL;
    
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

        // does the user exist in the user database?
        if ($accessLog->result === Tinebase_Auth::SUCCESS) {
            $accountName = $authResult->getIdentity();
            
            $accountsController = Tinebase_User::getInstance();
            $groupsController   = Tinebase_Group::getInstance();
            
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
        if ($accessLog->result === Tinebase_Auth::SUCCESS) {
            if($user->accountStatus !== 'enabled') {
                // account is expired
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $accountName . ' is not enabled');
                $accessLog->result = Tinebase_Auth::FAILURE_DISABLED;
            }
        }
                
        // is the password expired?
        if ($accessLog->result === Tinebase_Auth::SUCCESS) {
            if(($user->accountExpires instanceof Tinebase_DateTime) && Tinebase_DateTime::now()->isLater($user->accountExpires)) {
                // account is expired
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Account: '. $accountName . ' password is expired');
                $accessLog->result = Tinebase_Auth::FAILURE_PASSWORD_EXPIRED;
            }
        }
        
        if ($accessLog->result === Tinebase_Auth::SUCCESS) {
            Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
            if (Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::SESSIONIPVALIDATION, NULL, TRUE)->value) {
                Zend_Session::registerValidator(new Zend_Session_Validator_IpAddress());
            } else {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Session ip validation disabled.');
            }
            Zend_Session::regenerateId();
            
            Tinebase_Core::set(Tinebase_Core::USER, $user);
            Tinebase_Core::getSession()->currentAccount = $user;
            
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
            if ($accessLog->result == Tinebase_Auth::FAILURE_CREDENTIAL_INVALID) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' Invalid password provided for: ' . $_loginname);
            }
            
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
    public function getImage($_application, $_identifier, $_location='')
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
}
