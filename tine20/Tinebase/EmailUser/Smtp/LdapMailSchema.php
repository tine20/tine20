<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * plugin to handle smtp settings for mail ldap schema
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Smtp_LdapMailSchema extends Tinebase_EmailUser_Ldap implements Tinebase_EmailUser_Smtp_Interface
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
     * keep the addresses of unmanaged domains
     * 
     * @var array
     */
    protected $_unmanagedMailAdresses = array();
    
    /**
    * Returns a user object with raw data from ldap
    *
    * @param Tinebase_Model_User $_user
    * @param array $_ldapEntry
    * @return Tinebase_Record_Interface
    */
    protected function _ldap2User(Tinebase_Model_User $_user, array &$_ldapEntry)
    {
        $smtpUser = parent::_ldap2User($_user, $_ldapEntry);
        $emailAliases = array();
        $allowedDomains = explode(',', Tinebase_EmailUser::getConfig(Tinebase_Config::SMTP)['secondarydomains']);
        
        if (isset($_ldapEntry['mail'])) foreach ($_ldapEntry['mail'] as $mail) {
            if (isset($_user['accountEmailAddress']) && ($_user['accountEmailAddress'] != $mail)) {
                in_array(substr(strrchr($mail, "@"), 1), $allowedDomains)
                    ? $emailAliases[] = $mail
                    : $this->_unmanagedMailAdresses[] = $mail;
            }
        }
        
        $smtpUser['emailAliases'] = $emailAliases;
        $smtpUser['emailForwardOnly'] = null;
        $smtpUser['emailForwards'] = array();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($smtpUser, true));
        
        return $smtpUser;
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
        $mail = array_merge($mail, $this->_unmanagedMailAdresses);
        $_ldapData['mail'] = $mail;
        
        parent::_user2Ldap($_user, $_ldapData, $_ldapEntry);
        
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

