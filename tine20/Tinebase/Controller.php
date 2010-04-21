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
class Tinebase_Controller implements Tinebase_Event_Interface
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
     * @param   string $_username
     * @param   string $_password
     * @param   string $_ipAddress
     * @return  bool
     * @throws  Tinebase_Exception_NotFound
     */
    public function login($_username, $_password, $_ipAddress)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Tinebase_User::getInstance();
            try {
                $account = $accountsController->getFullUserByLoginName($authResult->getIdentity());
            } catch (Tinebase_Exception_NotFound $e) {
                Zend_Session::destroy();
                throw new Tinebase_Exception_NotFound('Account ' . $authResult->getIdentity() . ' not found in account storage.');
            }
            
            Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
            if (Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::SESSIONIPVALIDATION, NULL, TRUE)->value) {
                Zend_Session::registerValidator(new Zend_Session_Validator_IpAddress());
            } else {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Session ip validation disabled.');
            }
            Zend_Session::regenerateId();
            
            Tinebase_Core::set(Tinebase_Core::USER, $account);
            Tinebase_Core::getSession()->currentAccount = $account;
            
            $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($_username, $_password);
            Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
            
            $account->setLoginTime($_ipAddress);
            
            Tinebase_AccessLog::getInstance()->addLoginEntry(
                session_id(),
                $authResult->getIdentity(),
                $_ipAddress,
                $authResult->getCode(),
                Tinebase_Core::getUser()
            );
            
            return true;
        } else {
            Tinebase_AccessLog::getInstance()->addLoginEntry(
                session_id(),
                $_username,
                $_ipAddress,
                $authResult->getCode()
           );
            
            Tinebase_AccessLog::getInstance()->addLogoutEntry(
                session_id(),
                $_ipAddress
           );
            
            Zend_Session::destroy();
            
            sleep(2);
            
            return false;
        }
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
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " change password for $loginName");
        
        if (!Tinebase_Auth::getInstance()->isValidPassword($loginName, $_oldPassword)) {
            throw new Tinebase_Exception_InvalidArgument('Old password is wrong.');
        }
        
        Tinebase_User::getInstance()->setPassword($loginName, $_newPassword);
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
    
            Tinebase_AccessLog::getInstance()->addLogoutEntry(
                session_id(),
                $_ipAddress,
                $currentAccount->accountId
           );
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

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    public function handleEvents(Tinebase_Event_Abstract $_eventObject)
    {
        $eventName = get_class($_eventObject);
        switch($eventName) {
            case 'Tinebase_Event_Async_Minutely':
                
                // check if already running
                if (! Tinebase_AsyncJob::getInstance()->jobIsRunning($eventName)) {
                    
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No ' . $eventName . ' is running. Starting new one.');
                
                    $job = Tinebase_AsyncJob::getInstance()->startJob($eventName);
                    try {
                        Tinebase_Alarm::getInstance()->sendPendingAlarms();
                        
                        // save new status 'success'
                        $job = Tinebase_AsyncJob::getInstance()->finishJob($job);
                    } catch (Exception $e) {
                        // save new status 'failure'
                        $job = Tinebase_AsyncJob::getInstance()->finishJob($job, Tinebase_Model_AsyncJob::STATUS_FAILURE, $e->getMessage());
                    }
                    
                } else {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Job ' . $eventName . ' is already running. Skipping event.');
                }
                break;
        }
    }
}
