<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * User ldap backend
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_Ldap extends Tinebase_User_Sql implements Tinebase_User_Interface_SyncAble
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
     * mapping of ldap attributes to class properties
     *
     * @var array
     */
    protected $_rowNameMapping = array(
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
        'accountStatus'             => 'shadowinactive'
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
     * the base dn to work on (defaults to to userDn, but can also be machineDn)
     *
     * @var string
     */
    protected $_baseDn;

    /**
     * the basic group ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_groupBaseFilter = 'objectclass=posixgroup';

    /**
     * the basic user ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_userBaseFilter = 'objectclass=posixaccount';

    /**
     * the basic user search scope
     *
     * @var integer
     */
    protected $_userSearchScope = Zend_Ldap::SEARCH_SCOPE_SUB;

    protected $_ldapPlugins = array();
    
    protected $_isReadOnlyBackend     = false;
    
    /**
     * the constructor
     *
     * @param  array  $_options  Options used in connecting, binding, etc.
     * @throws Tinebase_Exception_Backend_Ldap
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        if(empty($_options['userUUIDAttribute'])) {
            $_options['userUUIDAttribute'] = 'entryUUID';
        }
        if(empty($_options['groupUUIDAttribute'])) {
            $_options['groupUUIDAttribute'] = 'entryUUID';
        }
        if(empty($_options['baseDn'])) {
            $_options['baseDn'] = $_options['userDn'];
        }
        if(empty($_options['userFilter'])) {
            $_options['userFilter'] = 'objectclass=posixaccount';
        }
        if(empty($_options['userSearchScope'])) {
            $_options['userSearchScope'] = Zend_Ldap::SEARCH_SCOPE_SUB;
        }
        if(empty($_options['groupFilter'])) {
            $_options['groupFilter'] = 'objectclass=posixgroup';
        }

        if (isset($_options['requiredObjectClass'])) {
            $this->_requiredObjectClass = (array)$_options['requiredObjectClass'];
        }
        if (array_key_exists('readonly', $_options)) {
            $this->_isReadOnlyBackend = (bool)$_options['readonly'];
        }
        if (array_key_exists('ldap', $_options)) {
            $this->_ldap = $_options['ldap'];
        }
        
        $this->_options = $_options;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Registering " . print_r($this->_options, true));
        
        $this->_userUUIDAttribute  = strtolower($this->_options['userUUIDAttribute']);
        $this->_groupUUIDAttribute = strtolower($this->_options['groupUUIDAttribute']);
        $this->_baseDn             = $this->_options['baseDn'];
        $this->_userBaseFilter     = $this->_options['userFilter'];
        $this->_userSearchScope    = $this->_options['userSearchScope'];
        $this->_groupBaseFilter    = $this->_options['groupFilter'];

        $this->_rowNameMapping['accountId'] = $this->_userUUIDAttribute;
        
        if (! $this->_ldap instanceof Tinebase_Ldap) {
            $this->_ldap = new Tinebase_Ldap($this->_options);
            try {
                $this->_ldap->bind();
            } catch (Zend_Ldap_Exception $zle) {
                // @todo move this to Tinebase_Ldap?
                throw new Tinebase_Exception_Backend_Ldap('Could not bind to LDAP: ' . $zle->getMessage());
            }
        }
        
        foreach ($this->_plugins as $plugin) {
            if ($plugin instanceof Tinebase_User_Plugin_LdapInterface) {
                $this->registerLdapPlugin($plugin);
            }
        }
    }

    /**
     * register ldap plugin
     * 
     * @param Tinebase_User_Plugin_LdapInterface $plugin
     */
    public function registerLdapPlugin(Tinebase_User_Plugin_LdapInterface $plugin)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Registering " . get_class($plugin) . ' LDAP plugin.');
        
        $plugin->setLdap($this->_ldap);
        $this->_ldapPlugins[] = $plugin;
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
    public function getUsersFromSyncBackend($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Model_User')
    {
        $filter = $this->_getBaseFilter();

        if (!empty($_filter)) {
            $filter = $filter->addFilter(Zend_Ldap_Filter::orFilter(
                Zend_Ldap_Filter::contains($this->_rowNameMapping['accountFirstName'], Zend_Ldap::filterEscape($_filter)),
                Zend_Ldap_Filter::contains($this->_rowNameMapping['accountLastName'], Zend_Ldap::filterEscape($_filter)),
                Zend_Ldap_Filter::contains($this->_rowNameMapping['accountLoginName'], Zend_Ldap::filterEscape($_filter))
            ));
        }

        $accounts = $this->_ldap->search(
            $filter,
            $this->_baseDn,
            $this->_userSearchScope,
            array_values($this->_rowNameMapping),
            $_sort !== null ? $this->_rowNameMapping[$_sort] : null
        );
        
        $result = new Tinebase_Record_RecordSet($_accountClass);

        // nothing to be done anymore
        if (count($accounts) == 0) {
            return $result;
        }

        foreach ($accounts as $account) {
            $accountObject = $this->_ldap2User($account, $_accountClass);
            
            if ($accountObject) {
                $result->addRecord($accountObject);
            }

        }

        return $result;

        // @todo implement limit, start, dir and status
//         $select = $this->_getUserSelectObject()
//             ->limit($_limit, $_start);

//         if ($_sort !== NULL) {
//             $select->order($this->rowNameMapping[$_sort] . ' ' . $_dir);
//         }

//         // return only active users, when searching for simple users
//         if ($_accountClass == 'Tinebase_Model_User') {
//             $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' = ?', 'enabled'));
//         }
    }
    
    /**
     * returns user base filter
     * 
     * @return Zend_Ldap_Filter_And
     */
    protected function _getBaseFilter()
    {
        return Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_userBaseFilter)
        );
    }
    
    /**
     * search for user attributes
     * 
     * @param array $attributes
     * @return array
     * 
     * @todo allow multi value attributes
     * @todo generalize this for usage in other Tinebase_User_Ldap fns?
     */
    public function getUserAttributes($attributes)
    {
        $ldapCollection = $this->_ldap->search(
            $this->_getBaseFilter(),
            $this->_baseDn,
            $this->_userSearchScope,
            $attributes
        );
        
        $result = array();
        foreach ($ldapCollection as $data) {
            $row = array('dn' => $data['dn']);
            foreach ($attributes as $key) {
                $lowerKey = strtolower($key);
                if (isset($data[$lowerKey]) && isset($data[$lowerKey][0])) {
                    $row[$key] = $data[$lowerKey][0];
                }
            }
            $result[] = $row;
        }
        
        return (array)$result;
    }
    
    /**
     * fetch LDAP backend 
     * 
     * @return Tinebase_Ldap
     */
    public function getLdap()
    {
        return $this->_ldap;
    }

    /**
     * get user by login name
     *
     * @param   string  $_property
     * @param   string  $_accountId
     * @return Tinebase_Model_User the user object
     */
    public function getUserByPropertyFromSyncBackend($_property, $_accountId, $_accountClass = 'Tinebase_Model_User')
    {
        if (!array_key_exists($_property, $this->_rowNameMapping)) {
            throw new Tinebase_Exception_NotFound("can't get user by property $_property. property not supported by ldap backend.");
        }

        $ldapEntry = $this->_getLdapEntry($_property, $_accountId);
        
        $user = $this->_ldap2User($ldapEntry, $_accountClass);
        
        // append data from ldap plugins
        foreach ($this->_ldapPlugins as $class => $plugin) {
            $plugin->inspectGetUserByProperty($user, $ldapEntry);
        }
        
        return $user;
    }

    /**
     * set the password for given account
     *
     * @param   string  $_userId
     * @param   string  $_password
     * @param   bool    $_encrypt encrypt password
     * @param   bool    $_mustChange
     * @return  void
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_userId, $_password, $_encrypt = TRUE, $_mustChange = null)
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        $user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : $this->getFullUserById($_userId);
        
        $this->checkPasswordPolicy($_password, $user);
        
        $metaData = $this->_getMetaData($user);

        $encryptionType = isset($this->_options['pwEncType']) ? $this->_options['pwEncType'] : Tinebase_User_Abstract::ENCRYPT_SSHA;
        $userpassword = $_encrypt ? Hash_Password::generate($encryptionType, $_password) : $_password;
        
        $ldapData = array(
            'userpassword'     => $userpassword,
            'shadowlastchange' => floor(Tinebase_DateTime::now()->getTimestamp() / 86400)
        );

        foreach ($this->_ldapPlugins as $plugin) {
            $plugin->inspectSetPassword($user, $_password, $_encrypt, $_mustChange, $ldapData);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
        
        // update last modify timestamp in sql backend too
        $values = array(
            'last_password_change' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
        );
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $user->getId())
        );
        
        $this->_db->update(SQL_TABLE_PREFIX . 'accounts', $values, $where);
        
        $this->_setPluginsPassword($user->getId(), $_password, $_encrypt);
    }

    /**
     * update user status (enabled or disabled)
     *
     * @param   mixed   $_accountId
     * @param   string  $_status
     */
    public function setStatusInSyncBackend($_accountId, $_status)
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        $metaData = $this->_getMetaData($_accountId);

        if ($_status == 'disabled') {
            $ldapData = array(
                'shadowMax'      => 1,
                'shadowInactive' => 1
            );
        } else {
            $ldapData = array(
                'shadowMax'      => 999999,
                'shadowInactive' => array()
            );
        }

        foreach ($this->_ldapPlugins as $plugin) {
            $plugin->inspectStatus($_status, $ldapData);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " {$metaData['dn']}  $ldapData: " . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
    }

    /**
     * sets/unsets expiry date in ldap backend
     *
     * expiryDate is the number of days since Jan 1, 1970
     *
     * @param   mixed      $_accountId
     * @param   Tinebase_DateTime  $_expiryDate
     */
    public function setExpiryDateInSyncBackend($_accountId, $_expiryDate)
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        $metaData = $this->_getMetaData($_accountId);

        if ($_expiryDate instanceof DateTime) {
            // days since Jan 1, 1970
            $ldapData = array('shadowexpire' => floor($_expiryDate->getTimestamp() / 86400));
        } else {
            $ldapData = array('shadowexpire' => array());
        }

        foreach ($this->_ldapPlugins as $plugin) {
            $plugin->inspectExpiryDate($_expiryDate, $ldapData);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " {$metaData['dn']}  $ldapData: " . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
    }

    /**
     * updates an existing user
     *
     * @todo check required objectclasses?
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function updateUserInSyncBackend(Tinebase_Model_FullUser $_account)
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        $ldapEntry = $this->_getLdapEntry('accountId', $_account);

        $ldapData = $this->_user2ldap($_account, $ldapEntry);
        
        foreach ($this->_ldapPlugins as $plugin) {
            $plugin->inspectUpdateUser($_account, $ldapData, $ldapEntry);
        }

        // no need to update this attribute, it's not allowed to change and even might not be updateable
        unset($ldapData[$this->_userUUIDAttribute]);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $ldapEntry['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));

        $this->_ldap->update($ldapEntry['dn'], $ldapData);
        
        $dn = Zend_Ldap_Dn::factory($ldapEntry['dn'], null);
        $rdn = $dn->getRdn();
        
        // do we need to rename the entry?
        if (isset($ldapData[key($rdn)]) && $rdn[key($rdn)] != $ldapData[key($rdn)]) {
            $groupsBackend = Tinebase_Group::factory(Tinebase_Group::LDAP);
            
            // get the current groupmemberships
            $memberships = $groupsBackend->getGroupMembershipsFromSyncBackend($_account);
            
            // remove the user from current groups, because the dn/uid has changed
            foreach ($memberships as $groupId) {
                $groupsBackend->removeGroupMemberInSyncBackend($groupId, $_account);
            }
            
            $newDN = $this->_generateDn($_account);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  rename ldap entry to: ' . $newDN);
            $this->_ldap->rename($dn, $newDN);
            
            // add the user to current groups again
            foreach ($memberships as $groupId) {
                $groupsBackend->addGroupMemberInSyncBackend($groupId, $_account);
            }
        }
        
        // refetch user from ldap backend
        $user = $this->getUserByPropertyFromSyncBackend('accountId', $_account, 'Tinebase_Model_FullUser');

        return $user;
    }

    /**
     * add an user
     * 
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser|NULL
     */
    public function addUserToSyncBackend(Tinebase_Model_FullUser $_user)
    {
        if ($this->_isReadOnlyBackend) {
            return NULL;
        }
        
        $ldapData = $this->_user2ldap($_user);

        $ldapData['uidnumber'] = $this->_generateUidNumber();
        $ldapData['objectclass'] = $this->_requiredObjectClass;

        foreach ($this->_ldapPlugins as $plugin) {
            $plugin->inspectAddUser($_user, $ldapData);
        }

        $dn = $this->_generateDn($_user);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  ldapData: ' . print_r($ldapData, true));

        $this->_ldap->add($dn, $ldapData);

        $userId = $this->_ldap->getEntry($dn, array($this->_userUUIDAttribute));

        $userId = $userId[$this->_userUUIDAttribute][0];

        $user = $this->getUserByPropertyFromSyncBackend('accountId', $userId, 'Tinebase_Model_FullUser');

        return $user;
    }

    /**
     * delete an user in ldap backend
     *
     * @param int $_userId
     */
    public function deleteUserInSyncBackend($_userId)
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        try {
            $metaData = $this->_getMetaData($_userId);
    
            // user does not exist in ldap anymore
            if (!empty($metaData['dn'])) {
                $this->_ldap->delete($metaData['dn']);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' user not found in sync backend: ' . $_userId);
        }
    }

    /**
     * delete multiple users from ldap only
     *
     * @param array $_accountIds
     */
    public function deleteUsersInSyncBackend(array $_accountIds)
    {
        if ($this->_isReadOnlyBackend) {
            return;
        }
        
        foreach ($_accountIds as $accountId) {
            $this->deleteUserInSyncBackend($accountId);
        }
    }

    /**
     * return ldap entry of user
     * 
     * @param string $_uid
     * @return array
     */
    protected function _getLdapEntry($_property, $_userId)
    {
        switch($_property) {
            case 'accountId':
                $value = Tinebase_Model_User::convertUserIdToInt($_userId);
                break;
            default:
                $value = $_userId;
                break;
        }

        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_userBaseFilter),
            Zend_Ldap_Filter::equals($this->_rowNameMapping[$_property], Zend_Ldap::filterEscape($value))
        );
        
        $attributes = array_values($this->_rowNameMapping);
        foreach ($this->_ldapPlugins as $plugin) {
            $attributes = array_merge($attributes, $plugin->getSupportedAttributes());
        }
        $attributes[] = 'objectclass';
        $attributes[] = 'uidnumber';
        $attributes[] = 'useraccountcontrol';
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filter ' . $filter);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' requested attributes ' . print_r($attributes, true));
        
        $accounts = $this->_ldap->search(
            $filter,
            $this->_baseDn,
            $this->_userSearchScope,
            $attributes
        );
        
        if (count($accounts) !== 1) {
            throw new Tinebase_Exception_NotFound('User with ' . $_property . ' =  ' . $value . ' not found.');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' current ldap values ' . print_r($accounts->getFirst(), true));
        
        return $accounts->getFirst();
    }
    
    /**
     * get metatada of existing user
     *
     * @param  string  $_userId
     * @return array
     */
    protected function _getMetaData($_userId)
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);

        $filter = Zend_Ldap_Filter::equals(
            $this->_rowNameMapping['accountId'], Zend_Ldap::filterEscape($userId)
        );

        $result = $this->_ldap->search(
            $filter,
            $this->_baseDn,
            $this->_userSearchScope
        );

        if (count($result) !== 1) {
            throw new Tinebase_Exception_NotFound("user with userid $_userId not found");
        }

        return $result->getFirst();
    }

    /**
     * generates dn for new user
     *
     * @param  Tinebase_Model_FullUser $_account
     * @return string
     */
    protected function _generateDn(Tinebase_Model_FullUser $_account)
    {
        $baseDn = $this->_baseDn;

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
            $allUidNumbers[$userData['uidnumber'][0]] = $userData['uidnumber'][0];
        }

        // fetch also the uidnumbers of machine accounts, if needed
        // @todo move this to samba plugin
        if (isset(Tinebase_Core::getConfig()->samba) && Tinebase_Core::getConfig()->samba->get('manageSAM', FALSE) == true) {
            $accounts = $this->_ldap->search(
                $filter,
                Tinebase_Core::getConfig()->samba->get('machineDn'),
                Zend_Ldap::SEARCH_SCOPE_SUB,
                array('uidnumber')
            );

            foreach ($accounts as $userData) {
                $allUidNumbers[$userData['uidnumber'][0]] = $userData['uidnumber'][0];
            }
        }
        sort($allUidNumbers);

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing uidnumbers " . print_r($allUidNumbers, true));
        $minUidNumber = isset($this->_options['minUserId']) ? $this->_options['minUserId'] : 1000;
        $maxUidNumber = isset($this->_options['maxUserId']) ? $this->_options['maxUserId'] : 65000;

        $numUsers = count($allUidNumbers);
        if ($numUsers == 0 || $allUidNumbers[$numUsers-1] < $minUidNumber) {
            $uidNumber = $minUidNumber;
        } elseif ($allUidNumbers[$numUsers-1] < $maxUidNumber) {
            $uidNumber = ++$allUidNumbers[$numUsers-1];
        } elseif (count($allUidNumbers) < ($maxUidNumber - $minUidNumber)) {
            // maybe there is a gap
            for($i = $minUidNumber; $i <= $maxUidNumber; $i++) {
                if (!in_array($i, $allUidNumbers)) {
                    $uidNumber = $i;
                    break;
                }
            }
        }

        if ($uidNumber === NULL) {
            throw new Tinebase_Exception_NotImplemented('Max User Id is reached');
        }

        return $uidNumber;
    }

    /**
     * return contact information for user
     *
     * @param  Tinebase_Model_FullUser    $_user
     * @param  Addressbook_Model_Contact  $_contact
     */
    public function updateContactFromSyncBackend(Tinebase_Model_FullUser $_user, Addressbook_Model_Contact $_contact)
    {
        $userData = $this->_getMetaData($_user);

        $userData = $this->_ldap->getEntry($userData['dn']);
        
        $this->_ldap2Contact($userData, $_contact);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  synced user object: " . print_r($_contact->toArray(), true));
    }
    
    /**
     * update contact data(first name, last name, ...) of user
     * 
     * @param Addressbook_Model_Contact $contact
     * @todo implement logic
     */
    public function updateContactInSyncBackend($_contact)
    {
        
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
                        $accountArray[$keyMapping] = new Tinebase_DateTime($value[0] * 86400);
                        break;
                        
                    case 'accountStatus':
                        if (array_key_exists('shadowmax', $_userData) && array_key_exists('shadowinactive', $_userData)) {
                            $lastChange = array_key_exists('shadowlastchange', $_userData) ? $_userData['shadowlastchange'] : 0;
                            if (($lastChange + $_userData['shadowmax'] + $_userData['shadowinactive']) * 86400 <= Tinebase_DateTime::now()->getTimestamp()) {
                                $accountArray[$keyMapping] = 'enabled';
                            } else {
                                $accountArray[$keyMapping] = 'disabled';
                            }
                        } else {
                            $accountArray[$keyMapping] = 'enabled';
                        }
                        break;

                    case 'accountId':
                        $accountArray[$keyMapping] = $this->_decodeAccountId($value[0]);
                        
                        break;
                        
                    default:
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }

        if (empty($accountArray['accountLastName']) && !empty($accountArray['accountFullName'])) {
            $accountArray['accountLastName'] = $accountArray['accountFullName'];
        }
        if ($errors) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not instantiate account object for ldap user ' . print_r($_userData, 1));
            $accountObject = null;
        } else {
            $accountObject = new $_accountClass($accountArray, TRUE);
        }

        return $accountObject;
    }
    
    /**
     * helper function to be overwriten in subclasses
     * 
     * @param  string  $accountId
     * @return string
     */
    protected function _decodeAccountId($accountId)
    {
        return $accountId;
    }

    /**
     * parse ldap result set and update Addressbook_Model_Contact
     *
     * @param array                      $_userData
     * @param Addressbook_Model_Contact  $_contact
     */
    protected function _ldap2Contact($_userData, Addressbook_Model_Contact $_contact)
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
                        $_contact->$keyMapping = Tinebase_DateTime::createFromFormat('Y-m-d', $value[0]);
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
    protected function _user2ldap(Tinebase_Model_FullUser $_user, array $_ldapEntry = array())
    {
        $ldapData = array();

        foreach ($_user as $key => $value) {
            $ldapProperty = array_key_exists($key, $this->_rowNameMapping) ? $this->_rowNameMapping[$key] : false;

            if ($ldapProperty) {
                switch ($key) {
                    case 'accountLastPasswordChange':
                        // field is readOnly
                        break;
                    case 'accountExpires':
                        $ldapData[$ldapProperty] = $value instanceof DateTime ? floor($value->getTimestamp() / 86400) : array();
                        break;
                    case 'accountStatus':
                        if ($value == 'enabled') {
                            $ldapData['shadowMax']      = 999999;
                            $ldapData['shadowInactive'] = array();
                        } else {
                            $ldapData['shadowMax']      = 1;
                            $ldapData['shadowInactive'] = 1;
                        }
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
        
        $ldapData['objectclass'] = isset($_ldapEntry['objectclass']) ? $_ldapEntry['objectclass'] : array();
        
        // check if user has all required object classes. This is needed
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $ldapData['objectclass'])) {
                // merge all required classes at once
                $ldapData['objectclass'] = array_unique(array_merge($ldapData['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' LDAP data ' . print_r($ldapData, true));
        
        return $ldapData;
    }

    public function resolveUIdNumberToUUId($_uidNumber)
    {
        if ($this->_userUUIDAttribute == 'uidnumber') {
            return $_uidNumber;
        }

        $filter = Zend_Ldap_Filter::equals(
            'uidnumber', Zend_Ldap::filterEscape($_uidNumber)
        );

        $userId = $this->_ldap->search(
            $filter,
            $this->_baseDn,
            $this->_userSearchScope,
            array($this->_userUUIDAttribute)
        )->getFirst();

        return $userId[$this->_userUUIDAttribute][0];
    }

    /**
     * resolve UUID(for example entryUUID) to uidnumber
     *
     * @param string $_uuid
     * @return string
     */
    public function resolveUUIdToUIdNumber($_uuid)
    {
        if ($this->_groupUUIDAttribute == 'uidnumber') {
            return $_uuid;
        }

        $filter = Zend_Ldap_Filter::equals(
            $this->_userUUIDAttribute, Zend_Ldap::filterEscape($_uuid)
        );

        $groupId = $this->_ldap->search(
            $filter,
            $this->_options['userDn'],
            $this->_userSearchScope,
            array('uidnumber')
        )->getFirst();

        return $groupId['uidnumber'][0];
    }
}
