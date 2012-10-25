<?php
// /**
//  * Tine 2.0
//  *
//  * @package     Sipgate
//  * @license     http://www.gnu.org/licenses/agpl.html AGPL3
//  * @author      Alexander Stintzing <alex@stintzing.net>
//  * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
//  *
//  */

// /**
//  * controller class for the Sipgate application
//  * @package     Sipgate
//  */
// class Sipgate_Controller extends Tinebase_Controller_Abstract
// {
//     /**
// <<<<<<< .merge_file_77tLhk
//      * @see Tinebase_Controller_Abstract
//      * @var unknown_type
//      */
//     protected $_applicationName = 'Sipgate';
    
//     protected $_credentialKey = 'NULL';
// =======
//      * call backend type
//      *
//      * @var string
//      */
//     protected $_callBackendType = NULL;

//     /**
//      * Application name
//      * @var string
//      */
//     protected $applicationName = NULL;

//     /**
//      * Holds the Preferences
//      * @var Sipgate_Preference
//      */
//     protected $_pref = NULL;

//     /**
//      * the constructor
//      *
//      * don't use the constructor. use the singleton
//      */
//     private function __construct() {
//         $this->_applicationName = 'Sipgate';
//         $this->_pref = new Sipgate_Preference();
//     }

//     /**
//      * don't clone. Use the singleton.
//      *
//      */
//     private function __clone() {
//     }

// >>>>>>> .merge_file_Tbopjl
//     /**
//      * holds the instance of the singleton
//      *
//      * @var Sipgate_Controller
//      */
//     private static $_instance = NULL;
// <<<<<<< .merge_file_77tLhk
// =======

// >>>>>>> .merge_file_Tbopjl
//     /**
//      * the singleton pattern
//      *
//      * @return Sipgate_Controller
//      */
//     public static function getInstance()
//     {
//         if (self::$_instance === NULL) {
//             self::$_instance = new Sipgate_Controller();
//         }

//         return self::$_instance;
//     }
// <<<<<<< .merge_file_77tLhk
    
//     /**
//      * returns the globalcredentials for this app
//      * @return array
//      */
//     public function getAccount()
//     {
//         if(!Tinebase_Core::getUser()->hasRight('Sipgate', 'admin')) {
//             throw new Tinebase_Exception_AccessDenied(_('You do not have admin rights on Sales'));
//         }
        
//         return new Sipgate_Model_Account_Shared();
        
// //         $cfg = Tinebase_Account::getInstance()->getAccount('Sipgate', Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(), false);

// //         die(var_dump($credentials));
// //         return array();
//     }

//     /**
//      * save Sales settings
//      *
//      * @param string autogenerate credentials
//      * @return Sales_Model_Account
//      *
//      * @todo generalize this
//      */
//     public function setAccount($_credentials)
//     {
//         if(!Tinebase_Core::getUser()->hasRight('Sipgate', 'admin')) {
//             throw new Tinebase_Exception_AccessDenied(_('You do not have admin rights on Sipgate'));
//         }
//         $c = new Sipgate_Model_Account_Shared($_credentials);
//         return $c;
        
// //         Tinebase_Config::getInstance()->setConfigForApplication($_name, $_value, $this->_applicationName);
// //         return Sipgate_Backend_Config::getInstance()->setConfig($config);
//     }
//     /**
//      * call backend type
//      *
//      * @var string
//      */
//     protected $_callBackendType = NULL;

//     /**
//      * Application name
//      * @var string
//      */
//     protected $_applicationName = 'Sipgate';

//     /**
//      * Holds the Preferences
//      * @var Sipgate_Preference
//      */
//     protected $_pref = NULL;

//     /**
//      * the constructor
//      *
//      * don't use the constructor. use the singleton
//      */
//     private function __construct() {
// //         $this->_pref = new Sipgate_Preference();
//     }

//     /**
//      * don't clone. Use the singleton.
//      *
//      */
//     private function __clone() {
//     }

//     /**
//      * holds the instance of the singleton
//      *
//      * @var Sipgate_Controller
//      */
//     private static $_instance = NULL;

    

//     /**
//      * dial number
//      *
//      * @param   int $_callee
//      * @return  mixed
//      */
//     public function dialNumber($_callee)
//     {
//         $caller = $this->_pref->{'phoneId'};

//         $backend = Sipgate_Backend_Api::getInstance();

//         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
//             . ' Dialing number ' . $_callee . ' with phone id ' . $caller);

//         return $backend->dialNumber($caller, $_callee);
//     }

//     /**
//      * gets the Session Status by an Id
//      * @param string $sessionId
//      */
//     public function getSessionStatus($sessionId) {
//         if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in Controller submitted!');
//         $backend = Sipgate_Backend_Api::getInstance();
//         return $backend->getSessionStatus($sessionId);
//     }

//     /**
//      * Closes the Session by an Id
//      * @param string $sessionId
//      */
//     public function closeSession($sessionId) {
//         if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in Controller submitted!');
//         $backend = Sipgate_Backend_Api::getInstance();
//         return $backend->closeSession($sessionId);
//     }




//     /**
//      *
//      * Gets the devices
//      */
//     public function getAllDevices() {



//         $backend = Sipgate_Backend_Api::getInstance();
//         return $backend->getAllDevices();
//     }

//     /**
//      * Gets the Phones
//      *
//      * @return array
//      */
//     public function getPhoneDevices() {
//         $backend = Sipgate_Backend_Api::getInstance();

//         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting sipgate phones.');
//         $result = $backend->getPhoneDevices();
//         if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, TRUE));

//         return $result;
//     }

//     /**
//      *
//      * Gets the Faxes
//      */
//     public function getFaxDevices() {
//         $backend = Sipgate_Backend_Api::getInstance();
//         return $backend->getFaxDevices();
//     }

//     /**
//      * Gets the CallHistory of the specified sipUri
//      *
//      * @param String $_sipUri
//      */

//     public function getCallHistory($_sipUri, $_start, $_stop, $_pstart, $_plimit) {

//         return Sipgate_Backend_Api::getInstance()->getCallHistory($_sipUri, $_start, $_stop, $_pstart, $_plimit);
//     }


//     /**
//      * send SMS
//      *
//      * @param   string $_number
//      * @param   string $_content
//      */
//     public function sendSms($_number,$_content)
//     {
//         $_sender = $this->_pref->getValue('mobileNumber');
//         $backend = Sipgate_Backend_Api::getInstance();
//         return $backend->sendSms($_sender, $_number, $_content);
//     }

//     /**
//      * Returns settings for crm app
//      * - result is cached
//      *
//      * @param boolean $_resolve if some values should be resolved (here yet unused)
//      * @return  Sipgate_Model_Config
//      *
//      * @todo check 'endslead' values
//      * @todo generalize this / adopt Tinebase_Controller_Abstract::getConfigSettings()
//      */
//     public function getConfigSettings($_resolve = FALSE)
//     {
//         //        return array('success' => 1);
//         //        $cache = Tinebase_Core::get('cache');
//         //        $cacheId = convertCacheId('getSipgateSettings');
//         //        $result = $cache->load($cacheId);
//         //        if (! $result) {
//         $settings = Tinebase_Config::getInstance()->getConfigAsArray('account_settings','Sipgate');
//         if(!empty($settings)) {
//             if($_resolve) {
//                 $cc = Tinebase_Auth_CredentialCache::getInstance()->get($settings['ccId']);
//                 $cc->key = 'sipgate_credential_cache';
//                 Tinebase_Auth_CredentialCache::getInstance()->getCachedAccount($cc);
//                 $settings['password'] = $cc->password;
//             } else {
//                 $settings['password'] = 'XXXXXX';
//             }
//         } else {
//             $settings['password'] = 'XXXXXX';
//         }
//         $result = new Sipgate_Model_Config($settings);

//         //            unset($settings['ccId']);
//         //die(var_dump($settings));
//         // save result and tag it with 'settings'
//         //            $cache->save($cc, $cacheId, array('getSipgateSettings'));
//         //        }

//         return $result;
//     }

//     /**
//      * save crm settings
//      *
//      * @param Crm_Model_Config $_settings
//      * @return Crm_Model_Config
//      *
//      * @todo generalize this
//      */
//     public function saveConfigSettings($_values)
//     {
//         // Get Password
//         if($_values['password'] == 'XXXXXX') {
//             $settings = Tinebase_Config::getInstance()->getConfigAsArray('account_settings','Sipgate');
//             $cc = Tinebase_Auth_CredentialCache::getInstance()->get($settings['ccId']);
//             $cc->key = 'sipgate_credential_cache';
//             Tinebase_Auth_CredentialCache::getInstance()->getCachedAccount($cc);
//             $_values['password'] = $cc->password;
//         }

//         $values['accounttype'] = $_values['accounttype'];

//         if ($_values['password'] && $_values['username']) {
//             $cc = Tinebase_Auth_CredentialCache::getInstance()->cacheAccount($_values['username'], $_values['password'], 'sipgate_credential_cache');
//             $ccRes = $cc->getCacheId();
//             $values['ccId'] = $ccRes['id'];
//             $values['username'] = $_values['username'];
//         } else {
//             return json_encode(array('success' => false));
//         }

//         $settings = new Sipgate_Model_Config($values);

//         Tinebase_Config::getInstance()->setConfigForApplication('account_settings', Zend_Json::encode($settings->toArray()), $this->_applicationName);
//         Tinebase_Core::get('cache')->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('sipgateSettings'));

//         return $this->getConfigSettings();
// =======

//     /**
//      * dial number
//      *
//      * @param   int $_callee
//      * @return  mixed
//      */
//     public function dialNumber($_callee)
//     {
//         $caller = $this->_pref->{'phoneId'};
          
//         $backend = Sipgate_Backend_Factory::factory();
        
//         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
//           . ' Dialing number ' . $_callee . ' with phone id ' . $caller);
          
//         return $backend->dialNumber($caller, $_callee);
//     }

//     /**
//      * gets the Session Status by an Id
//      * @param string $sessionId
//      */
//     public function getSessionStatus($sessionId) {
//         if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in Controller submitted!');
//         $backend = Sipgate_Backend_Factory::factory();
//         return $backend->getSessionStatus($sessionId);
//     }

//     /**
//      * Closes the Session by an Id
//      * @param string $sessionId
//      */
//     public function closeSession($sessionId) {
//         if(empty($sessionId)) throw new Sipgate_Exception('No Session-Id in Controller submitted!');
//         $backend = Sipgate_Backend_Factory::factory();
//         return $backend->closeSession($sessionId);
//     }

//     /**
//      *
//      * Gets the devices
//      */
//     public function getAllDevices() {
//         $backend = Sipgate_Backend_Factory::factory();
//         return $backend->getAllDevices();
//     }

//     /**
//      * Gets the Phones
//      * 
//      * @return array
//      */
//     public function getPhoneDevices() {
//         $backend = Sipgate_Backend_Factory::factory();
        
//         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
//           . ' Getting sipgate phones.');
          
//         $result = $backend->getPhoneDevices();
        
//         if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, TRUE));
        
//         return $result;
//     }

//     /**
//      *
//      * Gets the Faxes
//      */
//     public function getFaxDevices() {
//         $backend = Sipgate_Backend_Factory::factory();
//         return $backend->getFaxDevices();
//     }

//     /**
//      * Gets the CallHistory of the specified sipUri
//      *
//      * @param String $_sipUri
//      */

//     public function getCallHistory($_sipUri, $_start, $_stop, $_pstart, $_plimit) {

//         return Sipgate_Backend_Factory::factory()->getCallHistory($_sipUri, $_start, $_stop, $_pstart, $_plimit);
//     }


//     /**
//      * send SMS
//      *
//      * @param   string $_number
//      * @param   string $_content
//      */
//     public function sendSms($_number,$_content)
//     {
//         $_sender = $this->_pref->getValue('mobileNumber');
//         $backend = Sipgate_Backend_Factory::factory();
//         return $backend->sendSms($_sender, $_number, $_content);
//     }
// >>>>>>> .merge_file_Tbopjl

// //     }

// }
