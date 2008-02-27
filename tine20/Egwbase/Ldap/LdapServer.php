<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Accounts
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Tinebase_Ldap_LdapServer
{
    /**
     * the connection to the ldap server
     *
     * @var resource the ldap connection
     */
    protected $_ds = NULL;
    
    protected $_attrsOnly = 0;
    
    protected $_sizeLimit = 0;
    
    protected $_timeLimit = 0;
    
    /**
     * the constructor
     * 
     * @param string $_host url( ldap(s)://ldapserver(:389) ) of the ldap server
     */
    public function __construct($_host = NULL) {
        if($_host !== NULL) {
            $this->connect($_host);
        }
    }
    
    /**
     * initiate connection to the ldap serer
     *
     * @param string $_host url( ldap(s)://ldapserver(:389) ) of the ldap server
     * @todo set ldap_set_rebind_proc
     * @return void
     */
    public function connect($_host)
    {
        if(empty($_host)) {
            throw new Exception('$_host can not be empty');
        }
        
        $this->_ds = ldap_connect($_host);
            
        ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION, 3);    
    }
    
    /**
     * close the ldap connection
     *
     */
    public function disconnect()
    {
        if(is_resource($this->_ds)) {
            $result = ldap_unbind($this->_ds);
        }
        
        $this->_ds = NULL;
    }
    
    /**
     * bind to a ldap server
     *
     * @param string $_rdn
     * @param string $_password
     */
    public function bind($_rdn = NULL, $_password = NULL)
    {
        if(!is_resource($this->_ds)) {
            throw new Exception('not connected to ldap server');
        }
        
        if(!@ldap_bind($this->_ds, $_rdn, $_password)) {
             return false;
        }
        
        return true;
    }
    
    /**
     * update entry in the ldap directory
     *
     * @param string $_dn
     * @param array $_data
     */
    public function update($_dn, array $_data)
    {
        if(!is_resource($this->_ds)) {
            throw new Exception('not connected to ldap server');
        }
        
        if(!ldap_modify ($this->_ds, $_dn, $_data)) {
            throw new Exception('failed to update entry(' . $_dn . '): ' . ldap_error($this->_ds), ldap_errno($this->_ds));
        }
    }
    
    /**
     * insert entry into the ldap directory
     *
     * @param string $_dn
     * @param array $_data
     */
    public function insert($_dn, array $_data)
    {
        if(!is_resource($this->_ds)) {
            throw new Exception('not connected to ldap server');
        }
        
        if(!ldap_add($this->_ds, $_dn, $_data)) {
            throw new Exception('could not add entry(' . $_dn . '): ' . ldap_error($this->_ds), ldap_errno($this->_ds));
        }
    }
    
    /**
     * search ldap directory
     *
     * @param string $_dn base dn (where to start searching)
     * @param string $_filter search filter
     * @param array $_attributes which fields to return
     * @return array
     */
    public function fetchAll($_dn, $_filter= 'objectclass=*', array $_attributes = array())
    {
        if(!is_resource($this->_ds)) {
            throw new Exception('not connected to ldap server');
        }
        
        $searchResult = ldap_search($this->_ds, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);

        $entries = ldap_get_entries($this->_ds, $searchResult);
        
        ldap_free_result($searchResult);
        
        unset($entries['count']);
        
        return $entries;
    }
    
    /**
     * read one entry from the ldap directory
     *
     * @param string $_dn the dn to read
     * @param string $_filter search filter
     * @param array $_attributes which fields to return
     * @return array
     */
    public function fetchRow($_dn, $_filter = 'objectclass=*', array $_attributes = array())
    {
        if(!is_resource($this->_ds)) {
            throw new Exception('not connected to ldap server');
        }
        
        $searchResult = ldap_read($this->_ds, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);  

        if(ldap_count_entries($this->_ds, $searchResult) === 0) {
            return false;
        }
        
        $entries = ldap_get_entries($this->_ds, $searchResult);
        
        ldap_free_result($searchResult);
        
        return $entries[0];
    }
    
    /**
     * delete one entry from the ldap directory
     *
     * @param string $_dn the dn to delete
     */
    public function delete($_dn)
    {
        if(!is_resource($this->_ds)) {
        }
        
        if(!ldap_delete($this->_ds, $_dn)) {
            throw new Exception('could not delete entry(' . $_dn . '): ' . ldap_error($this->_ds), ldap_errno($this->_ds));
        }
    }
    
    /**
     * get information about the ldap server
     *
     * @return array
     */
    public function getServerInfo()
    {
        $dn = '';
        $filter='(objectclass=*)';
        $attributes = array(
            'structuralObjectClass',
            'namingContexts',
            'supportedLDAPVersion',
            'subschemaSubentry'
        );
        
        return $this->fetch($dn, $filter, $attributes);
    }
}