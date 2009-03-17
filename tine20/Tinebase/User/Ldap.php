<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * User ldap backend
 * 
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_Ldap extends Tinebase_User_Abstract
{
    /**
     * des encryption
     */
    const ENCRYPT_DES = 'des';
    
    /**
     * blowfish crypt encryption
     */
    const ENCRYPT_BLOWFISH_CRYPT = 'blowfish_crypt';
    
    /**
     * md5 crypt encryption
     */
    const ENCRYPT_MD5_CRYPT = 'md5_crypt';
    
    /**
     * ext crypt encryption
     */
    const ENCRYPT_EXT_CRYPT = 'ext_crypt';
    
    /**
     * md5 encryption
     */
    const ENCRYPT_MD5 = 'md5';
    
    /**
     * smd5 encryption
     */
    const ENCRYPT_SMD5 = 'smd5';

    /**
     * sha encryption
     */
    const ENCRYPT_SHA = 'sha';
    
    /**
     * ssha encryption
     */
    const ENCRYPT_SSHA = 'ssha';
    
    /**
     * no encryption
     */
    const ENCRYPT_PLAIN = 'plain';
    
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     * don't use the constructor. use the singleton 
     */
    private function __construct(array $_options) 
    {
        $this->_backend = new Tinebase_Ldap($_options);
        $this->_backend->bind();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_User_Ldap
     */
    private static $_instance = NULL;
    
    /**
     * @var Tinebase_Ldap
     */
    protected $_backend = NULL;
    
    /**
     * direct mapping
     *
     * @var array
     */
    protected $_rowNameMapping = array(
        'accountId'                 => 'uidnumber',
        'accountDisplayName'        => 'displayname',
        'accountFullName'           => 'cn',
        'accountFirstName'          => 'givenname',
        'accountLastName'           => 'sn',
        'accountLoginName'          => 'uid',
        'accountLastPasswordChange' => 'shadowlastchange',
        'accountExpires'            => 'shadowexpire',
        'accountPrimaryGroup'       => 'gidnumber',
        'accountEmailAddress'       => 'mail',
        'accountHomeDirectory'      => 'homedirectory',
        'accountLoginShell'         => 'loginshell',
    );
    
    /**
     * objectclasses required by this backend
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'top',
        'posixAccount',
        'shadowAccount',
        'inetOrgPerson',
    );
    
    /**
     * the singleton pattern
     *
     * @param  array $options Options used in connecting, binding, etc.
     * @return Tinebase_User_Ldap
     */
    public static function getInstance(array $_options = array()) 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_User_Ldap($_options);
        }
        
        return self::$_instance;
    }
    
    /**
     * returns all supported password encryptions types
     *
     * @return array
     */
    public static function getSupportedEncryptionTypes()
    {
        return array(
            self::ENCRYPT_BLOWFISH_CRYPT,
            self::ENCRYPT_EXT_CRYPT,
            self::ENCRYPT_DES,
            self::ENCRYPT_MD5,
            self::ENCRYPT_MD5_CRYPT,
            self::ENCRYPT_PLAIN,
            self::ENCRYPT_SHA,
            self::ENCRYPT_SMD5,
            self::ENCRYPT_SSHA
        );
    }
    /**
     * get list of users
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, 
        $_limit = NULL, $_accountClass = 'Tinebase_Model_User')
    {        
        if (!empty($_filter)) {
            $searchString = "*" . Tinebase_Ldap::filterEscape($_filter) . "*";
            $filter = "(&(objectclass=posixaccount)(|(uid=$searchString)(cn=$searchString)(sn=$searchString)(givenName=$searchString)))";
        } else {
            $filter = 'objectclass=posixaccount';
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        
        return $this->_getUsersFromBackend($filter, $_accountClass);
    }
    
    /**
     * get user by login name
     *
     * @param string $_loginName the loginname of the user
     * @return Tinebase_Model_User the user object
     */
    public function getUserByLoginName($_loginName, $_accountClass = 'Tinebase_Model_User')
    {
        $loginName = Zend_Ldap::filterEscape($_loginName);
        
        try {
            $account = $this->_backend->fetch(Tinebase_Core::getConfig()->accounts->get('ldap')->userDn, 'uid=' . $loginName);
            $result = $this->_ldap2User($account, $_accountClass);
        } catch (Tinebase_Exception_NotFound $enf) {
            $result = $this->getNonExistentUser($_accountClass);
        }
        
        return $result;
    }
    
    /**
     * get user by userId
     *
     * @param int $_accountId the account id
     * @return Tinebase_Model_User the user object
     */
    public function getUserById($_accountId, $_accountClass = 'Tinebase_Model_User')
    {
        try {
            $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
            $account = $this->_backend->fetch(Tinebase_Core::getConfig()->accounts->get('ldap')->userDn, 'uidnumber=' . $accountId);
            $result = $this->_ldap2User($account, $_accountClass);
        } catch (Tinebase_Exception_NotFound $enf) {
            $result = $this->getNonExistentUser($_accountClass, $accountId);
        }
        
        return $result;
    }
    
    /**
     * update the lastlogin time of user
     *
     * @param int $_accountId
     * @param string $_ipAddress
     * @return void
     */
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        // not supported by standart ldap schemas
        $user = $this->getFullUserById($_accountId);
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . "  User '{$user->accountLoginName}' loged in from {$_ipAddress}");
    }
    
    /**
     * set the password for given account
     * 
     * @param   int $_accountId
     * @param   string $_password
     * @param   bool $_encrypt encrypt password
     * @return  void
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_loginName, $_password, $_encrypt = TRUE)
    {
        if(empty($_loginName)) {
            throw new Tinebase_Exception_InvalidArgument('$_loginName can not be empty');
        }
        
        $user = $this->getFullUserByLoginName($_loginName);
        $metaData = $this->_getMetaData($user);
        
        $encryptionType = Zend_Registry::get('configFile')->accounts->get('ldap')->pwEncType;
        $userpassword = $_encrypt ? self::encryptPassword($_password, $encryptionType) : $_password;
        $ldapData = array('userpassword' => $userpassword);
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_backend->update($metaData['dn'], $ldapData);
    }
    
    /**
     * encryptes password
     *
     * @param string $_password
     * @param string $_method
     */
    public static function encryptPassword($_password, $_method)
    {
        switch (strtolower($_method)) {
            case self::ENCRYPT_BLOWFISH_CRYPT:
                if(@defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1) {
                    $salt = '$2$' . self::getRandomString(13);
                    $password = '{CRYPT}' . crypt($_password, $salt);
                }
                break;
                
            case self::ENCRYPT_EXT_CRYPT:
                if(@defined('CRYPT_EXT_DES') && CRYPT_EXT_DES == 1) {
                    $salt = self::getRandomString(9);
                    $password = '{CRYPT}' . crypt($_password, $salt);
                }
                break;
                
            case self::ENCRYPT_MD5:
                $password = '{MD5}' . base64_encode(pack("H*", md5($_password)));
                break;
                
            case self::ENCRYPT_MD5_CRYPT:
                if(@defined('CRYPT_MD5') && CRYPT_MD5 == 1) {
                    $salt = '$1$' . self::getRandomString(9);
                    $password = '{CRYPT}' . crypt($_password, $salt);
                }
                break;
                
            case self::ENCRYPT_PLAIN:
                $password = $_password;
                break;
            case self::ENCRYPT_SHA:
                if(function_exists('mhash')) {
                    $password = '{SHA}' . base64_encode(mhash(MHASH_SHA1, $_password));
                }
                break;
                
            case self::ENCRYPT_SMD5:
                if(function_exists('mhash')) {
                    $salt = self::getRandomString(8);
                    $hash = mhash(MHASH_MD5, $_password . $salt);
                    $password = '{SMD5}' . base64_encode($hash . $salt);
                }
                break;
            case self::ENCRYPT_SSHA:
                if(function_exists('mhash')) {
                    $salt = self::getRandomString(8);
                    $hash = mhash(MHASH_SHA1, $_password . $salt);
                    $password = '{SSHA}' . base64_encode($hash . $salt);
                }
                break;
            default:
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " using default password encryption method " . self::ENCRYPT_DES);
            case self::ENCRYPT_DES:
                $salt = self::getRandomString(2);
                $password  = '{CRYPT}'. crypt($_password, $salt);
                break;
            
        }
        
        if (! $password) {
            throw new Tinebase_Exception_NotImplemented("$_method is not supported by your php version");
        }
        
        return $password;
    }
    
    /**
     * generates a randomstrings of given length
     *
     * @param int $_length
     */
    public static function getRandomString($_length)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        $randomString = '';
        for ($i=0; $i<(int)$_length; $i++) {
            $randomString .= $chars[mt_rand(1, strlen($chars)) -1];
        }
        
        return $randomString;
    }
    
    /**
     * update user status
     * 
     * NOTE: It would be possible to model this via the expire date, but as all
     *       acclunt stuff must handle expire seperatly, it seems the best just
     *       to not support the status with ldap
     * 
     * @param   int         $_accountId
     * @param   string      $_status
    */
    public function setStatus($_accountId, $_status) 
    {
        // not supported by standart ldap schemas
        if ($_status == 'disabled') {
        
            $user = $this->getFullUserById($_accountId);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . "  With ldap user backend, user '{$user->accountLoginName}' can not be disabled!");
        }
    }

    /**
     * sets/unsets expiry date (calls backend class with the same name)
     *
     * @param   int         $_accountId
     * @param   Zend_Date   $_expiryDate
    */
    public function setExpiryDate($_accountId, $_expiryDate) 
    {
        
        $metaData = $this->_getMetaData($_accountId);
        $data = array('shadowexpire' => $_expiryDate->getTimestamp());
        
        $this->_backend->update($metaData['dn'], $data);
    }

    /**
     * blocks/unblocks the user (calls backend class with the same name)
     *
     * @param   int $_accountId
     * @param   Zend_Date   $_blockedUntilDate
    */
    public function setBlockedDate($_accountId, $_blockedUntilDate) 
    {
        // not supported by standart ldap schemas
        $user = $this->getFullUserById($_accountId);
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . "  With ldap user backend, user '{$user->accountLoginName}' could not be blocked until {$_blockedUntilDate}");
    }
        
    /**
     * updates an existing user
     * 
     * @todo check required objectclasses?
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function updateUser(Tinebase_Model_FullUser $_account) 
    {
        $metaData = $this->_getMetaData($_account);
        $ldapData = $this->_user2ldap($_account);
        
        // check if user has all required object classes. This is needed 
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $metaData['objectClass'])) {
                $ldapData['objectclass'] = array_unique(array_merge($metaData['objectClass'], $this->_requiredObjectClass));
                break;
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_backend->update($metaData['dn'], $ldapData);
        
        return $this->getFullUserByLoginName($_account->accountLoginName);
    }

    /**
     * adds a new user
     * 
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_account) 
    {
        $newDn = $this->_generateDn($_account);
        $ldapData = $this->_user2ldap($_account);
        
        $ldapData['uidnumber'] = $this->_generateUidNumber();
        $ldapData['objectclass'] = $this->_requiredObjectClass;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_backend->insert($newDn, $ldapData);
        
        return $this->getFullUserByLoginName($_account->accountLoginName);
    }
    
    /**
     * delete an user
     *
     * @param int $_accountId
     */
    public function deleteUser($_accountId) 
    {
        $metaData = $this->_getMetaData($_accountId);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        
        $this->_backend->delete($metaData['dn']);
    }

    /**
     * delete multiple users
     *
     * @param array $_accountIds
     */
    public function deleteUsers(array $_accountIds) 
    {
        foreach ($_accountIds as $accountId) {
            $this->deleteUser($accountId);
        }
    }

    /**
     * Get multiple users
     *
     * @param  string|array $_ids Ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids) 
    {
        $ids = is_array($_ids) ? $_ids : (array) $_ids;
        
        $idFilter = '';
        foreach ($ids as $id) {
            $idFilter .= "(uidnumber=$id)";
        }
        $filter = "(&(objectclass=posixaccount)(|$idFilter))";
        
        $result = $this->_getUsersFromBackend($filter, 'Tinebase_Model_User');
		
        // add unknown users if not found in database
        foreach($ids as $id) {
            if (!isset($result[$result->getIndexById($id)])) {
                $result->addRecord($this->getNonExistentUser('Tinebase_Model_User', $id));
            }
        }
		
		return $result;
    }
    
    /**
     * get metatada of existing account
     *
     * @param  int         $_accountId
     * @return string 
     */
    protected function _getMetaData($_accountId)
    {
        $metaData = array();
        
        try {
            $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
            $account = $this->_backend->fetch(Tinebase_Core::getConfig()->accounts->get('ldap')->userDn, 'uidnumber=' . $accountId, array('objectclass'));
            $metaData['dn'] = $account['dn'];
            
            $metaData['objectClass'] = $account['objectclass'];
            unset($metaData['objectClass']['count']);
            
        } catch (Tinebase_Exception_NotFound $enf) {
            throw new Exception("account with id $accountId not found");
        }
        
        return $metaData;
    }
    
    /**
     * generates a new dn
     *
     * @param  Tinebase_Model_FullUser $_account
     * @return string
     */
    protected function _generateDn(Tinebase_Model_FullUser $_account)
    {
        $baseDn = Zend_Registry::get('configFile')->accounts->get('ldap')->userDn;
        
        $uidProperty = array_search('uid', $this->_rowNameMapping);
        $newDn = "uid={$_account->$uidProperty},{$baseDn}";
        
        return $newDn;
    }
    
    /**
     * generates a uidnumber
     *
     * @todo add a persistent registry which id has been generated lastly to
     *       reduce amount of userid to be transfered
     * 
     * @return int
     */
    protected function _generateUidNumber()
    {
        $allUidNumbers = array();
        foreach ($this->_backend->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->userDn, 'objectclass=posixAccount', array('uidnumber')) as $userData) {
            $allUidNumbers[] = $userData['uidnumber'][0];
        }
        asort($allUidNumbers);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing uidnumbers " . print_r($allUidNumbers, true));
        
        $numUsers = count($allUidNumbers);
        if ($numUsers == 0) {
            $uidNumber = Zend_Registry::get('configFile')->accounts->get('ldap')->minUserId;
        } elseif ($allUidNumbers[$numUsers-1] < Zend_Registry::get('configFile')->accounts->get('ldap')->maxUserId) {
            $uidNumber = ++$allUidNumbers[$numUsers-1];
        } else {
            throw new Tinebase_Exception_NotImplemented('Max User Id is reached');
        }
        
        return $uidNumber;
    }
    
    /**
     * Fetches all accounts from backend matching the given filter
     *
     * @param string $_filter
     * @param string $_accountClass
     * @return Tinebase_Record_RecordSet
     */
    protected function _getUsersFromBackend($_filter, $_accountClass = 'Tinebase_Model_User')
    {
        $result = new Tinebase_Record_RecordSet($_accountClass);
        $accounts = $this->_backend->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->userDn, $_filter, array_values($this->_rowNameMapping));
        
        foreach ($accounts as $account) {
            $accountObject = $this->_ldap2User($account, $_accountClass);
            
            $result->addRecord($accountObject);
        }
        
        return $result;
    }
    
    /**
     * Returns a user obj with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Abstract
     */
    protected function _ldap2User($_userData, $_accountClass)
    {
        // accounts found in ldap tree are always enabled, see comment in setStatus
        $accountArray = array(
            'accountStatus'  => 'enabled'
        );
        
        foreach ($_userData as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_rowNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'accountLastPasswordChange':
                    case 'accountExpires':
                        $accountArray[$keyMapping] = new Zend_Date($value[0], Zend_Date::TIMESTAMP);
                        break;
                    case 'accountStatus':
                        break;
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        $accountObject = new $_accountClass($accountArray);
        
        return $accountObject;
    }
    
    /**
     * returns array of ldap data
     *
     * @param  Tinebase_Model_FullUser $_user
     * @return array
     */
    protected function _user2ldap(Tinebase_Model_FullUser $_user)
    {
        if ($_user->accountStatus == 'disabled') {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . "  With ldap user backend, user '{$_user->accountDisplayName}' can not be disabled!");
        }
        
        $ldapData = array();
        foreach ($_user as $key => $value) {
            $ldapProperty = array_key_exists($key, $this->_rowNameMapping) ? $this->_rowNameMapping[$key] : false;
            if ($ldapProperty) {
                switch ($key) {
                    case 'accountLastPasswordChange':
                    case 'accountExpires':
                        $ldapData[$ldapProperty] = $value instanceof Zend_Date ? $value->getTimestamp() : '';
                        break;
                    case 'accountStatus':
                        break;
                    default:
                        $ldapData[$ldapProperty] = $value;
                        break;
                }
            }
        }
        
        // homedir is an required attribute
        if (empty($ldapData['homedirectory'])) {
            $ldapData['homedirectory'] = '/dev/null';
        }
        
        return $ldapData;
    }
    
}
