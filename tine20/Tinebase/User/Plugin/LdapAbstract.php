<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * base class for ldap user plugins
 * 
 * @package Tinebase
 * @subpackage User
 */
abstract class Tinebase_User_Plugin_LdapAbstract implements Tinebase_User_Plugin_LdapInterface
{
    /**
     * @var Tinebase_Ldap
     */
    protected $_ldap = NULL;

    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_propertyMapping = array();
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array();
    
    /**
     * ldap / email user options array
     *
     * @var array
     */
    protected $_options = array();
    
    /**
     * the constructor
     *
     */
    public function __construct(array $_options = array())
    {
        if (Tinebase_User::getConfiguredBackend() != Tinebase_User::LDAP) {
            throw new Tinebase_Exception('No LDAP config found.');
        }
        
        $this->_options = array_merge(
            $this->_options,
            Tinebase_EmailUser::getConfig($this instanceof Tinebase_EmailUser_Imap_Interface ? Tinebase_Config::IMAP : Tinebase_Config::SMTP)
        );
        
        $this->_options = array_merge($this->_options, $_options);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_options, true));
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_User_Plugin_LdapInterface::getSupportedAttributes()
     */
    public function getSupportedAttributes()
    {
        return array_values($this->_propertyMapping);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_User_Plugin_LdapAbstract::inspectAddUser()
     */
    public function inspectAddUser(Tinebase_Model_FullUser $_user, array &$_ldapData)
    {
        $this->_user2ldap($_user, $_ldapData);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_User_Plugin_LdapAbstract::inspectGetUserByProperty()
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user, array &$_ldapEntry)
    {
        if (! $_user instanceof Tinebase_Model_FullUser) {
            return;
        }

        $emailUser = $this->_ldap2User($_user, $_ldapEntry);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUser->toArray(), TRUE));
        
        // modify/correct user name
        // set emailUsername to Tine 2.0 account login name and append domain for login purposes if set
        if (empty($emailUser->emailUsername)) {
            $emailUser->emailUsername = $this->_getEmailUserName($_user);
        }
        
        if ($this instanceof Tinebase_EmailUser_Smtp_Interface) {
            $_user->smtpUser  = $emailUser;
            $_user->emailUser = Tinebase_EmailUser::merge($_user->emailUser, clone $_user->smtpUser);
        } else {
            $_user->imapUser  = $emailUser;
            $_user->emailUser = Tinebase_EmailUser::merge(clone $_user->imapUser, $_user->emailUser);
        }
    }
    
    /**
     * 
     * @param Tinebase_Model_User $user
     * @return string
     */
    protected function _getEmailUserName(Tinebase_Model_User $user)
    {
        // @todo add documentation for config option and add it to setup gui
        if (isset($this->_options['useEmailAsUsername'])) {
            return $user->accountEmailAddress;
        }
        
        return $this->_appendDomain($user->accountLoginName);
    }
    
    /**
     * inspect set password
     * 
     * @param string   $_userId
     * @param string   $_password
     * @param boolean  $_encrypt
     * @param boolean  $_mustChange
     * @param array    $_ldapData    the data to be written to ldap
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt, $_mustChange, array &$_ldapData)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Nothing to be done on password change.');
    }
    
    /**
     * inspect setStatus
     * 
     * @param string  $_status    the status
     * @param array   $_ldapData  the data to be written to ldap
     */
    public function inspectStatus($_status, array &$_ldapData)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Nothing to be done on status change.');
    }
    
    /**
     * inspect set expiry date
     * 
     * @param Tinebase_DateTime  $_expiryDate  the expirydate
     * @param array      $_ldapData    the data to be written to ldap
     */
    public function inspectExpiryDate($_expiryDate, array &$_ldapData)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Nothing to be done on expiry change.');
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_User_Plugin_LdapAbstract::inspectUpdateUser()
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry)
    {
        $this->_user2ldap($_user, $_ldapData, $_ldapEntry);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_User_Plugin_LdapInterface::setLdap()
     */
    public function setLdap(Tinebase_Ldap $_ldap)
    {
        $this->_ldap = $_ldap;
    }
    
    /**
     * Check if we should append domain name or not
     *
     * @param  string $_userName
     * @return string
     */
    protected function _appendDomain($_userName)
    {
        $domainConfigKey = ($this instanceof Tinebase_EmailUser_Imap_Interface) ? 'domain' : 'primarydomain';
        
        if (!empty($this->_config[$domainConfigKey])) {
            $domain = '@' . $this->_config[$domainConfigKey];
            if (strpos($_userName, $domain) === FALSE) {
                $_userName .= $domain;
            }
        }
        
        return $_userName;
    }
    
    /**
     * Returns a user object with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Abstract
     * 
     * @todo add generic function for this in Tinebase_User_Ldap or Tinebase_Ldap?
     */
    abstract protected function _ldap2User(Tinebase_Model_User $_user, array &$_ldapEntry);
    
    /**
     * convert object with user data to ldap data array
     * 
     * @param  Tinebase_Model_FullUser  $_user
     * @param  array                    $_ldapData   the data to be written to ldap
     * @param  array                    $_ldapEntry  the data currently stored in ldap 
     */
    abstract protected function _user2ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry = array());
}
