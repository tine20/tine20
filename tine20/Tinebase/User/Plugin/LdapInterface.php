<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * interface for user ldap plugins
 * 
 * @package Tinebase
 * @subpackage User
 */
interface Tinebase_User_Plugin_LdapInterface extends Tinebase_User_Plugin_Interface
{
    /**
     * inspect data used to add user
     * 
     * @param  Tinebase_Model_FullUser  $_user
     * @param  array                    $_ldapData
     */
    public function inspectAddUser(Tinebase_Model_FullUser $_user, array &$_ldapData);
    
    /**
     * inspect get user by property
     * 
     * @param  Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user, array &$_ldapEntry);
      
    /**
     * inspect set password
     * 
     * @param  string   $_userId
     * @param  string   $_password
     * @param  boolean  $_encrypt
     * @param  boolean  $_mustChange
     * @param  array    $_ldapData    the data to be written to ldap
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt, $_mustChange, array &$_ldapData);
    
    /**
     * inspect data used to update user
     * 
     * @param  Tinebase_Model_FullUser  $_user
     * @param  array                    $_ldapData
     * @param  array                    $_ldapEntry
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry);
    
    /**
     * return list of attributes supported by plugin
     * @return array list of attributes supported by plugin
     */
    public function getSupportedAttributes();
    
    public function setLdap(Tinebase_Ldap $_ldap);
}  
