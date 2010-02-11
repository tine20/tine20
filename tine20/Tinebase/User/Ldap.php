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
     * @var array
     */
    protected $_options = array();
    
    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * the sql user backend
     * 
     * @var Tinebase_User_Sql
     */
    protected $_sql;
    
    /**
     * name of the ldap attribute which identifies a group uniquely
     * for example gidNumber, entryUUID, objectGUID
     * @var string
     */
    protected $_groupUUIDAttribute;
    
    /**
     * name of the ldap attribute which identifies a user uniquely
     * for example uidNumber, entryUUID, objectGUID
     * @var string
     */
    protected $_userUUIDAttribute;
    
    /**
     * direct mapping
     *
     * @var array
     */
    protected $_rowNameMapping = array(
        //'accountId'                 => 'uidnumber',  // set by constructor
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
     * the basic group ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_groupBaseFilter      = 'objectclass=posixgroup';
    
    /**
     * the basic user ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_userBaseFilter      = 'objectclass=posixaccount';
    
    /**
     * the basic user search scope
     *
     * @var integer
     */
    protected $_userSearchScope      = Zend_Ldap::SEARCH_SCOPE_SUB;
    
    /**
     * the query filter for the ldap search (for example uid=%s)
     *
     * @var string
     */
    protected $_queryFilter     = '|(uid=%s)(cn=%s)(sn=%s)(givenName=%s)';
        
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct(array $_options) 
    {
        $this->_options = $_options;

        if (isset($this->_options['requiredObjectClass'])) {
            $this->_requiredObjectClass = (array)$this->_options['requiredObjectClass'];
        }
        
        $this->_userUUIDAttribute  = isset($_options['userUUIDAttribute'])  ? $_options['userUUIDAttribute']  : 'entryUUID';
        $this->_groupUUIDAttribute = isset($_options['groupUUIDAttribute']) ? $_options['groupUUIDAttribute'] : 'entryUUID';
        $this->_userBaseFilter     = isset($_options['userFilter'])         ? $_options['userFilter']         : 'objectclass=posixaccount';
        $this->_userSearchScope    = isset($_options['userSearchScope'])    ? $_options['userSearchScope']    : Zend_Ldap::SEARCH_SCOPE_SUB;
        $this->_groupBaseFilter    = isset($_options['groupFilter'])        ? $_options['groupFilter']        : 'objectclass=posixgroup';
        
        $this->_rowNameMapping['accountId'] = strtolower($this->_userUUIDAttribute);
        
        $this->_ldap = new Tinebase_Ldap($_options);
        $this->_ldap->bind();
        
        $this->_sql = new Tinebase_User_Sql();
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
    public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Model_User')
    {        
        return $this->_sql->getUsers($_filter, $_sort, $_dir, $_start, $_limit, $_accountClass);
    }
    
   /**
     * get user by login name
     *
     * @param   string  $_property
     * @param   string  $_accountId
     * @return Tinebase_Model_User the user object
     */
    protected function _getUserByProperty($_property, $_accountId, $_accountClass = 'Tinebase_Model_User')
    {
        if(!array_key_exists($_property, $this->_rowNameMapping)) {
            throw new Tinebase_Exception_InvalidArgument("invalid property $_property requested");
        }
        
        switch($_property) {
            case 'accountId':
                $value = Tinebase_Model_User::convertUserIdToInt($_accountId);
                break;
            default:
                $value = $_accountId;
                break;
        }
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_userBaseFilter),
            Zend_Ldap_Filter::equals($this->_rowNameMapping[$_property], Zend_Ldap::filterEscape($value))
        );
        
        $accounts = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array_values($this->_rowNameMapping)
        );

        if(count($accounts) == 0) {
            throw new Tinebase_Exception_NotFound('User with ' . $_property . ' =  ' . $value . ' not found.');
        }
        
        $result = $this->_ldap2User($accounts->getFirst(), $_accountClass);
        
        return $result;
    }
    
    /**
     * get user by property / comply to abstract parent class / needs to be implemented
     *
     * @param   string  $_property
     * @param   string  $_accountId
     * @param   string  $_accountClass  type of model to return
     * @return  Tinebase_Model_User user
     */
    public function getUserByProperty($_property, $_accountId, $_accountClass = 'Tinebase_Model_User')
    {
        try {
            // first we try the get the user from the sql backend
            $user = $this->_sql->getUserByProperty($_property, $_accountId, $_accountClass);
        } catch (Tinebase_Exception_NotFound $e) {
            // if not found we try to get the user from the ldap backend
            $fullUser = $this->_getUserByProperty($_property, $_accountId, 'Tinebase_Model_FullUser');
            $fullUser = $this->_sql->addUser($fullUser);
            
            // fetch again to make sure the correct account class is used
            $user = $this->_sql->getUserByProperty('accountId', $fullUser, $_accountClass);
        }
        
        return $user;
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
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . "  User '{$user->accountLoginName}' logged in from {$_ipAddress}");
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
        
        $encryptionType = isset($this->_options['pwEncType']) ? $this->_options['pwEncType'] : Tinebase_User_Abstract::ENCRYPT_SSHA;
        $userpassword = $_encrypt ? Tinebase_User_Abstract::encryptPassword($_password, $encryptionType) : $_password;
        $ldapData = array(
            'userpassword'     => $userpassword,
            'shadowlastchange' => Zend_Date::now()->getTimestamp()
        );
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
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
        if($_expiryDate instanceof Zend_Date) {
            $data = array('shadowexpire' => $_expiryDate->getTimestamp());
        } else {
            $data = array('shadowexpire' => array());
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " {$metaData['dn']}  $data: " . print_r($data, true));
 
        $this->_ldap->update($metaData['dn'], $data);
        
        $this->_sql->setExpiryDate($_accountId, $_expiryDate);
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
        $this->_updateUser($_account);
        
        // refetch user from ldap backend
        $user = $this->_getUserByProperty('accountId', $_account, 'Tinebase_Model_FullUser');
        
        // update user in sql backend too (and add visibility info)
        $user->visibility = $_account->visibility;
        $user = $this->_sql->updateUser($user);
        
        return $user;
    }
    
    /**
     * updates an existing user
     * 
     * @todo check required objectclasses?
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    protected function _updateUser(Tinebase_Model_FullUser $_account) 
    {
        $metaData = $this->_getMetaData($_account);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($metaData, true));
        $ldapData = $this->_user2ldap($_account);
        
        // check if user has all required object classes. This is needed 
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $metaData['objectclass'])) {
                $ldapData['objectclass'] = array_unique(array_merge($metaData['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }
        
        // no need to update this attribute, it's not allowed to change and even might not be updateable
        unset($ldapData[strtolower($this->_userUUIDAttribute)]);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
    }
    
    /**
     * adds a new user
     * 
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_account) 
    {
        $dn = $this->_addUser($_account);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' added user with dn: ' . $dn);
        
        $userId = $this->_ldap->getEntry($dn, array($this->_userUUIDAttribute));
        
        $userId = $userId[strtolower($this->_userUUIDAttribute)][0];
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' new userid: ' . $userId);
        
        $user = $this->_getUserByProperty('accountId', $userId, 'Tinebase_Model_FullUser');
        
        // add account to sql backend too
        $user = $this->_sql->addUser($user);
        
        return $user;
    }
    
    protected function _addUser(Tinebase_Model_FullUser $_account)
    {
        $dn = $this->_generateDn($_account);
        $ldapData = $this->_user2ldap($_account);
        
        $ldapData['uidnumber'] = $this->_generateUidNumber();
        $ldapData['objectclass'] = $this->_requiredObjectClass;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->add($dn, $ldapData);
        
        return $dn;
    }
    
    /**
     * delete an user
     *
     * @param int $_accountId
     */
    public function deleteUser($_accountId) 
    {
        $metaData = $this->_getMetaData($_accountId);
        
        // delete user in sql backend first (foreign keys)
        $this->_sql->deleteUser($_accountId);
        
        // if does not exist in ldap anymore
        if(!empty($metaData['dn'])) {
            $this->_ldap->delete($metaData['dn']);
        }
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
        return $this->_sql->getMultiple($_ids);
    }
    
    /**
     * get metatada of existing account
     *
     * @param  int         $_userId
     * @return array 
     * 
     * @todo remove obsolete code
     */
    protected function _getMetaData($_userId)
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
        
        $filter = Zend_Ldap_Filter::equals(
            $this->_rowNameMapping['accountId'], Zend_Ldap::filterEscape($userId)
        );
        
        $result = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array('objectclass')
        )->getFirst();
        
        return $result;
        
        /*
        } catch (Tinebase_Exception_NotFound $enf) {
            throw new Exception("account with id $accountId not found");
        }
        */
    }
    
    /**
     * generates a new dn
     *
     * @param  Tinebase_Model_FullUser $_account
     * @return string
     */
    protected function _generateDn(Tinebase_Model_FullUser $_account)
    {
        $baseDn = $this->_options['userDn'];
        
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
        $uidNumber = null;

        $filter = Zend_Ldap_Filter::equals(
            'objectclass', 'posixAccount'
        );
        
        $accounts = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('uidnumber')
        );
        
        foreach ($accounts as $userData) {
            $allUidNumbers[] = $userData['uidnumber'][0];
        }
        sort($allUidNumbers);
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing uidnumbers " . print_r($allUidNumbers, true));
        
        $numUsers = count($allUidNumbers);
        if ($numUsers == 0 || $allUidNumbers[$numUsers-1] < $this->_options['minUserId']) {
            $uidNumber = $this->_options['minUserId'];
        } elseif ($allUidNumbers[$numUsers-1] < $this->_options['maxUserId']) {
            $uidNumber = ++$allUidNumbers[$numUsers-1];
        } elseif(count($allUidNumbers) < ($this->_options['maxUserId'] - $this->_options['minUserId'])) {
            // maybe there is a gap
            for($i = $this->_options['minUserId']; $i <= $this->_options['maxUserId']; $i++) {
                if(!in_array($i, $allUidNumbers)) {
                    $uidNumber = $i;
                    break;
                }
            }
        }
        
        if($uidNumber === NULL) {
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
    protected function _getUsersFromBackend(Zend_Ldap_Filter $_filter, $_accountClass = 'Tinebase_Model_User')
    {
        throw new RuntimeException('still untested');
        
        $result = new Tinebase_Record_RecordSet($_accountClass);
        
        $accounts = $this->_ldap->search(
            $_filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array_values($this->_rowNameMapping)
        );
        
        foreach ($accounts as $account) {
            $accountObject = $this->_ldap2User($account, $_accountClass);
            
            if ($accountObject) {
                $result->addRecord($accountObject);
            }
            
        }
        
        return $result;
    }
    
    /**
     * Fetches all accounts from backend matching the given filter
     *
     * @param string $_filter
     * @param string $_accountClass
     * @return Tinebase_Record_RecordSet
     */
    protected function _getContactFromBackend(Tinebase_Model_FullUser $_user)
    {
        $userData = $this->_getMetaData($_user);
        
        throw new RuntimeException('still untested');
        $userData = $this->_ldap->getEntry($userData['dn']);
        
        $contact = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL)->getByUserId($_user->getId());
        
        $this->_ldap2Contact($userData, $contact);
        
        return $contact;
    }
    
    /**
     * Returns a user obj with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Abstract
     */
    protected function _ldap2User(array $_userData, $_accountClass)
    {
        // accounts found in ldap tree are always enabled, see comment in setStatus
        $accountArray = array(
            'accountStatus'  => 'enabled'
        );
        
        $errors = false;
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
                    case 'accountPrimaryGroup':
                        try {
                            $accountArray[$keyMapping] = Tinebase_Group::getInstance()->resolveGIdNumberToUUId($value[0]);
                        } catch (Tinebase_Exception_NotFound $e) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Failed to resolve group Id ' . $value[0]);
                            $errors = true;
                        }
                        
                        break;
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        if ($errors) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not instantiate account object for ldap user ' . print_r($_userData, 1));
            $accountObject = null;
        } else {
            $accountObject = new $_accountClass($accountArray);
        }        
        
        return $accountObject;
    }
    
    /**
     * Returns a contact object with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Abstract
     */
    protected function _ldap2Contact($_userData, $_contact)
    {
        $rowNameMapping = array(
            'bday'                  => 'birthdate',
            'tel_cell'              => 'mobile',
            'tel_work'              => 'telephonenumber',
            'tel_home'              => 'homephone',
            'tel_fax'               => 'facsimiletelephonenumber',
            'org_name'              => 'o',
            'org_unit'              => 'ou',
            'email_home'            => 'mozillasecondemail',
            'jpegphoto'             => 'jpegphoto',
            'adr_two_locality'      => 'mozillahomelocalityname',
            'adr_two_postalcode'    => 'mozillahomepostalcode',
            'adr_two_region'        => 'mozillahomestate',
            'adr_two_street'        => 'mozillahomestreet',
            'adr_one_region'        => 'l',
            'adr_one_postalcode'    => 'postalcode',
            'adr_one_street'        => 'street',
            'adr_one_region'        => 'st',
        );
        
        foreach ($_userData as $key => $value) {
            if (is_int($key)) {
                continue;
            }

            $keyMapping = array_search($key, $rowNameMapping);
            
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'bday':
                        $_contact->$keyMapping = new Zend_Date($value[0], 'yyyy-MM-dd');
                        break;
                    default: 
                        $_contact->$keyMapping = $value[0];
                        break;
                }
            }
        }        
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
                    case 'accountPrimaryGroup':
                        $ldapData[$ldapProperty] = Tinebase_Group::getInstance()->resolveUUIdToGIdNumber($value);
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
    
    /**
     * import users from ldap
     * 
     * @param array | optional $_options [options hash passed through the whole setup initialization process]
     *
     */
    public function importUsers($_options = null)
    {
        $sqlGroupBackend = new Tinebase_Group_Sql();
        
        $users = $this->_getUsersFromBackend($this->_userBaseFilter, 'Tinebase_Model_FullUser');
        
        foreach($users as $user) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' user: ' . print_r($user->toArray(), true));
            $user->sanitizeAccountPrimaryGroup();
            $user = $this->_sql->addOrUpdateUser($user);
            if (!$user instanceof Tinebase_Model_FullUser) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not add user "' . $user->accountLoginName . '" => Skipping');
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' classname ' . get_class($user). ' attributes: ' . print_r($user,1));
                continue;
            }
            $sqlGroupBackend->addGroupMember($user->accountPrimaryGroup, $user);
            
            // import contactdata(phone, address, fax, birthday. photo)
            $contact = $this->_getContactFromBackend($user);
            Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL)->update($contact);
        }
    }
    
    public function resolveLdapUIdNumber($_uidNumber)
    {
        if(strtolower($this->_userUUIDAttribute) == 'uidnumber') {
            return $_uidNumber;
        }
        
        throw new RuntimeException('still untested');
        
        $filter = Zend_Ldap_Filter::equals(
            'uidnumber', Zend_Ldap::filterEscape($_uidNumber)
        );
        
        $userId = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_userSearchScope, 
            array($this->_userUUIDAttribute)
        )->getFirst();
        
        return $userId[strtolower($this->_userUUIDAttribute)][0];
    }
}
