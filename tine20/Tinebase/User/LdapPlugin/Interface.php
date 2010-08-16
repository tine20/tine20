<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * interface for user ldap plugins
 * 
 * @package Tinebase
 * @subpackage User
 */
interface Tinebase_User_LdapPlugin_Interface
{
    /**
     * the constructor
     *
     * @param  Tinebase_Ldap  $_ldap    the ldap resource
     * @param  array          $options  options used in connecting, binding, etc.
     */
    public function __construct(Tinebase_Ldap $_ldap, $_options = null); 
    
    /**
     * inspect data used to create user
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    public function inspectAddUser(Tinebase_Model_FullUser $_user, array &$_ldapData);
    
    /**
     * inspect data used to update user
     * 
     * @param Tinebase_Model_FullUser  $_user
     * @param array                    $_ldapData  the data to be written to ldap
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_user, array &$_ldapData);
    
    /**
     * inspect set blocked
     * 
     * @param string  $_accountId
     * @param string  $_blockedUntilDate
     */
    public function inspectSetBlocked($_accountId, $_blockedUntilDate);
    
    /**
     * inspect set expiry date
     * 
     * @param Zend_Date  $_expiryDate  the expirydate
     * @param array      $_ldapData    the data to be written to ldap
     */
    public function inspectExpiryDate($_expiryDate, array &$_ldapData);
    
    /**
     * inspect setStatus
     * 
     * @param string  $_status    the status
     * @param array   $_ldapData  the data to be written to ldap
     */
    public function inspectStatus($_status, array &$_ldapData);
    
    /**
     * inspect set password
     * 
     * @param string   $_userId
     * @param string   $_password
     * @param boolean  $_encrypt
     * @param boolean  $_mustChange
     * @param array    $_ldapData    the data to be written to ldap
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt, $_mustChange, array &$_ldapData);
    
    /**
     * inspect get user by property
     * 
     * @param Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user);    
}  
