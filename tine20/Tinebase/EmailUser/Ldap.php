<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail (+ ...) attributes in ldap backend
 * 
 * @package    Tinebase
 * @subpackage EmailLdap
 */
class Tinebase_EmailUser_Ldap extends Tinebase_User_Plugin_LdapAbstract
{
    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailAddress'  => 'mail',
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'inetOrgPerson'
    );    
    
    /**
     * the constructor
     *
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        $ldapOptions = Tinebase_User::getBackendConfiguration();
        $config  = Tinebase_EmailUser::getConfig($this->_backendType);
        $this->_options = array_merge($this->_options, $config);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_options, true));
    }
    
    /******************* protected functions *********************/
    
    /**
     * Check if we should append domain name or not
     *
     * @param  string $_userName
     * @return string
     */
    protected function _appendDomain($_userName)
    {
        if (array_key_exists('domain', $this->_config) && ! empty($this->_config['domain'])) {
            $_userName .= '@' . $this->_config['domain'];
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
    protected function _ldap2User(Tinebase_Model_User $_user, array &$_ldapEntry)
    {
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_ldapEntry, true));
        
        $accountArray = array();
        
        foreach ($_ldapEntry as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            
            $keyMapping = array_search($key, $this->_propertyMapping);
            
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'emailMailQuota':
                        // convert to megabytes
                        $accountArray[$keyMapping] = round($value[0] / 1024 / 1024);
                        break;
                        
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($accountArray, true));
        
        if ($this->_backendType == Tinebase_Model_Config::SMTP) {
            $_user->smtpUser  = new Tinebase_Model_EmailUser($accountArray);
            $_user->emailUser = Tinebase_EmailUser::merge(isset($_user->emailUser) ? $_user->emailUser : null, $_user->smtpUser);
        } else {
            $_user->imapUser  = new Tinebase_Model_EmailUser($accountArray);
            $_user->emailUser = Tinebase_EmailUser::merge($_user->imapUser, isset($_user->emailUser) ? $_user->emailUser : null);
        }
    }
    
    /**
     * returns array of ldap data
     *
     * @param  Tinebase_Model_EmailUser $_user
     * @return array
     * 
     * @todo add generic function for this?
     */
    protected function _user2Ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry)
    {
        if ($this->_backendType == Tinebase_Model_Config::SMTP) {
            if (empty($_user->smtpUser)) {
                return;
            }
            $mailSettings = $_user->smtpUser;
        } else {
            if (empty($_user->imapUser)) {
                return;
            }
            $mailSettings = $_user->imapUser;
        }
        
        foreach ($this->_propertyMapping as $objectProperty => $ldapAttribute) {
            $value = empty($mailSettings->{$objectProperty}) ? array() : $mailSettings->{$objectProperty};
            
            switch($objectProperty) {
                case 'emailMailQuota':
                    // convert to bytes
                    $_ldapData[$ldapAttribute] = !empty($mailSettings->{$objectProperty}) ? convertToBytes($mailSettings->{$objectProperty} . 'M') : array();
                    break;
                    
                case 'emailUID':
                    $_ldapData[$ldapAttribute] = $this->_appendDomain($_user->accountLoginName);
                    break;
                    
                case 'emailGID':
                    $_ldapData[$ldapAttribute] = $this->_config['emailGID'];
                    break;
                    
                default:
                    $_ldapData[$ldapAttribute] = $mailSettings->{$objectProperty};
                    break;
            }
        }
        
        // check if user has all required object classes. This is needed
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $_ldapEntry['objectclass'])) {
                // merge all required classes at once
                $_ldapData['objectclass'] = array_unique(array_merge($_ldapEntry['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }
    }
}  
