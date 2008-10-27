<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * php helpers
 */
require_once 'Helper.php';

/**
 * the class provides functions to handle applications
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Controller
{
    /**
     * holdes the instance of the singleton
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
     * create new user seesion
     *
     * @param string $_username
     * @param string $_password
     * @param string $_ipAddress
     * @return bool
     */
    public function login($_username, $_password, $_ipAddress)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Tinebase_User::getInstance();
            try {
                $account = $accountsController->getFullUserByLoginName($authResult->getIdentity());
            } catch (Exception $e) {
                Zend_Session::destroy();
                
                throw new Exception('account ' . $authResult->getIdentity() . ' not found in account storage');
            }
            
            Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
            Zend_Session::registerValidator(new Zend_Session_Validator_IpAddress());
            
            Tinebase_Core::set(Tinebase_Core::USER, $account);
            Tinebase_Core::getSession()->currentAccount = $account;
            
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
     * @param string $_newPassword1
     * @param string $_newPassword2
     */
    public function changePassword($_oldPassword, $_newPassword1, $_newPassword2)
    {
        //error_log(print_r(Tinebase_Core::getUser()->toArray(), true));
        
        // check config setting
        if (!isset(Zend_Registry::get('configFile')->accounts) 
            || !isset(Zend_Registry::get('configFile')->accounts->changepw)
            || !Zend_Registry::get('configFile')->accounts->changepw) {
            throw new Exception('Password change not allowed.');                
        }
        
        $loginName = Tinebase_Core::getUser()->accountLoginName;
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " change password for $loginName");
        
        if (!Tinebase_Auth::getInstance()->isValidPassword($loginName, $_oldPassword)) {
            throw new Exception('Old password is wrong.');
        }
        
        Tinebase_Auth::getInstance()->setPassword($loginName, $_newPassword1, $_newPassword2);
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
     * @param  string $_application application which manages the image
     * @param  string $_identifier identifier of image/record
     * @param  string $_location optional additional identifier
     * @return Tinebase_Model_Image
     */
    public function getImage($_application, $_identifier, $_location='')
    {
        $appController = $this->getApplicationInstance($_application);
        if (!method_exists($appController, 'getImage')) {
            throw new Exception("$_application has no getImage function");
        }
        $image = $appController->getImage($_identifier, $_location);
        
        if (!$image instanceof Tinebase_Model_Image) {
            throw new Exception("$_application returned invalid image");
        }
        return $image;
    }
}