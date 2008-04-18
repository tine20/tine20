<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Tinebase_Ldap extends Zend_Ldap
{
    protected $_attrsOnly = 0;
    
    protected $_sizeLimit = 0;
    
    protected $_timeLimit = 0;
    
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
        if(!is_resource($this->_resource)) {
            throw new Exception('not connected to ldap server');
        }
        
        $searchResult = ldap_search($this->_resource, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);

        $entries = ldap_get_entries($this->_resource, $searchResult);
        
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
    public function fetch($_dn, $_filter = 'objectclass=*', array $_attributes = array())
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('not connected to ldap server');
        }
        
        $searchResult = ldap_search($this->_resource, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);  

        if(ldap_count_entries($this->_resource, $searchResult) === 0) {
            throw new Exception('nothing found for filter: ' . $_filter);
        }
        
        $entries = ldap_get_entries($this->_resource, $searchResult);
        
        ldap_free_result($searchResult);
        
        return $entries[0];
    }
    
    /**
     * read one entry from the ldap directory
     *
     * @param string $_dn the dn to read
     * @param string $_filter search filter
     * @param array $_attributes which fields to return
     * @return array
     */
    public function fetchDn($_dn, $_filter = 'objectclass=*', array $_attributes = array())
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('not connected to ldap server');
        }
        
        $searchResult = @ldap_read($this->_resource, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);

        if($searchResult === FALSE) {
            throw new Exception(ldap_error($this->_resource));
        }

        if(ldap_count_entries($this->_resource, $searchResult) === 0) {
            throw new Exception('nothing found');
        }
        
        $entries = ldap_get_entries($this->_resource, $searchResult);
        
        ldap_free_result($searchResult);
        
        return $entries[0];
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
        
        return $this->fetchDn($dn, $filter, $attributes);
    }
}