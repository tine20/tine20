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
     * Extend constructor
     *
     * @param array $_options
     * @return @see Zend_Ldap
     */
    public function __construct(array $_options)
    {
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
            'optReferrals'              => null,
            'tryUsernameSplit'          => null
        ));
        
        $returnValue = parent::__construct($options);
        
        return $returnValue;
    }
    
    /**
     * Delete an LDAP entry
     *
     * @param  string|Zend_Ldap_Dn $dn
     * @param  array $data
     * @return Zend_Ldap *Provides a fluid interface*
     * @throws Zend_Ldap_Exception
     */
    public function deleteProperty($dn, array $data)
    {
        if ($dn instanceof Zend_Ldap_Dn) {
            $dn = $dn->toString();
        }

        $isDeleted = @ldap_mod_del($this->getResource(), $dn, $data);
        if($isDeleted === false) {
            /**
             * @see Zend_Ldap_Exception
             */
            require_once 'Zend/Ldap/Exception.php';
            throw new Zend_Ldap_Exception($this, 'deleting: ' . $dn);
        }
        return $this;
    }
    
    /**
     * read binary attribute from one entry from the ldap directory
     *
     * @todo still needed???
     * 
     * @param string $_dn the dn to read
     * @param string $_filter search filter
     * @param array $_attribute which field to return
     * @return blob binary data of given field
     * @throws  Exception with ldap error
     */
    public function fetchBinaryAttribute($_dn, $_filter, $_attribute)
    {
        $searchResult = @ldap_search($this->getResource(), $_dn, $_filter, $_attributes, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);  
        
        if($searchResult === FALSE) {
            throw new Exception(ldap_error($this->getResource()));
        }
        
        $searchCount = ldap_count_entries($this->getResource(), $searchResult);
        if($searchCount === 0) {
            throw new Exception('Nothing found for filter: ' . $_filter);
        } elseif ($searchCount > 1) {
            throw new Exception('More than one entry found for filter: ' . $_filter);
        }
        
        $entry = ldap_first_entry($this->getResource(), $searchResult);
        
        return ldap_get_values_len($this->getResource(), $entry, $attribute);
    }
    
    /**
     * Add new information to the LDAP repository
     *
     * @param string|Zend_Ldap_Dn $dn
     * @param array $entry
     * @return Zend_Ldap *Provides a fluid interface*
     * @throws Zend_Ldap_Exception
     */
    public function addProperty($dn, array $entry)
    {
        if (!($dn instanceof Zend_Ldap_Dn)) {
            $dn = Zend_Ldap_Dn::factory($dn, null);
        }
        self::prepareLdapEntryArray($entry);
        foreach ($entry as $key => $value) {
            if (is_array($value) && count($value) === 0) {
                unset($entry[$key]);
            }
        }

        $rdnParts = $dn->getRdn(Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);
        $adAttributes = array('distinguishedname', 'instancetype', 'name', 'objectcategory',
            'objectguid', 'usnchanged', 'usncreated', 'whenchanged', 'whencreated');
        $stripAttributes = array_merge(array_keys($rdnParts), $adAttributes);
        foreach ($stripAttributes as $attr) {
            if (array_key_exists($attr, $entry)) {
                unset($entry[$attr]);
            }
        }
                
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $dn->toString());
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $data: ' . print_r($entry, true));
        
        $isAdded = @ldap_mod_add($this->getResource(), $dn->toString(), $entry);
        if($isAdded === false) {
            /**
             * @see Zend_Ldap_Exception
             */
            require_once 'Zend/Ldap/Exception.php';
            throw new Zend_Ldap_Exception($this, 'adding: ' . $dn->toString());
        }
        return $this;
    }
    
    /**
     * Update LDAP registry
     *
     * @param string|Zend_Ldap_Dn $dn
     * @param array $entry
     * @return Zend_Ldap *Provides a fluid interface*
     * @throws Zend_Ldap_Exception
     */
    public function updateProperty($dn, array $entry)
    {
        if (!($dn instanceof Zend_Ldap_Dn)) {
            $dn = Zend_Ldap_Dn::factory($dn, null);
        }
        self::prepareLdapEntryArray($entry);

        $rdnParts = $dn->getRdn(Zend_Ldap_Dn::ATTR_CASEFOLD_LOWER);
        $adAttributes = array('distinguishedname', 'instancetype', 'name', 'objectcategory',
            'objectguid', 'usnchanged', 'usncreated', 'whenchanged', 'whencreated');
        $stripAttributes = array_merge(array_keys($rdnParts), $adAttributes);
        foreach ($stripAttributes as $attr) {
            if (array_key_exists($attr, $entry)) {
                unset($entry[$attr]);
            }
        }

        if (count($entry) > 0) {
            $isModified = @ldap_mod_replace($this->getResource(), $dn->toString(), $entry);
            if($isModified === false) {
                /**
                 * @see Zend_Ldap_Exception
                 */
                require_once 'Zend/Ldap/Exception.php';
                throw new Zend_Ldap_Exception($this, 'updating: ' . $dn->toString());
            }
        }
        return $this;
    }    
}
