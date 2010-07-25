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
class Tinebase_User_Ldap extends Tinebase_User_Sql implements Tinebase_User_Interface_SyncAble
{
    const PLUGIN_SAMBA = 'Tinebase_User_LdapPlugin_Samba';

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * list of plugins 
     * 
     * @var array
     */
    protected $_plugins = array();

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
        parent::__construct();
        
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

        $this->_options = $_options;

        $this->_userUUIDAttribute  = strtolower($this->_options['userUUIDAttribute']);
        $this->_groupUUIDAttribute = strtolower($this->_options['groupUUIDAttribute']);
        $this->_baseDn             = $this->_options['baseDn'];
        $this->_userBaseFilter     = $this->_options['userFilter'];
        $this->_userSearchScope    = $this->_options['userSearchScope'];
        $this->_groupBaseFilter    = $this->_options['groupFilter'];

        $this->_rowNameMapping['accountId'] = $this->_userUUIDAttribute;

        $this->_ldap = new Tinebase_Ldap($this->_options);
        $this->_ldap->bind();

        if(array_key_exists('plugins', $_options)) {
            foreach ($_options['plugins'] as $className) {
                $plugin = new $className($this->_ldap, $this->_options);
                if(! $plugin instanceof Tinebase_User_LdapPlugin_Interface) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . "Skipped plugin $className as it does NOT implement Tinebase_User_LdapPlugin_Interface");
                    continue;
                }
                $this->_plugins[$className] = new $className($this->_ldap, $this->_options);
            }
        }
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
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_userBaseFilter)
        );

        if (!empty($_filter)) {
            $filter = $filter->addFilter(Zend_Ldap_Filter::orFilter(
                Zend_Ldap_Filter::equals($this->_rowNameMapping['accountFirstName'], Zend_Ldap::filterEscape($_filter)),
                Zend_Ldap_Filter::equals($this->_rowNameMapping['accountLastName'], Zend_Ldap::filterEscape($_filter)),
                Zend_Ldap_Filter::equals($this->_rowNameMapping['accountLoginName'], Zend_Ldap::filterEscape($_filter))
            ));
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . "  filterString: " . $filter);

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
        $select = $this->_getUserSelectObject()
            ->limit($_limit, $_start);

        if ($_sort !== NULL) {
            $select->order($this->rowNameMapping[$_sort] . ' ' . $_dir);
        }

        // return only active users, when searching for simple users
        if ($_accountClass == 'Tinebase_Model_User') {
            $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' = ?', 'enabled'));
        }

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
            $this->_baseDn,
            $this->_userSearchScope,
            array_values($this->_rowNameMapping)
        );

        if (count($accounts) !== 1) {
            throw new Tinebase_Exception_NotFound('User with ' . $_property . ' =  ' . $value . ' not found.');
        }

        $result = $this->_ldap2User($accounts->getFirst(), $_accountClass);
        
        // append data from ldap plugins
        foreach ($this->_plugins as $plugin) {
            $plugin->inspectGetUserByProperty($result);
        }
        
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
        $user = parent::getUserByProperty($_property, $_accountId, $_accountClass);

        // append data from ldap plugins
        foreach ($this->_plugins as $plugin) {
            $plugin->inspectGetUserByProperty($user);
        }
        
        return $user;
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
        if (empty($_loginName)) {
            throw new Tinebase_Exception_InvalidArgument('$_loginName can not be empty');
        }

        $user = $this->getFullUserByLoginName($_loginName);
        $metaData = $this->_getMetaData($user);

        $encryptionType = isset($this->_options['pwEncType']) ? $this->_options['pwEncType'] : Tinebase_User_Abstract::ENCRYPT_SSHA;
        $userpassword = $_encrypt ? Tinebase_User_Abstract::encryptPassword($_password, $encryptionType) : $_password;
        $ldapData = array(
            'userpassword'     => $userpassword,
            'shadowlastchange' => floor(Zend_Date::now()->getTimestamp() / 86400)
        );

        foreach ($this->_plugins as $plugin) {
            $plugin->inspectSetPassword($_loginName, $_password, $_encrypt, false, $ldapData);
        }

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
    }

    /**
     * update user status (enabled or disabled)
     *
     * @param   mixed   $_accountId
     * @param   string  $_status
     */
    public function setStatusInSyncBackend($_accountId, $_status)
    {
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

        foreach ($this->_plugins as $plugin) {
            $plugin->inspectStatus($_status, $ldapData);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " {$metaData['dn']}  $ldapData: " . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
    }

    /**
     * sets/unsets expiry date in ldap backend
     *
     * expiryDate is the number of days since Jan 1, 1970
     *
     * @param   mixed      $_accountId
     * @param   Zend_Date  $_expiryDate
     */
    public function setExpiryDateInSyncBackend($_accountId, $_expiryDate)
    {
        $metaData = $this->_getMetaData($_accountId);

        if ($_expiryDate instanceof Zend_Date) {
            // days since Jan 1, 1970
            $ldapData = array('shadowexpire' => floor($_expiryDate->getTimestamp() / 86400));
        } else {
            $ldapData = array('shadowexpire' => array());
        }

        foreach ($this->_plugins as $plugin) {
            $plugin->inspectExpiryDate($_expiryDate, $ldapData);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " {$metaData['dn']}  $ldapData: " . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
    }

    /**
     * sets blocked until date 
     *
     * @param  mixed      $_accountId
     * @param  Zend_Date  $_blockedUntilDate set to NULL to disable blockedDate
    */
    public function setBlockedDateInSyncBackend($_accountId, $_blockedUntilDate)
    {
        // not supported by standart ldap schemas
        $user = $this->getFullUserById($_accountId);
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . "  With ldap user backend, user '{$user->accountLoginName}' could not be blocked until {$_blockedUntilDate}");

        foreach ($this->_plugins as $plugin) {
            $plugin->inspectSetBlocked($_accountId, $_blockedUntilDate);
        }

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
        $metaData = $this->_getMetaData($_account);

        $ldapData = $this->_user2ldap($_account);

        // check if user has all required object classes. This is needed
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $metaData['objectclass'])) {
                $ldapData['objectclass'] = array_unique(array_merge($metaData['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }

        foreach ($this->_plugins as $plugin) {
            $plugin->inspectUpdateUser($_account, $ldapData);
        }

        // no need to update this attribute, it's not allowed to change and even might not be updateable
        unset($ldapData[$this->_userUUIDAttribute]);

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);

        // refetch user from ldap backend
        $user = $this->getUserByPropertyFromSyncBackend('accountId', $_account, 'Tinebase_Model_FullUser');

        return $user;
    }

    /**
     * add an user
     * 
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser
     */
    public function addUserToSyncBackend(Tinebase_Model_FullUser $_user)
    {
        $dn = $this->_generateDn($_user);
        $ldapData = $this->_user2ldap($_user);

        $ldapData['uidnumber'] = $this->_generateUidNumber();
        $ldapData['objectclass'] = $this->_requiredObjectClass;

        foreach ($this->_plugins as $plugin) {
            $plugin->inspectAddUser($_user, $ldapData);
        }

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));

        $this->_ldap->add($dn, $ldapData);

        $userId = $this->_ldap->getEntry($dn, array($this->_userUUIDAttribute));

        $userId = $userId[$this->_userUUIDAttribute][0];

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' new ldap userid: ' . $userId);

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
        $metaData = $this->_getMetaData($_userId);

        // user does not exist in ldap anymore
        if (!empty($metaData['dn'])) {
            $this->_ldap->delete($metaData['dn']);
        }
    }

    /**
     * delete multiple users from ldap only
     *
     * @param array $_accountIds
     */
    public function deleteUsersInSyncBackend(array $_accountIds)
    {
        foreach ($_accountIds as $accountId) {
            $this->deleteUserInSyncBackend($accountId);
        }
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
            throw new Exception("user with userid $_userId not found");
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
        if (isset($this->_options['machineDn'])) {
            $accounts = $this->_ldap->search(
            $filter,
            $this->_options['machineDn'],
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
                        $accountArray[$keyMapping] = new Zend_Date($value[0] * 86400, Zend_Date::TIMESTAMP);
                        break;
                        
                    case 'accountStatus':
                        if (array_key_exists('shadowmax', $_userData) && array_key_exists('shadowinactive', $_userData)) {
                            $lastChange = array_key_exists('shadowlastchange', $_userData) ? $_userData['shadowlastchange'] : 0;
                            if (($lastChange + $_userData['shadowmax'] + $_userData['shadowinactive']) * 86400 <= Zend_Date::now()->getTimestamp()) {
                                $accountArray[$keyMapping] = 'enabled';
                            } else {
                                $accountArray[$keyMapping] = 'disabled';
                            }
                        } else {
                            $accountArray[$keyMapping] = 'enabled';
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
        $ldapData = array();

        foreach ($_user as $key => $value) {
            #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $key => $value");
            $ldapProperty = array_key_exists($key, $this->_rowNameMapping) ? $this->_rowNameMapping[$key] : false;
            #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $ldapProperty");
            if ($ldapProperty) {
                switch ($key) {
                    case 'accountLastPasswordChange':
                        // field is readOnly
                        break;
                    case 'accountExpires':
                        $ldapData[$ldapProperty] = $value instanceof Zend_Date ? floor($value->getTimestamp() / 86400) : array();
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
