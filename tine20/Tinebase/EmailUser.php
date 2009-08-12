<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Ldap
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        make it work
 * @todo        add forward / alias
 * @todo        add support for qmail
 * @todo        add factory / different backends?
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail attributes in ldap backend
 * 
 * @package Tinebase
 * @subpackage Ldap
 */
class Tinebase_EmailUser
{

    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'emailUID'      => 'dbmailUID', 
        'emailGID'      => 'dbmailGID', 
        'emailQuota'    => 'mailQuota',
        //'emailAliases'  => 'alias',
        //'emailForward'  => 'forward',
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'dbmailUser',
    );
    
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     */
    public function __construct() 
    {
        $ldapOptions = Tinebase_Core::getConfig()->accounts->get('ldap')->toArray();
        $emailOptions = Tinebase_Core::getConfig()->emailUser->toArray();
        $options = array_merge($ldapOptions, $emailOptions);
                
        $this->_ldap = new Tinebase_Ldap($options);
        $this->_ldap->bind();
    }
    
    /**
     * get user by id
     *
     * @param   int         $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    public function getUserById($_userId) 
    {
        try {
            $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
            $ldapData = $this->_ldap->fetch($this->_options['userDn'], 'uidnumber=' . $userId);
            $user = $this->_ldap2User($ldapData);
        } catch (Exception $e) {
            throw new Exception('User not found');
        }
        
        return $user;
    }

    /**
     * adds sam properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     * @todo    implement
     */
	public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    /*
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_emailUser);
        
        $ldapData['objectclass'] = array_unique(array_merge($metaData['objectClass'], $this->_requiredUserObjectClass));
        
        // defaults
        $ldapData['sambasid'] = $this->_options['sid'] . '-' . (2 * $_user->getId() + 1000);
        $ldapData['sambaacctflags'] = (isset($ldapData['sambaacctflags']) && !empty($ldapData['sambaacctflags'])) ? $ldapData['sambaacctflags'] : '[U          ]';
        $ldapData['sambapwdcanchange']	= isset($ldapData['sambapwdcanchange'])  ? $ldapData['sambapwdcanchange']  : 0;
        $ldapData['sambapwdmustchange']	= isset($ldapData['sambapwdmustchange']) ? $ldapData['sambapwdmustchange'] : 2147483647;

        $ldapData['sambaprimarygroupsid'] = $this->getGroupById($_user->accountPrimaryGroup)->sid;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        */
        
        return $this->getUserById($_user->getId());
	}
	
	/**
     * updates sam properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     * @todo    implement
     */
	public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    /*
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_emailUser);
        
        // check if user has all required object classes.
        foreach ($this->_requiredUserObjectClass as $className) {
            if (! in_array($className, $metaData['objectClass'])) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn'] . ' had no samba account. Make shure to reset the users password!');

                return $this->addUser($_user, $_emailUser);
            }
        }

        $ldapData['sambaprimarygroupsid'] = $this->getGroupById($_user->accountPrimaryGroup)->sid;

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        */
        
        return $this->getUserById($_user->getId());
	}

    /**
     * get metatada of existing account
     *
     * @param  int         $_userId
     * @return string 
     * 
     * @todo check if this is needed
     */
    protected function _getUserMetaData($_userId)
    {
        /*
        $metaData = array();
        
        try {
            $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
            $account = $this->_ldap->fetch($this->_options['userDn'], 'uidnumber=' . $userId, array('objectclass'));
            $metaData['dn'] = $account['dn'];
            
            $metaData['objectClass'] = $account['objectclass'];
            unset($metaData['objectClass']['count']);
            
        } catch (Tinebase_Exception_NotFound $enf) {
            throw new Exception("account with id $userId not found");
        }
        
        return $metaData;

        */
    }
    
    /**
     * Fetches all accounts from backend matching the given filter
     *
     * @param string $_filter
     * @param string $_accountClass
     * @return Tinebase_Record_RecordSet
     * 
     * @todo check if this is needed
     */
    protected function _getUsersFromBackend($_filter, $_accountClass = 'Tinebase_Model_EmailUser')
    {
        /*
        $result = new Tinebase_Record_RecordSet($_accountClass);
        $accounts = $this->_ldap->fetchAll($this->_options['userDn'], $_filter, array_values($this->_userPropertyNameMapping));
        
        foreach ($accounts as $account) {
            $accountObject = $this->_ldap2User($account, $_accountClass);
            
            $result->addRecord($accountObject);
        }
        
        return $result;
        */
    }
    
    /**
     * Returns a user obj with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Abstract
     * 
     * @todo add generic function for this?
     */
    protected function _ldap2User($_userData, $_accountClass = 'Tinebase_Model_EmailUser')
    {
        $accountArray = array();
        
        foreach ($_userData as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_userPropertyNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    /*
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                        $accountArray[$keyMapping] = new Zend_Date($value[0], Zend_Date::TIMESTAMP);
                        break;
                    */
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
     * @param  Tinebase_Model_EmailUser $_user
     * @return array
     * 
     * @todo add generic function for this?
     */
    protected function _user2ldap(Tinebase_Model_EmailUser $_user)
    {
        $ldapData = array();
        foreach ($_user as $key => $value) {
            $ldapProperty = array_key_exists($key, $this->_userPropertyNameMapping) ? $this->_userPropertyNameMapping[$key] : false;
            if ($ldapProperty) {
                switch ($key) {
                    /*
                    case 'pwdLastSet':
                    case 'logonTime':
                    case 'logoffTime':
                    case 'kickoffTime':
                    case 'pwdCanChange':
                    case 'pwdMustChange':
                        $ldapData[$ldapProperty] = $value instanceof Zend_Date ? $value->getTimestamp() : '';
                        break;
                    */
                    default:
                        $ldapData[$ldapProperty] = $value;
                        break;
                }
            }
        }
        
        return $ldapData;
    }
}  
