<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * ActiveDirectory user backend
 * 
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_ActiveDirectory extends Tinebase_User_Ldap
{    
    /**
     * @var array
     */
    protected $_options = array();
    
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
        'accountId'                 => 'objectGUID',
        'accountDisplayName'        => 'displayName',
        'accountFullName'           => 'cn',
        'accountFirstName'          => 'givenName',
        'accountLastName'           => 'sn',
        'accountLoginName'          => 'sAMAccountName',
        'accountLastPasswordChange' => 'pwdLastSet', 
        'accountExpires'            => 'accountExpires',
        'accountPrimaryGroup'       => 'primaryGroupID',
        'accountEmailAddress'       => 'mail',
        'accountHomeDirectory'      => null, // not available (?)
        'accountLoginShell'         => null, // not available (?)
    );
    
    /**
     * objectclasses required by this backend
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'user'
    );
    
    /**
     * the basic ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_baseFilter      = 'objectclass=user';
    
    /**
     * the query filter for the ldap search (for example uid=%s)
     *
     * @var string
     */
    protected $_queryFilter     = '|(sAMAccountName=%s)(cn=%s)(sn=%s)(givenName=%s)';
    
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
        
        $encryptionType = $this->_options['pwEncType'];
        $userpassword = $_encrypt ? Tinebase_User_Abstract::encryptPassword($_password, $encryptionType) : $_password;
        $ldapData = array(
            'userpassword'     => $userpassword,
            'shadowlastchange' => Zend_Date::now()->getTimestamp()
        );
                
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_backend->update($metaData['dn'], $ldapData);
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $newDn);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        
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
        foreach ($this->_backend->fetchAll($this->_options['userDn'], 'objectclass=posixAccount', array('uidnumber')) as $userData) {
            $allUidNumbers[] = $userData['uidnumber'][0];
        }
        sort($allUidNumbers);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  Existing uidnumbers " . print_r($allUidNumbers, true));
        
        $numUsers = count($allUidNumbers);
        if ($numUsers == 0 || $allUidNumbers[$numUsers-1] < $this->_options['minUserId']) {
            $uidNumber = $this->_options['minUserId'];
        } elseif ($allUidNumbers[$numUsers-1] < $this->_options['maxUserId']) {
            $uidNumber = ++$allUidNumbers[$numUsers-1];
        } else {
            throw new Tinebase_Exception_NotImplemented('Max User Id is reached');
        }
        
        return $uidNumber;
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
                
        return $ldapData;
    }
    
}
