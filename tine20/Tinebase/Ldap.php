<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Ldap
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::LDAP_DISABLE_TLSREQCERT)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Disable TLS certificate check');
            putenv('LDAPTLS_REQCERT=never');
        }
        
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

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' LDAP options: ' . print_r($options, true));
        
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
        $searchResult = @ldap_search($this->getResource(), $_dn, $_filter, $_attribute, $this->_attrsOnly, $this->_sizeLimit, $this->_timeLimit);
        
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
        
        return ldap_get_values_len($this->getResource(), $entry, $_attribute);
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
            if ((isset($entry[$attr]) || array_key_exists($attr, $entry))) {
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
            if ((isset($entry[$attr]) || array_key_exists($attr, $entry))) {
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
    
    /**
     * return first namingContext from LDAP root DSE
     * 
     * @return string
     */
    public function getFirstNamingContext()
    {
        return Tinebase_Helper::array_value(0, $this->getRootDse()->getNamingContexts());
    }
    
    /**
     * convert binary objectGUID to to plain ASCII string
     * example guid: c8ab4322-3a4b-4af9-a100-9ed746049c91
     * 
     * @param  string  $binaryGuid
     * @return string
     */
    public static function decodeGuid($binaryGuid)
    {
        $hexGuid = unpack("H*hex", $binaryGuid); 
        $hex = $hexGuid["hex"];
        
        $hex1 = substr($hex, -26, 2) . substr($hex, -28, 2) . substr($hex, -30, 2) . substr($hex, -32, 2);
        $hex2 = substr($hex, -22, 2) . substr($hex, -24, 2);
        $hex3 = substr($hex, -18, 2) . substr($hex, -20, 2);
        $hex4 = substr($hex, -16, 4);
        $hex5 = substr($hex, -12, 12);
        
        $guid = $hex1 . "-" . $hex2 . "-" . $hex3 . "-" . $hex4 . "-" . $hex5;
        
        return $guid;
    }
    
    /**
     * convert plain ASCII objectGUID to binary string
     * example guid: c8ab4322-3a4b-4af9-a100-9ed746049c91
     * 
     * @param  string $guid
     * @return string
     */
    public static function encodeGuid($guid)
    {
        $hex  = substr($guid, -30, 2) . substr($guid, -32, 2) . substr($guid, -34, 2) . substr($guid, -36, 2);
        $hex .= substr($guid, -25, 2) . substr($guid, -27, 2);
        $hex .= substr($guid, -20, 2) . substr($guid, -22, 2);
        $hex .= substr($guid, -17, 4);
        $hex .= substr($guid, -12, 12);
        
        $binaryGuid = pack('H*', $hex);
        
        return $binaryGuid;
    }
    
    /**
     * decode ActiveDirectory SID
     *
     * @see https://msdn.microsoft.com/en-us/library/ff632068.aspx
     *
     * @param  string  $binarySid  the binary encoded SID
     * @return string
     *
     * TODO should be moved to AD trait/abstract
     */
    public static function decodeSid($binarySid) 
    {
        if (preg_match('/^S\-1/', $binarySid)) {
            // already decoded
            return $binarySid;
        }
        
        $sid = false; 
        
        $unpacked = unpack("crev/cdashes/nc/Nd/V*e", $binarySid); 
        
        if ($unpacked) { 
            $n232 = pow(2,32); 
            unset($unpacked["dashes"]); // unused 
            $unpacked["c"] = $n232 * $unpacked["c"] + $unpacked["d"]; 
            unset($unpacked["d"]); 
            
            $sid = "S";
            
            foreach ($unpacked as $v) { 
                if ($v < 0) {
                    $v = $n232 + $v; 
                }
                $sid .= '-' . $v; 
            } 
        }
         
        return $sid; 
    }
}
