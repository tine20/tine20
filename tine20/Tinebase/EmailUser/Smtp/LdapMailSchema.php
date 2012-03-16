<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * plugin to handle smtp settings for mail ldap schema
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_LdapMailSchema extends Tinebase_EmailUser_Ldap
{
    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_propertyMapping = array(
        // NOTE: no mapping needed, only mail attribute is used
    );
    
    /**
     * backend type
     * 
     * @var string
     */
    protected $_backendType = Tinebase_Config::SMTP;
    
    /**
    * Returns a user object with raw data from ldap
    *
    * @param array $_userData
    * @param string $_accountClass
    * @return Tinebase_Record_Abstract
    */
    protected function _ldap2User(Tinebase_Model_User $_user, array &$_ldapEntry)
    {
        if ($this->_backendType == Tinebase_Config::SMTP) {
            $accountArray = array(
                'emailAliases'     => array()
            );
        } else {
            $accountArray = array();
        }
        
        if (isset($_ldapEntry['mail'])) {
            if (is_array($_ldapEntry['mail'])) {
                $email = array_shift($_ldapEntry['mail']);
                $accountArray['emailAliases'] = $_ldapEntry['mail'];
            } else {
                $email = $_ldapEntry['mail'];
            }
            $accountArray['emailAddress'] = $email;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($accountArray, true));
        
        $_user->smtpUser  = new Tinebase_Model_EmailUser($accountArray);
        $_user->emailUser = Tinebase_EmailUser::merge(isset($_user->emailUser) ? $_user->emailUser : null, $_user->smtpUser);
    }
    
    /**
     * populate mail attribute(s)
     * 
     * (non-PHPdoc)
     * @see Tinebase_EmailUser_Ldap::_user2Ldap()
     */
    protected function _user2Ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry = array())
    {
        if (! empty($_user->smtpUser) && $_user->smtpUser->emailAliases && ! empty($_user->smtpUser->emailAliases)) {
            $mail = $_user->smtpUser->emailAliases;
            array_unshift($mail, $_user->accountEmailAddress);
        } else {
            $mail = $_user->accountEmailAddress;
        }
        $_ldapData['mail'] = $mail;
        
        parent::_user2Ldap($_user, $_ldapData, $_ldapEntry);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($_ldapData, true));
    }
}
