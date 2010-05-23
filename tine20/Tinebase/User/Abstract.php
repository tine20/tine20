<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @deprecated  user backends should be refactored
 * @todo        add searchCount function
 */

/**
 * abstract class for all user backends
 *
 * @package     Tinebase
 * @subpackage  User
 */
 
abstract class Tinebase_User_Abstract
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
     * lmpassword encryption
     */
    const ENCRYPT_LMPASSWORD = 'lmpassword';
    
    /**
     * ntpassword encryption
     */
    const ENCRYPT_NTPASSWORD = 'ntpassword';
    
    /**
     * no encryption
     */
    const ENCRYPT_PLAIN = 'plain';
    
    /**
     * user property for openid
     */
    const PROPERTY_OPENID = 'openid';
    
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
            self::ENCRYPT_SSHA,
            self::ENCRYPT_LMPASSWORD,
            self::ENCRYPT_NTPASSWORD
        );
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
                
            case self::ENCRYPT_LMPASSWORD:
                $crypt = new Crypt_CHAP_MSv1();
                $password = strtoupper(bin2hex($crypt->lmPasswordHash($_password)));
                break;
                
            case self::ENCRYPT_NTPASSWORD:
                $crypt = new Crypt_CHAP_MSv1();
                $password = strtoupper(bin2hex($crypt->ntPasswordHash($_password)));
                
                // @todo replace Crypt_CHAP_MSv1
                //$password = hash('md4', Zend_Auth_Adapter_Http_Ntlm::toUTF16LE($_password), TRUE);
                break;
                
            default:
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " using default password encryption method " . self::ENCRYPT_DES);
                // fall through
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
     * get list of users
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_FullUser
     */
    public function getFullUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        return $this->getUsers($_filter, $_sort, $_dir, $_start, $_limit, 'Tinebase_Model_FullUser');
    }
    
    /**
     * get full user by login name
     *
     * @param   string      $_loginName
     * @return  Tinebase_Model_FullUser full user
     */
    public function getFullUserByLoginName($_loginName)
    {
        return $this->getUserByLoginName($_loginName, 'Tinebase_Model_FullUser');
    }
    
    /**
     * get full user by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_Model_FullUser full user
     */
    public function getFullUserById($_accountId)
    {
        return $this->getUserById($_accountId, 'Tinebase_Model_FullUser');
    }
    
    /**
     * get dummy user record
     *
     * @param string $_accountClass Tinebase_Model_User|Tinebase_Model_FullUser
     * @param integer $_id [optional]
     * @return Tinebase_Model_User|Tinebase_Model_FullUser
     */
    public function getNonExistentUser($_accountClass = 'Tinebase_Model_User', $_id = 0) 
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        $data = array(
            'accountId'             => ($_id !== NULL) ? $_id : 0,
            'accountLoginName'      => $translate->_('unknown'),
            'accountDisplayName'    => $translate->_('unknown'),
            'accountLastName'       => $translate->_('unknown'),
            'accountFirstName'      => $translate->_('unknown'),
            'accountFullName'       => $translate->_('unknown'),
        );
        
        if ($_accountClass === 'Tinebase_Model_FullUser') {
            $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
            $data['accountPrimaryGroup'] = $defaultUserGroup->getId();
        }
        
        $result = new $_accountClass($data);
        
        return $result;
    }
    
    /**
     * account name generation
     *
     * @param Tinebase_Model_FullUser $_account
     * @return string
     */
    public function generateUserName($_account)
    {
        if (! empty($_account->accountFirstName)) {
            
            for ($i=0; $i<strlen($_account->accountFirstName); $i++) {
                
                $userName = strtolower(self::replaceSpechialChars(substr($_account->accountFirstName, 0, $i+1) . $_account->accountLastName));
                if (! $this->userNameExists($userName)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  generated username: ' . $userName);
                    return $userName;
                }
            }
        }
        
        $numSuffix = 1;
        while(true) {
            if (! $this->userNameExists($userName . $numSuffix)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  generated username: ' . $userName . $numSuffix);
                return $userName . $numSuffix;
            }
            $numSuffix++;
        }
    }
    
    /**
     * replaces and/or strips spechialchars from given string
     *
     * @param string $_input
     * @return string
     */
    public static function replaceSpechialChars($_input)
    {
        $search  = array('ä',  'ü',  'ö',  'ß',  'é', 'è', 'ê', 'ó' ,'ô', 'á', 'ź'); 
        $replace = array('ae', 'ue', 'oe', 'ss', 'e', 'e', 'e', 'o', 'o', 'a', 'z');
                    
        $output = str_replace($search, $replace, $_input);
        
        return preg_replace('/[^a-zA-Z0-9._\-]/', '', $output);
    }
    
    /**
     * resolves users of given record
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string|array             $_userProperties
     * @param bool                     $_addNonExistingUsers
     * @return void
     */
    public function resolveUsers(Tinebase_Record_Abstract $_record, $_userProperties, $_addNonExistingUsers = FALSE)
    {
    	$recordSet = new Tinebase_Record_RecordSet('Tinebase_Record_Abstract', array($_record));
    	$this->resolveMultipleUsers($recordSet, $_userProperties, $_addNonExistingUsers);
    }
    
    /**
     * resolves users of given record
     * 
     * @param Tinebase_Record_RecordSet $_records
     * @param string|array              $_userProperties
     * @param bool                      $_addNonExistingUsers
     * @return void
     */
    public function resolveMultipleUsers(Tinebase_Record_RecordSet $_records, $_userProperties, $_addNonExistingUsers = FALSE)
    {
    	$userIds = array();
        foreach ((array)$_userProperties as $property) {
            $userIds = array_merge($userIds, $_records->$property);
        }

        $userIds = array_unique($userIds);
        foreach ($userIds as $index => $userId) {
        	if (empty($userId)) {
        		unset ($userIds[$index]);
        	}
        }
        
        $users = $this->getMultiple($userIds);
        $nonExistingUser = $this->getNonExistentUser();
        
        foreach ($_records as $record) {
            foreach ((array)$_userProperties as $property) {
            	if ($record->$property) {
            	    $idx = $users->getIndexById($record->$property);
            	    $user = $idx !== false ? $users[$idx] : NULL;
            	    
            	    if (!$user && $_addNonExistingUsers) {
            	        $user = $nonExistingUser;
            	    }
            	    
            	    if ($user) {
            	        $record->$property = $user;
            	    }
            	}
            }
        }
    }
    
    /**
     * checks if username already exists
     *
     * @param   string  $_username
     * @return  bool    
     * 
     */
    public function userNameExists($_username)
    {
        try {
            $this->getUserByLoginName($_username)->getId();
        } catch (Tinebase_Exception_NotFound $e) {
            // username not found
            return false;
        }
        
        return true;
    }
    
    /******************* abstract functions *********************/
    
    /**
     * get list of users with NO internal informations
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    abstract public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL);
    
    /**
     * get user by login name
     *
     * @param   string  $_loginName
     * @param   string  $_accountClass  type of model to return
     * @return  Tinebase_Model_User full user
     */
    public function getUserByLoginName($_loginName, $_accountClass = 'Tinebase_Model_User')
    {
        return $this->getUserByProperty('accountLoginName', $_loginName, $_accountClass);
    }
    
    /**
     * get user by id
     *
     * @param   string  $_accountId
     * @param   string  $_accountClass  type of model to return
     * @return  Tinebase_Model_User user
     */
    public function getUserById($_accountId, $_accountClass = 'Tinebase_Model_User') 
    {
        return $this->getUserByProperty('accountId', $_accountId, $_accountClass);
    }
    
    /**
     * get user by property
     *
     * @param   string  $_property
     * @param   string  $_accountId
     * @param   string  $_accountClass  type of model to return
     * @return  Tinebase_Model_User user
     */
    abstract public function getUserByProperty($_property, $_accountId, $_accountClass = 'Tinebase_Model_User');
    
    /**
     * setPassword() - sets / updates the password in the account backend
     *
     * @param string $_loginName
     * @param string $_password
     * @param bool   $_encrypt encrypt password
     * @return void
     */
    abstract public function setPassword($_loginName, $_password, $_encrypt = TRUE);
    
    /**
     * update user status
     *
     * @param   int         $_accountId
     * @param   string      $_status
     */
    abstract public function setStatus($_accountId, $_status);

    /**
     * sets/unsets expiry date (calls backend class with the same name)
     *
     * @param   int         $_accountId
     * @param   Zend_Date   $_expiryDate
    */
    abstract public function setExpiryDate($_accountId, $_expiryDate);

    /**
     * blocks/unblocks the user (calls backend class with the same name)
     *
     * @param   int $_accountId
     * @param   Zend_Date   $_blockedUntilDate
    */
    abstract public function setBlockedDate($_accountId, $_blockedUntilDate);
    
    /**
     * set login time for user (with ip address)
     *
     * @param int $_accountId
     * @param string $_ipAddress
     */
    abstract public function setLoginTime($_accountId, $_ipAddress);
    
    /**
     * updates an existing user
     *
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser
     */
    abstract public function updateUser(Tinebase_Model_FullUser $_user);

    /**
     * adds a new user
     *
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser
     */
    abstract public function addUser(Tinebase_Model_FullUser $_user);
    
    /**
     * delete an user
     *
     * @param  mixed  $_userId
     */
    abstract public function deleteUser($_userId);

    /**
     * delete multiple users
     *
     * @param array $_accountIds
     */
    abstract public function deleteUsers(array $_accountIds);
    
    /**
     * Get multiple users
     *
     * @param string|array $_id Ids
     * @return Tinebase_Record_RecordSet
     */
    abstract public function getMultiple($_id);
}
