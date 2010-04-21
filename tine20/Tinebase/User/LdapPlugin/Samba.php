<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * plugin to handle sambaSAM attributes
 * 
 * @package Tinebase
 * @subpackage Samba
 */
class Tinebase_User_LdapPlugin_Samba
{

    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * mapping of ldap attributes to class properties
     *
     * @var array
     */
    protected $_rowNameMapping = array(
        'sid'              => 'sambasid', 
        'primaryGroupSID'  => 'sambaprimarygroupsid', 
        'acctFlags'        => 'sambaacctflags',
        'homeDrive'        => 'sambahomedrive',
        'homePath'         => 'sambahomepath',
        'profilePath'      => 'sambaprofilepath',
        'logonScript'      => 'sambalogonscript',    
        'logonTime'        => 'sambalogontime',
        'logoffTime'       => 'sambalogofftime',
        'kickoffTime'      => 'sambakickofftime',
        'pwdLastSet'       => 'sambapwdlastset',
        'pwdCanChange'     => 'sambapwdcanchange',
        'pwdMustChange'    => 'sambapwdmustchange',
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'sambaSamAccount'
    );
    
    /**
     * the constructor
     *
     * @param  Tinebase_Ldap  $_ldap    the ldap resource
     * @param  array          $options  options used in connecting, binding, etc.
     */
    public function __construct(Tinebase_Ldap $_ldap, $_options = null) 
    {
        if (!isset($_options[Tinebase_User_Ldap::PLUGIN_SAMBA]) || empty($_options[Tinebase_User_Ldap::PLUGIN_SAMBA]['sid'])) {
            throw new Exception('you need to configure the sid of the samba installation');
        }
    	
        $this->_ldap    = $_ldap;
        $this->_options = $_options;
    }
    
    /**
     * inspect data used to create user
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    public function inspectAddUser(Tinebase_Model_FullUser $_user, array &$_ldapData)
    {
        $this->_user2ldap($_user, $_ldapData);    
    }
    
    /**
     * inspect data used to update user
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_user, array &$_ldapData)
    {
        $this->_user2ldap($_user, $_ldapData);
    }
    
    public function inspectSetBlocked($_accountId, $_blockedUntilDate)
    {
    	// does nothing
    }
    
    /**
     * inspect set expiry date
     * 
     * @param Zend_Date  $_expiryDate  the expirydate
     * @param array      $_ldapData    the data to be written to ldap
     */
    public function inspectExpiryDate($_expiryDate, array &$_ldapData)
    {
        if ($_expiryDate instanceof Zend_Date) {
            // seconds since Jan 1, 1970
            $_ldapData['sambakickofftime'] = $_expiryDate->getTimestamp();
        } else {
            $_ldapData['sambakickofftime'] = array();
        }
    }
    
    /**
     * inspect setStatus
     * 
     * @param string  $_status    the status
     * @param array   $_ldapData  the data to be written to ldap
     */
    public function inspectStatus($_status, array &$_ldapData)
    {
        $acctFlags = '[U          ]';
        $acctFlags[2] = $_status == 'disabled' ? 'D' : ' ';
        
        $_ldapData['sambaacctflags'] = $acctFlags;    	
    }
    
    /**
     * inspect set password
     * 
     * @param string   $_loginName
     * @param string   $_password
     * @param boolean  $_encrypt
     * @param boolean  $_mustChange
     * @param array    $_ldapData    the data to be written to ldap
     */
    public function inspectSetPassword($_loginName, $_password, $_encrypt, $_mustChange, array &$_ldapData)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ENCRYPT ' . print_r($_loginName, true));
        if ($_encrypt !== true) {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' can not transform crypted password into nt/lm samba password. Make sure to reset password for user ' . $_loginName);
        } else {
            $_ldapData['sambantpassword'] = Tinebase_User_Abstract::encryptPassword($_password, Tinebase_User_Abstract::ENCRYPT_NTPASSWORD);
            $_ldapData['sambalmpassword'] = Tinebase_User_Abstract::encryptPassword($_password, Tinebase_User_Abstract::ENCRYPT_LMPASSWORD);
            $_ldapData['sambapwdlastset'] = Zend_Date::now()->getTimestamp();
            
            if ($_mustChange !== false) {
                $_ldapData['sambapwdmustchange'] = '1';
                $_ldapData['sambapwdcanchange'] = '1';
            } else {
                $_ldapData['sambapwdmustchange'] = '2147483647';
                $_ldapData['sambapwdcanchange'] = '1';
            }
        }
    }
    
    /**
     * inspect get user by property
     * 
     * @param Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user)
    {
    	if ($_user->has('sambaSAM')) {
	        $filter = Zend_Ldap_Filter::equals(
	            $this->_options['userUUIDAttribute'], Zend_Ldap::filterEscape($_user->getId())
	        );
	        
            $accounts = $this->_ldap->search(
	            $filter, 
	            $this->_options['userDn'], 
	            Zend_Ldap::SEARCH_SCOPE_SUB, 
	            array_values($this->_rowNameMapping)
	        );
	        
	        // count can not be 0 under normal conditions
	        if (count($accounts) == 0) {
	            throw new Tinebase_Exception_NotFound('Account not found in LDAP: ' . $filter->toString());
	        }
	        
	        $_user->sambaSAM = $this->_ldap2User($accounts->getFirst());
    	}
    }
    
    /**
     * Returns a user obj with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Abstract
     */
    protected function _ldap2User($_userData, $_accountClass='Tinebase_Model_SAMUser')
    {
        $accountArray = array();
        
        foreach ($_userData as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_rowNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                        $accountArray[$keyMapping] = new Zend_Date($value[0], Zend_Date::TIMESTAMP);
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
     * return uidnumber of user
     * 
     * @param string $_uid
     * @return string
     */
    protected function _getUidNUmber($_uid)
    {
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['userUUIDAttribute'], Zend_Ldap::filterEscape($_uid)
        );
        
        $users = $this->_ldap->search(
            $filter, 
            $this->_options['userDn'], 
            $this->_options['userSearchScope'], 
            array('uidnumber')
        );
        
        if (count($users) == 0) {
            throw new Tinebase_Exception_NotFound('User not found! Filter: ' . $filter->toString());
        }
        
        $user = $users->getFirst();
        
        if (empty($user['uidnumber'][0])) {
            throw new Tinebase_Exception_NotFound('User has no uidnumber');
        }
        
        return $user['uidnumber'][0];
    }
    
    /**
     * return sid of group
     * 
     * @param string  $_groupId
     * @return string the sid of the group 
     */
    protected function _getGroupSID($_groupId)
    {
        $filter = Zend_Ldap_Filter::equals(
            $this->_options['groupUUIDAttribute'], Zend_Ldap::filterEscape($_groupId)
        );
        
        $groups = $this->_ldap->search(
            $filter, 
            $this->_options['groupsDn'], 
            Zend_Ldap::SEARCH_SCOPE_SUB, 
            array('sambasid')
        );
        
        if (count($groups) == 0) {
            throw new Tinebase_Exception_NotFound('Group not found! Filter: ' . $filter->toString());
        }
        
        $group = $groups->getFirst();
        
        if (empty($group['sambasid'][0])) {
            throw new Tinebase_Exception_NotFound('Group has no sambaSID');
        }
        
        return $group['sambasid'][0];
    }
        
    /**
     * convert objects with user data to ldap data array
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    protected function _user2ldap(Tinebase_Model_FullUser $_user, array &$_ldapData)
    {
        if (isset($_ldapData['objectclass'])) {
            $_ldapData['objectclass'] = array_unique(array_merge($_ldapData['objectclass'], $this->_requiredObjectClass));
        }
        if (isset($_ldapData['uidnumber'])) {
            $uidNumber = $_ldapData['uidnumber'];
        } else {
            $uidNumber = $this->_getUidNUmber($_user->getId());
        }
        
        $this->inspectExpiryDate(isset($_user->accountExpires) ? $_user->accountExpires : null, $_ldapData);
        $this->inspectStatus($_user->accountStatus, $_ldapData);
        
        // defaults
        $_ldapData['sambasid']             = $this->_options[Tinebase_User_Ldap::PLUGIN_SAMBA]['sid'] . '-' . (2 * $uidNumber + 1000);
        $_ldapData['sambapwdcanchange']    = 1;
        $_ldapData['sambapwdmustchange']   = 2147483647;
        $_ldapData['sambaprimarygroupsid'] = $this->_getGroupSID($_user->accountPrimaryGroup);
        
        foreach ($_user->sambaSAM as $key => $value) {
            if (array_key_exists($key, $this->_rowNameMapping)) {
                switch ($key) {
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                    case 'acctFlags':
                    case 'sid':
                    case 'primaryGroupSID':
                        // do nothing
                        break;
                        
                    default:
                        $_ldapData[$this->_rowNameMapping[$key]] = $value;
                        break;
                }
            }
        }
    }
}  
