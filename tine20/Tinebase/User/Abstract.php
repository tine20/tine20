<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
 
abstract class Tinebase_User_Abstract implements Tinebase_User_Interface
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
     * list of plugins 
     * 
     * @var array
     */
    protected $_plugins = array();
    
    protected $_blockTime        = 15;
    
    /**
     * the constructor
     */
    public function __construct(array $_options = array())
    {
        if (array_key_exists('plugins', $_options)) {
            foreach ($_options['plugins'] as $plugin) {
                $this->registerPlugin($plugin);
            }
        }
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
            self::ENCRYPT_SSHA,
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
                
            case self::ENCRYPT_NTPASSWORD:
                $password = strtoupper(hash('md4', iconv('UTF-8','UTF-16LE',$_password)));
                
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
     * (non-PHPdoc)
     * @see Tinebase_User_Interface::getPlugins()
     */
    public function getPlugins()
    {
       return $this->_plugins;
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
        
        $result = new $_accountClass($data, TRUE);
        
        return $result;
    }
    
    /**
     * account name generation
     *
     * @param Tinebase_Model_FullUser $_account
     * @param integer $_schema 0 = lastname (10 chars) / 1 = lastname + 2 chars of firstname / 2 = 1-x chars of firstname + lastname 
     * @return string
     */
    public function generateUserName($_account, $_schema = 1)
    {
        if (! empty($_account->accountFirstName) && $_schema > 0) {
            $userName = ($_schema === 1) ? $this->_generateUserWithSchema1($_account) : $this->_generateUserWithSchema2($_account);
        } else {
            $userName = strtolower(replaceSpecialChars(substr($_account->accountLastName, 0, 10)));
        }
        
        $userName = $this->_addSuffixToUsernameIfExists($userName);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  generated username: ' . $userName);
        
        return $userName;
    }
    
    /**
     * schema 1 = lastname + 2 chars of firstname
     * 
     * @param Tinebase_Model_FullUser $_account
     * @return string
     */
    protected function _generateUserWithSchema1($_account)
    {
        $result = strtolower(replaceSpecialChars(substr($_account->accountLastName, 0, 10) . substr($_account->accountFirstName, 0, 2)));
        return $result;
    }
    
    /**
     * schema 2 = 1-x chars of firstname + lastname
     * 
     * @param Tinebase_Model_FullUser $_account
     * @return string
     */
    protected function _generateUserWithSchema2($_account)
    {
        $result = $_account->accountLastName;
        for ($i=0; $i < strlen($_account->accountFirstName); $i++) {
        
            $userName = strtolower(replaceSpecialChars(substr($_account->accountFirstName, 0, $i+1) . $_account->accountLastName));
            if (! $this->userNameExists($userName)) {
                $result = $userName;
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * add a suffix to username if it already exists
     * 
     * @param string $_userName
     * @return string
     */
    protected function _addSuffixToUsernameIfExists($_userName)
    {
        $result = $_userName;
        if ($this->userNameExists($_userName)) {
            $numSuffix = 0;
        
            while ($numSuffix < 100) {
                $suffix = sprintf('%02d', $numSuffix);
        
                if (! $this->userNameExists($_userName . $suffix)) {
                    $result = $_userName . $suffix;
                    break;
                }
        
                $numSuffix++;
            }
        }
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_User_Interface::registerPlugin()
     */
    public function registerPlugin(Tinebase_User_Plugin_Interface $_plugin)
    {
        $className = get_class($_plugin);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Registering " . $className . ' plugin.');
        
        $this->_plugins[$className] = $_plugin;
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
                if ($record->$property && is_string($record->$property)) {
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
        $userId = $_accountId instanceof Tinebase_Model_User ? $_accountId->getId() : $_accountId;
        
        return $this->getUserByProperty('accountId', $userId, $_accountClass);
    }
    
    /**
     * setPassword() - sets / updates the password in the account backend
     *
     * @param  string  $_userId
     * @param  string  $_password
     * @param  bool    $_encrypt encrypt password
     * @param  bool    $_mustChange
     * @return void
     */
    abstract public function setPassword($_userId, $_password, $_encrypt = TRUE, $_mustChange = null);
    
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
     * @param   Tinebase_DateTime   $_expiryDate
    */
    abstract public function setExpiryDate($_accountId, $_expiryDate);

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
     * update contact data(first name, last name, ...) of user
     * 
     * @param Addressbook_Model_Contact $contact
     */
    abstract public function updateContact(Addressbook_Model_Contact $_contact);
    
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
     * @param string|array     $_id Ids
     * @param string          $_accountClass  type of model to return
     * @return Tinebase_Record_RecordSet
     */
    abstract public function getMultiple($_id, $_accountClass = 'Tinebase_Model_User');
}
