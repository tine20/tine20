<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Ldap
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * LDAP base class for tine 2.0
 * @package     Tinebase
 * @subpackage  Ldap
 */
class Tinebase_Ldap extends Zend_Ldap
{
    /**
     * options set by object construction
     *
     * @var array
     */
    protected $_options = NULL;
    
    protected $_attrsOnly = 0;
    
    protected $_sizeLimit = 0;
    
    protected $_timeLimit = 0;
    
    /**
     * Extend constructor
     *
     * @param array $_options
     * @return @see Zend_Ldap
     */
    public function __construct(array $_options)
    {
        $this->_options = $_options;
        
        // strip non Zend_Ldap options
        $options = array_intersect_key($_options, array(
            'host'                      => null,
            'port'                      => null,
            'useSsl'                    => null,
            'username'                  => null,
            'password'                  => null,
            'bindRequiresDn'            => null,
            'baseDn'                    => null,
            'accountCanonicalForm'      => null,
            'accountDomainName'         => null,
            'accountDomainNameShort'    => null,
            'accountFilterFormat'       => null,
            'allowEmptyPassword'        => null,
            'useStartTls'               => null,
        ));
        
        return parent::__construct($options);
    }
    
    /**
     * search ldap directory
     *
     * @param   string $_dn base dn (where to start searching)
     * @param   string $_filter search filter
     * @param   array  $_attributes which fields to return
     * @param   string $_order sort result by given attreibute ASC
     * @return  array
     * @throws  Exception with ldap error
     */
    public function fetchAll($_dn, $_filter= 'objectclass=*', array $_attributes = array(), $_order = NULL)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        $searchResult = @ldap_search($this->_resource, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);
        
        if($_order !== NULL) {
            ldap_sort($this->_resource, $searchResult, $_order);
        }
        
        if($searchResult === FALSE) {
            throw new Exception(ldap_error($this->_resource));
        }
        
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
     * @throws  Exception with ldap error
     */
    public function fetch($_dn, $_filter = 'objectclass=*', array $_attributes = array())
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        $searchResult = @ldap_search($this->_resource, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);  
        
        if($searchResult === FALSE) {
            throw new Exception(ldap_error($this->_resource));
        }
        
        if(ldap_count_entries($this->_resource, $searchResult) === 0) {
            throw new Exception('Nothing found for filter: ' . $_filter);
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
     * @throws  Exception with ldap error
     */
    public function fetchDn($_dn, $_filter = 'objectclass=*', array $_attributes = array())
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        $searchResult = @ldap_read($this->_resource, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);

        if($searchResult === FALSE) {
            throw new Exception(ldap_error($this->_resource));
        }

        if(ldap_count_entries($this->_resource, $searchResult) === 0) {
            throw new Exception('Nothing found.');
        }
        
        $entries = ldap_get_entries($this->_resource, $searchResult);
        
        ldap_free_result($searchResult);
        
        return $entries[0];
    }
    
    /**
     * read binary attribute from one entry from the ldap directory
     *
     * @param string $_dn the dn to read
     * @param string $_filter search filter
     * @param array $_attribute which field to return
     * @return blob binary data of given field
     * @throws  Exception with ldap error
     */
    public function fetchBinaryAttribute($_dn, $_filter, $_attribute)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        $searchResult = @ldap_search($this->_resource, $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);  
        
        if($searchResult === FALSE) {
            throw new Exception(ldap_error($this->_resource));
        }
        
        $searchCount = ldap_count_entries($this->_resource, $searchResult);
        if($searchCount === 0) {
            throw new Exception('Nothing found for filter: ' . $_filter);
        } elseif ($searchCount > 1) {
            throw new Exception('More than one entry found for filter: ' . $_filter);
        }
        
        $entry = ldap_first_entry($this->_resource, $searchResult);
        
        return ldap_get_values_len($this->_resource, $entry, $attribute);
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