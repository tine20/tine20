<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * plugin to handle smtp settings for univentionMail ldap schema
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_LdapUniventionMailSchema extends Tinebase_EmailUser_Ldap implements Tinebase_EmailUser_Smtp_Interface
{
    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailUsername' => 'mailprimaryaddress',
        'emailHost'     => 'univentionmailhomeserver',
        'emailAddress'  => 'mailprimaryaddress',
        'emailAliases'  => 'mailalternativeaddress'
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'univentionMail'
    );
    
    protected $_defaults = array(
        'emailPort'   => 25,
        'emailSecure' => Tinebase_EmailUser_Model_Account::SECURE_TLS,
        'emailAuth'   => 'login'
    );
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_EmailUser_Ldap::_user2Ldap()
     */
    protected function _user2Ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry = array())
    {
        if (empty($_user->accountEmailAddress)) {
            foreach ($this->_propertyMapping as $ldapKeyName) {
                $_ldapData[$ldapKeyName] = array();
            }
            
            $_ldapData['objectclass'] = array_unique(array_diff($_ldapData['objectclass'], $this->_requiredObjectClass));
            
        } else {
            parent::_user2Ldap($_user, $_ldapData, $_ldapEntry);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($_ldapData, true));
    }

    /**
     * check if user exists already in email backend user table
     *
     * @param  Tinebase_Model_FullUser  $_user
     * @return boolean
     *
     * TODO implement
     */
    public function emailAddressExists(Tinebase_Model_FullUser $_user)
    {
        return false;
    }
}
