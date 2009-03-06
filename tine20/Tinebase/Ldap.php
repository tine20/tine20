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
     * Infos about the ldap server
     *
     * @var array
     */
    protected $_serverInfo = NULL;
    
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
        
        $returnValue = parent::__construct($options);
        
        return $returnValue;
    }
    
    /**
     * deletes an entry from ldap
     *
     * @param  string $_dn
     * @return void
     */
    public function delete($_dn)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        if (! @ldap_delete($this->_resource, $_dn)) {
            throw new Exception(ldap_error($this->_resource));
        }
    }
    
    /**
     * Removes one or more attributes from the specified dn
     *
     * @param  string $_dn
     * @param  array $data
     * @return void
     */
    public function deleteProperty($_dn, array $data)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        if (! @ldap_mod_del($this->_resource, $_dn, $data)) {
            throw new Exception(ldap_error($this->_resource));
        }
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
            throw new Tinebase_Exception_NotFound('Nothing found for filter: ' . $_filter);
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
        if (! $this->_serverInfo) {
            $this->_serverInfo = new Tinebase_LdapInfo($this);
        }
        
        return $this->_serverInfo;
    }
    
    /**
     * Add entries to LDAP directory
     *
     * @param  string $_dn
     * @param  array $_data
     * @return void
     */
    public function insert($_dn, array $_data)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        self::convertEmpty($_data, NULL);
        if (! @ldap_add($this->_resource, $_dn, $_data)) {
            throw new Exception(ldap_error($this->_resource));
        }
    }
    
    /**
     * Add property
     *
     * @param  string $_dn
     * @param  array $_data
     * @return void
     */
    public function insertProperty($_dn, array $_data)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        self::convertEmpty($_data, NULL);
        if (! @ldap_mod_add($this->_resource, $_dn, $_data)) {
            throw new Exception(ldap_error($this->_resource));
        }
    }
    
    /**
     * Modify an LDAP entry
     *
     * @param  string $_dn
     * @param  array $_data
     * @return void
     */
    public function update($_dn, array $_data)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        $dnParts = explode(',', $_dn);
        $rdn = $dnParts[0];
        list ($rdnAttribute, $rdnValue) = explode('=', $rdn);
        
        // check if we need to rename entry
        if (array_key_exists($rdnAttribute, $_data) && $_data[$rdnAttribute] != $rdnValue) {
            $newRdn = $rdnAttribute . "=" . $_data[$rdnAttribute];
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  About to rename dn '{$_dn}' to new rdn '{$newRdn}'");
            if (! @ldap_rename($this->_resource, $_dn, $newRdn, NULL, true)) {
                throw new Exception(ldap_error($this->_resource));
            }
            
            $dnParts[0] = $newRdn;
            $_dn = implode(',', $dnParts);
        }
        
        self::convertEmpty($_data);
        if (! @ldap_modify($this->_resource, $_dn, $_data)) {
            throw new Exception(ldap_error($this->_resource));
        }
    }
    
    /**
     * Modify (Replace) the given properties 
     *
     * @param  string $_dn
     * @param  array $_data
     * @return void
     */
    public function updateProperty($_dn, array $_data)
    {
        if(!is_resource($this->_resource)) {
            throw new Exception('Not connected to ldap server.');
        }
        
        self::convertEmpty($_data);
        if (! @ldap_mod_replace($this->_resource, $_dn, $_data)) {
            throw new Exception(ldap_error($this->_resource));
        }
    }
    
    /**
     * converts empty values into empty arrays
     *
     * @param array &$_data
     * @param mixed $_to  if set to NULL, attribute will be removed
     */
    public static function convertEmpty(&$_data, $_to = array()) {
        foreach ($_data as $attribute => $value) {
            if (empty($value)) {
                if (is_null($_to)) {
                    unset($_data[$attribute]);
                } else {
                    $_data[$attribute] = $_to;
                }
            }
        }
    }
}