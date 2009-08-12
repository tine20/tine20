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
 * @todo        test it!
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
     * ldap / email user options array
     *
     * @var array
     */
    protected $_options = array();
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_EmailUser
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        if (! isset(Tinebase_Core::getConfig()->accounts)) {
            throw new Tinebase_Exception('No LDAP config found.');
        }
        $ldapOptions = Tinebase_Core::getConfig()->accounts->get('ldap')->toArray();
        $emailOptions = Tinebase_Core::getConfig()->emailUser->toArray();
        $this->_options = array_merge($ldapOptions, $emailOptions);
                
        $this->_ldap = new Tinebase_Ldap($this->_options);
        $this->_ldap->bind();
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_EmailUser
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_EmailUser();
        }
        return self::$instance;
    }
    
    /**
     * get user by id
     *
     * @param   int         $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    public function getUserById($_userId) 
    {
        // @todo remove that later
        /*
        return new Tinebase_Model_EmailUser(array(
            'emailUID'      => 'uid',
            'emailGID'      => 'gid',
            'emailQuota'    => 10000,
        ));
        */
        
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
     * @todo add defaults?
     */
	public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_emailUser);
        
        $ldapData['objectclass'] = array_unique(array_merge($metaData['objectClass'], $this->_requiredUserObjectClass));
                
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        
        return $this->getUserById($_user->getId());
	}
	
	/**
     * updates sam properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
	public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser)
	{
	    // @todo remove that later
	    /*
	    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_emailUser->toArray(), TRUE));
	    return $this->getUserById($_user->getId());
        */
	    
        $metaData = $this->_getUserMetaData($_user);
        $ldapData = $this->_user2ldap($_emailUser);
        
        // check if user has all required object classes.
        foreach ($this->_requiredUserObjectClass as $className) {
            if (! in_array($className, $metaData['objectClass'])) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn'] . ' had no email objectclass.');

                return $this->addUser($_user, $_emailUser);
            }
        }

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));
        
        $this->_ldap->update($metaData['dn'], $ldapData);
        
        return $this->getUserById($_user->getId());
	}

    /**
     * get metatada of existing account
     *
     * @param  int         $_userId
     * @return string 
     */
    protected function _getUserMetaData($_userId)
    {
        $userId = Tinebase_Model_User::convertUserIdToInt($_userId);
        $result = $this->_ldap->getMetaData($this->_options['userDn'], 'uidnumber=' . $userId);
        return $result;
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
